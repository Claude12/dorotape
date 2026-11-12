<?php
/**
 * Reward points: the My Account tab.
 *
 * RewardsWP shows a member's points only in its floating panel, and QA
 * (12 Nov) asked for them in My Account too. This tab shows the balance and
 * every entry behind it, read through the plugin's own helpers. Spending
 * points stays in the panel, which the button here opens.
 *
 * Built like the address book (inc/address-book-account.php): a classic My
 * Account endpoint, with the same check that puts its rewrite rule back when a
 * database move has lost it. The tab is left out when RewardsWP is inactive or
 * its points programme is switched off.
 *
 * @package Dorotape
 */

defined( 'ABSPATH' ) || exit;

const DOROTAPE_REWARDS_ENDPOINT = 'reward-points';

/**
 * Whether RewardsWP is here and its points programme is on.
 *
 * @return bool
 */
function dorotape_rewards_account_enabled(): bool {
	return function_exists( 'Awesomemotive\Rewardswp\Helpers\get_points_entries' )
		&& function_exists( 'Awesomemotive\Rewardswp\Helpers\is_points_program_enabled' )
		&& Awesomemotive\Rewardswp\Helpers\is_points_program_enabled();
}

// ─── Endpoint ─────────────────────────────────────────────────────────────────

add_action(
	'init',
	function (): void {
		add_rewrite_endpoint( DOROTAPE_REWARDS_ENDPOINT, EP_ROOT | EP_PAGES );
	}
);

/**
 * Put the rewrite rule back if it is missing. See the address book's version
 * for why this checks the rules rather than an option.
 */
add_action(
	'init',
	function (): void {
		$rules = get_option( 'rewrite_rules' );
		if ( ! is_array( $rules ) || ! $rules ) {
			return;
		}
		foreach ( $rules as $rewrite ) {
			if ( str_contains( (string) $rewrite, DOROTAPE_REWARDS_ENDPOINT . '=' ) ) {
				return;
			}
		}
		// Keyed by site: a database copied from another site carries that
		// site's flag, which would otherwise hold this repair back for an hour.
		$flag = 'dorotape_rewards_account_flush_' . md5( home_url() );
		if ( get_transient( $flag ) ) {
			return;
		}
		set_transient( $flag, 1, HOUR_IN_SECONDS );
		flush_rewrite_rules( false );
	},
	11
);

add_filter(
	'woocommerce_get_query_vars',
	function ( array $vars ): array {
		$vars[ DOROTAPE_REWARDS_ENDPOINT ] = DOROTAPE_REWARDS_ENDPOINT;
		return $vars;
	}
);

/**
 * The tab sits straight after Orders, where the points come from.
 */
add_filter(
	'woocommerce_account_menu_items',
	function ( array $items ): array {
		if ( ! dorotape_rewards_account_enabled() ) {
			return $items;
		}

		$label = __( 'Reward points', 'dorotape' );
		$out   = array();
		foreach ( $items as $key => $item ) {
			$out[ $key ] = $item;
			if ( 'orders' === $key ) {
				$out[ DOROTAPE_REWARDS_ENDPOINT ] = $label;
			}
		}
		if ( ! isset( $out[ DOROTAPE_REWARDS_ENDPOINT ] ) ) {
			$out[ DOROTAPE_REWARDS_ENDPOINT ] = $label;
		}

		return $out;
	},
	20
);

add_filter(
	'woocommerce_endpoint_' . DOROTAPE_REWARDS_ENDPOINT . '_title',
	static fn (): string => __( 'Reward points', 'dorotape' )
);

// ─── Screen ───────────────────────────────────────────────────────────────────

add_action(
	'woocommerce_account_' . DOROTAPE_REWARDS_ENDPOINT . '_endpoint',
	function ( $page = '' ): void {
		if ( ! dorotape_rewards_account_enabled() ) {
			printf( '<p>%s</p>', esc_html__( 'Reward points are not available at the moment.', 'dorotape' ) );
			return;
		}

		$member = Awesomemotive\Rewardswp\Helpers\get_current_member();

		if ( ! $member || ! $member->id ) {
			printf(
				'<p>%s</p><p><a class="button" href="#rewardswp-join">%s</a></p>',
				esc_html__( 'You have not joined Dorotape Rewards yet. Members earn points on every order and spend them on discounts.', 'dorotape' ),
				esc_html__( 'Join Dorotape Rewards', 'dorotape' )
			);
			return;
		}

		$per_page = 20;
		$page     = max( 1, absint( $page ) );
		$args     = array( 'member_id' => (int) $member->id );
		$total    = Awesomemotive\Rewardswp\Helpers\count_points_entries( $args );
		$entries  = Awesomemotive\Rewardswp\Helpers\get_points_entries( $args, $per_page, $page );
		$balance  = (int) $member->points_balance;

		?>
		<div class="dt-rewards-account">
			<p class="dt-rewards-account__balance">
				<?php
				printf(
					/* translators: %s: points balance, already formatted. */
					esc_html( _n( 'You have %s point.', 'You have %s points.', $balance, 'dorotape' ) ),
					'<strong>' . esc_html( number_format_i18n( $balance ) ) . '</strong>'
				);
				?>
			</p>
			<p>
				<a class="button" href="#rewardswp-redeem"><?php esc_html_e( 'Spend points', 'dorotape' ); ?></a>
				<a class="button" href="#rewardswp-earn"><?php esc_html_e( 'Ways to earn', 'dorotape' ); ?></a>
			</p>

			<?php if ( empty( $entries ) ) : ?>
				<p><?php esc_html_e( 'No points yet. They appear here once an order has been dispatched.', 'dorotape' ); ?></p>
			<?php else : ?>
				<table class="woocommerce-orders-table shop_table shop_table_responsive my_account_orders dt-rewards-account__table">
					<thead>
						<tr>
							<th scope="col" class="woocommerce-orders-table__header"><?php esc_html_e( 'Date', 'dorotape' ); ?></th>
							<th scope="col" class="woocommerce-orders-table__header"><?php esc_html_e( 'Details', 'dorotape' ); ?></th>
							<th scope="col" class="woocommerce-orders-table__header"><?php esc_html_e( 'Points', 'dorotape' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $entries as $entry ) : ?>
							<tr class="woocommerce-orders-table__row">
								<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-date" data-title="<?php esc_attr_e( 'Date', 'dorotape' ); ?>">
									<?php echo esc_html( $entry->created_at ? date_i18n( get_option( 'date_format' ), strtotime( $entry->created_at ) /* RewardsWP stores site time, not GMT. */ ) : '' ); ?>
								</td>
								<td class="woocommerce-orders-table__cell dt-rewards-account__details" data-title="<?php esc_attr_e( 'Details', 'dorotape' ); ?>">
									<?php echo wp_kses( dorotape_rewards_entry_label( $entry ), array( 'a' => array( 'href' => array() ) ) ); ?>
									<?php
									$state = dorotape_rewards_entry_state( $entry );
									if ( '' !== $state ) {
										printf( ' <span class="dt-rewards-account__state">(%s)</span>', esc_html( $state ) );
									}
									?>
								</td>
								<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-total dt-rewards-account__points" data-title="<?php esc_attr_e( 'Points', 'dorotape' ); ?>">
									<?php echo esc_html( $entry->get_formatted_points() ); ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<?php if ( $total > $per_page ) : ?>
					<div class="woocommerce-pagination woocommerce-pagination--without-numbers woocommerce-Pagination">
						<?php if ( $page > 1 ) : ?>
							<a class="woocommerce-button woocommerce-button--previous woocommerce-Button woocommerce-Button--previous button" href="<?php echo esc_url( wc_get_endpoint_url( DOROTAPE_REWARDS_ENDPOINT, $page - 1 ) ); ?>"><?php esc_html_e( 'Newer', 'dorotape' ); ?></a>
						<?php endif; ?>
						<?php if ( $page * $per_page < $total ) : ?>
							<a class="woocommerce-button woocommerce-button--next woocommerce-Button woocommerce-Button--next button" href="<?php echo esc_url( wc_get_endpoint_url( DOROTAPE_REWARDS_ENDPOINT, $page + 1 ) ); ?>"><?php esc_html_e( 'Older', 'dorotape' ); ?></a>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php
	}
);

/**
 * What a points entry was for, in the customer's words.
 *
 * @param object $entry A RewardsWP Point.
 * @return string Plain text, or text with a link to the order.
 */
function dorotape_rewards_entry_label( $entry ): string {
	$note  = trim( (string) $entry->note_public );
	$order = dorotape_rewards_entry_order_link( $entry );

	switch ( $entry->source_type ) {
		case 'purchase':
			/* translators: %s: order number, linked. */
			return $order ? sprintf( __( 'Order %s', 'dorotape' ), $order ) : __( 'Order', 'dorotape' );
		case 'first_order':
			return __( 'Bonus for your first order', 'dorotape' );
		case 'signup':
			return __( 'Welcome bonus for joining', 'dorotape' );
		case 'import':
			return 'spent' === $entry->type
				? __( 'Used on our previous website', 'dorotape' )
				: __( 'Carried over from our previous website', 'dorotape' );
		case 'refund':
		case 'partial_refund':
			/* translators: %s: order number, linked. */
			return $order ? sprintf( __( 'Taken back for a refund on order %s', 'dorotape' ), $order ) : __( 'Taken back for a refund', 'dorotape' );
		case 'reward_redemption':
			return '' !== $note ? esc_html( $note ) : __( 'Spent on a reward', 'dorotape' );
		case 'referral':
			return __( 'Referred a friend', 'dorotape' );
		case 'birthday':
			return __( 'Birthday bonus', 'dorotape' );
		case 'product_review':
			return __( 'Product review', 'dorotape' );
	}

	if ( '' !== $note ) {
		return esc_html( $note );
	}

	return 'spent' === $entry->type ? __( 'Points adjusted down', 'dorotape' ) : __( 'Points added', 'dorotape' );
}

/**
 * "#993701", linked to the order when it is this customer's.
 *
 * @param object $entry A RewardsWP Point.
 * @return string Empty when the entry has no order.
 */
function dorotape_rewards_entry_order_link( $entry ): string {
	if ( ! in_array( $entry->source_type, array( 'purchase', 'refund', 'partial_refund' ), true ) || empty( $entry->source_id ) ) {
		return '';
	}

	$order = wc_get_order( (int) $entry->source_id );
	if ( ! $order instanceof WC_Order ) {
		return '';
	}

	$number = esc_html( '#' . $order->get_order_number() );
	if ( (int) $order->get_customer_id() !== get_current_user_id() ) {
		return $number;
	}

	return '<a href="' . esc_url( $order->get_view_order_url() ) . '">' . $number . '</a>';
}

/**
 * A word on entries that do not count towards the balance yet, or any more.
 *
 * @param object $entry A RewardsWP Point.
 * @return string Empty for an ordinary, counted entry.
 */
function dorotape_rewards_entry_state( $entry ): string {
	if ( method_exists( $entry, 'is_pending' ) && $entry->is_pending() ) {
		return __( 'pending until the order is dispatched', 'dorotape' );
	}
	if ( method_exists( $entry, 'is_revoked' ) && $entry->is_revoked() ) {
		return __( 'cancelled', 'dorotape' );
	}
	if ( method_exists( $entry, 'is_expired' ) && $entry->is_expired() ) {
		return __( 'expired', 'dorotape' );
	}
	return '';
}
