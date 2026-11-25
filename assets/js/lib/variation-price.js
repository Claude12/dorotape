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

  // The note under the description says what the price is for: "Prices shown
  // are per linear metre at 625mm wide". Where the widths are the options, the
  // price moved with the choice and the note went on naming the first width.
  // Only a note that names exactly one width is touched, so "25mm x 50m" and
  // anything worded another way is left as written.
  var sizeNote     = document.querySelector( '.product-size-note' );
  var sizeNoteText = sizeNote ? sizeNote.textContent : '';
  var widthInNote  = /\d+(?:\.\d+)?\s*mm(?=\s+wide)/gi;
  var noteHasWidth = ( sizeNoteText.match( widthInNote ) || [] ).length === 1;

  // A width option: "625mm" under a width attribute, or "1220mm-per-metre" /
  // "1220mm wide" under any attribute. Never "305mm x 500mm", a cut piece.
  function widthOf( key, value ) {
    var v = String( value );
    var found = key.indexOf( 'width' ) !== -1
      ? v.match( /^(\d+(?:\.\d+)?)\s*mm$/i )
      : v.match( /^(\d+(?:\.\d+)?)\s*mm[\s-]+(?:per[\s-]+metre|wide)\b/i );
    return found ? found[ 1 ] + 'mm' : '';
  }

  function chosenWidth( variation ) {
    var attrs = ( variation && variation.attributes ) || {};
    for ( var key in attrs ) {
      var width = widthOf( key, attrs[ key ] );
      if ( width ) return width;
    }
    return '';
  }

  // Until a width is picked the note names none: "at 625mm wide" read as if
  // the first width were the only one. Only where two or more widths are on
  // offer, so a product in one width keeps its note as written.
  var widthOptions = 0;
  variationsForm.querySelectorAll( 'select[name^="attribute_"] option' ).forEach( function ( opt ) {
    var key = opt.closest( 'select' ).name.replace( /^attribute_/, '' );
    if ( widthOf( key, opt.value ) ) widthOptions++;
  } );
  var widthIsChoice = widthOptions > 1;
  var noteNoWidth   = sizeNoteText.replace( /\s+at\s+\d+(?:\.\d+)?\s*mm\s+wide/i, '' );

  // A variation sold another way (a sheet on a film sold by the metre) gets
  // no note: "per linear metre" is not what its price is for.
  var productUnit = ( document.getElementById( 'dt_live_price' ) || tierTable || {} ).dataset;
  productUnit = productUnit ? productUnit.unit : '';

  function updateSizeNote( variation ) {
    if ( ! sizeNote ) return;
    var otherUnit = !! ( variation && variation.dt_unit && productUnit && variation.dt_unit !== productUnit );
    sizeNote.hidden = otherUnit;
    if ( ! noteHasWidth || otherUnit ) return;
    var width = chosenWidth( variation );
    if ( width ) {
      sizeNote.textContent = sizeNoteText.replace( widthInNote, width );
    } else {
      sizeNote.textContent = widthIsChoice ? noteNoWidth : sizeNoteText;
    }
  }
  updateSizeNote( null );

  // The wording that follows the unit: the quantity box caption, the tier
  // table's price header and its footnote. A sheet on a film sold by the metre
  // reads "Item(s)" and "Price each" while it is the one selected.
  var qtyLabel    = document.querySelector( 'form.cart .dt-qty-label' );
  var tierHeader  = tierTable ? tierTable.querySelector( 'thead th:nth-child(2)' ) : null;
  var tierNote    = tierTable ? tierTable.querySelector( '.dt-tier-pricing__note' ) : null;
  var origLabels  = {
    qty_label: qtyLabel ? qtyLabel.textContent : '',
    header:    tierHeader ? tierHeader.textContent : '',
    note:      tierNote ? tierNote.textContent : '',
  };

  function updateUnitLabels( variation ) {
    var labels = ( variation && variation.dt_labels ) || origLabels;
    if ( qtyLabel && labels.qty_label ) qtyLabel.textContent = labels.qty_label;
    if ( tierHeader && labels.header ) tierHeader.textContent = labels.header;
    if ( tierNote && labels.note ) tierNote.textContent = labels.note;
  }

  function showVariation( variation ) {
    clearTimeout( swapTimer );
    updateSizeNote( variation );
    updateUnitLabels( variation );

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
    updateSizeNote( null );
    updateUnitLabels( null );

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
