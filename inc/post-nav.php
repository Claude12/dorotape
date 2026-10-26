<?php
declare( strict_types=1 );
/**
 * Previous, next and back: the band at the end of an article or a case study.
 *
 * A reader at the end of one case study or article had nowhere to go but the
 * menu (Site Edits, October 2026). This is the way on: the previous and next
 * one either side, and under them the way back to the listing and, where the
 * editor gives one, a second button such as Contact.
 *
 * Two callers. Blog articles take their neighbours by date and their wording
 * from Blog Page > Article. A page takes its neighbours from its siblings
 * under the same parent, in the order set under Page Attributes, and only
 * when that parent has "Previous and next on child pages" turned on in its
 * Child Pages box, so the case studies get it and the support pages, which
 * are siblings too, do not.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

/**
 * Print the band.
 *
 * @param array{prev?:int,next?:int,prev_label?:string,next_label?:string,back?:array{url:string,title:string},extra?:array{url:string,title:string,target?:string}} $args
 *        Neighbour post ids (0 for none), their labels, and the buttons.
 */
function dorotape_post_nav( array $args ): void {
	$prev  = (int) ( $args['prev'] ?? 0 );
	$next  = (int) ( $args['next'] ?? 0 );
	$back  = (array) ( $args['back'] ?? array() );
	$extra = (array) ( $args['extra'] ?? array() );

	if ( ! $prev && ! $next && empty( $back['url'] ) ) {
		return;
	}

	$links = array(
		'prev' => array( $prev, (string) ( $args['prev_label'] ?? '' ), 'chevron-left' ),
		'next' => array( $next, (string) ( $args['next_label'] ?? '' ), 'chevron-right' ),
	);
	?>
	<nav class="post-nav" aria-label="<?php esc_attr_e( 'More to read', 'dorotape' ); ?>">
		<div class="container">

			<?php if ( $prev || $next ) : ?>
				<div class="post-nav__links">
					<?php foreach ( $links as $dt_side => $dt_link ) : ?>
						<?php if ( ! $dt_link[0] ) : ?>
							<?php // An empty cell keeps a lone next link on the right. ?>
							<span class="post-nav__link post-nav__link--empty" aria-hidden="true"></span>
							<?php continue; ?>
						<?php endif; ?>
						<a class="post-nav__link post-nav__link--<?php echo esc_attr( $dt_side ); ?>" href="<?php echo esc_url( (string) get_permalink( $dt_link[0] ) ); ?>" rel="<?php echo esc_attr( $dt_side ); ?>">
							<?php echo dorotape_ui_icon( $dt_link[2], 'post-nav__icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed icon markup. ?>
							<span class="post-nav__text">
								<?php if ( '' !== $dt_link[1] ) : ?>
									<span class="post-nav__label"><?php echo esc_html( $dt_link[1] ); ?></span>
								<?php endif; ?>
								<span class="post-nav__title"><?php echo esc_html( get_the_title( $dt_link[0] ) ); ?></span>
							</span>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $back['url'] ) || ! empty( $extra['url'] ) ) : ?>
				<div class="post-nav__actions">
					<?php if ( ! empty( $back['url'] ) && '' !== (string) ( $back['title'] ?? '' ) ) : ?>
						<a class="btn btn--outline" href="<?php echo esc_url( $back['url'] ); ?>"><?php echo esc_html( $back['title'] ); ?></a>
					<?php endif; ?>

					<?php if ( ! empty( $extra['url'] ) && '' !== (string) ( $extra['title'] ?? '' ) ) : ?>
						<a class="btn" href="<?php echo esc_url( $extra['url'] ); ?>"<?php echo '_blank' === ( $extra['target'] ?? '' ) ? ' target="_blank" rel="noopener"' : ''; ?>>
							<?php echo esc_html( $extra['title'] ); ?>
							<?php echo dorotape_arrow_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed icon markup. ?>
						</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>

		</div>
	</nav>
	<?php
}

/**
 * The band for a child page, when its parent asks for one.
 *
 * Neighbours are the parent's other published children in Page Attributes
 * order, then by date, so the case studies run in the order they were
 * added unless an editor numbers them.
 *
 * @param int $page_id The page being viewed.
 */
function dorotape_child_page_nav( int $page_id ): void {
	$parent = (int) wp_get_post_parent_id( $page_id );

	if ( ! $parent || ! function_exists( 'get_field' ) || ! get_field( 'child_nav', $parent ) ) {
		return;
	}

	$siblings = get_posts(
		array(
			'post_type'      => 'page',
			'post_parent'    => $parent,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => array(
				'menu_order' => 'ASC',
				'date'       => 'ASC',
			),
			'fields'         => 'ids',
		)
	);

	$index = array_search( $page_id, array_map( 'intval', $siblings ), true );

	if ( false === $index ) {
		return;
	}

	$back_label = trim( (string) get_field( 'child_back_label', $parent ) );
	$extra      = (array) get_field( 'child_extra_link', $parent );

	dorotape_post_nav(
		array(
			'prev'       => (int) ( $siblings[ $index - 1 ] ?? 0 ),
			'next'       => (int) ( $siblings[ $index + 1 ] ?? 0 ),
			'prev_label' => trim( (string) get_field( 'child_prev_label', $parent ) ),
			'next_label' => trim( (string) get_field( 'child_next_label', $parent ) ),
			'back'       => array(
				'url'   => (string) get_permalink( $parent ),
				'title' => '' !== $back_label ? $back_label : get_the_title( $parent ),
			),
			'extra'      => array(
				'url'    => (string) ( $extra['url'] ?? '' ),
				'title'  => (string) ( $extra['title'] ?? '' ),
				'target' => (string) ( $extra['target'] ?? '' ),
			),
		)
	);
}
