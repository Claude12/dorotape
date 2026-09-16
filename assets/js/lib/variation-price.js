/**
 * Variable product: swap the selected variation's price into the position the
 * price range occupies, and restore the range when the selection is reset.
 */

import { invalidateBaseline } from './live-price';

export function initVariationPriceSwap() {
  if ( typeof jQuery === 'undefined' ) return;

  const variationsForm = document.querySelector( 'form.variations_form' );
  if ( ! variationsForm ) return;

  const rangePrice = document.querySelector( '.entry-summary > p.price' );
  if ( ! rangePrice ) return;

  var originalHTML = rangePrice.innerHTML;
  var tierTable    = document.getElementById( 'dt_variable_tier_table' );
  var discountHint = document.getElementById( 'dt_discount_hint' );
  var swapTimer;

  function showVariation( variation ) {
    clearTimeout( swapTimer );

    // Does this specific variation have tier pricing?
    var hasTiers = false;
    if ( variation && tierTable ) {
      try {
        var allTiers = JSON.parse( tierTable.dataset.variationTiers || '{}' );
        var varTierData = allTiers[ String( variation.variation_id ) ];
        hasTiers = !! ( varTierData && varTierData.tiers && varTierData.tiers.length );
      } catch { /* malformed data attribute, keep the empty default above */ }
    }

    // Kick off fade-outs.
    rangePrice.classList.add( 'dt-price-fading' );
    if ( discountHint ) discountHint.classList.add( 'dt-hint-out' );

    swapTimer = setTimeout( function () {
      // Swap price content mid-fade. This is now the baseline price, so
      // invalidate the live-tier capture so its next refresh re-reads it.
      if ( variation && variation.price_html ) {
        rangePrice.innerHTML = variation.price_html;
        invalidateBaseline();
      }
      rangePrice.classList.remove( 'dt-price-fading' );

      // Collapse hint fully now that it has faded.
      if ( discountHint ) {
        discountHint.style.display = 'none';
        discountHint.classList.remove( 'dt-hint-out' );
      }

      if ( tierTable ) {
        if ( hasTiers ) {
          // Reveal tier table and animate it in.
          tierTable.style.display = 'block';
          requestAnimationFrame( function () {
            requestAnimationFrame( function () {
              tierTable.classList.add( 'dt-table-in' );
            } );
          } );
        } else {
          // Flat-price variation, hide tier table immediately.
          tierTable.classList.remove( 'dt-table-in' );
          tierTable.style.display = 'none';
        }
      }
    }, 110 );
  }

  function resetVariation() {
    clearTimeout( swapTimer );

    // Fade tier table out, then hide.
    if ( tierTable ) {
      tierTable.classList.remove( 'dt-table-in' );
      swapTimer = setTimeout( function () {
        tierTable.style.display = 'none';
      }, 150 );
    }

    // Crossfade the price back to the range.
    rangePrice.classList.add( 'dt-price-fading' );
    setTimeout( function () {
      rangePrice.innerHTML = originalHTML;
      invalidateBaseline(); // baseline is the range again
      rangePrice.classList.remove( 'dt-price-fading' );
    }, 100 );

    // Slide hint back in.
    if ( discountHint ) {
      discountHint.style.display = '';
      requestAnimationFrame( function () {
        requestAnimationFrame( function () {
          discountHint.classList.remove( 'dt-hint-out' );
        } );
      } );
    }
  }

  jQuery( variationsForm )
    .on( 'found_variation', function ( _e, variation ) { showVariation( variation ); } )
    .on( 'reset_data',      function ()               { resetVariation(); } );
}
