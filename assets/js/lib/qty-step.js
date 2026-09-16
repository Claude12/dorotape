/**
 * Quantity step: keep typed values on the step.
 */

export function initQtyStep() {
  function stepOf( input ) {
    return parseInt( input.getAttribute( 'step' ), 10 ) || 1;
  }
  function minOf( input, step ) {
    return parseInt( input.getAttribute( 'min' ), 10 ) || step;
  }
  function maxOf( input ) {
    var max = parseInt( input.getAttribute( 'max' ), 10 );
    return ( ! isNaN( max ) && max > 0 ) ? max : 0;
  }

  function commit( input, value ) {
    if ( String( value ) === input.value ) return;
    input.value = value;
    input.dispatchEvent( new Event( 'input',  { bubbles: true } ) );
    input.dispatchEvent( new Event( 'change', { bubbles: true } ) );
  }

  // Snap to the nearest valid multiple. Still needed even with the box
  // locked: WooCommerce rewrites min (and therefore the valid sequence)
  // when a variation is chosen, which can strand the current value.
  function snap( input ) {
    var step = stepOf( input );
    if ( step <= 1 ) return;
    var min = minOf( input, step );
    var val = parseInt( input.value, 10 );
    if ( isNaN( val ) || val < min ) { val = min; }

    // Ties round up: on a step of 5, 7.5 → 10 is the friendlier default.
    var snapped = Math.round( val / step ) * step;
    if ( snapped < min ) snapped = min;

    var max = maxOf( input );
    if ( max && snapped > max ) snapped = Math.floor( max / step ) * step;

    commit( input, snapped );
  }

  function nudge( input, direction ) {
    var step = stepOf( input );
    if ( step <= 1 ) return;
    var min  = minOf( input, step );
    var max  = maxOf( input );
    var val  = parseInt( input.value, 10 );
    if ( isNaN( val ) ) val = min;

    var next = val + ( direction * step );
    if ( next < min ) next = min;
    if ( max && next > max ) next = Math.floor( max / step ) * step;

    commit( input, next );
  }

  function isStepped( input ) {
    return !! input && stepOf( input ) > 1;
  }

  function button( direction, label, glyph ) {
    var el = document.createElement( 'button' );
    el.type = 'button';
    el.className = 'dt-qty-step dt-qty-step--' + direction;
    el.setAttribute( 'data-dt-qty', direction );
    el.setAttribute( 'aria-label', label );
    el.tabIndex = -1;
    el.innerHTML = glyph;
    return el;
  }

  // PHP renders the buttons on the single-product page, where it has the
  // product in hand. The cart page's quantity boxes go through the same
  // step filter but WooCommerce gives the before/after hooks no product
  // context there, so the markup arrives without them, and a locked box
  // with no buttons is worse than a typable one. Fill the gap here.
  function addButtons( input ) {
    var wrap = input.closest( '.quantity' );
    if ( ! wrap || wrap.querySelector( '[data-dt-qty]' ) ) return;
    wrap.insertBefore( button( 'down', 'Decrease quantity', '&minus;' ), input );
    if ( input.nextSibling ) {
      wrap.insertBefore( button( 'up', 'Increase quantity', '+' ), input.nextSibling );
    } else {
      wrap.appendChild( button( 'up', 'Increase quantity', '+' ) );
    }
  }

  // Lock every stepped box, so an invalid quantity cannot be typed at all.
  // Readonly is set here rather than in PHP on purpose: the buttons only
  // work with scripts running, so with scripts off the field stays typable
  // and the PHP add-to-cart check is what refuses a bad quantity.
  function lock( scope ) {
    ( scope || document ).querySelectorAll(
      '.quantity input[type="number"], .quantity input.qty'
    ).forEach( function ( input ) {
      if ( ! isStepped( input ) ) {
        input.removeAttribute( 'readonly' );
        return;
      }
      addButtons( input );
      input.setAttribute( 'readonly', 'readonly' );
      snap( input );
    } );
  }

  // Delegated, because WooCommerce's add-to-cart-variation.js rewrites the
  // quantity box's attributes (and can replace the surrounding markup)
  // every time a variation is picked. Listeners bound directly to the
  // input at load would quietly stop applying after the first change.
  document.addEventListener( 'click', function ( e ) {
    var button = e.target.closest ? e.target.closest( '[data-dt-qty]' ) : null;
    if ( ! button ) return;
    var wrap  = button.closest( '.quantity' );
    var input = wrap ? wrap.querySelector( 'input[type="number"], input.qty' ) : null;
    if ( ! input ) return;
    e.preventDefault();
    nudge( input, 'up' === button.dataset.dtQty ? 1 : -1 );
  } );

  document.addEventListener( 'blur', function ( e ) {
    if ( isStepped( e.target ) ) snap( e.target );
  }, true );

  document.addEventListener( 'submit', function ( e ) {
    if ( ! e.target.matches || ! e.target.matches( 'form.cart' ) ) return;
    var input = e.target.querySelector( '.quantity input[type="number"], input.qty' );
    if ( isStepped( input ) ) snap( input );
  }, true );

  lock();

  if ( typeof jQuery === 'undefined' ) return;

  // Re-lock after a variation swap: the step can differ per variation, and
  // WooCommerce restores its own attributes when it shows one.
  var variationsForm = document.querySelector( '.variations_form' );
  if ( variationsForm ) {
    jQuery( variationsForm ).on( 'show_variation reset_data', function () {
      window.setTimeout( function () { lock( variationsForm ); }, 0 );
    } );
  }

  // And after the cart redraws itself, which replaces the quantity boxes
  // wholesale along with the buttons and the readonly flag.
  jQuery( document.body ).on( 'updated_wc_div updated_cart_totals wc_fragments_refreshed', function () {
    lock();
  } );
}
