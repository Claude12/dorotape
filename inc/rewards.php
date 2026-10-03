<?php
/**
 * Reward points
 *
 * RewardsWP pays a reward out as an ordinary WooCommerce coupon and marks it
 * individual use, and every marketing code Dorotape runs is individual use too.
 * Either flag on its own is enough for WooCommerce to refuse the second code,
 * so on the old site's behaviour of points plus a code the cart would drop one
 * of them. Michael asked for both together (17 Sept), so this pairs them up.
 *
 * WooCommerce only enforces individual use in WC_Cart::apply_coupon(), through
 * two filters meant for exactly this, so nothing here has to fight validation
 * later: the Store API applies coupons through the same method.
 *
 * The rule is one reward plus one marketing code. Two marketing codes together
 * are still refused, and so are two rewards, because neither was asked for.
 *
 * @package dorotape
 */

/**
 * Is this code a RewardsWP reward payout rather than a marketing code?
 *
 * Asks RewardsWP's own reward records, so a marketing code that happens to be
 * named like a reward is not mistaken for one. Returns false when the plugin
 * is not installed, which leaves WooCommerce's normal behaviour untouched.
 *
 * @param string $code Coupon code.
 */
function dorotape_is_reward_coupon( string $code ): bool {
	global $wpdb;

	static $cache = [];

	$code = wc_format_coupon_code( $code );

	if ( '' === $code ) {
		return false;
	}

	if ( isset( $cache[ $code ] ) ) {
		return $cache[ $code ];
	}

	$table = $wpdb->prefix . 'rewardswp_rewardmeta';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
		$cache[ $code ] = false;

		return false;
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$cache[ $code ] = (bool) $wpdb->get_var(
		$wpdb->prepare( "SELECT meta_id FROM {$table} WHERE meta_key = 'coupon_code' AND meta_value = %s LIMIT 1", $code )
	);

	return $cache[ $code ];
}

/**
 * Keep the other half of the pair when an individual use coupon is applied.
 *
 * WooCommerce clears every coupon that is not returned here, so a code applied
 * after a reward would otherwise knock the reward out, and a reward applied
 * after a code would knock the code out. Keeping whatever is of the other kind
 * covers both orders, and still lets a second code of the same kind replace
 * the first the way WooCommerce normally does.
 *
 * @param array     $keep       Codes to keep.
 * @param WC_Coupon $new_coupon The coupon being applied.
 * @param array     $applied    Codes already in the cart.
 */
add_filter(
	'woocommerce_apply_individual_use_coupon',
	function ( $keep, $new_coupon, $applied ) {
		$new_is_reward = dorotape_is_reward_coupon( $new_coupon->get_code() );

		foreach ( (array) $applied as $code ) {
			if ( dorotape_is_reward_coupon( $code ) !== $new_is_reward ) {
				$keep[] = $code;
			}
		}

		return $keep;
	},
	10,
	3
);

/**
 * Allow a second code alongside an individual use one when one of them is a reward.
 *
 * @param bool      $allow          Whether to allow the pair.
 * @param WC_Coupon $new_coupon     The coupon being applied.
 * @param WC_Coupon $applied_coupon The individual use coupon already applied.
 */
add_filter(
	'woocommerce_apply_with_individual_use_coupon',
	function ( $allow, $new_coupon, $applied_coupon ) {
		$new_is_reward     = dorotape_is_reward_coupon( $new_coupon->get_code() );
		$applied_is_reward = dorotape_is_reward_coupon( $applied_coupon->get_code() );

		// One of each: a reward and a marketing code.
		return $new_is_reward !== $applied_is_reward ? true : $allow;
	},
	10,
	3
);

/*
 * ─── Which products earn points ──────────────────────────────────────────────
 *
 * The old site did not give points on everything: 65 of its 1,494 priced lines
 * carried a points value, all of them application tapes, print media and
 * laminates. Michael asked on 17 Sept to keep it that way ("I would prefer to
 * only have points for certain products").
 *
 * RewardsWP has one site-wide earning rate and no per-product control in the
 * free version, so the choice is made here: a switch on the product, and an
 * order total for points that counts only the lines that qualify. Nothing is
 * hardcoded, so Michael can turn a product on or off himself.
 */

/**
 * Does this product earn reward points?
 *
 * The switch lives on the parent, like the other Dorotape product settings, so
 * a variation follows whatever its parent is set to.
 *
 * @param WC_Product|int|null $product Product, product id, or null.
 */
function dorotape_product_earns_points( $product ): bool {
	if ( is_numeric( $product ) ) {
		$product = wc_get_product( (int) $product );
	}

	if ( ! $product instanceof WC_Product ) {
		return false;
	}

	$parent = $product->get_parent_id();

	if ( $parent ) {
		$product = wc_get_product( $parent );
	}

	return $product instanceof WC_Product && 'yes' === $product->get_meta( '_dt_reward_points' );
}

/**
 * How many times over a product earns points.
 *
 * Fifteen products paid double on the old site (loyaltypoints 2 in
 * ecom_pricemap_dataexport.csv, against 1 for the other fifty), and the client
 * asked for that to carry over: "Yes, let's keep it that way please" (25 Sept).
 * RewardsWP has one earning rate for the whole shop and no per-product rate
 * below its paid tier, so the weighting is applied to the qualifying total
 * instead: a line that earns double is counted twice towards the order's
 * points, which comes to the same number of points.
 *
 * Stored as _dt_reward_multiplier on the parent product, so variations inherit
 * it the way the earning flag does.
 *
 * @param mixed $product
 * @return float 1.0 unless the product is weighted.
 */
function dorotape_reward_multiplier( $product ): float {
	if ( ! $product instanceof WC_Product ) {
		return 1.0;
	}

	$id         = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();
	$multiplier = (float) get_post_meta( $id, '_dt_reward_multiplier', true );

	return $multiplier > 1 ? $multiplier : 1.0;
}
// ─── Admin field (Product data → Advanced) ───────────────────────────────────

add_action( 'woocommerce_product_options_advanced', function (): void {
	global $post;

	/*
	 * An unticked box posts nothing at all, which is indistinguishable from a
	 * save that never showed the box. This marker is how the save handler tells
	 * "turned off" from "not on this screen".
	 */
	echo '<input type="hidden" name="_dt_reward_points_shown" value="1" />';

	woocommerce_wp_checkbox(
		array(
			'id'          => '_dt_reward_points',
			'label'       => __( 'Reward points', 'dorotape' ),
			'description' => __( 'Customers earn points on this product.', 'dorotape' ),
			'value'       => 'yes' === get_post_meta( $post->ID, '_dt_reward_points', true ) ? 'yes' : 'no',
			'desc_tip'    => false,
		)
	);

	woocommerce_wp_text_input(
		array(
			'id'                => '_dt_reward_multiplier',
			'label'             => __( 'Points multiplier', 'dorotape' ),
			'description'       => __( 'Leave at 1 for the normal rate. 2 means this product earns double points.', 'dorotape' ),
			'desc_tip'          => false,
			'type'              => 'number',
			'value'             => get_post_meta( $post->ID, '_dt_reward_multiplier', true ) ?: '1',
			'custom_attributes' => array(
				'min'  => '1',
				'step' => '1',
			),
		)
	);
} );

add_action( 'woocommerce_admin_process_product_object', function ( WC_Product $product ): void {
	if ( ! isset( $_POST['_dt_reward_points_shown'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WC verified.
		return;
	}

	if ( isset( $_POST['_dt_reward_points'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$product->update_meta_data( '_dt_reward_points', 'yes' );
	} else {
		$product->delete_meta_data( '_dt_reward_points' ); // Not earning is the default.
	}

	$multiplier = isset( $_POST['_dt_reward_multiplier'] ) ? (float) wp_unslash( $_POST['_dt_reward_multiplier'] ) : 1.0; // phpcs:ignore WordPress.Security.NonceVerification.Missing

	if ( $multiplier > 1 ) {
		$product->update_meta_data( '_dt_reward_multiplier', $multiplier );
	} else {
		$product->delete_meta_data( '_dt_reward_multiplier' ); // The normal rate is the default.
	}
} );

// ─── Earning ─────────────────────────────────────────────────────────────────

/**
 * Count only the qualifying lines towards the points for an order.
 *
 * RewardsWP works from the order subtotal less discounts plus fees. Each line
 * total is already that line's share after coupons and before VAT, so adding up
 * the qualifying ones keeps the plugin's own basis and simply leaves the rest
 * out. Fees are dropped with them: points were always for goods.
 *
 * @param float $total The plugin's order total for points.
 * @param mixed $order The order.
 */
add_filter(
	'rewardswp_order_total_for_points',
	function ( $total, $order ) {
		if ( ! $order instanceof WC_Order ) {
			return $total;
		}

		$qualifying = 0.0;

		foreach ( $order->get_items() as $item ) {
			$item_product = $item->get_product();

			if ( dorotape_product_earns_points( $item_product ) ) {
				$qualifying += (float) $item->get_total() * dorotape_reward_multiplier( $item_product );
			}
		}

		return $qualifying;
	},
	10,
	2
);

// ─── "Earn X points" on the product page ─────────────────────────────────────

/**
 * Hide the plugin's product page points notice on products that do not earn.
 *
 * The notice is printed by a method on the plugin's WooCommerce integration and
 * takes no filter, so its callback is taken off the hook and put back wrapped
 * in the same test the order uses. Calling the plugin's own callback keeps its
 * markup, wording and member/guest handling; if a future version drops the
 * method, nothing here runs and the notice behaves as the plugin intends.
 */
add_action( 'wp', function (): void {
	$hook = 'woocommerce_single_product_summary';

	if ( empty( $GLOBALS['wp_filter'][ $hook ] ) ) {
		return;
	}

	foreach ( $GLOBALS['wp_filter'][ $hook ]->callbacks as $priority => $callbacks ) {
		foreach ( $callbacks as $registered ) {
			$callback = $registered['function'];

			if ( ! is_array( $callback ) || ! is_object( $callback[0] ) || 'render_product_points_notice' !== $callback[1] ) {
				continue;
			}

			remove_action( $hook, $callback, $priority );

			add_action(
				$hook,
				function () use ( $callback ): void {
					global $product;

					if ( ! dorotape_product_earns_points( $product ) ) {
						return;
					}

					/*
					 * The plugin counts the notice's points from the price on
					 * screen and offers no filter on the number, so a weighted
					 * product is shown the price it earns as, for the length of
					 * that one call. Without this the page would promise half
					 * the points the order goes on to award.
					 */
					$multiplier = dorotape_reward_multiplier( $product );

					if ( $multiplier <= 1 ) {
						call_user_func( $callback );
						return;
					}

					$weight = static function ( $price ) use ( $multiplier ) {
						return '' === $price ? $price : (float) $price * $multiplier;
					};

					add_filter( 'woocommerce_product_get_price', $weight, 99 );
					add_filter( 'woocommerce_product_variation_get_price', $weight, 99 );
					call_user_func( $callback );
					remove_filter( 'woocommerce_product_get_price', $weight, 99 );
					remove_filter( 'woocommerce_product_variation_get_price', $weight, 99 );
				},
				$priority
			);
		}
	}
}, 20 );


/*
 * ─── Refunds ─────────────────────────────────────────────────────────────────
 *
 * RewardsWP 1.4.6 gets three things wrong when an order is refunded, all found
 * in testing on 17 Sept:
 *
 *   1. A full refund deducts the whole of the original earning a second time,
 *      even where a partial refund has already taken part of it back. Refund
 *      half an order and then the rest and the customer pays three points for
 *      two.
 *   2. Where that leaves more to deduct than the customer holds, the plugin
 *      throws and the refund itself dies with a fatal error, so the shop is
 *      left with a half refunded order. A single full refund through the
 *      Refund button is enough to hit this, because the refund is counted
 *      once on the way in and again when the status turns to refunded.
 *   3. The reward coupon goes back to issued and its use count is lowered, but
 *      the customer stays on the coupon's list of people who have used it, so
 *      spending it again is refused with "usage limit reached" and the points
 *      are gone for good. The plugin says as much in a comment of its own.
 *
 * Correcting the plugin afterwards cannot work, since point 2 stops anything
 * later from running at all. The refund is therefore handled here first, on
 * woocommerce_order_status_refunded, which fires before the
 * woocommerce_order_status_<from>_to_refunded hooks the plugin listens on, and
 * the plugin's own handler is marked as already done. Should a later version
 * of RewardsWP put these right, delete this section: the plugin takes the work
 * back and there is nothing here to undo.
 */

/**
 * The RewardsWP reward a coupon pays out, if any.
 *
 * @param string $code Coupon code.
 *
 * @return object|null Row from the rewards table.
 */
function dorotape_reward_for_coupon( string $code ) {
	global $wpdb;

	$code = wc_format_coupon_code( $code );

	if ( '' === $code || ! dorotape_is_reward_coupon( $code ) ) {
		return null;
	}

	$meta    = $wpdb->prefix . 'rewardswp_rewardmeta';
	$rewards = $wpdb->prefix . 'rewardswp_rewards';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	return $wpdb->get_row(
		$wpdb->prepare(
			"SELECT r.* FROM {$rewards} r INNER JOIN {$meta} m ON m.reward_id = r.id WHERE m.meta_key = 'coupon_code' AND m.meta_value = %s LIMIT 1",
			$code
		)
	);
}

/**
 * Take back the points an order earned, less anything already taken back.
 *
 * Every deduction RewardsWP writes carries the id of the entry it is deducting
 * from, so what is left to take back is the earning less the deductions
 * already made against it. Nothing is taken beyond what the customer holds,
 * which keeps the balance off negative numbers and keeps the ledger and the
 * balance saying the same thing; any shortfall is left as an order note.
 *
 * @param WC_Order $order Order being refunded.
 */
function dorotape_rewards_deduct_for_refund( WC_Order $order ): void {
	$order_id = $order->get_id();

	$earned = Awesomemotive\Rewardswp\Helpers\get_points_entries(
		array(
			'source_id' => $order_id,
			'type'      => 'earned',
			'status'    => 'approved',
		),
		9999,
		1
	);

	if ( empty( $earned ) ) {
		return;
	}

	$spent = Awesomemotive\Rewardswp\Helpers\get_points_entries(
		array(
			'source_id' => $order_id,
			'type'      => 'spent',
			'status'    => 'approved',
		),
		9999,
		1
	);

	$taken    = array();
	$unplaced = 0;

	foreach ( $spent as $entry ) {
		if ( ! in_array( $entry->source_type, array( 'refund', 'partial_refund' ), true ) ) {
			continue;
		}

		$against = (int) $entry->get_meta( 'refunded_point_id' );

		if ( $against ) {
			$taken[ $against ] = ( isset( $taken[ $against ] ) ? $taken[ $against ] : 0 ) + (int) $entry->points;
		} else {
			$unplaced += (int) $entry->points;
		}
	}

	foreach ( $earned as $point ) {
		$point_id = (int) $point->id;
		$already  = isset( $taken[ $point_id ] ) ? $taken[ $point_id ] : 0;

		// A deduction that names no entry still came off this order, so count it
		// against the earnings in turn.
		if ( $unplaced > 0 ) {
			$share     = max( 0, min( $unplaced, (int) $point->points - $already ) );
			$already  += $share;
			$unplaced -= $share;
		}

		$outstanding = (int) $point->points - $already;

		if ( $outstanding < 1 ) {
			continue;
		}

		$member  = Awesomemotive\Rewardswp\Helpers\get_member( (int) $point->member_id );
		$balance = $member ? (int) $member->points_balance : 0;
		$off     = min( $outstanding, $balance );

		if ( $off > 0 ) {
			$deduction = Awesomemotive\Rewardswp\Helpers\create_point_entry(
				array(
					'member_id'   => (int) $point->member_id,
					'points'      => $off,
					'type'        => 'spent',
					'source_type' => 'refund',
					'source_id'   => $order_id,
					'status'      => 'approved',
					'note_public' => sprintf(
						/* translators: %d: order number */
						__( 'Points deducted due to refund of order #%d', 'dorotape' ),
						$order_id
					),
				),
				false,
				false
			);

			if ( is_wp_error( $deduction ) || ! is_object( $deduction ) ) {
				continue;
			}

			$deduction->update_meta( 'integration', 'woocommerce' );
			$deduction->update_meta( 'refunded_point_id', $point_id );

			$member->deduct_points_balance( $off, true, false );

			$order->add_order_note(
				sprintf(
					/* translators: 1: number of points taken back, 2: the customer's balance afterwards */
					__( '%1$d reward point(s) taken back for the refund. Balance now %2$d.', 'dorotape' ),
					$off,
					$balance - $off
				)
			);
		}

		if ( $outstanding > $off ) {
			$order->add_order_note(
				sprintf(
					/* translators: %d: number of points */
					__( '%d reward point(s) could not be taken back: the customer had already spent them.', 'dorotape' ),
					$outstanding - $off
				)
			);
		}
	}
}

/**
 * Put a reward back to issued so the customer can spend it again.
 *
 * @param int $reward_id Row id in the rewards table.
 *
 * @return bool Whether the reward was changed.
 */
function dorotape_rewards_reissue_reward( int $reward_id ): bool {
	if ( ! function_exists( 'Awesomemotive\Rewardswp\Helpers\get_reward' ) ) {
		return false;
	}

	$reward = Awesomemotive\Rewardswp\Helpers\get_reward( $reward_id );

	if ( ! $reward || 'redeemed' !== $reward->status ) {
		return false;
	}

	$reward->status     = 'issued';
	$reward->applied_at = null;

	return (bool) $reward->update();
}

/**
 * Hand back any reward the customer spent on a refunded order.
 *
 * The reward goes back to issued and the coupon's use count comes down, as the
 * plugin does, and the customer's own entry on the coupon is removed as well,
 * which is the part the plugin leaves behind. Only the one entry for this
 * order's customer goes.
 *
 * @param int $order_id Order being refunded.
 */
function dorotape_rewards_release_reward_coupons( int $order_id ): void {
	global $wpdb;

	$order = wc_get_order( $order_id );

	if ( ! $order ) {
		return;
	}

	foreach ( $order->get_coupon_codes() as $code ) {
		$reward = dorotape_reward_for_coupon( $code );

		if ( ! $reward || 'redeemed' !== $reward->status ) {
			continue;
		}

		dorotape_rewards_reissue_reward( (int) $reward->id );

		$coupon_id = wc_get_coupon_id_by_code( $code );

		if ( ! $coupon_id ) {
			continue;
		}

		$coupon      = new WC_Coupon( $coupon_id );
		$usage_count = $coupon->get_usage_count();

		if ( $usage_count > 0 ) {
			$coupon->set_usage_count( $usage_count - 1 );
			$coupon->save();
		}

		// WooCommerce records the customer as a user id, or an email for a guest.
		$candidates = array_filter( array( (string) $order->get_user_id(), $order->get_billing_email() ) );

		foreach ( $candidates as $used_by ) {
			if ( '0' === $used_by || '' === $used_by ) {
				continue;
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$meta_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT meta_id FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = '_used_by' AND meta_value = %s LIMIT 1",
					$coupon_id,
					$used_by
				)
			);

			if ( ! $meta_id ) {
				continue;
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->delete( $wpdb->postmeta, array( 'meta_id' => $meta_id ), array( '%d' ) );
			wp_cache_delete( $coupon_id, 'post_meta' );

			break;
		}

		$order->add_order_note(
			sprintf(
				/* translators: %s: coupon code */
				__( 'Reward %s released, so the customer can spend it again.', 'dorotape' ),
				wc_format_coupon_code( $code )
			)
		);
	}
}

/**
 * Handle the points side of a refund in place of RewardsWP.
 *
 * @param int $order_id Order that has just been refunded.
 */
function dorotape_rewards_handle_refund( $order_id ): void {
	if ( ! function_exists( 'Awesomemotive\Rewardswp\Helpers\get_points_entries' ) ) {
		return;
	}

	$order = wc_get_order( (int) $order_id );

	if ( ! $order || 'refunded' !== $order->get_status() ) {
		return;
	}

	// The plugin's own guard. Set below so its handler stands down, and read
	// here so that a version of RewardsWP that has got there first is left
	// alone.
	if ( $order->get_meta( '_rewardswp_refund_processed' ) ) {
		return;
	}

	dorotape_rewards_deduct_for_refund( $order );
	dorotape_rewards_release_reward_coupons( $order->get_id() );

	$order->update_meta_data( '_rewardswp_refund_processed', true );
	$order->update_meta_data( '_dorotape_refund_points_handled', 'yes' );
	$order->save();
}

/*
 * woocommerce_order_status_refunded fires before the
 * woocommerce_order_status_<from>_to_refunded hooks RewardsWP listens on, so
 * this runs first whichever status the order came from.
 */
add_action( 'woocommerce_order_status_refunded', 'dorotape_rewards_handle_refund', 5 );

/*
 * ---------------------------------------------------------------------------
 * The rewards panel, loaded after the page instead of inside it.
 *
 * RewardsWP prints its whole panel into wp_footer on every page, twice over
 * (a mobile and a desktop copy inside Alpine x-if templates), which is about
 * 500KB of HTML and 300 <template> elements the browser has to parse before
 * the page finishes, for a launcher most visitors never open. It also carries
 * the member's details, so a page cache cannot keep the page.
 *
 * Here the plugin's footer output is swapped for an empty slot, and once the
 * page has loaded the same output is fetched from ?dt-rewards-panel=1 and put
 * in the slot. The panel's custom element clones its template when it is
 * connected and starts Alpine on its own shadow root, so arriving late is
 * something it already handles. Nothing of the plugin is copied: the endpoint
 * calls the plugin's own render method, so plugin updates carry through. If
 * the plugin renames that method the lookup finds nothing and the plugin's
 * footer output is left exactly as it was.
 * ---------------------------------------------------------------------------
 */

/**
 * The RewardsWP callback that prints the panel on wp_footer, if it is hooked.
 *
 * The plugin only hooks it on template_redirect, and only when its own
 * visibility filter says the widget shows on this page, so finding it is also
 * the answer to "does this page have the panel".
 *
 * @return array{0:object,1:string}|null
 */
function dorotape_rewards_panel_callback(): ?array {
	global $wp_filter;

	if ( empty( $wp_filter['wp_footer'] ) ) {
		return null;
	}

	foreach ( $wp_filter['wp_footer']->callbacks as $callbacks ) {
		foreach ( $callbacks as $callback ) {
			$fn = $callback['function'];
			if ( is_array( $fn ) && is_object( $fn[0] ) && 'render_template' === $fn[1]
				&& str_ends_with( get_class( $fn[0] ), '\\AdvocateController' ) ) {
				return $fn;
			}
		}
	}

	return null;
}

/**
 * Serve the panel on its own, or swap it out of the page for the loader.
 *
 * Priority 20 runs after RewardsWP's own template_redirect hook (10) has
 * decided whether to add the panel.
 */
function dorotape_rewards_defer_panel(): void {
	$callback = dorotape_rewards_panel_callback();

	if ( ! $callback ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only fragment.
	if ( isset( $_GET['dt-rewards-panel'] ) ) {
		// Per-member content: never let a page cache keep it.
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		nocache_headers();
		header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
		header( 'X-Robots-Tag: noindex, nofollow' );
		call_user_func( $callback );
		exit;
	}

	$priority = has_action( 'wp_footer', $callback );
	remove_action( 'wp_footer', $callback, $priority );
	add_action( 'wp_footer', 'dorotape_rewards_panel_loader', $priority );
}
add_action( 'template_redirect', 'dorotape_rewards_defer_panel', 20 );

/**
 * The empty slot and the script that fills it once the page is idle.
 */
function dorotape_rewards_panel_loader(): void {
	$url = add_query_arg( 'dt-rewards-panel', '1', home_url( '/' ) );
	?>
	<div id="dt-rewards-slot" hidden></div>
	<script>
	(function () {
		var url = <?php echo wp_json_encode( esc_url_raw( $url ) ); ?>;
		function fill() {
			fetch(url, { credentials: 'same-origin' })
				.then(function (r) { return r.ok ? r.text() : ''; })
				.then(function (html) {
					var slot = document.getElementById('dt-rewards-slot');
					if (!html || !slot) return;
					var t = document.createElement('template');
					t.innerHTML = html;
					slot.replaceWith(t.content);
				})
				.catch(function () {});
		}
		function idle() {
			if ('requestIdleCallback' in window) requestIdleCallback(fill, { timeout: 3000 });
			else setTimeout(fill, 300);
		}
		if (document.readyState === 'complete') idle();
		else window.addEventListener('load', idle);
	})();
	</script>
	<?php
}
