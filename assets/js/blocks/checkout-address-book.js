/**
 * Fills the checkout's address from the saved address the customer picks.
 *
 * Shipped unbundled, like checkout-payment-methods.js: everything comes off the
 * `wp` global the block checkout has already put on the page. See
 * inc/address-book-checkout.php for the picker itself.
 *
 * Without this the order still carried the chosen address, because the server
 * copies it over at the end, but delivery was priced for whatever was typed in
 * the form. Pick a Highlands site with a Leicester address in the form and the
 * order went to Inverness at the mainland rate. Writing the choice into the
 * cart store makes the address card change in front of the customer and the
 * shipping rates and total recalculate, so what they pay matches where it goes.
 */
( function ( wp, data ) {
  'use strict';

  if ( ! wp || ! wp.data || ! data ) {
    return;
  }

  data.book = data.book || {};

  var CART = 'wc/store/cart';
  var CHECKOUT = 'wc/store/checkout';

  // Which picker => [ additional field id, cart store setter ]. Either picker
  // lists the whole book: an address has no type (Site Edits (1), General
  // row 19). An empty picker means the address in the form.
  var pickers = {
    shipping: [ data.fields.shipping, 'setShippingAddress' ],
    billing: [ data.fields.billing, 'setBillingAddress' ]
  };

  // What each picker was last seen holding, so a choice is applied once,
  // when it changes, and not again on every store update after it.
  var seen = {};

  function apply( type, id ) {
    var address = data.book[ id ];

    if ( ! address ) {
      return;
    }

    var fields = {};

    Object.keys( address ).forEach( function ( key ) {
      // `type` is only on entries saved before addresses lost their type.
      if ( 'label' !== key && 'type' !== key ) {
        fields[ key ] = address[ key ];
      }
    } );

    var checkout = wp.data.dispatch( CHECKOUT );

    // A separate invoice address means billing can no longer mirror delivery,
    // or the next delivery change would quietly overwrite it.
    if ( 'billing' === type && checkout.__internalSetUseShippingAsBilling ) {
      checkout.__internalSetUseShippingAsBilling( false );
    }

    wp.data.dispatch( CART )[ pickers[ type ][ 1 ] ]( fields );
  }

  // Case and spacing aside, because the Store API hands a postcode back in
  // its own format and that is not the customer changing anything.
  function same( a, b ) {
    return String( a || '' ).replace( /\s+/g, '' ).toLowerCase() === String( b || '' ).replace( /\s+/g, '' ).toLowerCase();
  }

  // Whether the address in the form still is the saved one picked.
  function matches( type, id, current ) {
    var address = data.book[ id ];

    if ( ! address ) {
      return false;
    }

    return Object.keys( address ).every( function ( key ) {
      return 'label' === key || 'type' === key || same( address[ key ], current[ key ] );
    } );
  }

  // Whether the chosen delivery option is collection. The old site's
  // "Collect from Doro Tape" is WooCommerce's classic local_pickup method,
  // which the field rules' prefers_collection flag does not recognise, so the
  // delivery picker is hidden here instead of by a rule on the field.
  function collecting( cartStore ) {
    var packages = cartStore.getShippingRates ? cartStore.getShippingRates() : [];

    return ( packages || [] ).some( function ( pack ) {
      return ( pack.shipping_rates || [] ).some( function ( rate ) {
        return rate.selected && ( 'local_pickup' === rate.method_id || 'pickup_location' === rate.method_id );
      } );
    } );
  }

  // ── The pickers, inside the address boxes ──────────────────────────────
  //
  // The pickers are WooCommerce additional fields, which can only sit in the
  // contact, address-form or order sections, so they landed under
  // "Additional order information", away from the address they change
  // (Scott Bale, 19 November 2026). WooCommerce's own select is cloned into
  // the top of each address box and the original is hidden by CSS. The clone
  // writes to the same field in the checkout store, so the server and the
  // logic below work exactly as before. The clone sits outside the nodes
  // React draws, and is put back if the box is redrawn without it.

  var boxes = {
    shipping: '.wp-block-woocommerce-checkout-shipping-address-block',
    billing: '.wp-block-woocommerce-checkout-billing-address-block'
  };

  function fieldClass( type ) {
    return 'wc-block-components-select-input-' + pickers[ type ][ 0 ].replace( '/', '-' );
  }

  function placePicker( type, value, hidden ) {
    var box = document.querySelector( boxes[ type ] );
    var original = document.querySelector( '.' + fieldClass( type ) + ':not(.dt-address-picker)' );

    if ( ! box || ! original ) {
      return;
    }

    var clone = box.querySelector( '.dt-address-picker' );

    if ( ! clone ) {
      var content = box.querySelector( '.wc-block-components-checkout-step__content' );

      if ( ! content ) {
        return;
      }

      clone = original.cloneNode( true );
      clone.classList.add( 'dt-address-picker' );

      var select = clone.querySelector( 'select' );

      select.id = 'dt-picker-' + type;
      select.removeAttribute( 'name' );
      clone.querySelector( 'label' ).setAttribute( 'for', select.id );
      select.addEventListener( 'change', function () {
        var update = {};
        update[ pickers[ type ][ 0 ] ] = select.value;
        wp.data.dispatch( CHECKOUT ).setAdditionalFields( update );
      } );

      box.insertBefore( clone, content );

      // Only now hide the original, so the picker never disappears altogether.
      document.documentElement.classList.add( 'dt-pickers-moved-' + type );
    }

    var cloneSelect = clone.querySelector( 'select' );

    if ( cloneSelect.value !== value ) {
      cloneSelect.value = value;
    }

    if ( ( 'none' === clone.style.display ) !== hidden ) {
      clone.style.display = hidden ? 'none' : '';
    }
  }

  wp.data.subscribe( function () {
    var checkoutStore = wp.data.select( CHECKOUT );
    var cartStore = wp.data.select( CART );

    if ( ! checkoutStore || ! checkoutStore.getAdditionalFields || ! cartStore ) {
      return;
    }

    var values = checkoutStore.getAdditionalFields() || {};
    var cart = cartStore.getCustomerData ? cartStore.getCustomerData() : null;
    var collection = collecting( cartStore );

    if ( data.fields.shipping ) {
      placePicker( 'shipping', values[ data.fields.shipping ] || '', collection );
    }
    if ( data.fields.billing ) {
      placePicker( 'billing', values[ data.fields.billing ] || '', false );
    }

    // A delivery address picked before switching to collection would still
    // be copied onto the order by the server, so let it go.
    if ( collection && values[ data.fields.shipping ] ) {
      var clear = {};
      clear[ data.fields.shipping ] = '';
      seen.shipping = '';
      wp.data.dispatch( CHECKOUT ).setAdditionalFields( clear );
      return;
    }

    Object.keys( pickers ).forEach( function ( type ) {
      var fieldId = pickers[ type ][ 0 ];
      var value = values[ fieldId ] || '';

      if ( seen[ type ] === undefined ) {
        // First look. A value already there was restored from the session,
        // and the address it chose was applied when it was first picked.
        seen[ type ] = value;
        return;
      }

      if ( value !== seen[ type ] ) {
        seen[ type ] = value;

        if ( value ) {
          apply( type, value );
        }
        return;
      }

      // The customer picked a saved address and then changed it in the form.
      // Empty the picker, or the server would copy the saved version over
      // their edit when the order is placed.
      var current = cart && ( 'billing' === type ? cart.billingAddress : cart.shippingAddress );

      if ( value && current && ! matches( type, value, current ) ) {
        var update = {};
        update[ fieldId ] = '';
        seen[ type ] = '';
        wp.data.dispatch( CHECKOUT ).setAdditionalFields( update );
      }
    } );
  } );
}( window.wp, window.dorotapeAddressBook ) );
