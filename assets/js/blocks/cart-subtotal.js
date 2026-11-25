/**
 * Keep the Subtotal row in the basket and checkout summaries.
 *
 * WooCommerce's Subtotal block draws nothing unless the order has a delivery
 * charge, a fee or a discount, on the reasoning that the subtotal would then
 * equal the total. It does not count VAT, so on an order over the free
 * delivery threshold the summary went from the items straight to VAT and the
 * total, with no net figure (dev QA, 25 November 2026).
 *
 * While WooCommerce leaves it out, this draws the same row in its place, just
 * before the delivery row, from the cart store. As soon as WooCommerce draws
 * its own again (delivery is charged, a code is applied) this one is removed.
 *
 * Shipped unbundled, like the other checkout scripts: everything comes off the
 * `wp` global the blocks have already put on the page.
 */
( function ( wp, data ) {
  'use strict';

  if ( ! wp || ! wp.data || ! data ) {
    return;
  }

  var CART = 'wc/store/cart';
  var ROW = 'dt-subtotal';
  var ANCHORS = [
    '.wp-block-woocommerce-cart-order-summary-shipping-block',
    '.wp-block-woocommerce-checkout-order-summary-shipping-block',
    '.wp-block-woocommerce-cart-order-summary-taxes-block',
    '.wp-block-woocommerce-checkout-order-summary-taxes-block'
  ];

  function money( minor, totals ) {
    var unit = parseInt( totals.currency_minor_unit, 10 ) || 0;
    var value = ( parseInt( minor, 10 ) || 0 ) / Math.pow( 10, unit );
    var parts = value.toFixed( unit ).split( '.' );
    var whole = parts[ 0 ].replace( /\B(?=(\d{3})+(?!\d))/g, totals.currency_thousand_separator || ',' );
    var number = parts[ 1 ] ? whole + ( totals.currency_decimal_separator || '.' ) + parts[ 1 ] : whole;

    return ( totals.currency_prefix || '' ) + number + ( totals.currency_suffix || '' );
  }

  function sync() {
    var store = wp.data.select( CART );

    if ( ! store || ! store.getCartTotals ) {
      return;
    }

    var totals = store.getCartTotals();

    // The block WooCommerce leaves out sits in the same summary as the
    // delivery or VAT row, so find that and see whether it drew its own.
    ANCHORS.some( function ( selector ) {
      var anchor = document.querySelector( selector );

      if ( ! anchor ) {
        return false;
      }

      var summary = anchor.parentNode;
      var ours = summary.querySelector( '.' + ROW );
      var theirs = summary.querySelector( '.wp-block-woocommerce-cart-order-summary-subtotal-block, .wp-block-woocommerce-checkout-order-summary-subtotal-block' );
      var needed = ! theirs && totals && totals.total_items !== undefined;

      if ( ! needed ) {
        if ( ours ) {
          ours.remove();
        }
        return true;
      }

      var items = parseInt( totals.total_items, 10 ) || 0;

      if ( data.includingTax ) {
        items += parseInt( totals.total_items_tax, 10 ) || 0;
      }

      if ( ! ours ) {
        ours = document.createElement( 'div' );
        ours.className = 'wc-block-components-totals-wrapper ' + ROW;
        ours.innerHTML =
          '<div class="wc-block-components-totals-item">' +
          '<span class="wc-block-components-totals-item__label"></span>' +
          '<span class="wc-block-components-totals-item__value"></span>' +
          '<div class="wc-block-components-totals-item__description"></div>' +
          '</div>';
        ours.querySelector( '.wc-block-components-totals-item__label' ).textContent = data.label;
      }

      if ( ours.nextSibling !== anchor ) {
        summary.insertBefore( ours, anchor );
      }

      var text = money( items, totals );
      var value = ours.querySelector( '.wc-block-components-totals-item__value' );

      if ( value.textContent !== text ) {
        value.textContent = text;
      }

      return true;
    } );
  }

  var queued = false;

  wp.data.subscribe( function () {
    if ( queued ) {
      return;
    }
    queued = true;
    // After React has drawn, so its own Subtotal block is there to be seen.
    window.requestAnimationFrame( function () {
      queued = false;
      sync();
    } );
  } );
}( window.wp, window.dorotapeSubtotal ) );
