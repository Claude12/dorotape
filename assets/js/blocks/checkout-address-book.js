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

  if ( ! wp || ! wp.data || ! data || ! data.book ) {
    return;
  }

  var CART = 'wc/store/cart';
  var CHECKOUT = 'wc/store/checkout';

  // Address book type => [ additional field id, cart store setter ].
  var pickers = {
    shipping: [ data.fields.shipping, 'setShippingAddress' ],
    billing: [ data.fields.billing, 'setBillingAddress' ]
  };

  // What each picker was last seen holding, so a choice is applied once,
  // when it changes, and not again on every store update after it.
  var seen = {};

  function apply( type, id ) {
    var address = data.book[ id ];

    if ( ! address || address.type !== type ) {
      return;
    }

    var fields = {};

    Object.keys( address ).forEach( function ( key ) {
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

  wp.data.subscribe( function () {
    var checkoutStore = wp.data.select( CHECKOUT );
    var cartStore = wp.data.select( CART );

    if ( ! checkoutStore || ! checkoutStore.getAdditionalFields || ! cartStore ) {
      return;
    }

    var values = checkoutStore.getAdditionalFields() || {};
    var cart = cartStore.getCustomerData ? cartStore.getCustomerData() : null;
    var collection = collecting( cartStore );
    var deliveryPicker = document.querySelector( '.wc-block-components-select-input-' + data.fields.shipping.replace( '/', '-' ) );

    if ( deliveryPicker && ( 'none' === deliveryPicker.style.display ) !== collection ) {
      deliveryPicker.style.display = collection ? 'none' : '';
    }

    // A delivery address picked before switching to collection would still
    // be copied onto the order by the server, so let it go.
    if ( collection && values[ data.fields.shipping ] && data.formValue !== values[ data.fields.shipping ] ) {
      var clear = {};
      clear[ data.fields.shipping ] = data.formValue;
      seen.shipping = data.formValue;
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

        if ( value && data.formValue !== value ) {
          apply( type, value );
        }
        return;
      }

      // The customer picked a saved address and then changed it in the form.
      // Step the picker back to "the address entered above", or the server
      // would copy the saved version over their edit when the order is placed.
      var current = cart && ( 'billing' === type ? cart.billingAddress : cart.shippingAddress );

      if ( value && data.formValue !== value && current && ! matches( type, value, current ) ) {
        var update = {};
        update[ fieldId ] = data.formValue;
        seen[ type ] = data.formValue;
        wp.data.dispatch( CHECKOUT ).setAdditionalFields( update );
      }
    } );
  } );
}( window.wp, window.dorotapeAddressBook ) );
