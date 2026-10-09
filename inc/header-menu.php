<?php
declare( strict_types=1 );
/**
 * The header's main navigation: desktop menu bar and mobile drawer.
 *
 * Both are drawn from the menu in the Primary Navigation slot, which is three
 * levels deep (Products > Sign & Display Vinyl > Signmaking Vinyl), so the
 * markup is written here rather than by wp_nav_menu(). A walker could produce
 * it, but the two views need different elements for the same item (a panel
 * trigger is a <button>, a leaf is an <a>) and a tree is easier to read than
 * a walker's start_lvl/start_el callbacks.
 *
 * What an item becomes is decided by its shape, so an editor never has to
 * pick a "type":
 *   - no children                      a plain link in the bar
 *   - children, none with children     a dropdown list (Support)
 *   - children with children           a mega panel (Products, Applications):
 *                                       the children become a list of groups
 *                                       on the left, and the hovered or
 *                                       focused group's own children fill
 *                                       the rest of the panel
 *
 * A custom link of "#" is a heading with no page of its own: it renders as a
 * button, never as a link to "#". The Applications groups are these.
 *
 * Where a parent has a real page that its children do not already link to,
 * the panel ends with a "View all ..." link to it, since the bar's trigger is
 * a button and would otherwise leave that page unreachable. The label comes
 * from Theme Settings > Header.
 *
 * Behaviour is assets/js/lib/header.js, styles assets/scss/layout/_header.scss.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

/**
 * The menu in a theme location as a tree, with WordPress's current-item
 * classes applied.
 *
 * Memoised, because the header draws the same menu twice (bar and drawer).
 *
 * @param string $location Theme location slug.
 * @return array<int, array{item: WP_Post, children: array}> Top-level nodes.
 */
function dorotape_menu_tree( string $location ): array {
	static $trees = array();

	if ( isset( $trees[ $location ] ) ) {
		return $trees[ $location ];
	}

	$locations = get_nav_menu_locations();
	$items     = empty( $locations[ $location ] ) ? array() : wp_get_nav_menu_items( $locations[ $location ], array( 'update_post_term_cache' => false ) );

	if ( ! $items ) {
		$trees[ $location ] = array();
		return array();
	}

	// Adds current-menu-item / current-menu-ancestor to each item's classes,
	// the same way wp_nav_menu() does before it walks the menu.
	_wp_menu_item_classes_by_context( $items );

	$nodes = array();
	foreach ( $items as $item ) {
		$nodes[ (int) $item->ID ] = array(
			'item'     => $item,
			'children' => array(),
		);
	}

	$tree = array();
	foreach ( $items as $item ) {
		$id     = (int) $item->ID;
		$parent = (int) $item->menu_item_parent;

		if ( $parent && isset( $nodes[ $parent ] ) ) {
			$nodes[ $parent ]['children'][] = &$nodes[ $id ];
		} else {
			$tree[] = &$nodes[ $id ];
		}
	}

	$trees[ $location ] = $tree;
	return $tree;
}

/**
 * Whether a menu item goes somewhere. "#" and empty are headings.
 */
function dorotape_menu_has_url( WP_Post $item ): bool {
	return '' !== $item->url && '#' !== $item->url;
}

/**
 * Whether any child of a node has children of its own, which makes it a mega
 * panel rather than a dropdown list.
 */
function dorotape_menu_is_mega( array $node ): bool {
	foreach ( $node['children'] as $child ) {
		if ( $child['children'] ) {
			return true;
		}
	}
	return false;
}

/**
 * Whether the item is the current page or one of its ancestors.
 */
function dorotape_menu_is_current( WP_Post $item ): bool {
	return (bool) array_intersect(
		(array) $item->classes,
		array( 'current-menu-item', 'current-menu-ancestor', 'current-menu-parent' )
	);
}

/**
 * The "View all ..." link for a parent, or null when it has no page of its
 * own or a child already links there (Inspiration's Case Studies).
 *
 * @return array{url:string,label:string}|null
 */
function dorotape_menu_overview( array $node ): ?array {
	$item = $node['item'];

	if ( ! dorotape_menu_has_url( $item ) ) {
		return null;
	}

	foreach ( $node['children'] as $child ) {
		if ( untrailingslashit( $child['item']->url ) === untrailingslashit( $item->url ) ) {
			return null;
		}
	}

	$label = trim( (string) dorotape_setting( 'header_menu_view_all' ) );

	return array(
		'url'   => (string) $item->url,
		'label' => trim( $label . ' ' . $item->title ),
	);
}

/**
 * Attributes every link shares: href, plus target/rel/title when the editor
 * set them on the menu item.
 */
function dorotape_menu_link_attrs( WP_Post $item, bool $current = false ): string {
	$attrs = ' href="' . esc_url( $item->url ) . '"';

	if ( $item->target ) {
		$attrs .= ' target="' . esc_attr( $item->target ) . '" rel="noopener"';
	}
	if ( $item->attr_title ) {
		$attrs .= ' title="' . esc_attr( $item->attr_title ) . '"';
	}
	if ( $current && in_array( 'current-menu-item', (array) $item->classes, true ) ) {
		$attrs .= ' aria-current="page"';
	}

	return $attrs;
}

/**
 * Category thumbnail for a mega panel's group heading, when the group is a
 * product category that has one.
 */
function dorotape_menu_thumb( WP_Post $item ): string {
	if ( 'taxonomy' !== $item->type || 'product_cat' !== $item->object ) {
		return '';
	}

	$image = (int) get_term_meta( (int) $item->object_id, 'thumbnail_id', true );

	if ( ! $image ) {
		return '';
	}

	return (string) wp_get_attachment_image(
		$image,
		'thumbnail',
		false,
		array(
			'class'   => 'site-header__mega-thumb',
			'alt'     => '',
			'loading' => 'lazy',
		)
	);
}

/**
 * The desktop menu bar.
 */
function dorotape_header_nav(): void {
	$tree = dorotape_menu_tree( 'primary' );

	if ( ! $tree ) {
		return;
	}

	$chevron = dorotape_ui_icon( 'chevron-down', 'site-header__nav-chevron' );
	?>
	<ul class="site-header__nav-list" id="primary-menu">
		<?php
		foreach ( $tree as $node ) :
			$item    = $node['item'];
			$panel   = 'nav-panel-' . (int) $item->ID;
			$mega    = $node['children'] && dorotape_menu_is_mega( $node );
			$classes = array( 'site-header__nav-item' );

			if ( $mega ) {
				$classes[] = 'site-header__nav-item--mega';
			}
			if ( dorotape_menu_is_current( $item ) ) {
				$classes[] = 'is-current';
			}
			?>
			<li class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
				<?php if ( ! $node['children'] ) : ?>
					<a class="site-header__nav-link"<?php echo dorotape_menu_link_attrs( $item, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the helper. ?>><?php echo esc_html( $item->title ); ?></a>
				<?php else : ?>
					<button type="button" class="site-header__nav-link js-nav-trigger" aria-expanded="false" aria-controls="<?php echo esc_attr( $panel ); ?>">
						<?php echo esc_html( $item->title ); ?>
						<?php echo $chevron; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup. ?>
					</button>
					<?php
					if ( $mega ) {
						dorotape_header_mega_panel( $node, $panel );
					} else {
						dorotape_header_dropdown( $node, $panel );
					}
					?>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php
}

/**
 * Category shortcuts, at the right-hand end of the menu row.
 *
 * The design's row was the product categories themselves. They now sit inside
 * Products, but the row has room to spare, so the Category shortcuts menu puts
 * the most used ones back as direct links. Flat by design: anything nested in
 * that menu is ignored.
 *
 * The list is one line tall with overflow hidden and wraps, so whatever does
 * not fit at the current width drops onto a hidden second line; the menu
 * order decides which survive. header.js takes the dropped links out of the
 * tab order, so the keyboard never lands on a link nobody can see.
 */
function dorotape_header_shortcuts(): void {
	$tree = dorotape_menu_tree( 'shortcuts' );

	if ( ! $tree ) {
		return;
	}

	?>
	<ul class="site-header__shortcuts js-nav-shortcuts" aria-label="<?php esc_attr_e( 'Popular categories', 'dorotape' ); ?>">
		<?php foreach ( $tree as $node ) : ?>
			<li class="site-header__shortcut<?php echo dorotape_menu_is_current( $node['item'] ) ? ' is-current' : ''; ?>">
				<a class="site-header__shortcut-link"<?php echo dorotape_menu_link_attrs( $node['item'], true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the helper. ?>><?php echo esc_html( $node['item']->title ); ?></a>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php
}

/**
 * A plain dropdown list under the bar.
 */
function dorotape_header_dropdown( array $node, string $id ): void {
	$overview = dorotape_menu_overview( $node );
	?>
	<div class="site-header__dropdown js-nav-panel" id="<?php echo esc_attr( $id ); ?>">
		<ul class="site-header__dropdown-list">
			<?php foreach ( $node['children'] as $child ) : ?>
				<li>
					<a class="site-header__dropdown-link"<?php echo dorotape_menu_link_attrs( $child['item'], true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo esc_html( $child['item']->title ); ?></a>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php if ( $overview ) : ?>
			<a class="site-header__overview" href="<?php echo esc_url( $overview['url'] ); ?>">
				<?php echo esc_html( $overview['label'] ); ?>
				<?php echo dorotape_arrow_icon( 'site-header__overview-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</a>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * A mega panel: groups down the left, the active group's links on the right.
 *
 * Each group's links sit in the same <li> as the group's own entry, so the
 * tab order runs group, its links, next group, and a screen reader hears
 * them in that order too. CSS lifts the links out to the right-hand column.
 *
 * The first group is active in the markup, so the panel is never empty on
 * open, even before a pointer has moved over the list.
 */
function dorotape_header_mega_panel( array $node, string $id ): void {
	$overview = dorotape_menu_overview( $node );
	$view_all = trim( (string) dorotape_setting( 'header_menu_view_all' ) );
	$first    = true;
	?>
	<div class="site-header__mega js-nav-panel" id="<?php echo esc_attr( $id ); ?>">
		<div class="site-header__mega-rail">
			<ul class="site-header__mega-groups">
				<?php
				foreach ( $node['children'] as $group ) :
					$item = $group['item'];
					$pane = $id . '-' . (int) $item->ID;
					?>
					<li class="site-header__mega-group js-mega-group<?php echo $first ? ' is-active' : ''; ?>">
						<?php if ( ! $group['children'] ) : ?>
							<a class="site-header__mega-tab"<?php echo dorotape_menu_link_attrs( $item, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo esc_html( $item->title ); ?></a>
						<?php else : ?>
							<?php if ( dorotape_menu_has_url( $item ) ) : ?>
								<a class="site-header__mega-tab js-mega-tab"<?php echo dorotape_menu_link_attrs( $item, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
							<?php else : ?>
								<button type="button" class="site-header__mega-tab js-mega-tab" aria-expanded="<?php echo $first ? 'true' : 'false'; ?>" aria-controls="<?php echo esc_attr( $pane ); ?>">
							<?php endif; ?>
								<span><?php echo esc_html( $item->title ); ?></span>
								<?php echo dorotape_ui_icon( 'chevron-down', 'site-header__mega-tab-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php echo dorotape_menu_has_url( $item ) ? '</a>' : '</button>'; ?>

							<div class="site-header__mega-pane" id="<?php echo esc_attr( $pane ); ?>">
								<div class="site-header__mega-head">
									<?php echo dorotape_menu_thumb( $item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image(). ?>
									<p class="site-header__mega-title"><?php echo esc_html( $item->title ); ?></p>
									<?php if ( dorotape_menu_has_url( $item ) ) : ?>
										<a class="site-header__overview site-header__overview--head" href="<?php echo esc_url( $item->url ); ?>">
											<?php echo esc_html( trim( $view_all . ' ' . $item->title ) ); ?>
											<?php echo dorotape_arrow_icon( 'site-header__overview-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										</a>
									<?php endif; ?>
								</div>
								<ul class="site-header__mega-links">
									<?php foreach ( $group['children'] as $link ) : ?>
										<li>
											<a class="site-header__mega-link"<?php echo dorotape_menu_link_attrs( $link['item'], true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo esc_html( $link['item']->title ); ?></a>
										</li>
									<?php endforeach; ?>
								</ul>
							</div>
						<?php endif; ?>
					</li>
					<?php
					$first = false;
				endforeach;
				?>
			</ul>
			<?php if ( $overview ) : ?>
				<a class="site-header__overview site-header__overview--rail" href="<?php echo esc_url( $overview['url'] ); ?>">
					<?php echo esc_html( $overview['label'] ); ?>
					<?php echo dorotape_arrow_icon( 'site-header__overview-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</a>
			<?php endif; ?>
		</div>
	</div>
	<?php
}

/**
 * The drawer's menu: the same tree as an accordion, two levels of toggles.
 *
 * Every parent is a button that opens its list in place. A parent with a
 * page of its own gets that page as the first entry of its list ("View all
 * Sign & Display Vinyl"), so nothing the bar links to is missing here.
 */
function dorotape_header_drawer_menu(): void {
	$tree = dorotape_menu_tree( 'primary' );

	if ( ! $tree ) {
		return;
	}
	?>
	<nav aria-label="<?php esc_attr_e( 'Main menu', 'dorotape' ); ?>">
		<ul class="site-header__drawer-list" id="primary-menu-drawer">
			<?php foreach ( $tree as $node ) : ?>
				<li class="site-header__drawer-item<?php echo dorotape_menu_is_current( $node['item'] ) ? ' is-current' : ''; ?>">
					<?php dorotape_header_drawer_node( $node, 0 ); ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</nav>
	<?php
}

/**
 * One drawer entry: a link, or a toggle and the list it opens.
 */
function dorotape_header_drawer_node( array $node, int $depth ): void {
	$item  = $node['item'];
	$class = 0 === $depth ? 'site-header__drawer-link' : 'site-header__drawer-sublink';

	if ( ! $node['children'] ) {
		?>
		<a class="<?php echo esc_attr( $class ); ?>"<?php echo dorotape_menu_link_attrs( $item, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo esc_html( $item->title ); ?></a>
		<?php
		return;
	}

	$id       = 'drawer-sub-' . (int) $item->ID;
	$view_all = trim( (string) dorotape_setting( 'header_menu_view_all' ) );
	$overview = 0 === $depth
		? dorotape_menu_overview( $node )
		: ( dorotape_menu_has_url( $item ) ? array(
			'url'   => (string) $item->url,
			'label' => trim( $view_all . ' ' . $item->title ),
		) : null );
	?>
	<button type="button" class="<?php echo esc_attr( $class ); ?> site-header__drawer-toggle js-drawer-toggle" aria-expanded="false" aria-controls="<?php echo esc_attr( $id ); ?>">
		<span><?php echo esc_html( $item->title ); ?></span>
		<?php echo dorotape_ui_icon( 'chevron-down', 'site-header__drawer-chevron' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</button>
	<ul class="site-header__drawer-submenu site-header__drawer-submenu--<?php echo (int) $depth; ?>" id="<?php echo esc_attr( $id ); ?>">
		<?php if ( $overview ) : ?>
			<li>
				<a class="site-header__drawer-sublink site-header__drawer-sublink--overview" href="<?php echo esc_url( $overview['url'] ); ?>"><?php echo esc_html( $overview['label'] ); ?></a>
			</li>
		<?php endif; ?>
		<?php foreach ( $node['children'] as $child ) : ?>
			<li class="site-header__drawer-subitem">
				<?php dorotape_header_drawer_node( $child, $depth + 1 ); ?>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php
}
