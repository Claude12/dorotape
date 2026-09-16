/**
 * Quick-add table: all rows share one combined qty for tier lookup, while each
 * row keeps its own base price and tier price scale.
 */

import { priceBreakdown, formatPrice, escapeHtml } from './price-utils';

export function initQuickAdd() {
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
    // The box is just the number now. The rule and the "N more to go"
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
      try { tiers = JSON.parse( priceCell.dataset.tiers || '[]' ); } catch { /* malformed data attribute, keep the empty default above */ }

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
      // discount is currently active, used to drive the summary price.
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
        // rate". An empty row's rate is not part of what's being bought,
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
      // same shape updateDisplayedPrice() gives every other product:
      // big total, then "N rolls × £X/roll" underneath. Michael: the
      // price under the title has to move with the quantity here too.
      //
      // The rate alone used to go here, so the headline sat at £9.90
      // until the 10th roll and then jumped to £8.91. It never moved
      // with the quantity, which is exactly what he was reporting.
      //
      // Below 2 rolls there is no total to add up (1 × the rate IS the
      // rate), so WooCommerce's own markup is restored, again matching
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
