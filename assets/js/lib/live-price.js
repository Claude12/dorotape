/**
 * Live price: the on-screen price shows the running line total.
 *
 * Holds the captured baseline price, so it is a single module. The variation
 * price swap invalidates that baseline through invalidateBaseline() when it
 * rewrites the same element.
 */

import { tableUnit, priceBreakdown, formatPrice, escapeHtml } from './price-utils';

/* ── Live price: the on-screen price shows the running line total ────
 *
 * The visible price is rewritten in place to the total for the quantity
 * entered (qty × the effective unit price). For example 5m of F-Sign Platinum at
 * £3.01/m reads £15.05, not £3.01. The effective unit price is the active
 * quantity-break tier when one applies, otherwise the standard base price,
 * so the total also reflects any discount the quantity has unlocked. At
 * qty 1 the single unit price is correct, so the original markup is left
 * untouched. On variable products initVariationPriceSwap() rewrites the
 * same element after every variation change. It invalidates
 * dtLivePrice.original when it does, so the next refresh re-captures the
 * fresh variation price as the baseline (the two show_variation refresh
 * timers straddle its 110 ms rewrite).
 * ──────────────────────────────────────────────────────────────────── */
var dtLivePrice = { el: null, original: null };

export function updateDisplayedPrice( active, table, qty ) {
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

  // Variable products: only act on a concrete selected variation. The
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
      try { priceMap = JSON.parse( live.dataset.variationPrices || '{}' ); } catch { /* malformed data attribute, keep the empty default above */ }
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
  // price WooCommerce already shows is correct, so restore and bail.
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

/**
 * Drop the captured baseline so the next refresh re-reads the price element.
 * Called by the variation price swap after it rewrites that element.
 */
export function invalidateBaseline() {
  dtLivePrice.original = null;
}
