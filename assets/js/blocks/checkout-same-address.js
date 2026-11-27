/**
 * Invoice address first, then "Use same address for shipping".
 *
 * WooCommerce's checkout asks for the delivery address and offers "Use same
 * address for billing" under it. The trade thinks the other way round: who
 * is being invoiced, then whether the goods go somewhere else (Scott Bale,
 * 19 November 2026). The invoice block now comes first in the checkout page
 * itself, and this script turns the checkbox round to match:
 *
 *  - WooCommerce's own "same address" box is switched off and hidden, so the
 *    invoice form always shows and is never overwritten by delivery.
 *  - A box reading "Use same address for shipping" goes at the top of the
 *    shipping section. While it is ticked the shipping form is hidden and the
 *    shipping address is kept a copy of the invoice address, so delivery is
 *    priced for where the goods are going.
 *  - Unticking it shows the shipping form again with the delivery address the
 *    customer had before, or their saved one, rather than the invoice address
 *    to retype over.
 *
 * Shipped unbundled, like checkout-address-book.js: everything comes off the
 * `wp` global the block checkout has already put on the page. Without it the
 * checkout still works, with WooCommerce's own box.
 */
( function ( wp, data ) {
  'use strict';

  if ( ! wp || ! wp.data || ! data ) {
    return;
  }

  var CART = 'wc/store/cart';
  var CHECKOUT = 'wc/store/checkout';
  var BOX = '.wp-block-woocommerce-checkout-shipping-address-block';
  var root = document.documentElement;

  var ticked;          // Undecided until the store has the addresses.
  var kept = null;     // The delivery address to put back on unticking.
  var lastCopied = ''; // The invoice address last copied, so it is copied once.

  // Case and spacing aside, because the Store API hands a postcode back in
  // its own format and that is not the customer changing anything.
  function same( a, b ) {
    return String( a || '' ).replace( /\s+/g, '' ).toLowerCase() === String( b || '' ).replace( /\s+/g, '' ).toLowerCase();
  }

  // The invoice address in shipping's shape: every shipping field the invoice
  // address also has, which leaves out the email.
  function asShipping( billing, shipping ) {
    var out = {};

    Object.keys( shipping ).forEach( function ( key ) {
      if ( Object.prototype.hasOwnProperty.call( billing, key ) ) {
        out[ key ] = billing[ key ];
      }
    } );

    return out;
  }

  function differs( billing, shipping ) {
    return Object.keys( shipping ).some( function ( key ) {
      return Object.prototype.hasOwnProperty.call( billing, key ) && ! same( billing[ key ], shipping[ key ] );
    } );
  }

  function filled( address ) {
    return !! ( address && String( address.address_1 || '' ).trim() );
  }

  function setTicked( value ) {
    ticked = value;
    root.classList.toggle( 'dt-ship-same', value );

    var input = document.getElementById( 'dt-same-address' );

    if ( input && input.checked !== value ) {
      input.checked = value;
    }
  }

  function onChange( event ) {
    var cart = wp.data.select( CART ).getCustomerData();
    var billing = cart.billingAddress;
    var shipping = cart.shippingAddress;

    if ( event.target.checked ) {
      // Remember a separate delivery address in case they change their mind.
      if ( filled( shipping ) && differs( billing, shipping ) ) {
        kept = shipping;
      }

      // A saved delivery address picked before ticking would otherwise be
      // copied over the invoice address by the server when the order is placed.
      if ( data.pickerField ) {
        var values = wp.data.select( CHECKOUT ).getAdditionalFields() || {};

        if ( values[ data.pickerField ] ) {
          var reset = {};
          reset[ data.pickerField ] = '';
          wp.data.dispatch( CHECKOUT ).setAdditionalFields( reset );
        }
      }

      lastCopied = '';
      setTicked( true );
      wp.data.dispatch( CART ).setShippingAddress( asShipping( billing, shipping ) );
      return;
    }

    setTicked( false );

    var back = null;

    if ( kept && differs( billing, kept ) ) {
      back = kept;
    } else if ( filled( data.delivery ) && differs( billing, data.delivery ) ) {
      back = data.delivery;
    }

    if ( ! back ) {
      // Nothing else to offer: an empty form in the customer's country.
      back = {};
      Object.keys( shipping ).forEach( function ( key ) {
        back[ key ] = 'country' === key ? shipping.country : '';
      } );
    }

    wp.data.dispatch( CART ).setShippingAddress( asShipping( back, shipping ) );
  }

  // The box, at the top of the shipping section. It sits outside the nodes
  // React draws, and is put back if the section is redrawn without it.
  function placeBox() {
    var box = document.querySelector( BOX );

    if ( ! box || box.querySelector( '.dt-same-address' ) ) {
      return;
    }

    var content = box.querySelector( '.wc-block-components-checkout-step__content' );

    if ( ! content ) {
      return;
    }

    var wrap = document.createElement( 'div' );
    wrap.className = 'wc-block-components-checkbox dt-same-address';
    wrap.innerHTML =
      '<label for="dt-same-address">' +
      '<input id="dt-same-address" class="wc-block-components-checkbox__input" type="checkbox">' +
      '<svg class="wc-block-components-checkbox__mark" aria-hidden="true" viewBox="0 0 24 20"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"></path></svg>' +
      '<span class="wc-block-components-checkbox__label"></span>' +
      '</label>';
    wrap.querySelector( '.wc-block-components-checkbox__label' ).textContent = data.label;

    var input = wrap.querySelector( 'input' );
    input.checked = !! ticked;
    input.addEventListener( 'change', onChange );

    box.insertBefore( wrap, box.querySelector( '.dt-address-picker' ) || content );

    // Only now hide WooCommerce's own box, so there is always one of them.
    root.classList.add( 'dt-same-address-ready' );
  }

  wp.data.subscribe( function () {
    var checkoutStore = wp.data.select( CHECKOUT );
    var cartStore = wp.data.select( CART );

    if ( ! checkoutStore || ! cartStore || ! cartStore.getCustomerData || ! checkoutStore.getUseShippingAsBilling ) {
      return;
    }

    var cart = cartStore.getCustomerData();

    if ( ! cart || ! cart.billingAddress || ! cart.shippingAddress ) {
      return;
    }

    var billing = cart.billingAddress;
    var shipping = cart.shippingAddress;

    // The invoice form always shows, and delivery never writes over it.
    if ( checkoutStore.getUseShippingAsBilling() ) {
      wp.data.dispatch( CHECKOUT ).__internalSetUseShippingAsBilling( false );
    }

    if ( undefined === ticked ) {
      // Ticked unless the basket already holds a separate delivery address.
      // For a customer with an account that starts as their saved one, and
      // after ticking the box it is the invoice address, so a reload keeps
      // whichever way the customer left it.
      setTicked( ! ( filled( shipping ) && differs( billing, shipping ) ) );
    }

    placeBox();

    if ( ! ticked ) {
      return;
    }

    var copy = asShipping( billing, shipping );
    var key = JSON.stringify( copy );

    if ( key !== lastCopied && differs( billing, shipping ) ) {
      lastCopied = key;
      wp.data.dispatch( CART ).setShippingAddress( copy );
    }
  } );
}( window.wp, window.dorotapeSameAddress ) );
