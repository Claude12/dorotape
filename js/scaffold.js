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

		document.addEventListener( 'click', function ( e ) {
			if ( ! nav.contains( e.target ) ) {
				parents.forEach( function ( item ) { item.classList.remove( 'dt-open' ); } );
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

		// Collect tiers from DOM rows (only min_qty needed for highlighting).
		var currentTiers = tiersFromTable( table );
		highlightActiveTier( currentTiers, getQty() );

		if ( qtyInput ) {
			qtyInput.addEventListener( 'input', function () {
				highlightActiveTier( currentTiers, getQty() );
			} );
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
			highlightActiveTier( currentTiers, getQty() );
		} );
	}

	/* Build a minimal tier list from the data-min attributes in a rendered table. */
	function tiersFromTable( table ) {
		var tiers = [];
		table.querySelectorAll( 'tr[data-min]' ).forEach( function ( row ) {
			var min = parseInt( row.dataset.min, 10 );
			if ( min > 0 ) tiers.push( { min_qty: min } );
		} );
		return tiers;
	}

	/* Replace the tbody rows with updated prices, preserving the thead. */
	function rebuildTbody( table, tiers, basePrice ) {
		var sym      = ( typeof dorotapeProduct !== 'undefined' && dorotapeProduct.currencySymbol )
			? dorotapeProduct.currencySymbol : '£';
		var decimals = ( typeof dorotapeProduct !== 'undefined' && dorotapeProduct.priceDecimals != null )
			? dorotapeProduct.priceDecimals : 2;

		function fmt( price ) { return sym + price.toFixed( decimals ) + '/m'; }

		var tbody = table.querySelector( 'tbody' );
		if ( ! tbody ) return;

		var html = '';

		html += '<tr class="dt-tier-pricing__row dt-tier-pricing__row--base" data-min="0">'
			+ '<td>1m+</td>'
			+ '<td>' + ( basePrice > 0 ? fmt( basePrice ) : '&mdash;' ) + '</td>'
			+ '<td>&mdash;</td>'
			+ '</tr>';

		tiers.forEach( function ( tier ) {
			if ( tier.tier_price <= 0 ) return;
			var saving = basePrice > 0
				? Math.round( ( ( basePrice - tier.tier_price ) / basePrice ) * 100 )
				: 0;
			html += '<tr class="dt-tier-pricing__row" data-min="' + tier.min_qty + '">'
				+ '<td>' + escapeHtml( tier.min_qty + 'm+' ) + '</td>'
				+ '<td>' + fmt( tier.tier_price ) + '</td>'
				+ '<td>' + ( saving > 0 ? saving + '% off' : '&mdash;' ) + '</td>'
				+ '</tr>';
		} );

		tbody.innerHTML = html;
	}

	/* Highlight the active tier row based on the current quantity. */
	function highlightActiveTier( tiers, qty ) {
		const table = document.querySelector( '.dt-tier-pricing__table' );
		if ( ! table ) return;

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
	}

	function escapeHtml( str ) {
		return String( str )
			.replace( /&/g, '&amp;' ).replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' ).replace( /"/g, '&quot;' );
	}

	/* ── Boot ───────────────────────────────────────────────────────────── */
	document.addEventListener( 'DOMContentLoaded', function () {
		initDropdownNav();
		initTierTable();
	} );

}() );
