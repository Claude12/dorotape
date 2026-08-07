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
		// The live running total also runs on products with NO discount table,
		// driven by the hidden #dt_live_price data source (metre/roll products).
		const livePriceEl = document.getElementById( 'dt_live_price' );
		if ( ! table && ! livePriceEl ) return;

		const qtyInput = document.querySelector( 'form.cart .quantity input[type="number"], form.cart input.qty' );
		function getQty() { return qtyInput ? ( parseInt( qtyInput.value, 10 ) || 1 ) : 1; }

		// Collect tiers from DOM rows (min_qty + unit price via data attributes).
		var currentTiers = table ? tiersFromTable( table ) : [];

		function refresh() {
			var qty    = getQty();
			var active = table ? highlightActiveTier( currentTiers, qty ) : { min: 0, price: 0 };
			updateDisplayedPrice( active, table, qty );
		}
		refresh();

		if ( qtyInput ) {
			qtyInput.addEventListener( 'input', refresh );
		}

		// Variable products only: recompute when a variation is selected.
		if ( typeof jQuery === 'undefined' ) return;

		const variationsForm = document.querySelector( 'form.variations_form' );
		if ( ! variationsForm ) return;

		// WooCommerce's onFoundVariation sets input.variation_id and calls .trigger('change')
		// on it directly — this is more reliable than catching the found_variation custom event
		// which can be silently swallowed by stopPropagation in other plugins.
		var varIdInput = variationsForm.querySelector( 'input.variation_id, input[name="variation_id"]' );
		if ( ! varIdInput ) return;

		// Discount table present? Then also swap the tbody prices per variation.
		// Absent (no-tier product)? We still recompute the total from the new
		// variation's base price in #dt_live_price.
		const tierContainer = document.getElementById( 'dt_variable_tier_table' );
		// JSON structure: { "varId": { "base_price": float, "tiers": [{min_qty, tier_price}] } }
		var variationTiers = {};
		if ( tierContainer ) {
			try {
				variationTiers = JSON.parse( tierContainer.dataset.variationTiers || '{}' );
			} catch ( e ) {}
		}

		jQuery( varIdInput ).on( 'change', function () {
			var newId = parseInt( this.value, 10 );
			if ( ! newId ) return; // 0 or empty = variation deselected, keep current table
			if ( table && tierContainer ) {
				var varData = variationTiers[ String( newId ) ];
				if ( varData && varData.tiers && varData.tiers.length ) {
					rebuildTbody( table, varData.tiers, varData.base_price || 0 );
					currentTiers = tiersFromTable( table );
				}
			}
			refresh();
		} );

		// Re-apply after variation events. The second, delayed call runs after
		// initVariationPriceSwap's 110 ms price rewrite so the badge and dim
		// class reflect the final on-screen price.
		jQuery( variationsForm ).on( 'show_variation reset_data', function () {
			setTimeout( refresh, 0 );
			setTimeout( refresh, 160 );
		} );
	}

	/* Pricing unit of the table's product: 'metre' | 'roll' | 'item'. */
	function tableUnit( table ) {
		var container = table.closest( '.dt-tier-pricing' );
		return ( container && container.dataset.unit ) || 'metre';
	}

	/* "5 rolls × £8.91/roll" — the qty × unit-price phrase, shared by the
	   single-product live total and the quick-add rows so the wording stays
	   identical across both. */
	function priceBreakdown( qty, unitPrice, unit ) {
		if ( 'metre' === unit ) {
			return qty + 'm × ' + formatPrice( unitPrice ) + '/m';
		}
		if ( 'roll' === unit ) {
			return qty + ( 1 === qty ? ' roll' : ' rolls' ) + ' × ' + formatPrice( unitPrice ) + '/roll';
		}
		return String( qty ) + ' × ' + formatPrice( unitPrice );
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
		// Mirrors dorotape_unit_strings() in inc/pricing.php — keep in step.
		var suffix    = 'metre' === unit ? '/m' : ( 'roll' === unit ? '/roll' : '' );
		var qtySuffix = 'metre' === unit ? 'm+' : '+';

		function fmt( price ) { return formatPrice( price ) + suffix; }

		var tbody = table.querySelector( 'tbody' );
		if ( ! tbody ) return;

		var html = '';

		html += '<tr class="dt-tier-pricing__row dt-tier-pricing__row--base" data-min="0" data-price="' + basePrice + '">'
			+ '<td>1' + qtySuffix + '</td>'
			+ '<td>' + ( basePrice > 0 ? fmt( basePrice ) : '&ndash;' ) + '</td>'
			+ '<td>&ndash;</td>'
			+ '</tr>';

		tiers.forEach( function ( tier ) {
			if ( tier.tier_price <= 0 ) return;
			var saving = basePrice > 0
				? Math.round( ( ( basePrice - tier.tier_price ) / basePrice ) * 100 )
				: 0;
			html += '<tr class="dt-tier-pricing__row" data-min="' + tier.min_qty + '" data-price="' + tier.tier_price + '">'
				+ '<td>' + escapeHtml( tier.min_qty + qtySuffix ) + '</td>'
				+ '<td>' + fmt( tier.tier_price ) + '</td>'
				+ '<td>' + ( saving > 0 ? saving + '% off' : '&ndash;' ) + '</td>'
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

	/* ── Live price: the on-screen price shows the running line total ────
	 *
	 * The visible price is rewritten in place to the total for the quantity
	 * entered (qty × the effective unit price) — e.g. 5m of F-Sign Platinum at
	 * £3.01/m reads £15.05, not £3.01. The effective unit price is the active
	 * quantity-break tier when one applies, otherwise the standard base price,
	 * so the total also reflects any discount the quantity has unlocked. At
	 * qty 1 the single unit price is correct, so the original markup is left
	 * untouched. On variable products initVariationPriceSwap() rewrites the
	 * same element after every variation change — it invalidates
	 * dtLivePrice.original when it does, so the next refresh re-captures the
	 * fresh variation price as the baseline (the two show_variation refresh
	 * timers straddle its 110 ms rewrite).
	 * ──────────────────────────────────────────────────────────────────── */
	var dtLivePrice = { el: null, original: null };

	function updateDisplayedPrice( active, table, qty ) {
		var variationsForm = document.querySelector( 'form.variations_form' );
		var target = variationsForm
			? ( document.querySelector( '.entry-summary > p.price' )
				|| document.querySelector( '.product .summary .price' ) )
			: document.querySelector( '.product .summary .price' );
		if ( ! target ) return;

		if ( dtLivePrice.el !== target ) {
			dtLivePrice.el       = target;
			dtLivePrice.original = null;
		}

		// Variable products: only act on a concrete selected variation — the
		// range price ("£2.55 – £256") has no single unit price to total.
		var varSelected = true, varId = 0;
		if ( variationsForm ) {
			var varIdInput = variationsForm.querySelector( 'input.variation_id, input[name="variation_id"]' );
			varId = varIdInput ? ( parseInt( varIdInput.value, 10 ) || 0 ) : 0;
			varSelected = !! varId;
		}

		// Canonical unit + base price. #dt_live_price is present on metre/roll
		// products (with or without discounts) and is the source of truth; the
		// discount table's data-min="0" row is the fallback for products that
		// only render the table. When neither supplies a price we bail below,
		// which is exactly how 'item' (sold each) products opt out.
		var live      = document.getElementById( 'dt_live_price' );
		var unit      = ( live && live.dataset.unit ) || ( table ? tableUnit( table ) : 'metre' );
		var basePrice = 0;
		if ( live ) {
			if ( variationsForm ) {
				var priceMap = {};
				try { priceMap = JSON.parse( live.dataset.variationPrices || '{}' ); } catch ( e ) {}
				basePrice = parseFloat( priceMap[ String( varId ) ] ) || 0;
			} else {
				basePrice = parseFloat( live.dataset.base ) || 0;
			}
		} else if ( table ) {
			var baseRow = table.querySelector( 'tr[data-min="0"]' );
			basePrice = baseRow ? ( parseFloat( baseRow.dataset.price ) || 0 ) : 0;
		}

		// Effective unit price: the active tier price when a discount break
		// applies, otherwise the standard base price.
		var unitPrice = active.price > 0 ? active.price : basePrice;

		// qty 1 (or no valid unit price / unselected variation): the single
		// price WooCommerce already shows is correct — restore and bail.
		if ( ! varSelected || unitPrice <= 0 || qty <= 1 ) {
			if ( dtLivePrice.original !== null ) {
				target.innerHTML     = dtLivePrice.original;
				dtLivePrice.original = null;
			}
			return;
		}

		if ( dtLivePrice.original === null ) {
			dtLivePrice.original = target.innerHTML;
		}

		var total = qty * unitPrice;

		var breakdown = priceBreakdown( qty, unitPrice, unit );
		if ( active.min > 0 ) {
			breakdown += ' · your ' + active.min + ( 'metre' === unit ? 'm' : '' ) + '+ price';
		}

		target.innerHTML = '<span class="woocommerce-Price-amount amount">'
			+ escapeHtml( formatPrice( total ) ) + '</span> '
			+ '<span class="dt-live-price__note">' + escapeHtml( breakdown ) + '</span>';
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
				// Swap price content mid-fade. This is now the baseline price —
				// invalidate the live-tier capture so its next refresh re-reads it.
				if ( variation && variation.price_html ) {
					rangePrice.innerHTML = variation.price_html;
					dtLivePrice.original = null;
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
				rangePrice.innerHTML     = originalHTML;
				dtLivePrice.original     = null; // baseline is the range again
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
		if ( ! toggle ) return;

		// The field itself is now FiboSearch's, which renders .dss-search-input.
		// Looked up on open rather than at boot: the plugin builds its markup from
		// its own script, so the input may not exist yet when this runs. The
		// theme's own fallback field is still matched, for when the plugin is off.
		function field() {
			return wrap.querySelector( '.dgwt-wcas-search-input, .dt-header-search__input' );
		}

		function setOpen( open ) {
			wrap.classList.toggle( 'dt-header-search--open', open );
			toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
			if ( open ) {
				const input = field();
				if ( input ) input.focus();
			}
		}

		toggle.addEventListener( 'click', function () {
			setOpen( ! wrap.classList.contains( 'dt-header-search--open' ) );
		} );

		document.addEventListener( 'click', function ( e ) {
			// FiboSearch renders its suggestions in a wrapper appended to <body>,
			// so a click on a result is "outside" the header — closing on it would
			// tear the panel down mid-click.
			if ( e.target.closest && e.target.closest( '.dgwt-wcas-suggestions-wrapp, .dgwt-wcas-details-wrapp' ) ) {
				return;
			}
			if ( ! wrap.contains( e.target ) ) setOpen( false );
		} );

		wrap.addEventListener( 'keydown', function ( e ) {
			if ( 'Escape' === e.key ) {
				// First Escape belongs to FiboSearch, to dismiss its suggestions.
				// Only close the header panel once nothing is showing.
				const suggestions = document.querySelector( '.dgwt-wcas-suggestions-wrapp' );
				if ( suggestions && suggestions.offsetParent !== null ) return;
				setOpen( false );
				toggle.focus();
			}
		} );
	}

	/* ── Cut size rows: one table row-group per *cut pattern*, not per roll —
	 * a pattern is one or more cut sizes plus how many of the ordered rolls
	 * get it, so a qty-100 order doesn't need a 100-row table. Pattern
	 * quantities are validated live to add up to the order quantity, and each
	 * pattern's cuts are validated as a group against the roll's width. ──── */
	function initCutRows() {
		const box = document.getElementById( 'dt_cutsize' );
		if ( ! box ) return;

		const body       = box.querySelector( '.dt-cutsize__body' );
		const maxNote    = box.querySelector( '.dt-cutsize__max' );
		const allocNote  = box.querySelector( '.dt-cutsize__alloc' );
		const addGroupBtn = box.querySelector( '.dt-cutsize__addgroup' );
		const form       = box.closest( 'form.cart' );
		if ( ! body || ! form ) return;

		var widthMap = {};
		try { widthMap = JSON.parse( box.dataset.maxWidths || '{}' ); } catch ( e ) {}
		var currentMax = parseInt( box.dataset.maxWidth, 10 ) || 0;

		/* Most customers take the roll uncut, so the cut form starts collapsed
		   behind "Do you need your rolls cutting?" and only those who need it
		   open it. Collapsed is also the safe default for validation: with no
		   sizes entered the server treats the add as a normal single line. */
		const toggle = box.querySelector( '.dt-cutsize__toggle' );
		const panel  = box.querySelector( '.dt-cutsize__panel' );
		if ( toggle && panel ) {
			toggle.addEventListener( 'click', function () {
				const open = 'true' === toggle.getAttribute( 'aria-expanded' );
				toggle.setAttribute( 'aria-expanded', open ? 'false' : 'true' );
				panel.hidden = open;
				box.classList.toggle( 'dt-cutsize--open', ! open );
				const icon = toggle.querySelector( '.dt-cutsize__toggle-icon' );
				if ( icon ) icon.textContent = open ? '+' : '−';
				// Clearing on close keeps the visible state and what gets posted
				// in step: a collapsed panel must never submit stale cut sizes.
				if ( open ) {
					panel.querySelectorAll( '.dt-cutsize__size' ).forEach( function ( i ) { i.value = ''; } );
					validateAll();
				}
			} );
		}

		function toMm( size, unit ) {
			if ( 'cm' === unit ) return size * 10;
			if ( 'in' === unit ) return size * 25.4;
			return size;
		}

		function getQty() {
			const input = form.querySelector( '.quantity input[type="number"], input.qty' );
			return input ? Math.max( 1, parseInt( input.value, 10 ) || 1 ) : 1;
		}

		// Every future row/qty-cell is built from the initial PHP-rendered
		// row's own markup, so translated placeholders/aria-labels carry over
		// untouched. The qty cell is captured and detached separately from the
		// rest of the row: it belongs to the *group*, not to any one row, and
		// render() moves the same node (never recreates it) onto whichever
		// row is currently first in its group, so an in-progress qty edit is
		// never reset mid-keystroke by an unrelated add/remove elsewhere.
		const firstRow  = body.querySelector( '.dt-cutsize__row' );
		const qtyCellTpl = firstRow.querySelector( '.dt-cutsize__qty-cell' ).outerHTML;
		const rowCellsTpl = Array.prototype.slice.call( firstRow.children )
			.filter( function ( cell ) { return ! cell.classList.contains( 'dt-cutsize__qty-cell' ); } )
			.map( function ( cell ) { return cell.outerHTML; } )
			.join( '' );
		const firstQtyCell = firstRow.querySelector( '.dt-cutsize__qty-cell' );
		firstQtyCell.remove();

		function newRow() {
			var tr = document.createElement( 'tr' );
			tr.className = 'dt-cutsize__row';
			tr.innerHTML = rowCellsTpl;
			tr.querySelectorAll( '.dt-cutsize__size' ).forEach( function ( input ) { input.value = ''; } );
			tr.querySelectorAll( '.dt-cutsize__row-error' ).forEach( function ( span ) { span.textContent = ''; } );
			return tr;
		}

		function newQtyCell( value ) {
			var wrap = document.createElement( 'tbody' );
			wrap.innerHTML = '<tr>' + qtyCellTpl + '</tr>';
			var cell = wrap.querySelector( '.dt-cutsize__qty-cell' );
			cell.querySelector( '.dt-cutsize__qty' ).value = value;
			return cell;
		}

		// groups[g] = { qty: <td>, rows: [<tr>, ...] }, in on-screen order.
		var groups = [ { qty: firstQtyCell, rows: [ firstRow ] } ];

		function render() {
			groups.forEach( function ( group ) {
				group.rows.forEach( function ( tr ) { body.appendChild( tr ); } ); // re-attaches or reorders in place
				if ( group.rows[ 0 ].firstElementChild !== group.qty ) {
					group.rows[ 0 ].insertBefore( group.qty, group.rows[ 0 ].firstChild );
				}
				group.qty.setAttribute( 'rowspan', String( group.rows.length ) );
			} );
			groups.forEach( function ( group, g ) {
				var qtyInput = group.qty.querySelector( '.dt-cutsize__qty' );
				if ( qtyInput ) qtyInput.name = 'dt_cut_qty[' + g + ']';
				group.rows.forEach( function ( tr, c ) {
					var size = tr.querySelector( '.dt-cutsize__size' );
					var unit = tr.querySelector( '.dt-cutsize__unit' );
					if ( size ) size.name = 'dt_cut_rows[' + g + '][' + c + '][size]';
					if ( unit ) unit.name = 'dt_cut_rows[' + g + '][' + c + '][unit]';
				} );
			} );
			box.classList.toggle( 'dt-cutsize--multi', groups.length > 1 );
			validateAll();
		}

		function validateGroup( rows ) {
			var sum = 0, any = false;
			rows.forEach( function ( tr ) {
				var size = parseFloat( tr.querySelector( '.dt-cutsize__size' ).value ) || 0;
				// Cuts are entered in mm only; the unit select is kept optional
				// here so any legacy markup still resolves rather than throwing.
				var unitEl = tr.querySelector( '.dt-cutsize__unit' );
				if ( size > 0 ) { any = true; sum += toMm( size, unitEl ? unitEl.value : 'mm' ); }
			} );
			var invalid = any && currentMax > 0 && sum > currentMax;
			rows.forEach( function ( tr, idx ) {
				tr.classList.toggle( 'dt-cutsize__row--invalid', invalid );
				var error = tr.querySelector( '.dt-cutsize__row-error' );
				if ( ! error ) return;
				error.textContent = ( invalid && idx === rows.length - 1 )
					? 'Cuts add up to ' + ( Math.round( sum * 10 ) / 10 ) + 'mm, wider than the roll (' + currentMax + 'mm)'
					: '';
			} );
			return ! invalid;
		}

		function validateAll() {
			var ok = true;
			groups.forEach( function ( group ) { if ( ! validateGroup( group.rows ) ) ok = false; } );

			var allocated = 0;
			groups.forEach( function ( group ) {
				allocated += parseInt( group.qty.querySelector( '.dt-cutsize__qty' ).value, 10 ) || 0;
			} );
			var total   = getQty();
			var matches = allocated === total;
			if ( allocNote ) {
				allocNote.classList.toggle( 'dt-cutsize__alloc--bad', ! matches );
				allocNote.textContent = matches
					? ''
					: 'Cut pattern quantities add up to ' + allocated + ' of ' + total + ' ordered. Please adjust so they match.';
			}
			if ( ! matches ) ok = false;

			const submit = form.querySelector( 'button[type="submit"], .single_add_to_cart_button' );
			if ( submit ) submit.disabled = ! ok;
			return ok;
		}

		function syncSingleGroupQty() {
			if ( groups.length === 1 ) {
				groups[ 0 ].qty.querySelector( '.dt-cutsize__qty' ).value = getQty();
			}
		}

		body.addEventListener( 'input', validateAll );
		body.addEventListener( 'change', validateAll );

		body.addEventListener( 'click', function ( e ) {
			const addBtn = e.target.closest( '.dt-cutsize__addcut' );
			if ( addBtn ) {
				const tr    = addBtn.closest( 'tr' );
				const group = groups.find( function ( g ) { return -1 !== g.rows.indexOf( tr ); } );
				if ( group ) {
					group.rows.push( newRow() );
					render();
				}
				return;
			}
			const removeBtn = e.target.closest( '.dt-cutsize__remove' );
			if ( removeBtn ) {
				const row   = removeBtn.closest( 'tr' );
				const group = groups.find( function ( g ) { return -1 !== g.rows.indexOf( row ); } );
				if ( ! group ) return;
				if ( group.rows.length > 1 ) {
					group.rows = group.rows.filter( function ( tr ) { return tr !== row; } );
					row.remove();
					render();
				} else {
					// Only cut in this pattern: clear it, the pattern's row always stays.
					row.querySelector( '.dt-cutsize__size' ).value = '';
					validateAll();
				}
				return;
			}
			const removeGroupBtn = e.target.closest( '.dt-cutsize__removegroup' );
			if ( removeGroupBtn ) {
				if ( groups.length <= 1 ) return; // always keep at least one pattern
				const cell = removeGroupBtn.closest( '.dt-cutsize__qty-cell' );
				const idx  = groups.findIndex( function ( g ) { return g.qty === cell; } );
				if ( idx === -1 ) return;
				groups[ idx ].rows.forEach( function ( tr ) { tr.remove(); } );
				groups.splice( idx, 1 );
				render();
			}
		} );

		if ( addGroupBtn ) {
			addGroupBtn.addEventListener( 'click', function () {
				var allocated = 0;
				groups.forEach( function ( group ) {
					allocated += parseInt( group.qty.querySelector( '.dt-cutsize__qty' ).value, 10 ) || 0;
				} );
				var remaining = Math.max( 1, getQty() - allocated );
				groups.push( { qty: newQtyCell( remaining ), rows: [ newRow() ] } );
				render();
			} );
		}

		const qtyInput = form.querySelector( '.quantity input[type="number"], input.qty' );
		if ( qtyInput ) {
			qtyInput.addEventListener( 'input', function () { syncSingleGroupQty(); validateAll(); } );
			qtyInput.addEventListener( 'change', function () { syncSingleGroupQty(); validateAll(); } );
		}

		// Variable products: max width follows the selected variation, and
		// WooCommerce may itself rewrite the qty input's value on variation change.
		const variationsForm = document.querySelector( 'form.variations_form' );
		if ( variationsForm && Object.keys( widthMap ).length ) {
			const varIdInput = variationsForm.querySelector( 'input.variation_id, input[name="variation_id"]' );
			if ( varIdInput && typeof jQuery !== 'undefined' ) {
				jQuery( varIdInput ).on( 'change', function () {
					const vid = parseInt( this.value, 10 );
					currentMax = ( vid && widthMap[ vid ] ) ? parseInt( widthMap[ vid ], 10 ) : 0;
					if ( maxNote ) {
						if ( currentMax > 0 ) {
							maxNote.style.display = '';
							maxNote.innerHTML = 'Maximum cut width: <strong>' + currentMax + 'mm</strong>.';
						} else {
							maxNote.style.display = 'none';
						}
					}
					validateAll();
				} );
			}
			if ( typeof jQuery !== 'undefined' ) {
				jQuery( variationsForm ).on( 'show_variation reset_data', function () {
					setTimeout( function () { syncSingleGroupQty(); validateAll(); }, 0 );
				} );
			}
		}

		// Initial sync: the default single pattern starts allocated to the
		// qty field's actual starting value (it may not be 1 if the product
		// has a minimum purchase quantity).
		syncSingleGroupQty();
		render();
	}

	/* ── Quick-add table: all rows share one combined qty for tier lookup ──
	   (Hook & Loop: 5 Hook + 5 Loop unlocks the 10+ rate for both rows),
	   while each row keeps its own base price and tier price scale. ──── */
	function initQuickAdd() {
		document.querySelectorAll( '.dt-quickadd__table' ).forEach( function ( table ) {
			var rows = [];
			var unit = table.dataset.unit || 'item';

			// The product summary's headline price (e.g. £9.90). When every
			// quick-add row resolves to the same rate we mirror the live rate
			// here too, so the big price updates alongside the row prices.
			var summaryEl    = table.closest( '.summary, .entry-summary' );
			var summaryPrice = null;
			var summaryOrig  = null;

			// Mix-and-match prompt (rendered by inc/quickadd.php when the product
			// has a combined-quantity break) and its live "N more to go" counter.
			var form    = table.closest( '.dt-quickadd' );
			var mixEl   = form ? form.querySelector( '.dt-quickadd__mix' ) : null;
			var mixLive = mixEl ? mixEl.querySelector( '.dt-quickadd__mix-live' ) : null;
			var mixMin  = mixEl ? ( parseInt( mixEl.dataset.mixMin, 10 ) || 0 ) : 0;
			if ( ! mixMin ) mixLive = null;

			// Combined-quantity box beside the headline price (inc/quickadd.php).
			// It is a mirror of the grid, so it lives or dies with the grid.
			var totalBox     = summaryEl ? summaryEl.querySelector( '.dt-quickadd-total' ) : null;
			var totalInput   = totalBox ? totalBox.querySelector( '.dt-quickadd-total__input' ) : null;
			// The box is just the number now — the rule and the "N more to go"
			// line are stated once above the grid rather than in both places.
			if ( totalBox && ! mixMin ) {
				mixMin = parseInt( totalBox.dataset.mixMin, 10 ) || 0;
			}
			if ( summaryEl ) {
				var priceEls = summaryEl.querySelectorAll( '.price' );
				for ( var p = 0; p < priceEls.length; p++ ) {
					if ( ! priceEls[ p ].closest( '.dt-quickadd' ) ) { summaryPrice = priceEls[ p ]; break; }
				}
			}

			table.querySelectorAll( 'tbody tr' ).forEach( function ( row ) {
				var priceCell = row.querySelector( '.dt-quickadd__price-cell' );
				var qtyInput  = row.querySelector( '.dt-quickadd__qty-input' );
				var priceEl   = priceCell ? priceCell.querySelector( '.dt-quickadd__price' ) : null;
				if ( ! priceCell || ! qtyInput || ! priceEl ) return;

				var tiers = [];
				try { tiers = JSON.parse( priceCell.dataset.tiers || '[]' ); } catch ( e ) {}

				rows.push( {
					qtyInput: qtyInput,
					priceEl:  priceEl,
					lineEl:   priceCell.querySelector( '.dt-quickadd__line' ),
					base:     parseFloat( priceCell.dataset.base ) || 0,
					tiers:    tiers
						.map( function ( t ) { return { min_qty: parseInt( t.min_qty, 10 ), tier_price: parseFloat( t.tier_price ) }; } )
						.sort( function ( a, b ) { return b.min_qty - a.min_qty; } ),
				} );
			} );
			if ( ! rows.length ) return;

			function combinedQty() {
				var total = 0;
				rows.forEach( function ( r ) {
					if ( r.tiers.length ) total += parseInt( r.qtyInput.value, 10 ) || 0;
				} );
				return total;
			}

			function refreshAll() {
				var qty = combinedQty();

				// Track whether all rows land on one shared rate, and whether a
				// discount is currently active — used to drive the summary price.
				var commonActive = null, mixed = false, anyDiscount = false;
				// Running order value: the sum of the rows' own line totals, so a
				// mixed basket (7 black + 3 white) still totals correctly.
				var lineTotal = 0;

				rows.forEach( function ( r ) {
					var rowQty = parseInt( r.qtyInput.value, 10 ) || 0;

					// Effective unit price: the combined-qty tier break when one
					// applies, otherwise this row's own base price.
					var active     = r.base;
					var discounted = false;
					for ( var i = 0; i < r.tiers.length; i++ ) {
						if ( qty >= r.tiers[ i ].min_qty ) {
							active     = r.tiers[ i ].tier_price;
							discounted = active < r.base;
							break;
						}
					}

					if ( discounted ) anyDiscount = true;
					if ( rowQty >= 1 && active > 0 ) lineTotal += rowQty * active;

					// Rows with nothing in them don't count towards "one shared
					// rate" — an empty row's rate is not part of what's being bought,
					// and letting it vote turns an unmixed order into a mixed one.
					if ( rowQty >= 1 ) {
						if ( commonActive === null ) commonActive = active;
						else if ( Math.abs( commonActive - active ) > 0.001 ) mixed = true;
					}

					// Per-unit price cell reflects the active (possibly discounted) rate.
					if ( r.tiers.length ) {
						r.priceEl.innerHTML = '<span class="woocommerce-Price-amount amount">'
							+ escapeHtml( formatPrice( active ) ) + '</span>';
					}

					// Running line total for this row, at the active rate.
					if ( r.lineEl ) {
						if ( rowQty >= 1 && active > 0 ) {
							r.lineEl.textContent = priceBreakdown( rowQty, active, unit )
								+ ' = ' + formatPrice( rowQty * active );
							r.lineEl.classList.toggle( 'dt-quickadd__line--discount', discounted );
							r.lineEl.hidden = false;
						} else {
							r.lineEl.hidden = true;
						}
					}
				} );

				// How many more to reach the break, in plain words. Shown twice:
				// beside the headline price where people look for a quantity box,
				// and again in the grid where they are actually typing.
				var progress = '';
				var hit      = qty >= mixMin;
				if ( qty > 0 ) {
					progress = hit
						? qty + ' in total, discount applied.'
						: qty + ' so far, ' + ( mixMin - qty ) + ' more to unlock the discount.';
				}

				if ( mixLive ) {
					mixLive.textContent = progress;
					mixLive.classList.toggle( 'dt-quickadd__mix-live--hit', !! progress && hit );
				}

				// Combined-quantity box next to the price: a running mirror of the
				// grid, so the page keeps the quantity box every other product has.
				if ( totalInput ) {
					totalInput.value = qty;
					totalInput.classList.toggle( 'dt-quickadd-total__input--hit', qty > 0 && hit );
				}

				// Headline price: the running total for what's in the grid, in the
				// same shape updateDisplayedPrice() gives every other product —
				// big total, then "N rolls × £X/roll" underneath. Michael: the
				// price under the title has to move with the quantity here too.
				//
				// The rate alone used to go here, so the headline sat at £9.90
				// until the 10th roll and then jumped to £8.91 — it never moved
				// with the quantity, which is exactly what he was reporting.
				//
				// Below 2 rolls there is no total to add up (1 × the rate IS the
				// rate), so WooCommerce's own markup is restored — again matching
				// the other products, which leave qty 1 alone.
				if ( summaryPrice ) {
					if ( qty > 1 && lineTotal > 0 ) {
						if ( summaryOrig === null ) summaryOrig = summaryPrice.innerHTML;

						// A mixed grid has no single rate to quote, so the breakdown
						// falls back to the quantity alone. Both Hook & Loop colours
						// share a rate, so in practice this reads "10 rolls × £8.91/roll".
						var note = mixed
							? qty + ( 1 === qty ? ' roll' : ' rolls' ) + ' in total'
							: priceBreakdown( qty, commonActive, unit );
						if ( anyDiscount && ! mixed && mixMin > 0 ) {
							note += ' · your ' + mixMin + '+ price';
						}

						summaryPrice.innerHTML = '<span class="woocommerce-Price-amount amount">'
							+ escapeHtml( formatPrice( lineTotal ) ) + '</span> '
							+ '<span class="dt-live-price__note">' + escapeHtml( note ) + '</span>';
					} else if ( summaryOrig !== null ) {
						summaryPrice.innerHTML = summaryOrig;
						summaryOrig = null;
					}
				}
			}

			rows.forEach( function ( r ) { r.qtyInput.addEventListener( 'input', refreshAll ); } );
			refreshAll();
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

	/* ── Quantity step: keep typed values on the step ────────────────────
	 *
	 * The step attribute only governs the +/- buttons — the box itself will
	 * happily accept a typed 7 on a product sold in 5s, which the warehouse
	 * can't fulfil. Snap to the nearest valid multiple when the customer
	 * leaves the field (not while typing, or "15" would be rewritten the
	 * moment the "1" lands). PHP re-checks this on add-to-cart regardless.
	 */
	function initQtyStep() {
		function stepOf( input ) {
			return parseInt( input.getAttribute( 'step' ), 10 ) || 1;
		}
		function minOf( input, step ) {
			return parseInt( input.getAttribute( 'min' ), 10 ) || step;
		}
		function maxOf( input ) {
			var max = parseInt( input.getAttribute( 'max' ), 10 );
			return ( ! isNaN( max ) && max > 0 ) ? max : 0;
		}

		function commit( input, value ) {
			if ( String( value ) === input.value ) return;
			input.value = value;
			input.dispatchEvent( new Event( 'input',  { bubbles: true } ) );
			input.dispatchEvent( new Event( 'change', { bubbles: true } ) );
		}

		// Snap to the nearest valid multiple. Still needed even with the box
		// locked: WooCommerce rewrites min (and therefore the valid sequence)
		// when a variation is chosen, which can strand the current value.
		function snap( input ) {
			var step = stepOf( input );
			if ( step <= 1 ) return;
			var min = minOf( input, step );
			var val = parseInt( input.value, 10 );
			if ( isNaN( val ) || val < min ) { val = min; }

			// Ties round up: on a step of 5, 7.5 → 10 is the friendlier default.
			var snapped = Math.round( val / step ) * step;
			if ( snapped < min ) snapped = min;

			var max = maxOf( input );
			if ( max && snapped > max ) snapped = Math.floor( max / step ) * step;

			commit( input, snapped );
		}

		function nudge( input, direction ) {
			var step = stepOf( input );
			if ( step <= 1 ) return;
			var min  = minOf( input, step );
			var max  = maxOf( input );
			var val  = parseInt( input.value, 10 );
			if ( isNaN( val ) ) val = min;

			var next = val + ( direction * step );
			if ( next < min ) next = min;
			if ( max && next > max ) next = Math.floor( max / step ) * step;

			commit( input, next );
		}

		function isStepped( input ) {
			return !! input && stepOf( input ) > 1;
		}

		function button( direction, label, glyph ) {
			var el = document.createElement( 'button' );
			el.type = 'button';
			el.className = 'dt-qty-step dt-qty-step--' + direction;
			el.setAttribute( 'data-dt-qty', direction );
			el.setAttribute( 'aria-label', label );
			el.tabIndex = -1;
			el.innerHTML = glyph;
			return el;
		}

		// PHP renders the buttons on the single-product page, where it has the
		// product in hand. The cart page's quantity boxes go through the same
		// step filter but WooCommerce gives the before/after hooks no product
		// context there, so the markup arrives without them — and a locked box
		// with no buttons is worse than a typable one. Fill the gap here.
		function addButtons( input ) {
			var wrap = input.closest( '.quantity' );
			if ( ! wrap || wrap.querySelector( '[data-dt-qty]' ) ) return;
			wrap.insertBefore( button( 'down', 'Decrease quantity', '&minus;' ), input );
			if ( input.nextSibling ) {
				wrap.insertBefore( button( 'up', 'Increase quantity', '+' ), input.nextSibling );
			} else {
				wrap.appendChild( button( 'up', 'Increase quantity', '+' ) );
			}
		}

		// Lock every stepped box, so an invalid quantity cannot be typed at all.
		// Readonly is set here rather than in PHP on purpose: the buttons only
		// work with scripts running, so with scripts off the field stays typable
		// and the PHP add-to-cart check is what refuses a bad quantity.
		function lock( scope ) {
			( scope || document ).querySelectorAll(
				'.quantity input[type="number"], .quantity input.qty'
			).forEach( function ( input ) {
				if ( ! isStepped( input ) ) {
					input.removeAttribute( 'readonly' );
					return;
				}
				addButtons( input );
				input.setAttribute( 'readonly', 'readonly' );
				snap( input );
			} );
		}

		// Delegated, because WooCommerce's add-to-cart-variation.js rewrites the
		// quantity box's attributes (and can replace the surrounding markup)
		// every time a variation is picked. Listeners bound directly to the
		// input at load would quietly stop applying after the first change.
		document.addEventListener( 'click', function ( e ) {
			var button = e.target.closest ? e.target.closest( '[data-dt-qty]' ) : null;
			if ( ! button ) return;
			var wrap  = button.closest( '.quantity' );
			var input = wrap ? wrap.querySelector( 'input[type="number"], input.qty' ) : null;
			if ( ! input ) return;
			e.preventDefault();
			nudge( input, 'up' === button.dataset.dtQty ? 1 : -1 );
		} );

		document.addEventListener( 'blur', function ( e ) {
			if ( isStepped( e.target ) ) snap( e.target );
		}, true );

		document.addEventListener( 'submit', function ( e ) {
			if ( ! e.target.matches || ! e.target.matches( 'form.cart' ) ) return;
			var input = e.target.querySelector( '.quantity input[type="number"], input.qty' );
			if ( isStepped( input ) ) snap( input );
		}, true );

		lock();

		if ( typeof jQuery === 'undefined' ) return;

		// Re-lock after a variation swap: the step can differ per variation, and
		// WooCommerce restores its own attributes when it shows one.
		var variationsForm = document.querySelector( '.variations_form' );
		if ( variationsForm ) {
			jQuery( variationsForm ).on( 'show_variation reset_data', function () {
				window.setTimeout( function () { lock( variationsForm ); }, 0 );
			} );
		}

		// And after the cart redraws itself, which replaces the quantity boxes
		// wholesale along with the buttons and the readonly flag.
		jQuery( document.body ).on( 'updated_wc_div updated_cart_totals wc_fragments_refreshed', function () {
			lock();
		} );
	}

	/* ── Boot ───────────────────────────────────────────────────────────── */

	// Each feature starts independently. Run bare, a throw in any one of these
	// takes down every later one — which is how a fault in the cut-to-size panel
	// could silently disable the quantity lock on the same page.
	document.addEventListener( 'DOMContentLoaded', function () {
		[
			initDropdownNav,
			initTierTable,
			initVariationPriceSwap,
			initHeaderSearch,
			initFilterBar,
			initCutRows,
			initQuickAdd,
			initQtyStep
		].forEach( function ( init ) {
			try {
				init();
			} catch ( e ) {
				if ( window.console && window.console.error ) {
					window.console.error( 'dorotape: ' + init.name + ' failed', e );
				}
			}
		} );
	} );

}() );
