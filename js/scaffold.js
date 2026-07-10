/**
 * scaffold.js — Sprint 1 UI scaffold
 *
 * 1. Desktop dropdown navigation (hover + keyboard)
 * 2. Tier-table row highlighting driven by the qty input (simple + variable products)
 * 3. Variable-product tier table price swap when the variation dropdown changes
 *    (table is already rendered by PHP on page load — no AJAX, no timing issues)
 */

( function () {
	'use strict';

	/* ── 1. Desktop dropdown navigation ────────────────────────────────── */
	function initDropdownNav() {
		const navs = [
			document.getElementById( 'site-navigation' ),
			document.getElementById( 'secondary-navigation' ),
		].filter( Boolean );
		if ( ! navs.length ) return;

		navs.forEach( function ( nav ) { initSingleNav( nav ); } );

		document.addEventListener( 'click', function ( e ) {
			navs.forEach( function ( nav ) {
				if ( ! nav.contains( e.target ) ) {
					nav.querySelectorAll( '.menu-item-has-children' )
						.forEach( function ( item ) { item.classList.remove( 'dt-open' ); } );
				}
			} );
		} );
	}

	function initSingleNav( nav ) {
		const parents = nav.querySelectorAll( '.menu-item-has-children' );

		parents.forEach( function ( item ) {
			let closeTimer;

			item.addEventListener( 'mouseenter', function () {
				clearTimeout( closeTimer );
				item.classList.add( 'dt-open' );
			} );
			item.addEventListener( 'mouseleave', function () {
				closeTimer = setTimeout( function () { item.classList.remove( 'dt-open' ); }, 120 );
			} );

			const link = item.querySelector( ':scope > a' );
			if ( link ) {
				link.addEventListener( 'keydown', function ( e ) {
					if ( e.key === 'Enter' || e.key === ' ' ) {
						const isOpen = item.classList.contains( 'dt-open' );
						if ( e.key === 'Enter' && ! isOpen && link.getAttribute( 'href' ) && link.getAttribute( 'href' ) !== '#' ) {
							return;
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
	}

	/* ── 2 & 3. Tier table: highlight + variable price swap ─────────────
	 *
	 * Works for both simple and variable products.
	 * Simple products: table is fully rendered by PHP, we only handle highlighting.
	 * Variable products: table is rendered by PHP for the initial/URL variation;
	 *   when the user changes the dropdown, we swap the tbody prices in-place.
	 * ──────────────────────────────────────────────────────────────────── */
	function initTierTable() {
		const table = document.querySelector( '.dt-tier-pricing__table' );
		if ( ! table ) return;

		const qtyInput = document.querySelector( 'form.cart .quantity input[type="number"], form.cart input.qty' );
		function getQty() { return qtyInput ? ( parseInt( qtyInput.value, 10 ) || 1 ) : 1; }

		// Collect tiers from DOM rows (min_qty + unit price via data attributes).
		var currentTiers = tiersFromTable( table );

		function refresh() {
			var active = highlightActiveTier( currentTiers, getQty() );
			updateDisplayedPrice( active, table );
		}
		refresh();

		if ( qtyInput ) {
			qtyInput.addEventListener( 'input', refresh );
		}

		// Variable products only: swap prices when a variation is selected.
		if ( typeof jQuery === 'undefined' ) return;

		const variationsForm = document.querySelector( 'form.variations_form' );
		if ( ! variationsForm ) return;

		const tierContainer = document.getElementById( 'dt_variable_tier_table' );
		if ( ! tierContainer ) return;

		// JSON structure: { "varId": { "base_price": float, "tiers": [{min_qty, tier_price}] } }
		var variationTiers = {};
		try {
			variationTiers = JSON.parse( tierContainer.dataset.variationTiers || '{}' );
		} catch ( e ) {}

		// WooCommerce's onFoundVariation sets input.variation_id and calls .trigger('change')
		// on it directly — this is more reliable than catching the found_variation custom event
		// which can be silently swallowed by stopPropagation in other plugins.
		var varIdInput = variationsForm.querySelector( 'input.variation_id, input[name="variation_id"]' );
		if ( ! varIdInput ) return;

		jQuery( varIdInput ).on( 'change', function () {
			var newId = parseInt( this.value, 10 );
			if ( ! newId ) return; // 0 or empty = variation deselected, keep current table
			var varData = variationTiers[ String( newId ) ];
			if ( ! varData || ! varData.tiers || ! varData.tiers.length ) return;
			rebuildTbody( table, varData.tiers, varData.base_price || 0 );
			currentTiers = tiersFromTable( table );
			refresh();
		} );

		// WooCommerce renders .woocommerce-variation-price after this event —
		// re-apply so a pre-entered tier quantity is reflected immediately.
		jQuery( variationsForm ).on( 'show_variation', function () {
			setTimeout( refresh, 0 );
		} );
	}

	/* Pricing unit of the table's product: 'metre' | 'roll' | 'item'. */
	function tableUnit( table ) {
		var container = table.closest( '.dt-tier-pricing' );
		return ( container && container.dataset.unit ) || 'metre';
	}

	function formatPrice( price ) {
		var sym      = ( typeof dorotapeProduct !== 'undefined' && dorotapeProduct.currencySymbol )
			? dorotapeProduct.currencySymbol : '£';
		var decimals = ( typeof dorotapeProduct !== 'undefined' && dorotapeProduct.priceDecimals != null )
			? dorotapeProduct.priceDecimals : 2;
		return sym + price.toFixed( decimals );
	}

	/* Build the tier list from data attributes in a rendered table (incl. the
	 * base row at data-min="0", whose data-price is the standard unit price). */
	function tiersFromTable( table ) {
		var tiers = [];
		table.querySelectorAll( 'tr[data-min]' ).forEach( function ( row ) {
			tiers.push( {
				min_qty: parseInt( row.dataset.min, 10 ) || 0,
				price:   parseFloat( row.dataset.price ) || 0,
			} );
		} );
		return tiers;
	}

	/* Replace the tbody rows with updated prices, preserving the thead. */
	function rebuildTbody( table, tiers, basePrice ) {
		var unit      = tableUnit( table );
		var suffix    = 'metre' === unit ? '/m' : '';
		var qtySuffix = 'metre' === unit ? 'm+' : '+';

		function fmt( price ) { return formatPrice( price ) + suffix; }

		var tbody = table.querySelector( 'tbody' );
		if ( ! tbody ) return;

		var html = '';

		html += '<tr class="dt-tier-pricing__row dt-tier-pricing__row--base" data-min="0" data-price="' + basePrice + '">'
			+ '<td>1' + qtySuffix + '</td>'
			+ '<td>' + ( basePrice > 0 ? fmt( basePrice ) : '&mdash;' ) + '</td>'
			+ '<td>&mdash;</td>'
			+ '</tr>';

		tiers.forEach( function ( tier ) {
			if ( tier.tier_price <= 0 ) return;
			var saving = basePrice > 0
				? Math.round( ( ( basePrice - tier.tier_price ) / basePrice ) * 100 )
				: 0;
			html += '<tr class="dt-tier-pricing__row" data-min="' + tier.min_qty + '" data-price="' + tier.tier_price + '">'
				+ '<td>' + escapeHtml( tier.min_qty + qtySuffix ) + '</td>'
				+ '<td>' + fmt( tier.tier_price ) + '</td>'
				+ '<td>' + ( saving > 0 ? saving + '% off' : '&mdash;' ) + '</td>'
				+ '</tr>';
		} );

		tbody.innerHTML = html;
	}

	/* Highlight the active tier row; returns { min, price } for the active row
	 * (min 0 = standard price, no tier). */
	function highlightActiveTier( tiers, qty ) {
		const table = document.querySelector( '.dt-tier-pricing__table' );
		if ( ! table ) return { min: 0, price: 0 };

		const sorted = tiers
			.filter( function ( t ) { return t.min_qty > 1; } )
			.sort( function ( a, b ) { return b.min_qty - a.min_qty; } );

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

		var price = 0;
		tiers.forEach( function ( t ) {
			if ( t.min_qty === activeMin ) price = t.price;
		} );
		return { min: activeMin, price: price };
	}

	/* ── Live price: the on-screen price reflects the active tier ────────
	 *
	 * Simple products update the summary price; variable products update the
	 * per-variation price WooCommerce renders after selection. The original
	 * markup is restored whenever the quantity drops back to the base tier.
	 * ──────────────────────────────────────────────────────────────────── */
	var dtPrice = { target: null, original: '' };

	function updateDisplayedPrice( active, table ) {
		if ( dtPrice.target && ! document.contains( dtPrice.target ) ) {
			dtPrice.target = null; // variation price re-rendered — re-capture
		}

		var isVariable = !! document.querySelector( 'form.variations_form' );
		var target = isVariable
			? document.querySelector( '.woocommerce-variation-price .price' )
			: document.querySelector( '.product .summary .price' );
		if ( ! target ) return;

		if ( dtPrice.target !== target ) {
			dtPrice.target   = target;
			dtPrice.original = target.innerHTML;
		}

		if ( active.min === 0 || active.price <= 0 ) {
			target.innerHTML = dtPrice.original;
			return;
		}

		var unit      = tableUnit( table );
		var suffix    = 'metre' === unit ? '/m' : '';
		var qtySuffix = 'metre' === unit ? 'm' : '';
		target.innerHTML = '<span class="woocommerce-Price-amount amount">'
			+ escapeHtml( formatPrice( active.price ) + suffix ) + '</span> '
			+ '<span class="dt-price-tier-note">'
			+ escapeHtml( active.min + qtySuffix + '+ price' ) + '</span>';
	}

	function escapeHtml( str ) {
		return String( str )
			.replace( /&/g, '&amp;' ).replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' ).replace( /"/g, '&quot;' );
	}

	/* ── 4. Variable product: swap variation price into the range position ──
	 *
	 * WooCommerce renders the price range ("£11–£22") server-side as a direct
	 * child <p class="price"> of .entry-summary. When a variation is chosen it
	 * also populates .woocommerce-variation-price lower in the form — so two
	 * prices appear. Instead: write the variation's price_html straight into the
	 * range element so it updates in-place, then hide the duplicate below.
	 *
	 * Transition sequence on selection:
	 *   0 ms  — price fades out, hint slides up & fades out
	 *   110 ms — price content swaps, fades back in; hint hidden; tier table
	 *            revealed then fades + slides in
	 *
	 * Reversed on reset_data.
	 * ──────────────────────────────────────────────────────────────────── */
	function initVariationPriceSwap() {
		if ( typeof jQuery === 'undefined' ) return;

		const variationsForm = document.querySelector( 'form.variations_form' );
		if ( ! variationsForm ) return;

		const rangePrice = document.querySelector( '.entry-summary > p.price' );
		if ( ! rangePrice ) return;

		var originalHTML = rangePrice.innerHTML;
		var tierTable    = document.getElementById( 'dt_variable_tier_table' );
		var discountHint = document.getElementById( 'dt_discount_hint' );
		var swapTimer;

		function showVariation( variation ) {
			clearTimeout( swapTimer );

			// Does this specific variation have tier pricing?
			var hasTiers = false;
			if ( variation && tierTable ) {
				try {
					var allTiers = JSON.parse( tierTable.dataset.variationTiers || '{}' );
					var varTierData = allTiers[ String( variation.variation_id ) ];
					hasTiers = !! ( varTierData && varTierData.tiers && varTierData.tiers.length );
				} catch ( e ) {}
			}

			// Kick off fade-outs.
			rangePrice.classList.add( 'dt-price-fading' );
			if ( discountHint ) discountHint.classList.add( 'dt-hint-out' );

			swapTimer = setTimeout( function () {
				// Swap price content mid-fade.
				if ( variation && variation.price_html ) {
					rangePrice.innerHTML = variation.price_html;
				}
				rangePrice.classList.remove( 'dt-price-fading' );

				// Collapse hint fully now that it has faded.
				if ( discountHint ) {
					discountHint.style.display = 'none';
					discountHint.classList.remove( 'dt-hint-out' );
				}

				if ( tierTable ) {
					if ( hasTiers ) {
						// Reveal tier table and animate it in.
						tierTable.style.display = 'block';
						requestAnimationFrame( function () {
							requestAnimationFrame( function () {
								tierTable.classList.add( 'dt-table-in' );
							} );
						} );
					} else {
						// Flat-price variation — hide tier table immediately.
						tierTable.classList.remove( 'dt-table-in' );
						tierTable.style.display = 'none';
					}
				}
			}, 110 );
		}

		function resetVariation() {
			clearTimeout( swapTimer );

			// Fade tier table out, then hide.
			if ( tierTable ) {
				tierTable.classList.remove( 'dt-table-in' );
				swapTimer = setTimeout( function () {
					tierTable.style.display = 'none';
				}, 150 );
			}

			// Crossfade the price back to the range.
			rangePrice.classList.add( 'dt-price-fading' );
			setTimeout( function () {
				rangePrice.innerHTML = originalHTML;
				rangePrice.classList.remove( 'dt-price-fading' );
			}, 100 );

			// Slide hint back in.
			if ( discountHint ) {
				discountHint.style.display = '';
				requestAnimationFrame( function () {
					requestAnimationFrame( function () {
						discountHint.classList.remove( 'dt-hint-out' );
					} );
				} );
			}
		}

		jQuery( variationsForm )
			.on( 'found_variation', function ( _e, variation ) { showVariation( variation ); } )
			.on( 'reset_data',      function ()               { resetVariation(); } );
	}

	/* ── Header product search ──────────────────────────────────────────── */
	function initHeaderSearch() {
		const wrap = document.getElementById( 'dt-header-search' );
		if ( ! wrap ) return;

		const toggle = wrap.querySelector( '.dt-header-search__toggle' );
		const input  = wrap.querySelector( '.dt-header-search__input' );

		function setOpen( open ) {
			wrap.classList.toggle( 'dt-header-search--open', open );
			toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
			if ( open ) input.focus();
		}

		toggle.addEventListener( 'click', function () {
			setOpen( ! wrap.classList.contains( 'dt-header-search--open' ) );
		} );

		document.addEventListener( 'click', function ( e ) {
			if ( ! wrap.contains( e.target ) ) setOpen( false );
		} );

		wrap.addEventListener( 'keydown', function ( e ) {
			if ( 'Escape' === e.key ) {
				setOpen( false );
				toggle.focus();
			}
		} );
	}

	/* ── Category filter bar: auto-apply on change ──────────────────────── */
	function initFilterBar() {
		const bar = document.querySelector( '.dt-filter-bar' );
		if ( ! bar ) return;
		bar.classList.add( 'dt-filter-bar--auto' ); // hides the no-JS Apply button
		bar.querySelectorAll( '.dt-filter-bar__select' ).forEach( function ( sel ) {
			sel.addEventListener( 'change', function () { bar.submit(); } );
		} );
	}

	/* ── Boot ───────────────────────────────────────────────────────────── */
	document.addEventListener( 'DOMContentLoaded', function () {
		initDropdownNav();
		initTierTable();
		initVariationPriceSwap();
		initHeaderSearch();
		initFilterBar();
	} );

}() );
