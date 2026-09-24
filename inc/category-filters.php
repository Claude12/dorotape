<?php
declare( strict_types=1 );
/**
 * Facets for the leaf category page.
 *
 * Built from final designs/main category: a sticky filter panel beside the
 * product grid, with a search box, a checkbox list per attribute, finish
 * pills, an in-stock switch, a live count and a clear button.
 *
 * Two decisions worth knowing about.
 *
 * The facets are derived from the products, not configured. A leaf category
 * is asked which attributes its own products carry, and anything at least two
 * of them disagree about becomes a filter. There are 151 leaf categories and
 * they share almost nothing: the Optima range filters on colour, finish and
 * width, Knives and Cutting Tools on blade type and fitment. A hard coded
 * list would be right for one of them.
 *
 * The filtering itself happens in the browser, not here. The largest leaf
 * category holds 93 products, and rendering every one of them costs about
 * 250ms more than the 24 the paginated archive used to show. Against that, a
 * server round trip per checkbox measured about 1.3 seconds. So the whole
 * category is rendered once, each card carries its own facet tokens, and
 * assets/js/lib/category-filters.js shows and hides them with no network at
 * all. The page still works with the script blocked: what is rendered is the
 * complete category, which is the design's own default state.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

/**
 * Attributes that are never offered as a filter.
 *
 * These are purchase options rather than facets: the answer is chosen on the
 * product page after picking the product, so filtering the grid by one would
 * hide products that can in fact be bought that way.
 *
 * @return array<int, string> Attribute names, without the pa_ prefix.
 */
function dorotape_category_filter_excluded(): array {
	return array(
		'choose-quantity',
		'choose-quantity-required',
		'choose-size-and-quantity',
		'select-quantity-of-rolls',
		'select-quantity-required',
		'choose-length',
		'select-length-of-roll',
		'choose-roll-length-below',
		'choose-roll-size-below',
		'select-size-of-roll',
		'size-format',
		'option',
	);
}

/**
 * The products in a category, in the order the archive would list them.
 *
 * Ordered by menu_order then title, which is what WooCommerce's own default
 * catalogue ordering resolves to, so the grid reads the same as the shop.
 *
 * @param WP_Term $term Category being viewed.
 * @return array<int, int> Product IDs.
 */
function dorotape_category_filter_products( WP_Term $term ): array {
	$ids = get_posts(
		array(
			'post_type'        => 'product',
			'post_status'      => 'publish',
			'posts_per_page'   => -1,
			'fields'           => 'ids',
			'orderby'          => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
			'no_found_rows'    => true,
			'suppress_filters' => false,
			'tax_query'        => array(
				'relation' => 'AND',
				array(
					'taxonomy' => 'product_cat',
					'field'    => 'term_id',
					'terms'    => $term->term_id,
				),
				// The same visibility rule the shop loop applies, so a product
				// hidden from the catalogue does not reappear here.
				array(
					'taxonomy' => 'product_visibility',
					'field'    => 'name',
					'terms'    => array( 'exclude-from-catalog' ),
					'operator' => 'NOT IN',
				),
			),
		)
	);

	$ids = array_map( 'intval', $ids );

	/*
	 * One query for every product's terms, instead of one per product per
	 * attribute.
	 *
	 * Everything below reads attribute terms off these products, and
	 * WC_Product_Attribute::get_terms() goes through get_the_terms(), which
	 * only avoids a query when the object term cache is already primed. On the
	 * largest category, 93 products across four attributes, leaving it cold
	 * cost 517ms of the page. Priming it here rather than in each caller keeps
	 * the saving whichever of them runs first.
	 */
	if ( $ids ) {
		_prime_post_caches( $ids, false, true );
		update_object_term_cache( $ids, 'product' );
	}

	return $ids;
}

/**
 * The facet tokens a product carries, keyed by attribute taxonomy.
 *
 * Only visible attributes count. An attribute an editor has hidden on the
 * product page is one the customer is not meant to be choosing by.
 *
 * @param WC_Product $product Product to read.
 * @return array<string, array<int, array{slug:string,name:string}>>
 */
function dorotape_category_filter_product_terms( WC_Product $product ): array {
	$out = array();

	foreach ( $product->get_attributes() as $taxonomy => $attribute ) {
		if ( ! $attribute->is_taxonomy() || ! $attribute->get_visible() ) {
			continue;
		}

		if ( in_array( str_replace( 'pa_', '', $taxonomy ), dorotape_category_filter_excluded(), true ) ) {
			continue;
		}

		$terms = $attribute->get_terms();

		if ( ! is_array( $terms ) || ! $terms ) {
			continue;
		}

		foreach ( $terms as $term ) {
			$out[ $taxonomy ][] = array(
				'slug' => $term->slug,
				'name' => $term->name,
			);
		}
	}

	return $out;
}

/**
 * The facets for a set of products, ready to render.
 *
 * An attribute earns a place only when the products disagree about it: one
 * every product answers the same way filters nothing and is a row of dead
 * controls. The cap on distinct values is there for the same reason from the
 * other end, since a list of sixty checkboxes is a worse way to find
 * something than the search box above it.
 *
 * Ordering puts the attributes the filter bar already names first, in its
 * order, so the labels a customer has seen elsewhere on the site keep their
 * usual places. Anything else follows alphabetically.
 *
 * @param array<int, int> $product_ids Products in the category.
 * @return array<int, array{taxonomy:string,label:string,terms:array<int, array{slug:string,name:string,count:int}>}>
 */
function dorotape_category_filter_facets( array $product_ids ): array {
	$found = array();

	foreach ( $product_ids as $id ) {
		$product = wc_get_product( $id );

		if ( ! $product instanceof WC_Product ) {
			continue;
		}

		foreach ( dorotape_category_filter_product_terms( $product ) as $taxonomy => $terms ) {
			foreach ( $terms as $term ) {
				if ( ! isset( $found[ $taxonomy ][ $term['slug'] ] ) ) {
					$found[ $taxonomy ][ $term['slug'] ] = array(
						'slug'  => $term['slug'],
						'name'  => $term['name'],
						'count' => 0,
					);
				}

				++$found[ $taxonomy ][ $term['slug'] ]['count'];
			}
		}
	}

	$curated = array_keys( dorotape_filter_bar_attributes() );
	$facets  = array();

	foreach ( $found as $taxonomy => $terms ) {
		if ( count( $terms ) < 2 || count( $terms ) > 40 ) {
			continue;
		}

		$name  = str_replace( 'pa_', '', $taxonomy );
		$rank  = array_search( $name, $curated, true );
		$terms = array_values( $terms );

		/*
		 * Numbers in the name first, so 630mm comes before 1260mm rather than
		 * after it. These terms are collected off the products instead of
		 * through get_terms(), so the sort that inc/woocommerce.php hooks onto
		 * get_terms() never sees them; calling the same key builder here keeps
		 * a Width pill list in the order the variation dropdowns already use.
		 * A facet of words, Gloss and Matt, has no numbers and stays
		 * alphabetical.
		 */
		usort(
			$terms,
			static function ( array $a, array $b ): int {
				$cmp = dorotape_size_sort_key( $a['name'] ) <=> dorotape_size_sort_key( $b['name'] );

				return 0 !== $cmp ? $cmp : strcasecmp( $a['name'], $b['name'] );
			}
		);

		$facets[] = array(
			'taxonomy' => $taxonomy,
			'label'    => wc_attribute_label( $taxonomy ),
			'rank'     => false === $rank ? PHP_INT_MAX : $rank,
			'terms'    => $terms,
		);
	}

	usort(
		$facets,
		static fn( array $a, array $b ): int => $a['rank'] === $b['rank']
			? strcasecmp( $a['label'], $b['label'] )
			: $a['rank'] <=> $b['rank']
	);

	return $facets;
}

/**
 * The sibling categories, for the "Browse" list at the foot of the panel.
 *
 * Siblings rather than the design's list of the eight top level categories,
 * which is the main navigation and is already on the page twice. The other
 * ranges inside this one's parent are the links that are not anywhere else,
 * and they are what someone comparing ranges actually wants. A category with
 * no parent, or an only child, falls back to the top level.
 *
 * @param WP_Term $term Category being viewed.
 * @return array{label:string, terms:array<int, WP_Term>}
 */
function dorotape_category_filter_siblings( WP_Term $term ): array {
	$parent   = $term->parent ? get_term( $term->parent, 'product_cat' ) : null;
	$siblings = array();

	if ( $parent instanceof WP_Term ) {
		$siblings = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'parent'     => $parent->term_id,
				'hide_empty' => true,
				'exclude'    => array( $term->term_id ),
			)
		);
	}

	if ( is_wp_error( $siblings ) || ! $siblings ) {
		$top = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'parent'     => 0,
				'hide_empty' => true,
			)
		);

		return array(
			'label' => __( 'Browse categories', 'dorotape' ),
			'terms' => is_wp_error( $top ) ? array() : $top,
		);
	}

	return array(
		/* translators: %s: parent category name. */
		'label' => sprintf( __( 'More in %s', 'dorotape' ), $parent->name ),
		'terms' => $siblings,
	);
}

/**
 * The filter panel.
 *
 * A real <form> around real inputs, wired to nothing. Submitting it reloads
 * the page unfiltered, which is the honest no-JS outcome: what the server
 * rendered is the whole category, so there is nothing for a submit to do.
 * assets/js/lib/category-filters.js takes it over on load, and until it does
 * the controls are marked hidden so a customer is never offered a filter that
 * cannot act.
 *
 * Small facets are pills and large ones are checkboxes, at the four value
 * mark. That is the split the design draws between Finish, which has two
 * values, and Colour, which has twelve, and it generalises: a list of pills
 * stops being scannable at about a line and a half.
 *
 * @param WP_Term                                                                                                 $term   Category being viewed.
 * @param array<int, array{taxonomy:string,label:string,terms:array<int, array{slug:string,name:string,count:int}>}> $facets Facets to offer.
 * @param int                                                                                                     $total  Products in the category.
 */
function dorotape_category_filter_panel( WP_Term $term, array $facets, int $total ): void {
	$browse = dorotape_category_filter_siblings( $term );
	?>
	<aside class="category-filters" data-category-filters hidden>
		<form class="category-filters__panel" id="dt-filter-panel" method="get" action="">

			<?php
			/*
			 * What is currently filtered, built by the script as boxes are
			 * ticked. Empty and hidden in the markup because nothing can be
			 * ticked before the script runs: the panel itself is hidden until
			 * then. Each chip takes its own value off, which on a twelve colour
			 * list is the difference between undoing a filter and hunting for
			 * the box it came from.
			 */
			?>
			<div
				class="category-filters__active"
				data-filter-chips
				hidden
				data-chip-remove="<?php echo esc_attr( __( 'Remove the {facet} filter {name}', 'dorotape' ) ); ?>"
				data-chip-search="<?php esc_attr_e( 'Search', 'dorotape' ); ?>"
				data-chip-stock="<?php esc_attr_e( 'Availability', 'dorotape' ); ?>"
			>
				<p class="category-filters__active-label"><?php esc_html_e( 'Filtering by', 'dorotape' ); ?></p>
				<ul class="category-filters__chips" data-filter-chip-list></ul>
			</div>

			<div class="category-filters__group">
				<label class="category-filters__legend" for="dt-filter-search">
					<?php esc_html_e( 'Search this range', 'dorotape' ); ?>
				</label>
				<div class="category-filters__search">
					<?php echo dorotape_ui_icon( 'search', 'category-filters__search-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed icon markup. ?>
					<input
						type="search"
						id="dt-filter-search"
						class="category-filters__input"
						data-filter-search
						autocomplete="off"
						placeholder="<?php esc_attr_e( 'Name or item number', 'dorotape' ); ?>"
					>
				</div>
			</div>

			<?php foreach ( $facets as $facet ) : ?>
				<?php $dt_pills = count( $facet['terms'] ) <= 4; ?>
				<fieldset class="category-filters__group">
					<legend class="category-filters__legend"><?php echo esc_html( $facet['label'] ); ?></legend>

					<?php if ( $dt_pills ) : ?>
						<div class="category-filters__pills">
							<?php foreach ( $facet['terms'] as $dt_term ) : ?>
								<label class="category-filters__pill">
									<input
										type="checkbox"
										class="category-filters__pill-input"
										data-filter-facet="<?php echo esc_attr( $facet['taxonomy'] ); ?>"
										value="<?php echo esc_attr( $dt_term['slug'] ); ?>"
									>
									<span class="category-filters__pill-label"><?php echo esc_html( $dt_term['name'] ); ?></span>
								</label>
							<?php endforeach; ?>
						</div>
					<?php else : ?>
						<ul class="category-filters__list">
							<?php foreach ( $facet['terms'] as $dt_term ) : ?>
								<li>
									<label class="category-filters__check">
										<input
											type="checkbox"
											class="category-filters__checkbox"
											data-filter-facet="<?php echo esc_attr( $facet['taxonomy'] ); ?>"
											value="<?php echo esc_attr( $dt_term['slug'] ); ?>"
										>
										<span class="category-filters__check-label"><?php echo esc_html( $dt_term['name'] ); ?></span>
										<?php
										/*
										 * Rewritten by the script as the other
										 * filters narrow things, so the number
										 * beside a value is always what ticking
										 * it would actually leave on screen.
										 */
										?>
										<span class="category-filters__count" data-filter-count><?php echo esc_html( (string) $dt_term['count'] ); ?></span>
									</label>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</fieldset>
			<?php endforeach; ?>

			<div class="category-filters__group">
				<label class="category-filters__check">
					<input type="checkbox" class="category-filters__checkbox" data-filter-stock>
					<span class="category-filters__check-label"><?php esc_html_e( 'In stock only', 'dorotape' ); ?></span>
				</label>
			</div>


			<?php if ( $browse['terms'] ) : ?>
				<nav class="category-filters__browse" aria-label="<?php echo esc_attr( $browse['label'] ); ?>">
					<h3 class="category-filters__legend"><?php echo esc_html( $browse['label'] ); ?></h3>
					<ul class="category-filters__browse-list">
						<?php foreach ( $browse['terms'] as $dt_sibling ) : ?>
							<li>
								<a class="category-filters__browse-link" href="<?php echo esc_url( (string) get_term_link( $dt_sibling ) ); ?>">
									<?php echo esc_html( $dt_sibling->name ); ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</nav>
			<?php endif; ?>

			<div class="category-filters__footer">
				<?php
				/*
				 * The count the script keeps up to date. Polite rather than
				 * assertive because it changes on every keystroke in the
				 * search box, and assertive would interrupt the typing.
				 */
				?>
				<?php
				/*
				 * Both plural forms go on the element, so the script can count
				 * without knowing English: replacing just the digit would leave
				 * "1 products", and a language with three forms would be worse
				 * still.
				 */
				?>
				<p
					class="category-filters__result"
					data-filter-result
					aria-live="polite"
					data-result-one="<?php echo esc_attr( sprintf( _n( '%s product', '%s products', 1, 'dorotape' ), '{n}' ) ); ?>"
					data-result-many="<?php echo esc_attr( sprintf( _n( '%s product', '%s products', 2, 'dorotape' ), '{n}' ) ); ?>"
				>
					<?php
					printf(
						/* translators: %s: number of products. */
						esc_html( _n( '%s product', '%s products', $total, 'dorotape' ) ),
						esc_html( number_format_i18n( $total ) )
					);
					?>
				</p>
				<button type="button" class="category-filters__clear" data-filter-clear hidden>
					<?php echo dorotape_ui_icon( 'x', 'category-filters__clear-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed icon markup. ?>
					<?php esc_html_e( 'Clear filters', 'dorotape' ); ?>
				</button>
			</div>

		</form>
	</aside>
	<?php
}

/**
 * The tokens the browser filters a card by.
 *
 * Written into attributes as space separated strings rather than JSON, so the
 * script can match with indexOf and never parses anything. On a 93 product
 * category that is 93 parses saved on every keystroke.
 *
 * @param WC_Product $product Product to describe.
 * @return array{facets:string, stock:string, search:string}
 */
function dorotape_category_filter_tokens( WC_Product $product ): array {
	$facets = array();

	foreach ( dorotape_category_filter_product_terms( $product ) as $taxonomy => $terms ) {
		foreach ( $terms as $term ) {
			$facets[] = $taxonomy . ':' . $term['slug'];
		}
	}

	/*
	 * A variable product is in stock when any variation is, which is what
	 * is_in_stock() already answers, so this needs no special case for the
	 * cut sizes and roll lengths that make up much of the catalogue.
	 */
	$search = $product->get_name() . ' ' . $product->get_sku();

	return array(
		'facets' => implode( ' ', $facets ),
		'stock'  => $product->is_in_stock() ? 'in' : 'out',
		'search' => strtolower( trim( preg_replace( '/\s+/', ' ', $search ) ?? $search ) ),
	);
}

/**
 * The pills under a card's name.
 *
 * These take the place of the category line the card carries elsewhere. On a
 * leaf category every product sits in the same category, so printing its name
 * ninety three times says nothing; the design puts the stock state and the
 * item number there instead, which differ per card and are what someone
 * phoning an order through reads out.
 *
 * @param WC_Product $product Product to describe.
 * @return array<int, array{label:string, tone?:string}>
 */
function dorotape_category_filter_pills( WC_Product $product ): array {
	$pills = array();

	if ( $product->is_in_stock() ) {
		$pills[] = array(
			'label' => __( 'In stock', 'dorotape' ),
			'tone'  => 'cyan',
		);
	}

	$sku = $product->get_sku();

	if ( '' !== $sku ) {
		/* translators: %s: product SKU. */
		$pills[] = array( 'label' => sprintf( __( 'Item no. %s', 'dorotape' ), $sku ) );
	}

	return $pills;
}

/**
 * The heading and standfirst above the shop.
 *
 * Full width, above both columns, because that is where the design puts it and
 * because the count it carries describes the whole category rather than the
 * grid beside the panel.
 *
 * @param int $count Products in the category.
 */
function dorotape_category_filter_header( int $count ): void {
	$heading = dorotape_category_field( 'category_products_heading', __( 'Select your product', 'dorotape' ) );

	$note  = dorotape_category_page_field( 'category_products_note', __( 'stocked in the UK and dispatched across the UK and Ireland.', 'dorotape' ) );
	$intro = dorotape_category_field(
		'category_products_intro',
		trim(
			sprintf(
				/* translators: 1: number of products, 2: a closing phrase such as "stocked in the UK." */
				_n( '%1$d product in this range, %2$s', '%1$d products in this range, %2$s', $count, 'dorotape' ),
				$count,
				$note
			)
		)
	);

	if ( '' === $heading && '' === $intro ) {
		return;
	}
	?>
	<?php
	/*
	 * The ranges band's own header, not a second one. Heading left, standfirst
	 * right, wrapping at the same point: the leaf design draws the identical
	 * row, so this is the same component rather than a copy of its rules under
	 * another name.
	 */
	?>
	<?php
	/*
	 * data-filter-top: where the script puts the customer back when a filter
	 * shortens the grid out from under them.
	 */
	?>
	<div class="category-grid-block__header" data-filter-top>
		<?php if ( '' !== $heading ) : ?>
			<h2 class="category-grid-block__heading"><?php echo esc_html( $heading ); ?></h2>
		<?php endif; ?>

		<?php if ( '' !== $intro ) : ?>
			<p class="category-grid-block__intro"><?php echo esc_html( $intro ); ?></p>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * The product grid, the right hand column of the shop.
 *
 * Every product in the category, once, with the tokens each card is filtered
 * by on its own <li>. Nothing here is paginated: see the file docblock for
 * why the whole category is rendered up front.
 *
 * The cards are dorotape_product_card() in its 'title' mode, the same call
 * Related products makes, so a product looks the same wherever it is shown.
 *
 * @param array<int, int> $product_ids Products to show.
 */
function dorotape_category_filter_grid( array $product_ids ): void {
	$empty = dorotape_category_page_field( 'category_products_empty', __( 'No products match those filters. Try clearing them.', 'dorotape' ) );
	?>
	<div class="category-shop__products">

		<ul class="category-shop__grid" data-filter-grid>
			<?php foreach ( $product_ids as $dt_product_id ) : ?>
				<?php
				$dt_product = wc_get_product( $dt_product_id );

				if ( ! $dt_product instanceof WC_Product ) {
					continue;
				}

				$dt_tokens = dorotape_category_filter_tokens( $dt_product );
				?>
				<li
					class="category-shop__item"
					data-filter-item
					data-facets="<?php echo esc_attr( $dt_tokens['facets'] ); ?>"
					data-stock="<?php echo esc_attr( $dt_tokens['stock'] ); ?>"
					data-search="<?php echo esc_attr( $dt_tokens['search'] ); ?>"
				>
					<?php
					/*
					 * The loop globals the card's add to cart button reads are
					 * not set here, since this is not the WooCommerce loop: the
					 * archive's own loop was emptied so the whole category
					 * could be rendered at once. Setting them per card is what
					 * makes woocommerce_template_loop_add_to_cart() pick the
					 * right label and behaviour for each product type.
					 */
					$GLOBALS['product'] = $dt_product; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Required by WooCommerce loop templates.

					dorotape_product_card(
						$dt_product,
						array(
							'link'        => 'title',
							'add_to_cart' => true,
							'pills'       => dorotape_category_filter_pills( $dt_product ),
							// The same modifier Related products uses: equal
							// height cards with the button pinned to the foot,
							// which is what a grid of them needs in both places.
							'modifier'    => 'product-card--grid',
							'sizes'       => '(min-width: 1280px) 300px, (min-width: 576px) 44vw, 88vw',
						)
					);
					?>
				</li>
			<?php endforeach; ?>
		</ul>

		<p class="category-shop__empty" data-filter-empty hidden><?php echo esc_html( $empty ); ?></p>

	</div>
	<?php
	unset( $GLOBALS['product'] );
}

/**
 * The shop: heading, filter panel and product grid.
 *
 * The band around it is the same category-grid-block shell the top level page
 * uses for its ranges, with the same divider, corner glow and editable
 * background shape. Only the inside differs, so this reuses the shell rather
 * than drawing a second one that would have to be kept in step with it.
 */
function dorotape_category_shop(): void {
	$term = dorotape_category_term();

	if ( ! $term instanceof WP_Term ) {
		return;
	}

	$product_ids = dorotape_category_filter_products( $term );
	$facets      = dorotape_category_filter_facets( $product_ids );

	/*
	 * Whether the panel earns its column.
	 *
	 * A facet is reason enough. Without one the panel is a search box and an
	 * in stock tickbox, which is worth a quarter of the screen on a long
	 * category and nothing at all on a short one: with a dozen products
	 * already in front of you, searching them is slower than reading them. So
	 * a small category with no attributes gets the full width grid instead,
	 * and a long one keeps the search box for the item numbers trade
	 * customers order by.
	 */
	$filters = $facets || count( $product_ids ) >= 12;

	$shape   = dorotape_background_shape_value( dorotape_category_page_field( 'category_products_shape', 'cubes-right' ) );
	$classes  = 'category-grid-block category-grid-block--glow-soft category-grid-block--shop';
	$has_sections = dorotape_category_has_term_sections();
	$classes     .= $has_sections ? ' category-grid-block--divider' : '';
	$classes .= dorotape_background_shape_class( $shape );
	?>
	<?php
	/*
	 * animate-offset="0" because the reveal threshold is a fraction of the
	 * element's own height, and this one grows with the category: at 54
	 * products it is nearly ten thousand pixels tall, so the default tenth of
	 * it could never fit on screen and the whole shop stayed at opacity 0.
	 */
	?>
	<section id="products" class="<?php echo esc_attr( $classes ); ?>" animate="fade-in-up" animate-offset="0">
		<?php dorotape_background_shape( $shape ); ?>
		<?php if ( $has_sections ) : ?>
			<div class="aurora-rule category-grid-block__rule" aria-hidden="true"></div>
		<?php endif; ?>

		<div class="container">
			<?php if ( $product_ids ) : ?>
				<?php dorotape_category_filter_header( count( $product_ids ) ); ?>
			<?php endif; ?>

			<?php if ( ! $product_ids ) : ?>
				<p class="category-shop__empty">
					<?php
					echo esc_html(
						dorotape_category_page_field(
							'category_products_none',
							__( 'There is nothing in this category at the moment. Call us on 01858 431642 and we will point you to the nearest material.', 'dorotape' )
						)
					);
					?>
				</p>
			<?php else : ?>
				<div class="category-shop<?php echo $filters ? '' : ' category-shop--plain'; ?>">
					<?php if ( $filters ) : ?>
						<?php
						/*
						 * Below the two column breakpoint the panel is a
						 * drawer, and this opens it. It ships hidden with the
						 * panel, for the same reason: with no script there is
						 * nothing to open, and the whole category is already
						 * on the page.
						 */
						?>
						<button type="button" class="category-shop__toggle btn btn--sm btn--outline" data-filter-toggle aria-expanded="false" aria-controls="dt-filter-panel" hidden>
							<?php esc_html_e( 'Filters', 'dorotape' ); ?>
							<span class="category-shop__toggle-count" data-filter-active hidden></span>
						</button>

						<?php dorotape_category_filter_panel( $term, $facets, count( $product_ids ) ); ?>
					<?php endif; ?>

					<?php dorotape_category_filter_grid( $product_ids ); ?>
				</div>
			<?php endif; ?>
		</div>
	</section>
	<?php
}
