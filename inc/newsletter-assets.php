<?php
/**
 * Load WPForms late on pages where its only form is the footer newsletter.
 *
 * The newsletter sits in the footer of every page, so WPForms loads its whole
 * front end everywhere: two stylesheets and about ten scripts. Since WordPress
 * 6.9 a stylesheet enqueued in the footer is hoisted into <head>, so those two
 * stylesheets block the first paint of every page (Lighthouse, 26 Nov 2026:
 * the largest render-blocking item on home, category and product pages).
 *
 * On a page where the newsletter is the only form, this:
 * - still prints the stylesheets at once, but as non-blocking (media="print"
 *   swapped to "all" on load), so the form is styled long before anyone
 *   scrolls to it;
 * - holds the scripts back in a <template> and runs them, in their original
 *   order, when the footer comes within a screen of the viewport or the
 *   visitor touches the form. A submit that beats them is held and replayed.
 *
 * Nothing changes on a page with any other form (contact, POA, bespoke
 * enquiry), on a request that is submitting a form, or in a WPForms preview:
 * WPForms loads there exactly as before. Anti-spam is unaffected: the token
 * and its time limit are stamped into the form markup by the server.
 *
 * Filter `dorotape_defer_newsletter_assets` to false to switch this off.
 *
 * @package dorotape
 */

defined( 'ABSPATH' ) || exit;

/**
 * The forms WPForms rendered on this request, keyed by form id.
 *
 * Captured from its own footer action, which passes them in.
 *
 * @param array|null $set Forms to store, or null to read.
 * @return array
 */
function dorotape_wpforms_rendered( $set = null ): array {
	static $forms = array();

	if ( is_array( $set ) ) {
		$forms = $set;
	}

	return $forms;
}
add_action( 'wpforms_wp_footer', 'dorotape_wpforms_rendered', 1 );

/**
 * How many forms WPForms has printed on this request, counting repeats.
 *
 * The id list above cannot tell the footer's newsletter from a second copy of
 * the same form in the page body (the Sign me up page has one), and that
 * copy is on screen, so it needs its scripts straight away.
 *
 * @return int
 */
function dorotape_wpforms_render_count(): int {
	static $count = 0;

	if ( doing_action( 'wpforms_frontend_output' ) ) {
		++$count;
	}

	return $count;
}
add_action( 'wpforms_frontend_output', 'dorotape_wpforms_render_count', 1 );

/**
 * Whether this request is one where the newsletter assets may be deferred.
 *
 * @return bool
 */
function dorotape_newsletter_assets_deferrable(): bool {
	if ( is_admin() || is_customize_preview() || ! function_exists( 'dorotape_footer_newsletter' ) ) {
		return false;
	}

	// phpcs:ignore WordPress.Security.NonceVerification -- read-only checks.
	if ( ! empty( $_POST['wpforms'] ) || isset( $_GET['wpforms_form_preview'] ) ) {
		return false;
	}

	if ( function_exists( 'wpforms_setting' ) && wpforms_setting( 'global-assets' ) ) {
		return false;
	}

	$newsletter = dorotape_footer_newsletter();
	$forms      = array_map( 'absint', array_keys( dorotape_wpforms_rendered() ) );

	if ( empty( $newsletter['form_id'] ) || array( (int) $newsletter['form_id'] ) !== $forms || 1 !== dorotape_wpforms_render_count() ) {
		return false;
	}

	return (bool) apply_filters( 'dorotape_defer_newsletter_assets', true );
}

/**
 * Every handle the given handles need, themselves included.
 *
 * @param WP_Dependencies $deps    Scripts or styles.
 * @param string[]        $handles Starting handles.
 * @return string[]
 */
function dorotape_dependency_closure( WP_Dependencies $deps, array $handles ): array {
	$seen  = array();
	$stack = $handles;

	while ( $stack ) {
		$handle = array_pop( $stack );
		if ( isset( $seen[ $handle ] ) || ! isset( $deps->registered[ $handle ] ) ) {
			continue;
		}
		$seen[ $handle ] = true;
		foreach ( $deps->registered[ $handle ]->deps as $dep ) {
			$stack[] = $dep;
		}
	}

	return array_keys( $seen );
}

/**
 * The queued WPForms handles, or none if deferring them could break anything.
 *
 * Bails if a handle has already been printed, if one needs something outside
 * WPForms that has not been printed yet (it would end up held back too), or if
 * anything else still in the queue needs one of them.
 *
 * @param WP_Dependencies $deps Scripts or styles.
 * @return string[]
 */
function dorotape_wpforms_handles( WP_Dependencies $deps ): array {
	$ours = array_values(
		array_filter(
			$deps->queue,
			static fn( $handle ) => str_starts_with( (string) $handle, 'wpforms' )
		)
	);

	if ( ! $ours ) {
		return array();
	}

	foreach ( dorotape_dependency_closure( $deps, $ours ) as $handle ) {
		$is_ours = str_starts_with( $handle, 'wpforms' );
		if ( in_array( $handle, $deps->done, true ) ? $is_ours : ! $is_ours ) {
			return array();
		}
	}

	foreach ( array_diff( $deps->queue, $ours ) as $other ) {
		if ( array_intersect( $ours, dorotape_dependency_closure( $deps, array( $other ) ) ) ) {
			return array();
		}
	}

	return $ours;
}

/**
 * Print the stylesheets non-blocking and park the scripts.
 *
 * Runs after WPForms has enqueued everything (wp_footer 15) and before
 * WordPress prints the footer scripts (wp_footer 20).
 */
function dorotape_defer_newsletter_assets(): void {
	if ( ! dorotape_newsletter_assets_deferrable() ) {
		return;
	}

	$scripts = dorotape_wpforms_handles( wp_scripts() );
	$styles  = dorotape_wpforms_handles( wp_styles() );

	if ( ! $scripts ) {
		return;
	}

	if ( $styles ) {
		ob_start();
		wp_styles()->do_items( $styles );
		$css = (string) ob_get_clean();

		// The fallback only needs the links: the inline styles printed
		// above apply with or without scripts, so copying them in as well
		// just sent the same CSS twice.
		$noscript = (string) preg_replace( '#<style\b[^>]*>.*?</style>\s*#s', '', $css );
		$tags     = new WP_HTML_Tag_Processor( $css );
		while ( $tags->next_tag( 'link' ) ) {
			if ( 'stylesheet' === strtolower( (string) $tags->get_attribute( 'rel' ) ) ) {
				$tags->set_attribute( 'media', 'print' );
				$tags->set_attribute( 'onload', "this.media='all'" );
			}
		}

		echo $tags->get_updated_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core's own style tags.
		echo '<noscript>' . $noscript . "</noscript>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- as above.
	}

	ob_start();
	wp_scripts()->do_items( $scripts );
	$js = (string) ob_get_clean();

	// WPForms' "scripts failed to load" notice for admins would fire before they run.
	add_filter( 'wpforms_frontend_missing_assets_error_js_disable', '__return_true' );

	echo '<template id="dorotape-wpforms-deferred">' . $js . "</template>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core's own script tags.
	?>
<script>
(function () {
	var tpl = document.getElementById('dorotape-wpforms-deferred');
	var box = document.querySelector('.site-footer__signup-form');
	if (!tpl || !box) { return; }

	var started = false, ready = false, held = null, io = null;
	var events = ['focusin', 'pointerdown', 'touchstart'];

	function replay() {
		var form = held; held = null;
		if (!form) { return; }
		var go = function () {
			var btn = form.querySelector('[type="submit"]');
			if (btn) { btn.click(); } else if (form.requestSubmit) { form.requestSubmit(); }
		};
		if (window.jQuery) { window.jQuery(function () { setTimeout(go, 0); }); } else { go(); }
	}

	function copy(old) {
		var el = document.createElement('script');
		for (var a = 0; a < old.attributes.length; a++) {
			var name = old.attributes[a].name;
			if (name !== 'async' && name !== 'defer' && name !== 'src') { el.setAttribute(name, old.attributes[a].value); }
		}
		return el;
	}

	// Fallback: one script after another, each waiting for the last to load.
	function chain(list, i) {
		if (i >= list.length) { ready = true; replay(); return; }
		var old = list[i], el = copy(old);
		if (old.src) {
			el.src = old.src;
			el.async = false;
			el.onload = el.onerror = function () { chain(list, i + 1); };
			document.body.appendChild(el);
		} else {
			el.text = old.textContent;
			document.body.appendChild(el);
			chain(list, i + 1);
		}
	}

	// Download everything first, then run it all in one go. Nothing else may
	// run in between: WPForms' analytics starts on jQuery ready, and if that
	// fired before its pro half had loaded, it crashed later (getFieldState).
	function run(list) {
		if (!window.fetch || !window.Promise) { chain(list, 0); return; }
		Promise.all(list.map(function (old) {
			return old.src ? fetch(old.src, { credentials: 'same-origin' }).then(function (r) {
				if (!r.ok) { throw new Error(r.status); }
				return r.text();
			}) : old.textContent;
		})).then(function (code) {
			list.forEach(function (old, i) {
				var el = copy(old);
				el.text = code[i] + (old.src ? '\n//# sourceURL=' + old.src : '');
				document.body.appendChild(el);
			});
			ready = true;
			replay();
		}, function () { chain(list, 0); });
	}

	function load() {
		if (started) { return; }
		started = true;
		if (io) { io.disconnect(); }
		events.forEach(function (e) { box.removeEventListener(e, load, true); });
		run(Array.prototype.slice.call(tpl.content.querySelectorAll('script')));
	}

	box.addEventListener('submit', function (e) {
		if (ready) { return; }
		e.preventDefault();
		e.stopImmediatePropagation();
		held = e.target;
		load();
	}, true);

	events.forEach(function (e) { box.addEventListener(e, load, { capture: true, passive: true }); });

	if ('IntersectionObserver' in window) {
		io = new IntersectionObserver(function (entries) {
			if (entries.some(function (entry) { return entry.isIntersecting; })) { load(); }
		}, { rootMargin: '100% 0px' });
		io.observe(box);
	} else if (document.readyState === 'complete') {
		load();
	} else {
		window.addEventListener('load', load);
	}
}());
</script>
	<?php
}
add_action( 'wp_footer', 'dorotape_defer_newsletter_assets', 16 );
