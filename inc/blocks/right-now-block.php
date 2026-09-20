<?php
declare( strict_types=1 );
/**
 * Block: Right Now
 *
 * ACF flexible content layout `right_now_block`. Built from "Right now" in
 * the signed-off homepage design
 * (v2 Final Homepage Design/src/components/site/RightNow.tsx): a list of
 * recent updates (blog posts, guides, news) beside a column of current
 * offers, over magenta shards in the testimonial band's palette.
 *
 * The updates are typed in rather than pulled from WordPress posts: the site
 * has no blog yet, and the design mixes guides and news that may live
 * elsewhere. An update or offer with no link is still shown, without the
 * arrow or hover.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

$dt_items = array();

foreach ( (array) get_sub_field( 'items' ) as $dt_row ) {
	$dt_title = trim( (string) ( $dt_row['title'] ?? '' ) );

	if ( '' !== $dt_title ) {
		$dt_items[] = array(
			'label' => trim( (string) ( $dt_row['label'] ?? '' ) ),
			'title' => $dt_title,
			'date'  => trim( (string) ( $dt_row['date'] ?? '' ) ),
			'url'   => (string) ( $dt_row['link'] ?? '' ),
		);
	}
}

$dt_offers = array();

foreach ( (array) get_sub_field( 'offers' ) as $dt_row ) {
	$dt_title = trim( (string) ( $dt_row['title'] ?? '' ) );

	if ( '' !== $dt_title ) {
		$dt_offers[] = array(
			'title'  => $dt_title,
			'detail' => trim( (string) ( $dt_row['detail'] ?? '' ) ),
			'url'    => (string) ( $dt_row['link'] ?? '' ),
		);
	}
}

if ( ! $dt_items && ! $dt_offers ) {
	return;
}

$dt_heading        = get_sub_field( 'heading' );
$dt_offers_heading = get_sub_field( 'offers_heading' );

$dt_classes = 'right-now-block';
if ( ! $dt_items || ! $dt_offers ) {
	$dt_classes .= ' right-now-block--single';
}

$dt_shape    = dorotape_background_shape_value( get_sub_field( 'background_shape' ) );
$dt_classes .= dorotape_background_shape_class( $dt_shape );
?>

<?php if ( get_sub_field( 'divider' ) ) : ?>
	<div class="aurora-rule" aria-hidden="true"></div>
<?php endif; ?>

<section class="<?php echo esc_attr( $dt_classes ); ?>" animate="fade-in-up">
	<?php dorotape_background_shape( $dt_shape ); ?>
	<div class="right-now-block__art" aria-hidden="true">
		<?php for ( $dt_i = 1; $dt_i <= 5; $dt_i++ ) : ?>
			<span class="right-now-block__shard right-now-block__shard--<?php echo esc_attr( (string) $dt_i ); ?>"></span>
		<?php endfor; ?>
	</div>

	<div class="container right-now-block__grid">

		<?php if ( $dt_items ) : ?>
			<div>
				<?php if ( $dt_heading ) : ?>
					<h2 class="right-now-block__heading"><?php echo esc_html( $dt_heading ); ?></h2>
				<?php endif; ?>

				<ul class="right-now-block__list">
					<?php foreach ( $dt_items as $dt_item ) : ?>
						<?php $dt_tag = $dt_item['url'] ? 'a' : 'div'; ?>
						<li>
							<<?php echo esc_html( $dt_tag ); ?> class="right-now-block__item"<?php if ( $dt_item['url'] ) : ?> href="<?php echo esc_url( $dt_item['url'] ); ?>"<?php endif; ?>>
								<?php if ( $dt_item['label'] ) : ?>
									<span class="right-now-block__label"><?php echo esc_html( $dt_item['label'] ); ?></span>
								<?php endif; ?>

								<span class="right-now-block__title"><?php echo esc_html( $dt_item['title'] ); ?></span>

								<?php if ( $dt_item['date'] ) : ?>
									<span class="right-now-block__date"><?php echo esc_html( $dt_item['date'] ); ?></span>
								<?php endif; ?>

								<?php if ( $dt_item['url'] ) : ?>
									<svg class="right-now-block__arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
								<?php endif; ?>
							</<?php echo esc_html( $dt_tag ); ?>>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<?php if ( $dt_offers ) : ?>
			<div>
				<?php if ( $dt_offers_heading ) : ?>
					<h3 class="right-now-block__offers-heading"><?php echo esc_html( $dt_offers_heading ); ?></h3>
				<?php endif; ?>

				<ul class="right-now-block__offers">
					<?php foreach ( $dt_offers as $dt_offer ) : ?>
						<?php $dt_tag = $dt_offer['url'] ? 'a' : 'div'; ?>
						<li>
							<<?php echo esc_html( $dt_tag ); ?> class="right-now-block__offer"<?php if ( $dt_offer['url'] ) : ?> href="<?php echo esc_url( $dt_offer['url'] ); ?>"<?php endif; ?>>
								<svg class="right-now-block__tag" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"/><circle cx="7.5" cy="7.5" r=".5" fill="currentColor"/></svg>
								<span>
									<span class="right-now-block__offer-title"><?php echo esc_html( $dt_offer['title'] ); ?></span>
									<?php if ( $dt_offer['detail'] ) : ?>
										<span class="right-now-block__offer-detail"><?php echo esc_html( $dt_offer['detail'] ); ?></span>
									<?php endif; ?>
								</span>
							</<?php echo esc_html( $dt_tag ); ?>>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

	</div>
</section>
