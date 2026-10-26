<?php
declare( strict_types=1 );
/**
 * Ink compatibility: the coloured drops the old site printed beside each ink.
 *
 * The digital print categories end their description with "Specially
 * formulated ... using these inks" and one line per ink, and each of those
 * lines used to start with a picture of a drop. The import kept the words and
 * lost the pictures, so a banner ended "Eco-solvent Latex UV" run together
 * (Site Edits, October 2026, Category Main A6). Product descriptions kept
 * their pictures, which is how a card can say which inks its material takes.
 *
 * Both are read from the content at render time, so nothing has to be
 * re-entered and a category or product edited later still gets them.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

/**
 * Every ink the catalogue names, in the order the old site listed them.
 *
 * The file is the old drop picture, which every product description that
 * mentions the ink still points at. The Product Page settings can rename an
 * ink or give it another picture (product_inks).
 *
 * @return array<string, array{label:string, file:string, names:string[], image:int}>
 */
function dorotape_inks(): array {
	static $inks = null;

	if ( null !== $inks ) {
		return $inks;
	}

	$inks = array(
		'ecosol'  => array(
			'label' => __( 'Eco-solvent', 'dorotape' ),
			'file'  => 'Ink-EcoSol.png',
			'names' => array( 'eco-solvent', 'eco solvent', 'ecosolvent', 'eco-sol' ),
		),
		'sol'     => array(
			'label' => __( 'Solvent', 'dorotape' ),
			'file'  => 'Ink-Sol.png',
			'names' => array( 'solvent' ),
		),
		'latex'   => array(
			'label' => __( 'Latex', 'dorotape' ),
			'file'  => 'Ink-Latex.png',
			'names' => array( 'latex' ),
		),
		'uv'      => array(
			'label' => __( 'UV', 'dorotape' ),
			'file'  => 'Ink-UV.png',
			'names' => array( 'uv', 'uv ink', 'uv curable' ),
		),
		'dye'     => array(
			'label' => __( 'Dye', 'dorotape' ),
			'file'  => 'Ink-Dye.png',
			'names' => array( 'dye' ),
		),
		'pigment' => array(
			'label' => __( 'Pigment', 'dorotape' ),
			'file'  => 'Ink-Pigment.png',
			'names' => array( 'pigment' ),
		),
	);

	foreach ( $inks as $key => $ink ) {
		$inks[ $key ]['image'] = 0;
	}

	$rows = function_exists( 'dorotape_product_section_field' ) ? dorotape_product_section_field( 'product_inks' ) : null;

	foreach ( is_array( $rows ) ? $rows : array() as $row ) {
		$key = (string) ( $row['ink'] ?? '' );

		if ( ! isset( $inks[ $key ] ) ) {
			continue;
		}

		$label = trim( (string) ( $row['label'] ?? '' ) );

		if ( '' !== $label ) {
			$inks[ $key ]['label'] = $label;
		}

		$inks[ $key ]['image'] = (int) ( $row['image'] ?? 0 );
	}

	return $inks;
}

/**
 * The drop's address: the picture chosen in settings, or the old site's.
 *
 * @param string $key Key from dorotape_inks().
 * @return string Empty when neither exists, so the label shows alone.
 */
function dorotape_ink_image_url( string $key ): string {
	$ink = dorotape_inks()[ $key ] ?? null;

	if ( ! $ink ) {
		return '';
	}

	if ( $ink['image'] > 0 ) {
		return (string) wp_get_attachment_image_url( $ink['image'], 'thumbnail' );
	}

	$uploads = wp_get_upload_dir();
	$path    = '/kryptronic-media/media/ecom/onpagegraphics/' . $ink['file'];

	return file_exists( $uploads['basedir'] . $path ) ? $uploads['baseurl'] . $path : '';
}

/**
 * The ink a line of text names, when that is all the line says.
 *
 * Whole lines only, so "compatible with Eco-Solvent, Solvent, Latex and UV
 * inks" in a sentence is left as the sentence it is.
 *
 * @param string $line One line of a description.
 * @return string Key from dorotape_inks(), or empty.
 */
function dorotape_ink_from_line( string $line ): string {
	$text = html_entity_decode( wp_strip_all_tags( $line ), ENT_QUOTES, 'UTF-8' );
	$text = strtolower( trim( str_replace( "\u{00A0}", ' ', $text ), " \t.,;:" ) );

	if ( '' === $text ) {
		return '';
	}

	foreach ( dorotape_inks() as $key => $ink ) {
		if ( in_array( $text, $ink['names'], true ) ) {
			return $key;
		}
	}

	return '';
}

/**
 * Split a category description into its text and the inks listed in it.
 *
 * @param string $description Term description, as stored.
 * @return array{text:string, inks:string[]} Text with the ink lines taken
 *                                           out, and the inks in order.
 */
function dorotape_ink_split_description( string $description ): array {
	$text = array();
	$inks = array();

	foreach ( preg_split( '/\r\n|\r|\n/', $description ) ?: array() as $line ) {
		$key = dorotape_ink_from_line( $line );

		if ( '' === $key ) {
			$text[] = $line;
		} elseif ( ! in_array( $key, $inks, true ) ) {
			$inks[] = $key;
		}
	}

	return array(
		'text' => implode( "\n", $text ),
		'inks' => $inks,
	);
}

/**
 * The inks a product's description shows drops for.
 *
 * @param WC_Product $product Product to read.
 * @return string[] Keys from dorotape_inks(), in that order.
 */
function dorotape_product_inks( WC_Product $product ): array {
	$content = $product->get_description();

	if ( false === stripos( $content, 'onpagegraphics/Ink-' ) ) {
		return array();
	}

	$found = array();

	foreach ( dorotape_inks() as $key => $ink ) {
		if ( false !== stripos( $content, 'onpagegraphics/' . $ink['file'] ) ) {
			$found[] = $key;
		}
	}

	return $found;
}

/**
 * Print a row of inks.
 *
 * @param string[] $keys   Keys from dorotape_inks().
 * @param string   $class  Block class for the list, e.g. "ink-list".
 * @param bool     $labels Whether the names show beside the drops. Without,
 *                         the names are still read out and shown on hover.
 */
function dorotape_ink_list( array $keys, string $class = 'ink-list', bool $labels = true ): void {
	$inks = dorotape_inks();
	$keys = array_values( array_filter( $keys, static fn ( $key ) => isset( $inks[ $key ] ) ) );

	if ( array() === $keys ) {
		return;
	}

	$title = dorotape_product_page_field( 'product_tab_inks', __( 'Ink compatibility', 'dorotape' ) );
	$list  = implode( ', ', array_map( static fn ( $key ) => $inks[ $key ]['label'], $keys ) );
	?>
	<ul class="<?php echo esc_attr( $class . ( $labels ? '' : ' ' . $class . '--compact' ) ); ?>" aria-label="<?php echo esc_attr( $title ); ?>"<?php echo $labels ? '' : ' title="' . esc_attr( $title . ': ' . $list ) . '"'; ?>>
		<?php foreach ( $keys as $key ) : ?>
			<?php $url = dorotape_ink_image_url( $key ); ?>
			<li class="<?php echo esc_attr( $class . '__item' ); ?>">
				<?php if ( '' !== $url ) : ?>
					<img class="<?php echo esc_attr( $class . '__drop' ); ?>" src="<?php echo esc_url( $url ); ?>" alt="" width="57" height="99" loading="lazy" decoding="async">
				<?php endif; ?>
				<span class="<?php echo esc_attr( $class . '__label' . ( $labels || '' === $url ? '' : ' screen-reader-text' ) ); ?>"><?php echo esc_html( $inks[ $key ]['label'] ); ?></span>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php
}
