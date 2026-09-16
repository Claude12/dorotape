/**
 * Shared price formatting helpers.
 *
 * Used by the tier table, the live price and the quick-add grid, so the
 * wording of a price phrase stays identical everywhere it appears.
 */

/* global dorotapeProduct */

/* Pricing unit of the table's product: 'metre' | 'roll' | 'item'. */
export function tableUnit( table ) {
  var container = table.closest( '.dt-tier-pricing' );
  return ( container && container.dataset.unit ) || 'metre';
}

/* "5 rolls × £8.91/roll", the qty × unit-price phrase, shared by the
   single-product live total and the quick-add rows so the wording stays
   identical across both. */
export function priceBreakdown( qty, unitPrice, unit ) {
  if ( 'metre' === unit ) {
    return qty + 'm × ' + formatPrice( unitPrice ) + '/m';
  }
  if ( 'roll' === unit ) {
    return qty + ( 1 === qty ? ' roll' : ' rolls' ) + ' × ' + formatPrice( unitPrice ) + '/roll';
  }
  return String( qty ) + ' × ' + formatPrice( unitPrice );
}

export function formatPrice( price ) {
  var sym      = ( typeof dorotapeProduct !== 'undefined' && dorotapeProduct.currencySymbol )
    ? dorotapeProduct.currencySymbol : '£';
  var decimals = ( typeof dorotapeProduct !== 'undefined' && dorotapeProduct.priceDecimals != null )
    ? dorotapeProduct.priceDecimals : 2;
  return sym + price.toFixed( decimals );
}

export function escapeHtml( str ) {
  return String( str )
    .replace( /&/g, '&amp;' ).replace( /</g, '&lt;' )
    .replace( />/g, '&gt;' ).replace( /"/g, '&quot;' );
}
