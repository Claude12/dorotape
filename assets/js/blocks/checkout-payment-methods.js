/**
 * Registers the theme's two payment methods with the block checkout.
 *
 * Shipped unbundled, on purpose. Everything here comes off the `wc` and `wp`
 * globals WordPress has already put on the page, so there is nothing to
 * resolve and nothing to compile, and the file that runs is the file you read.
 * See inc/payment-methods.php for why it is not part of assets/js/main.js.
 *
 * Both methods are plain radio choices: a label, a sentence under it, and no
 * fields. The money is taken afterwards, by telephone or through the link in
 * the order email, so there is nothing for the customer to fill in here.
 */
( function ( wc, wp ) {
  'use strict';

  var registry = wc && wc.wcBlocksRegistry;
  var settings = wc && wc.wcSettings;

  // Nothing to do on a page without a block checkout on it.
  if ( ! registry || ! settings || ! wp || ! wp.element ) {
    return;
  }

  var el = wp.element.createElement;
  var decode = wp.htmlEntities ? wp.htmlEntities.decodeEntities : function ( s ) { return s; };

  /**
   * @param {string} id Gateway id, which is also its block method name.
   */
  function register( id ) {
    // Absent when the gateway is switched off, which is how a disabled method
    // disappears from the checkout rather than registering an empty row.
    var data = settings.getSetting( id + '_data', null );

    if ( ! data ) {
      return;
    }

    var label = decode( data.title || '' );
    var description = decode( data.description || '' );

    // Always an element, never null. WooCommerce clones whatever it is given
    // here, and React.cloneElement( null ) throws and takes the whole payment
    // step down with it, leaving a checkout with no way to pay and an error
    // only visible in the console.
    var content = el(
      'div',
      { className: 'dorotape-payment-method__description' },
      description
    );

    registry.registerPaymentMethod( {
      name: id,
      label: el( 'span', { className: 'dorotape-payment-method__label' }, label ),
      ariaLabel: label,
      content: content,
      // What the Checkout block shows in the editor, where there is no cart to
      // price and no customer to be told anything. The same sentence is the
      // honest preview of the real thing.
      edit: content,
      canMakePayment: function () {
        return true;
      },
      supports: {
        features: data.supports || [ 'products' ],
      },
    } );
  }

  register( 'dorotape_phone' );
  register( 'dorotape_paylink' );
} )( window.wc, window.wp );
