<?php
declare( strict_types=1 );
/**
 * The product description: two columns and four tabs, the brand logo and the
 * reward line.
 *
 * The imported descriptions are one long column: a manufacturer logo, an
 * intro, then Structure, Suitability, Advantages, Ink Compatibility and
 * Technical Information, each a magenta bold span over a list. On a wide
 * screen that left the right half of the band empty and the page very long,
 * so the client asked for two columns, with the sections as four tabs:
 * Structure, Suitability (with the inks), Advantages and Downloads (Site
 * Edits, October 2026, Product A5).
 *
 * Nothing is rewritten in the database. The description is split at render
 * time on the headings it already has, so an editor can keep writing it the
 * old way and a description with fewer than two of the sections, about a
 * third of them, prints as it always did.
 *
 * Three more pieces of the same feedback come out of the same split:
 *
 * - A8: the manufacturer logo leaves the description and goes to the top of
 *   the summary, white, as the brands strip on the homepage draws them.
 * - A9: the datasheet, fire certificate and colour chart buttons, pictures of
 *   words on the old site's light grey, become cyan lozenges with real text.
 * - A10: the orange "Earn Reward Points" picture some descriptions carry is
 *   dropped, and the products that do earn points say so under the price.
 *
 * Every label is a field under Theme Settings > Product Pages.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

/**
 * The sections the description is split into, in tab order, and the
 * headings that open each one.
 *
 * Material Characteristics is the Cover Styl' range's word for Structure, and
 * the inks were always a separate heading that the client wants under
 * Suitability. "Compatability" is how ten descriptions spell it.
 *
 * @return array<string, string[]> Tab key => headings, lower case, no colon.
 */
function dorotape_product_description_tab_headings(): array {
	return array(
		'structure'   => array( 'structure', 'material characteristics' ),
		'suitability' => array( 'suitability', 'ink compatibility', 'ink compatability' ),
		'advantages'  => array( 'advantages' ),
		'downloads'   => array( 'technical information' ),
	);
}

/**
 * Split a product's description into its parts.
 *
 * @param int $product_id Product.
 * @return array{logo:array{src:string,alt:string}|null,intro:string,tabs:array<string,array<int,array{heading:string,html:string}>>,extras:string}
 *         The logo, the copy above the first section, each tab's chunks with
 *         the heading that opened them, and what followed the last list (the
 *         price note, a video, a blog link). Tabs is empty when the
 *         description has fewer than two sections.
 */
function dorotape_product_description_parts( int $product_id ): array {
	static $cache = array();

	if ( isset( $cache[ $product_id ] ) ) {
		return $cache[ $product_id ];
	}

	$html = dorotape_strip_document_wrapper( (string) get_post_field( 'post_content', $product_id ) );

	// The reward picture and the orange line beside it, from wherever they
	// sit in the list (A10). The summary says it now, and only for products
	// that really earn points.
	$html = (string) preg_replace( '#(?:<br\s*/?>\s*)?<img\b[^>]*Reward_Logo\.[a-z]+[^>]*>\s*(?:<span\b[^>]*>[^<]*</span>)?#i', '', $html );

	$parts = array(
		'logo'   => null,
		'intro'  => $html,
		'tabs'   => array(),
		'extras' => '',
	);

	$headings = dorotape_product_description_find_headings( $html );
	$top      = $headings ? substr( $html, 0, $headings[0]['start'] ) : $html;

	// The manufacturer logo: the first picture above the sections with "logo"
	// in its file name. The PVC free and reward badges are named that way too
	// but are not a brand.
	if ( preg_match_all( '#<img\b[^>]*\bsrc=["\']?([^"\'\s>]+)[^>]*>#i', $top, $images, PREG_SET_ORDER | PREG_OFFSET_CAPTURE ) ) {
		foreach ( $images as $image ) {
			$file = strtolower( wp_basename( (string) wp_parse_url( $image[1][0], PHP_URL_PATH ) ) );

			if ( false === strpos( $file, 'logo' ) || preg_match( '#pvc|reward#', $file ) ) {
				continue;
			}

			$tag = $image[0][0];

			preg_match( '#\balt=["\']([^"\']*)["\']#i', $tag, $alt );

			$parts['logo'] = array(
				'src' => $image[1][0],
				'alt' => isset( $alt[1] ) ? (string) $alt[1] : '',
			);

			// The paragraph it sat alone in, and the line breaks after it.
			$quoted = preg_quote( $tag, '#' );
			$top    = (string) preg_replace( '#(?:<p>\s*' . $quoted . '\s*</p>|' . $quoted . ')(?:\s*<br\s*/?>)*#i', '', $top, 1 );
			break;
		}
	}

	$groups = array();
	$map    = array();

	foreach ( dorotape_product_description_tab_headings() as $tab => $words ) {
		foreach ( $words as $word ) {
			$map[ $word ] = $tab;
		}
	}

	foreach ( $headings as $heading ) {
		$groups[ $map[ $heading['text'] ] ] = true;
	}

	if ( count( $groups ) < 2 ) {
		$parts['intro'] = $top . ( $headings ? substr( $html, $headings[0]['start'] ) : '' );
		$cache[ $product_id ] = $parts;

		return $parts;
	}

	$extras = '';
	$tabs   = array_fill_keys( array_keys( dorotape_product_description_tab_headings() ), array() );
	$last   = count( $headings ) - 1;

	foreach ( $headings as $i => $heading ) {
		$end  = $i < $last ? $headings[ $i + 1 ]['start'] : strlen( $html );
		$body = substr( $html, $heading['end'], $end - $heading['end'] );

		// After the last list come the notes that belong to the whole product
		// rather than to a section: the price unit, a video, a blog link.
		if ( $i === $last ) {
			$close = stripos( $body, '</ul>' );

			if ( false !== $close ) {
				$extras .= substr( $body, $close + 5 );
				$body    = substr( $body, 0, $close + 5 );
			}
		}

		$tabs[ $map[ $heading['text'] ] ][] = array(
			'heading' => $heading['text'],
			'html'    => $body,
		);
	}

	// The videos are as wide as their column, so they go beside the tabs
	// rather than inside one.
	$video = '#<div class="dt-product-video".*?</iframe></div>#is';

	foreach ( $tabs as $tab => $chunks ) {
		foreach ( $chunks as $n => $chunk ) {
			if ( preg_match_all( $video, $chunk['html'], $found ) ) {
				$extras                    .= implode( '', $found[0] );
				$tabs[ $tab ][ $n ]['html'] = (string) preg_replace( $video, '', $chunk['html'] );
			}
		}
	}

	$tabs = array_filter( $tabs );

	$parts['intro']  = $top;
	$parts['tabs']   = count( $tabs ) >= 2 ? $tabs : array();
	$parts['extras'] = $extras;

	if ( ! $parts['tabs'] ) {
		$parts['intro']  = $top . substr( $html, $headings[0]['start'] );
		$parts['extras'] = '';
	}

	$cache[ $product_id ] = $parts;

	return $parts;
}

/**
 * The section headings in a description, in order.
 *
 * A heading is a bold span holding nothing but a few words, as every
 * imported one is. Only the ones dorotape_product_description_tab_headings()
 * knows split the description: an unfamiliar one, such as "Typical
 * applications", stays inside the section above it.
 *
 * @param string $html Description.
 * @return array<int, array{text:string,start:int,end:int}> Heading text, lower
 *         case without the colon, and where its markup starts and ends.
 */
function dorotape_product_description_find_headings( string $html ): array {
	$known = array_merge( ...array_values( dorotape_product_description_tab_headings() ) );
	$found = array();

	if ( ! preg_match_all( '#<span\b[^>]*font-weight:\s*bold[^>]*>\s*([^<]{2,40}?)\s*</span>(?:\s*<br\s*/?>)?#i', $html, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE ) ) {
		return $found;
	}

	foreach ( $matches as $match ) {
		$text = strtolower( trim( rtrim( trim( $match[1][0] ), ':' ) ) );

		if ( in_array( $text, $known, true ) ) {
			$found[] = array(
				'text'  => $text,
				'start' => $match[0][1],
				'end'   => $match[0][1] + strlen( $match[0][0] ),
			);
		}
	}

	return $found;
}

/**
 * Run one piece of the description through what the_content does to it.
 *
 * Not the_content filter itself, which other plugins add to and which would
 * then repeat once per tab.
 *
 * @param string $html Raw piece.
 */
function dorotape_product_description_format( string $html ): string {
	$html = dorotape_strip_empty_paragraphs( do_shortcode( shortcode_unautop( wpautop( $html ) ) ) );

	return trim( wp_filter_content_tags( $html ) );
}

/**
 * The Downloads tab: each link in Technical Information as a lozenge.
 *
 * On the old site each was a picture of its own label. The picture's file
 * names which document it is, so the label comes from that through the
 * Download labels list in Product Pages. A link with words keeps them.
 *
 * @param string $html The section.
 * @return array{links:array<int,array{url:string,label:string,target:string}>,rest:string}
 *         The links, and whatever else the section held (a price note, a
 *         picture with no link), for the column beside the tabs.
 */
function dorotape_product_description_downloads( string $html ): array {
	$links = array();
	$names = dorotape_product_download_labels();

	$rest = (string) preg_replace_callback(
		'#<a\b([^>]*)\bhref=["\']?([^"\'\s>]+)["\']?([^>]*)>(.*?)</a>#is',
		static function ( array $m ) use ( &$links, $names ): string {
			$inner = $m[4];
			$label = trim( html_entity_decode( wp_strip_all_tags( $inner ) ) );

			if ( preg_match( '#<img\b[^>]*\bsrc=["\']?([^"\'\s>]+)#i', $inner, $src ) ) {
				$stem = strtolower( (string) pathinfo( (string) wp_parse_url( $src[1], PHP_URL_PATH ), PATHINFO_FILENAME ) );

				preg_match( '#\balt=["\']([^"\']*)["\']#i', $inner, $alt );
				$alt = trim( (string) ( $alt[1] ?? '' ) );

				if ( isset( $names[ $stem ] ) ) {
					$label = $names[ $stem ];
				} elseif ( '' !== $alt && 'pdf' !== strtolower( $alt ) ) {
					$label = ucfirst( $alt );
				} elseif ( '' === $label ) {
					$label = dorotape_product_page_field( 'product_download_fallback', __( 'Download', 'dorotape' ) );
				}
			}

			if ( '' === $label ) {
				return $m[0];
			}

			$links[] = array(
				'url'    => $m[2],
				'label'  => $label,
				'target' => preg_match( '#target=["\']?_blank#i', $m[1] . $m[3] ) ? '_blank' : '',
			);

			return '';
		},
		$html
	);

	$rest = (string) preg_replace( '#</?ul\b[^>]*>|<br\s*/?>#i', '', $rest );

	if ( '' === trim( str_replace( '&nbsp;', '', wp_strip_all_tags( $rest, true ) ) ) && false === stripos( $rest, '<img' ) ) {
		$rest = '';
	}

	return array(
		'links' => $links,
		'rest'  => $rest,
	);
}

/**
 * Download button picture => label, from Product Pages.
 *
 * The defaults are the fourteen pictures the imported descriptions use, so the
 * labels read properly before anyone has saved the list.
 *
 * @return array<string, string> Picture file name without extension, lower case => label.
 */
function dorotape_product_download_labels(): array {
	static $labels = null;

	if ( null !== $labels ) {
		return $labels;
	}

	$labels = array(
		'datasheet' => __( 'Technical datasheet', 'dorotape' ),
		'colour'    => __( 'Colour chart', 'dorotape' ),
		'fire'      => __( 'Fire certificate', 'dorotape' ),
		'profile'   => __( 'Printing profiles', 'dorotape' ),
		'blog'      => __( 'Read our blog', 'dorotape' ),
		'app'       => __( 'Application guide', 'dorotape' ),
		'hand'      => __( 'Handling guide', 'dorotape' ),
		'msds'      => __( 'Safety data sheet', 'dorotape' ),
		'coshh'     => __( 'Safety data sheet', 'dorotape' ),
		'template'  => __( 'Template', 'dorotape' ),
		'slip'      => __( 'Slip rating', 'dorotape' ),
		'war'       => __( 'Warranty', 'dorotape' ),
		'measure'   => __( 'How to measure', 'dorotape' ),
		'brox'      => __( 'Brochure', 'dorotape' ),
	);

	$rows = dorotape_product_section_field( 'product_download_labels' );

	foreach ( is_array( $rows ) ? $rows : array() as $row ) {
		$image = strtolower( trim( (string) ( $row['image'] ?? '' ) ) );
		$label = trim( (string) ( $row['label'] ?? '' ) );

		if ( '' !== $image && '' !== $label ) {
			$labels[ (string) pathinfo( $image, PATHINFO_FILENAME ) ] = $label;
		}
	}

	return $labels;
}

/**
 * Print the description tab.
 *
 * Replaces WooCommerce's woocommerce_product_description_tab, keeping its
 * heading, which the tab bar already says and _product-page.scss hides.
 */
function dorotape_product_description_tab(): void {
	global $product;

	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$parts   = dorotape_product_description_parts( $product->get_id() );
	$heading = apply_filters( 'woocommerce_product_description_heading', __( 'Description', 'woocommerce' ) );

	if ( $heading ) {
		echo '<h2>' . esc_html( $heading ) . '</h2>';
	}

	if ( ! $parts['tabs'] ) {
		echo dorotape_product_description_format( $parts['intro'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Post content, as the_content() prints it.
		return;
	}

	$labels = array(
		'structure'   => dorotape_product_page_field( 'product_tab_structure', __( 'Structure', 'dorotape' ) ),
		'suitability' => dorotape_product_page_field( 'product_tab_suitability', __( 'Suitability', 'dorotape' ) ),
		'advantages'  => dorotape_product_page_field( 'product_tab_advantages', __( 'Advantages', 'dorotape' ) ),
		'downloads'   => dorotape_product_page_field( 'product_tab_downloads', __( 'Downloads', 'dorotape' ) ),
	);
	$inks   = dorotape_product_page_field( 'product_tab_inks', __( 'Ink compatibility', 'dorotape' ) );
	$extras = $parts['extras'];
	$panels = array();

	foreach ( $parts['tabs'] as $tab => $chunks ) {
		$body = '';

		foreach ( $chunks as $n => $chunk ) {
			if ( 'downloads' === $tab ) {
				$downloads = dorotape_product_description_downloads( $chunk['html'] );
				$extras    = $downloads['rest'] . $extras;

				if ( $downloads['links'] ) {
					$body .= '<ul class="product-description__downloads">';

					foreach ( $downloads['links'] as $link ) {
						$body .= sprintf(
							'<li><a class="product-description__download" href="%1$s"%2$s>%3$s%4$s</a></li>',
							esc_url( $link['url'] ),
							'_blank' === $link['target'] ? ' target="_blank" rel="noopener"' : '',
							dorotape_icon( 'download', 'product-description__download-icon' ),
							esc_html( $link['label'] )
						);
					}

					$body .= '</ul>';
				}

				continue;
			}

			// A second heading inside one tab keeps its name over its list:
			// the inks under Suitability, mostly.
			if ( $n > 0 || 0 === strpos( $chunk['heading'], 'ink' ) ) {
				$title = 0 === strpos( $chunk['heading'], 'ink' ) ? $inks : ucfirst( $chunk['heading'] );
				$body .= '<h4 class="product-description__subheading">' . esc_html( $title ) . '</h4>';
			}

			$body .= dorotape_product_description_format( $chunk['html'] );
		}

		if ( '' !== trim( $body ) ) {
			$panels[ $tab ] = $body;
		}
	}

	$intro  = dorotape_product_description_format( $parts['intro'] );
	$extras = dorotape_product_description_format( $extras );
	$id     = 'product-description-' . $product->get_id();
	$class  = 'product-description product-description--split' . ( '' === $intro . $extras ? ' product-description--tabs-only' : '' );
	?>
	<div class="<?php echo esc_attr( $class ); ?>">

		<?php if ( '' !== $intro ) : ?>
			<div class="product-description__intro">
				<?php echo $intro; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Post content, as the_content() prints it. ?>
			</div>
		<?php endif; ?>

		<div class="product-description__tabs js-description-tabs">
			<?php // Hidden until the script runs, so without it every section shows, each under its own heading. ?>
			<div class="product-description__tablist" role="tablist" aria-label="<?php esc_attr_e( 'Product details', 'dorotape' ); ?>" hidden>
				<?php foreach ( array_keys( $panels ) as $n => $tab ) : ?>
					<button class="product-description__tab" type="button" role="tab" id="<?php echo esc_attr( $id . '-tab-' . $tab ); ?>" aria-controls="<?php echo esc_attr( $id . '-' . $tab ); ?>" aria-selected="<?php echo 0 === $n ? 'true' : 'false'; ?>"<?php echo 0 === $n ? '' : ' tabindex="-1"'; ?>><?php echo esc_html( $labels[ $tab ] ); ?></button>
				<?php endforeach; ?>
			</div>

			<?php foreach ( $panels as $tab => $body ) : ?>
				<div class="product-description__panel product-description__panel--<?php echo esc_attr( $tab ); ?>" id="<?php echo esc_attr( $id . '-' . $tab ); ?>">
					<h3 class="product-description__panel-title"><?php echo esc_html( $labels[ $tab ] ); ?></h3>
					<?php echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Post content, and links escaped above. ?>
				</div>
			<?php endforeach; ?>
		</div>

		<?php // After the tabs in the page, so a video does not push them a screen down on a phone. Beside them from desktop. ?>
		<?php if ( '' !== $extras ) : ?>
			<div class="product-description__extras">
				<?php echo $extras; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Post content, as the_content() prints it. ?>
			</div>
		<?php endif; ?>

	</div>
	<?php
}

add_filter(
	'woocommerce_product_tabs',
	static function ( array $tabs ): array {
		if ( isset( $tabs['description'] ) ) {
			$tabs['description']['callback'] = 'dorotape_product_description_tab';
		}

		return $tabs;
	},
	98
);

/**
 * The white logo filter, once per page.
 *
 * White from the logo's alpha, opacity from how dark it was: a light
 * background drops out and the wordmark stays. Shared by the brands strip and
 * the product page, and referenced by id, so one copy serves the page.
 *
 * @param string $class Class for the <svg>, which the caller's styles shrink to nothing.
 */
function dorotape_logo_white_filter( string $class ): void {
	if ( ! empty( $GLOBALS['dorotape_logo_filter_printed'] ) ) {
		return;
	}

	$GLOBALS['dorotape_logo_filter_printed'] = true;
	?>
	<svg class="<?php echo esc_attr( $class ); ?>" width="0" height="0" aria-hidden="true" focusable="false">
		<filter id="dorotape-logo-white" color-interpolation-filters="sRGB">
			<feColorMatrix type="matrix" values="0 0 0 0 1  0 0 0 0 1  0 0 0 0 1  -0.34 -1.144 -0.116 1.6 0" />
		</filter>
	</svg>
	<?php
}

/**
 * The manufacturer logo at the top of the summary (A8).
 *
 * The brand's own logo from Theme Settings > Brands when the description's
 * logo file names one of them, since those are the cleaner files the
 * homepage uses, and the description's own logo otherwise. Either way it is
 * drawn white on the dark hero, unless the brand has a white version.
 */
function dorotape_product_brand_logo(): void {
	global $product;

	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$logo = dorotape_product_description_parts( $product->get_id() )['logo'];

	if ( ! $logo ) {
		return;
	}

	$file  = preg_replace( '#[^a-z0-9]#', '', strtolower( wp_basename( (string) wp_parse_url( $logo['src'], PHP_URL_PATH ) ) ) );
	$brand = null;

	foreach ( (array) dorotape_setting( 'brands' ) as $row ) {
		foreach ( explode( '|', (string) ( $row['name'] ?? '' ) ) as $name ) {
			$name = preg_replace( '#[^a-z0-9]#', '', strtolower( $name ) );

			if ( strlen( $name ) >= 4 && false !== strpos( $file, $name ) && ! empty( $row['image']['ID'] ) ) {
				$brand = $row;
				break 2;
			}
		}
	}

	$white = $brand && ! empty( $brand['white_image']['ID'] );
	$alt   = $brand ? (string) $brand['name'] : $logo['alt'];
	$class = 'product-brand__image' . ( $brand ? ' product-brand__image--brand' : '' ) . ( $white ? ' product-brand__image--white' : '' );

	if ( $brand ) {
		$image = wp_get_attachment_image(
			(int) ( $white ? $brand['white_image']['ID'] : $brand['image']['ID'] ),
			'medium',
			false,
			array(
				'class' => $class,
				'alt'   => $alt,
				'sizes' => '200px',
			)
		);
	} else {
		$image = sprintf( '<img class="%1$s" src="%2$s" alt="%3$s" decoding="async">', esc_attr( $class ), esc_url( $logo['src'] ), esc_attr( $alt ) );
	}

	if ( ! $white ) {
		dorotape_logo_white_filter( 'product-brand__filter' );
	}

	echo '<p class="product-brand">' . $image . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image() output, or escaped above.
}

/**
 * The reward points line under the price (A10).
 *
 * From the product's own Reward points switch, which is what RewardsWP pays
 * on, rather than from the picture in the description, which 89 descriptions
 * carry and does not always agree with it.
 */
function dorotape_product_reward_line(): void {
	global $product;

	if ( ! $product instanceof WC_Product || ! function_exists( 'dorotape_product_earns_points' ) || ! dorotape_product_earns_points( $product ) ) {
		return;
	}

	$text = dorotape_product_page_field( 'product_reward_text', __( 'Earn reward points every time you buy this product', 'dorotape' ) );
	$link = dorotape_product_section_field( 'product_reward_link' );
	$url  = is_array( $link ) ? (string) ( $link['url'] ?? '' ) : '';

	echo '<p class="product-reward">';
	echo dorotape_icon( 'hand-coins', 'product-reward__icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Hard-coded SVG paths.
	echo '<span>' . esc_html( $text );

	if ( '' !== $url && '' !== (string) ( $link['title'] ?? '' ) ) {
		printf(
			' <a class="product-reward__link" href="%1$s"%2$s>%3$s</a>',
			esc_url( $url ),
			'_blank' === ( $link['target'] ?? '' ) ? ' target="_blank" rel="noopener"' : '',
			esc_html( (string) $link['title'] )
		);
	}

	echo '</span></p>';
}

add_action(
	'wp',
	static function (): void {
		if ( ! is_product() ) {
			return;
		}

		// Between the breadcrumb (2) and the title (5).
		add_action( 'woocommerce_single_product_summary', 'dorotape_product_brand_logo', 3 );
		// Straight under the price (10).
		add_action( 'woocommerce_single_product_summary', 'dorotape_product_reward_line', 11 );
	}
);
