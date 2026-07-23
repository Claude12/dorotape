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
				var unit = tr.querySelector( '.dt-cutsize__unit' ).value;
				if ( size > 0 ) { any = true; sum += toMm( size, unit ); }
			} );
			var invalid = any && currentMax > 0 && sum > currentMax;
			rows.forEach( function ( tr, idx ) {
				tr.classList.toggle( 'dt-cutsize__row--invalid', invalid );
				var error = tr.querySelector( '.dt-cutsize__row-error' );
				if ( ! error ) return;
				error.textContent = ( invalid && idx === rows.length - 1 )
					? 'Cuts add up to ' + ( Math.round( sum * 10 ) / 10 ) + 'mm — wider than the roll (' + currentMax + 'mm)'
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
					: 'Cut pattern quantities add up to ' + allocated + ' of ' + total + ' ordered — please adjust so they match.';
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
			jQuery( variationsForm ).on( 'show_variation reset_data', function () {
				setTimeout( function () { syncSingleGroupQty(); validateAll(); }, 0 );
			} );
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
					if ( commonActive === null ) commonActive = active;
					else if ( Math.abs( commonActive - active ) > 0.001 ) mixed = true;

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

				// Mirror the shared discounted rate into the headline price, but
				// only when every row agrees on it (a single price can't stand in
				// for a mix). Restore WooCommerce's original markup otherwise.
				if ( summaryPrice ) {
					if ( anyDiscount && ! mixed && commonActive > 0 ) {
						if ( summaryOrig === null ) summaryOrig = summaryPrice.innerHTML;
						summaryPrice.innerHTML = '<span class="woocommerce-Price-amount amount">'
							+ escapeHtml( formatPrice( commonActive ) ) + '</span>';
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

	/* ── Boot ───────────────────────────────────────────────────────────── */
	document.addEventListener( 'DOMContentLoaded', function () {
		initDropdownNav();
		initTierTable();
		initVariationPriceSwap();
		initHeaderSearch();
		initFilterBar();
		initCutRows();
		initQuickAdd();
	} );

}() );
