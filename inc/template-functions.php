<?php
/**
 * Functions which enhance the theme by hooking into WordPress
 *
 * @package dorotape
 */

function dorotape_body_classes( $classes ) {
	if ( ! is_singular() ) {
		$classes[] = 'hfeed';
	}
	return $classes;
}
add_filter( 'body_class', 'dorotape_body_classes' );

/**
 * BEM classes on wp_nav_menu() output.
 *
 * wp_nav_menu() prints bare <li>, <a> and nested <ul> elements, and the only
 * way to style those without targeting raw elements is to give them classes.
 * Any menu call can pass three extra arguments, and WordPress hands unknown
 * arguments straight through to these filters:
 *
 *   'item_class'    => 'site-header__nav-item',
 *   'link_class'    => 'site-header__nav-link',
 *   'submenu_class' => 'site-header__nav-submenu',
 *
 * Items and links below the top level also get a `--sub` modifier, so a
 * dropdown link can be styled apart from the row it hangs off.
 *
 * @param string $base  The BEM class to add.
 * @param int    $depth Menu depth, 0 for the top level.
 * @return string[]
 */
function dorotape_menu_bem_classes( string $base, int $depth ): array {
	$classes = array( $base );
	if ( $depth > 0 ) {
		$classes[] = $base . '--sub';
	}
	return $classes;
}

function dorotape_menu_item_class( $classes, $menu_item, $args, $depth = 0 ) {
	if ( ! empty( $args->item_class ) ) {
		$classes = array_merge( (array) $classes, dorotape_menu_bem_classes( (string) $args->item_class, (int) $depth ) );
	}
	return $classes;
}
add_filter( 'nav_menu_css_class', 'dorotape_menu_item_class', 10, 4 );

function dorotape_menu_link_class( $atts, $menu_item, $args, $depth = 0 ) {
	if ( ! empty( $args->link_class ) ) {
		$existing      = isset( $atts['class'] ) ? (string) $atts['class'] : '';
		$atts['class'] = trim( $existing . ' ' . implode( ' ', dorotape_menu_bem_classes( (string) $args->link_class, (int) $depth ) ) );
	}
	return $atts;
}
add_filter( 'nav_menu_link_attributes', 'dorotape_menu_link_class', 10, 4 );

function dorotape_menu_submenu_class( $classes, $args, $depth = 0 ) {
	if ( ! empty( $args->submenu_class ) ) {
		$classes[] = (string) $args->submenu_class;
	}
	return $classes;
}
add_filter( 'nav_menu_submenu_css_class', 'dorotape_menu_submenu_class', 10, 3 );

/**
 * A video's embed markup and poster, from oEmbed, cached.
 *
 * wp_oembed_get() asks the provider over HTTP on every call and caches
 * nothing outside post content, so the Video block was calling YouTube on
 * every page view. This keeps the answer for a week (an hour when the
 * provider fails, so a bad URL is not retried on every view either).
 *
 * The poster lets the block show a still and load the player only when it is
 * played: the YouTube player is about 1MB of script. For YouTube the full-size
 * still is used when the video has one, since oEmbed only offers 480px.
 *
 * @param string $url Video page URL, as pasted by the editor.
 * @return array{html:string,title:string,thumb:string} html is '' when the URL cannot be embedded.
 */
function dorotape_video_embed( string $url ): array {
	$key    = 'dt_video_' . md5( $url );
	$cached = get_transient( $key );

	if ( is_array( $cached ) ) {
		return $cached;
	}

	$oembed = _wp_oembed_get_object();
	$args   = array( 'width' => 1200 );
	$data   = $oembed->get_data( $url, $args );
	$html   = $data ? (string) $oembed->data2html( $data, $url ) : '';
	$html   = (string) apply_filters( 'oembed_result', $html, $url, $args ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core hook, as wp_oembed_get() applies it.
	$thumb  = $data && ! empty( $data->thumbnail_url ) ? (string) $data->thumbnail_url : '';

	if ( preg_match( '#^https://i\.ytimg\.com/vi/([\w-]+)/#', $thumb, $m ) ) {
		$full = 'https://i.ytimg.com/vi/' . $m[1] . '/maxresdefault.jpg';
		if ( 200 === wp_remote_retrieve_response_code( wp_remote_head( $full ) ) ) {
			$thumb = $full;
		}
	}

	$video = array(
		'html'  => $html,
		'title' => $data && ! empty( $data->title ) ? (string) $data->title : '',
		'thumb' => $thumb,
	);

	set_transient( $key, $video, '' !== $html ? WEEK_IN_SECONDS : HOUR_IN_SECONDS );

	return $video;
}

/**
 * The framed 16:9 player for a video URL, as the Video block draws it.
 *
 * Shared by the Video block and the Accordion block's panels. Until it is
 * played the frame shows the provider's still with a play button, which is a
 * link to the video, so it still works with scripts off; assets/js/lib/video.js
 * swaps in the player from the <template>.
 *
 * @param string $url Video page URL, as pasted by the editor.
 * @return string Frame markup, or '' when the URL cannot be embedded.
 */
function dorotape_video_frame( string $url ): string {
	$video = dorotape_video_embed( $url );
	$embed = $video['html'];

	if ( '' === $embed ) {
		return '';
	}

	// Provider markup does not go through wp_filter_content_tags(), so the
	// iframe arrives with no loading attribute. A video embed pulls in a lot of
	// third-party script, so it is deferred, as loading="lazy" in the design.
	if ( false === strpos( $embed, ' loading=' ) ) {
		$embed = str_replace( '<iframe ', '<iframe loading="lazy" ', $embed );
	}

	if ( '' === $video['thumb'] ) {
		return '<div class="video-block__frame">' . $embed . '</div>';
	}

	// Played from the still, so start playing once the player has loaded.
	$embed = (string) preg_replace_callback(
		'#( src=")([^"]+)#',
		static function ( array $m ): string {
			return $m[1] . esc_url( add_query_arg( 'autoplay', '1', html_entity_decode( $m[2] ) ) );
		},
		$embed,
		1
	);

	$label = '' !== $video['title']
		/* translators: %s: video title. */
		? sprintf( __( 'Play video: %s', 'dorotape' ), $video['title'] )
		: __( 'Play video', 'dorotape' );

	return sprintf(
		'<div class="video-block__frame"><a class="video-block__play" href="%1$s" data-dt-video><img src="%2$s" alt="" loading="lazy" decoding="async"><span class="video-block__play-icon">%3$s</span><span class="screen-reader-text">%4$s</span></a><template data-dt-video-embed>%5$s</template></div>',
		esc_url( $url ),
		esc_url( $video['thumb'] ),
		dorotape_ui_icon( 'play' ),
		esc_html( $label ),
		$embed
	);
}
