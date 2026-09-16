/**
 * Tier table: highlight the active quantity-break row as the qty input changes,
 * and rebuild the table when a variation is selected.
 */

import { tableUnit, formatPrice, escapeHtml } from './price-utils';
import { updateDisplayedPrice } from './live-price';

export function initTierTable() {
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
  // on it directly. This is more reliable than catching the found_variation custom event
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
    } catch { /* malformed data attribute, keep the empty default above */ }
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
  // Mirrors dorotape_unit_strings() in inc/pricing.php, keep in step.
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
