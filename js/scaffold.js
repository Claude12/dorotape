/**
 * scaffold.js — TEMPORARY (Sprint 1)
 *
 * Covers:
 *  1. Scaffold banner insertion (visible reminder this is temp UI)
 *  2. Desktop dropdown navigation (hover + keyboard)
 *  3. Product option panel — width and roll selectors fetched via AJAX,
 *     populates hidden fields that the server-side pricing hook reads.
 *  4. Estimated price preview on the single product page.
 *
 * Remove / replace entirely in Sprint 2.
 */

( function () {
	'use strict';

	/* ── 1. Desktop dropdown navigation ────────────────────────────────── */
	function initDropdownNav() {
		const nav = document.getElementById( 'site-navigation' );
		if ( ! nav ) return;

		const parents = nav.querySelectorAll( '.menu-item-has-children' );

		parents.forEach( function ( item ) {
			let closeTimer;

			item.addEventListener( 'mouseenter', function () {
				clearTimeout( closeTimer );
				item.classList.add( 'dt-open' );
			} );

			item.addEventListener( 'mouseleave', function () {
				closeTimer = setTimeout( function () {
					item.classList.remove( 'dt-open' );
				}, 120 );
			} );

			// Keyboard: open on Enter/Space when focused on the parent link
			const link = item.querySelector( ':scope > a' );
			if ( link ) {
				link.addEventListener( 'keydown', function ( e ) {
					if ( e.key === 'Enter' || e.key === ' ' ) {
						const isOpen = item.classList.contains( 'dt-open' );
						// If has a real href and not currently open, navigate on Enter
						if ( e.key === 'Enter' && ! isOpen && link.getAttribute( 'href' ) && link.getAttribute( 'href' ) !== '#' ) {
							return; // let the browser follow the link
						}
						e.preventDefault();
						item.classList.toggle( 'dt-open' );
					}
					if ( e.key === 'Escape' ) {
						item.classList.remove( 'dt-open' );
						link.focus();
					}
				} );
			}
		} );

		// Close all dropdowns when clicking outside the nav
		document.addEventListener( 'click', function ( e ) {
			if ( ! nav.contains( e.target ) ) {
				parents.forEach( function ( item ) {
					item.classList.remove( 'dt-open' );
				} );
			}
		} );
	}

	/* ── 3 & 4. Product option panel ───────────────────────────────────── */
	function initProductOptions() {
		// Only run on single product pages
		if ( ! document.body.classList.contains( 'single-product' ) ) return;

		// dorotapeProduct is localised by inc/woocommerce.php on product pages
		if ( typeof dorotapeProduct === 'undefined' ) return;

		const cartForm = document.querySelector( 'form.cart' );
		if ( ! cartForm ) return;

		const productIdInput = cartForm.querySelector( '[name="product_id"], [name="add-to-cart"]' );
		const productId = productIdInput
			? productIdInput.value
			: ( document.querySelector( 'button.single_add_to_cart_button' ) || {} ).value;

		if ( ! productId ) return;

		// Inject the scaffold option panel before the quantity/add-to-cart row
		const panel = buildOptionsPanel();
		const quantityDiv = cartForm.querySelector( '.quantity' );
		if ( quantityDiv ) {
			cartForm.insertBefore( panel, quantityDiv );
		} else {
			cartForm.prepend( panel );
		}

		// Fetch width and roll options from the AJAX endpoint (inc/woocommerce.php)
		fetchProductOptions( productId, panel );
	}

	function buildOptionsPanel() {
		const panel = document.createElement( 'div' );
		panel.className = 'dt-options-panel';
		panel.innerHTML = '<h3>Product Options</h3>'
			+ '<p class="dt-options-loading">Loading options…</p>';
		return panel;
	}

	function fetchProductOptions( productId, panel ) {
		const body = new URLSearchParams( {
			action:     'dorotape_get_product_options',
			nonce:      dorotapeProduct.nonce,
			product_id: productId,
		} );

		fetch( dorotapeProduct.ajaxUrl, {
			method:  'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body:    body.toString(),
		} )
			.then( function ( r ) { return r.json(); } )
			.then( function ( data ) {
				if ( ! data.success ) {
					panel.innerHTML = '<h3>Product Options</h3>'
						+ '<p style="color:#9b1c1c;font-size:.83rem;">Could not load options.</p>';
					return;
				}
				renderOptionSelectors( panel, data.data, productId );
			} )
			.catch( function () {
				panel.querySelector( '.dt-options-loading' ).textContent = 'Options unavailable.';
			} );
	}

	function renderOptionSelectors( panel, data, productId ) {
		const widths = data.width_options || [];
		const rolls  = data.roll_options  || [];
		const tiers  = data.price_tiers   || [];

		let html = '<h3>Product Options</h3>';

		// Hidden fields picked up by inc/pricing.php add-to-cart hook
		html += '<input type="hidden" name="dorotape_width" id="dt_width_val" value="">';
		html += '<input type="hidden" name="dorotape_roll_length" id="dt_roll_val" value="">';

		if ( widths.length ) {
			html += '<div class="dt-option-group">'
				+ '<label for="dt_width_sel">Width</label>'
				+ '<select id="dt_width_sel">';
			widths.forEach( function ( w ) {
				const label = w.label || ( w.value + 'mm' );
				html += '<option value="' + w.value + '">' + escapeHtml( label ) + '</option>';
			} );
			html += '</select></div>';
		}

		if ( rolls.length ) {
			html += '<div class="dt-option-group">'
				+ '<label for="dt_roll_sel">Roll Length</label>'
				+ '<select id="dt_roll_sel">';
			rolls.forEach( function ( r ) {
				html += '<option value="' + r.length + '">' + escapeHtml( r.label ) + '</option>';
			} );
			html += '</select></div>';
		}

		if ( ! widths.length && ! rolls.length ) {
			html += '<p style="font-size:.83rem;color:var(--dt-mid)">No configurable options for this product.</p>';
		} else {
			html += '<div class="dt-price-preview" id="dt_price_preview"></div>';
		}

		panel.innerHTML = html;

		// Use the PHP-localised raw float — avoids locale decimal-separator issues
		// (e.g. WooCommerce displaying £2,30 instead of £2.30 breaks DOM parsing).
		const basePrice = ( dorotapeProduct.basePrice !== undefined )
			? parseFloat( dorotapeProduct.basePrice )
			: 0;

		// Main WooCommerce price element — updated live as options change.
		const mainPriceEl = document.querySelector( '.entry-summary .price .woocommerce-Price-amount' );

		const widthSel  = panel.querySelector( '#dt_width_sel' );
		const rollSel   = panel.querySelector( '#dt_roll_sel' );
		const widthHid  = panel.querySelector( '#dt_width_val' );
		const rollHid   = panel.querySelector( '#dt_roll_val' );
		const preview   = panel.querySelector( '#dt_price_preview' );

		// Quantity input lives outside the options panel in the WC form.
		const qtyInput = document.querySelector( 'form.cart .quantity input[type="number"], form.cart input.qty' );

		function getQty() {
			return qtyInput ? ( parseInt( qtyInput.value, 10 ) || 1 ) : 1;
		}

		function syncHiddenFields() {
			if ( widthSel ) widthHid.value = widthSel.value;
			if ( rollSel  ) rollHid.value  = rollSel.value;
			updatePricePreview( widthSel, rollSel, widths, rolls, tiers, basePrice, preview, mainPriceEl, getQty() );
			if ( tiers.length ) highlightActiveTier( tiers, getQty() );
		}

		if ( widthSel ) widthSel.addEventListener( 'change', syncHiddenFields );
		if ( rollSel  ) rollSel.addEventListener( 'change', syncHiddenFields );
		if ( qtyInput ) qtyInput.addEventListener( 'input', syncHiddenFields );

		// Initialise fields and preview
		syncHiddenFields();
	}

	// Highlight the active tier row in the PHP-rendered .dt-tier-pricing__table.
	// When qty is below all tiers the base row (data-min="0") is highlighted instead.
	function highlightActiveTier( tiers, qty ) {
		const table = document.querySelector( '.dt-tier-pricing__table' );
		if ( ! table ) return;

		// Find highest applicable tier; fall back to 0 (base row) if none match.
		const sorted = tiers.slice().sort( function ( a, b ) { return b.min_qty - a.min_qty; } );
		let activeMin = 0;
		for ( var i = 0; i < sorted.length; i++ ) {
			if ( qty >= sorted[ i ].min_qty ) {
				activeMin = sorted[ i ].min_qty;
				break;
			}
		}

		table.querySelectorAll( 'tr[data-min]' ).forEach( function ( row ) {
			row.classList.toggle( 'dt-tier-active', parseInt( row.dataset.min, 10 ) === activeMin );
		} );
	}

	/* Compute price client-side, mirroring inc/pricing.php logic, and push it
	   to both the main WooCommerce price element and the small options panel note.
	   The server always recalculates the authoritative price at checkout. */
	function updatePricePreview( widthSel, rollSel, widths, rolls, tiers, basePrice, preview, mainPriceEl, qty ) {
		if ( ! basePrice ) return;

		let price = basePrice;

		// Width: use stored price_per_metre; 0/blank falls back to basePrice.
		if ( widthSel ) {
			const wVal = parseInt( widthSel.value, 10 );
			const wDef = widths.find( function ( w ) { return w.value === wVal; } );
			if ( wDef && wDef.price_per_metre > 0 ) {
				price = wDef.price_per_metre;
			}
		}

		const selectedWidthPrice = price;

		if ( rollSel ) {
			const rVal = parseFloat( rollSel.value );
			const rDef = rolls.find( function ( r ) { return Math.abs( r.length - rVal ) < 0.001; } );
			if ( rDef && rDef.roll_price > 0 ) {
				// Fixed roll: scale stored base-width roll_price to the selected width.
				const widthRatio = basePrice > 0 ? selectedWidthPrice / basePrice : 1;
				price = rDef.roll_price * widthRatio;
			} else {
				// Per-metre row selected: apply quantity tier if available.
				price = applyTierPrice( tiers, qty || 1, selectedWidthPrice, basePrice );
			}
		} else {
			// No roll selector: always apply tier pricing.
			price = applyTierPrice( tiers, qty || 1, selectedWidthPrice, basePrice );
		}

		const formatted = price.toFixed( 2 );

		// Update the main product price — preserve the WooCommerce currency symbol span.
		if ( mainPriceEl ) {
			const bdi = mainPriceEl.querySelector( 'bdi' );
			if ( bdi ) {
				const sym    = bdi.querySelector( '.woocommerce-Price-currencySymbol' );
				const symHtml = sym
					? sym.outerHTML
					: '<span class="woocommerce-Price-currencySymbol">£</span>';
				bdi.innerHTML = symHtml + formatted;
			}
		}

		// Small note below the options panel.
		if ( preview ) {
			preview.textContent = 'Final price recalculated at checkout.';
		}
	}

	/* Mirror of dorotape_get_tier_price() in inc/pricing.php. */
	function applyTierPrice( tiers, qty, widthPrice, basePrice ) {
		if ( ! tiers || ! tiers.length ) return widthPrice;
		const sorted = tiers.slice().sort( function ( a, b ) { return b.min_qty - a.min_qty; } );
		for ( var i = 0; i < sorted.length; i++ ) {
			const tier = sorted[ i ];
			if ( qty >= tier.min_qty && tier.tier_price > 0 ) {
				const ratio = basePrice > 0 ? widthPrice / basePrice : 1;
				return tier.tier_price * ratio;
			}
		}
		return widthPrice;
	}

	function escapeHtml( str ) {
		return String( str )
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' )
			.replace( /"/g, '&quot;' );
	}

	/* ── Boot ───────────────────────────────────────────────────────────── */
	document.addEventListener( 'DOMContentLoaded', function () {
		initDropdownNav();
		initProductOptions();
	} );

}() );
