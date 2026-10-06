/**
 * Clears a county error the checkout raised before it knew the country.
 *
 * Shipped unbundled, like the other scripts in this folder. Loaded on the
 * checkout by inc/woocommerce.php, which says why.
 */
( function ( wp, data ) {
  'use strict';

  if ( ! wp || ! wp.data || ! data || ! data.optional ) {
    return;
  }

  var addresses = { shipping: 'shippingAddress', billing: 'billingAddress' };

  wp.data.subscribe( function () {
    var validation = wp.data.select( 'wc/store/validation' );
    var cart = wp.data.select( 'wc/store/cart' );

    if ( ! validation || ! validation.getValidationError || ! cart || ! cart.getCustomerData ) {
      return;
    }

    var customer = cart.getCustomerData();

    Object.keys( addresses ).forEach( function ( type ) {
      var key = type + '-state';
      var address = customer[ addresses[ type ] ] || {};

      // Only for a country that never asks for a county, and only while the
      // box is empty, which is exactly the error that should not be there.
      if ( validation.getValidationError( key ) && -1 !== data.optional.indexOf( address.country ) && ! address.state ) {
        wp.data.dispatch( 'wc/store/validation' ).clearValidationError( key );
      }
    } );
  } );
}( window.wp, window.dorotapeCheckoutCounty ) );
