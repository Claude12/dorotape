<?php
declare( strict_types=1 );
/**
 * Block: Team
 *
 * ACF flexible content layout `team_block`. "Our experts" from the About
 * page in the signed-off internal pages design: a header row over a grid of
 * people, each with a portrait, role, short bio and a way to reach them.
 *
 * The anchor field is here because the design's intro copy links down to
 * this grid ("Meet our experts"). Putting the id on the block lets the
 * editor own both ends of that jump instead of hard-coding #experts.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

$dt_members = (array) get_sub_field( 'members' );

if ( ! $dt_members ) {
	return;
}

$dt_eyebrow = trim( (string) get_sub_field( 'eyebrow' ) );
$dt_heading = trim( (string) get_sub_field( 'heading' ) );
$dt_intro   = trim( (string) get_sub_field( 'intro' ) );
$dt_divider = (bool) get_sub_field( 'divider' );
$dt_anchor  = sanitize_title( (string) get_sub_field( 'anchor' ) );

$dt_shape   = dorotape_background_shape_value( get_sub_field( 'background_shape' ) );
$dt_classes = 'team-block' . dorotape_background_shape_class( $dt_shape );

// Portraits are a fixed height in a column that never gets wider than a
// quarter of the container, so the automatic sizes attribute over-requests.
add_filter( 'wp_img_tag_add_auto_sizes', '__return_false' );
?>

<?php if ( $dt_divider ) : ?>
	<div class="aurora-rule" aria-hidden="true"></div>
<?php endif; ?>

<section class="<?php echo esc_attr( $dt_classes ); ?>"<?php echo $dt_anchor ? ' id="' . esc_attr( $dt_anchor ) . '"' : ''; ?> animate="fade-in-up">
	<?php dorotape_background_shape( $dt_shape ); ?>

	<div class="container">
		<?php if ( '' !== $dt_eyebrow || '' !== $dt_heading || '' !== $dt_intro ) : ?>
			<div class="team-block__header">
				<div class="team-block__header-lead">
					<?php if ( '' !== $dt_eyebrow ) : ?>
						<p class="team-block__eyebrow"><?php echo esc_html( $dt_eyebrow ); ?></p>
					<?php endif; ?>
					<?php if ( '' !== $dt_heading ) : ?>
						<h2 class="team-block__heading"><?php echo esc_html( $dt_heading ); ?></h2>
					<?php endif; ?>
				</div>
				<?php if ( '' !== $dt_intro ) : ?>
					<p class="team-block__intro"><?php echo esc_html( $dt_intro ); ?></p>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<ul class="team-block__grid">
			<?php foreach ( $dt_members as $dt_member ) : ?>
				<?php
				$dt_name = trim( (string) ( $dt_member['name'] ?? '' ) );

				if ( '' === $dt_name ) {
					continue;
				}

				$dt_role     = trim( (string) ( $dt_member['role'] ?? '' ) );
				$dt_bio      = trim( (string) ( $dt_member['bio'] ?? '' ) );
				$dt_email    = trim( (string) ( $dt_member['email'] ?? '' ) );
				$dt_linkedin = trim( (string) ( $dt_member['linkedin'] ?? '' ) );
				$dt_photo    = $dt_member['image'] ?? array();
				?>
				<li class="team-block__card">
					<?php if ( ! empty( $dt_photo['ID'] ) ) : ?>
						<div class="team-block__portrait">
							<?php
							echo wp_get_attachment_image(
								(int) $dt_photo['ID'],
								'medium_large',
								false,
								array(
									'class'    => 'team-block__photo',
									'loading'  => 'lazy',
									'decoding' => 'async',
									'sizes'    => '(min-width: 1280px) 320px, (min-width: 1024px) 30vw, (min-width: 576px) 45vw, 90vw',
								)
							);
							?>
						</div>
					<?php endif; ?>

					<div class="team-block__body">
						<h3 class="team-block__name"><?php echo esc_html( $dt_name ); ?></h3>

						<?php if ( '' !== $dt_role ) : ?>
							<p class="team-block__role"><?php echo esc_html( $dt_role ); ?></p>
						<?php endif; ?>

						<?php if ( '' !== $dt_bio ) : ?>
							<p class="team-block__bio"><?php echo esc_html( $dt_bio ); ?></p>
						<?php endif; ?>

						<div class="team-block__contact">
							<?php if ( '' !== $dt_linkedin ) : ?>
								<a class="team-block__linkedin" href="<?php echo esc_url( $dt_linkedin ); ?>" target="_blank" rel="noopener">
									<span class="screen-reader-text">
										<?php
										/* translators: %s: team member's name. */
										echo esc_html( sprintf( __( '%s on LinkedIn', 'dorotape' ), $dt_name ) );
										?>
									</span>
									<svg viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false"><rect width="24" height="24" rx="4" fill="currentColor"/><path d="M5.337 7.433a2.062 2.062 0 1 1 0-4.125 2.062 2.062 0 0 1 0 4.125zM7.119 20.452H3.555V9h3.564v11.452zM20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286z" fill="var(--color-surface)"/></svg>
								</a>
							<?php endif; ?>

							<?php if ( '' !== $dt_email ) : ?>
								<a class="link link--arrow team-block__email" href="<?php echo esc_url( 'mailto:' . $dt_email ); ?>">
									<?php
									/* translators: %s: team member's first name. */
									echo esc_html( sprintf( __( 'Email %s', 'dorotape' ), $dt_name ) );
									?>
									<?php echo dorotape_arrow_icon( 'link__icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed icon markup. ?>
								</a>
							<?php endif; ?>
						</div>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>

<?php remove_filter( 'wp_img_tag_add_auto_sizes', '__return_false' ); ?>
