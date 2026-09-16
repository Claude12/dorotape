/**
 * Cut size rows: one table row-group per roll being cut, which is the shape the
 * order needs when a customer wants several cuts off fewer rolls than they are
 * ordering. The balance is supplied whole.
 */

export function initCutRows() {
  const box = document.getElementById( 'dt_cutsize' );
  if ( ! box ) return;

  const body        = box.querySelector( '.dt-cutsize__body' );
  const maxNote     = box.querySelector( '.dt-cutsize__max' );
  const allocNote   = box.querySelector( '.dt-cutsize__alloc' );
  const addRollBtn  = box.querySelector( '.dt-cutsize__addgroup' );
  const form        = box.closest( 'form.cart' );
  if ( ! body || ! form ) return;

  var widthMap = {};
  try { widthMap = JSON.parse( box.dataset.maxWidths || '{}' ); } catch { /* malformed data attribute, keep the empty default above */ }
  var currentMax = parseInt( box.dataset.maxWidth, 10 ) || 0;

  /* Most customers take the roll uncut, so the cut form starts collapsed
     behind "Do you need your rolls cutting?" and only those who need it
     open it. Collapsed is also the safe default for validation: with no
     sizes entered the server treats the add as a normal single line. */
  const toggle = box.querySelector( '.dt-cutsize__toggle' );
  const panel  = box.querySelector( '.dt-cutsize__panel' );
  if ( toggle && panel ) {
    toggle.addEventListener( 'click', function () {
      const open = 'true' === toggle.getAttribute( 'aria-expanded' );
      toggle.setAttribute( 'aria-expanded', open ? 'false' : 'true' );
      panel.hidden = open;
      box.classList.toggle( 'dt-cutsize--open', ! open );
      const icon = toggle.querySelector( '.dt-cutsize__toggle-icon' );
      if ( icon ) icon.textContent = open ? '+' : '−';
      // Clearing on close keeps the visible state and what gets posted
      // in step: a collapsed panel must never submit stale cut sizes.
      if ( open ) {
        panel.querySelectorAll( '.dt-cutsize__size' ).forEach( function ( i ) { i.value = ''; } );
        panel.querySelectorAll( '.dt-cutsize__cutqty' ).forEach( function ( i ) { i.value = '1'; } );
        validateAll();
      }
    } );
  }

  function toMm( size, unit ) {
    if ( 'cm' === unit ) return size * 10;
    if ( 'in' === unit ) return size * 25.4;
    return size;
  }

  function getQty() {
    const input = form.querySelector( '.quantity input[type="number"], input.qty' );
    return input ? Math.max( 1, parseInt( input.value, 10 ) || 1 ) : 1;
  }

  // Every future row and roll cell is built from the initial PHP-rendered
  // row's own markup, so translated placeholders/aria-labels carry over
  // untouched. The roll cell is captured and detached separately from the
  // rest of the row: it belongs to the *roll*, not to any one cut row, and
  // render() moves the same node (never recreates it) onto whichever row
  // is currently first in its roll.
  const firstRow    = body.querySelector( '.dt-cutsize__row' );
  const rollCellTpl = firstRow.querySelector( '.dt-cutsize__roll-cell' ).outerHTML;
  const rowCellsTpl = Array.prototype.slice.call( firstRow.children )
    .filter( function ( cell ) { return ! cell.classList.contains( 'dt-cutsize__roll-cell' ); } )
    .map( function ( cell ) { return cell.outerHTML; } )
    .join( '' );
  const firstRollCell = firstRow.querySelector( '.dt-cutsize__roll-cell' );
  firstRollCell.remove();

  function newRow() {
    var tr = document.createElement( 'tr' );
    tr.className = 'dt-cutsize__row';
    tr.innerHTML = rowCellsTpl;
    tr.querySelectorAll( '.dt-cutsize__size' ).forEach( function ( input ) { input.value = ''; } );
    tr.querySelectorAll( '.dt-cutsize__cutqty' ).forEach( function ( input ) { input.value = '1'; } );
    tr.querySelectorAll( '.dt-cutsize__row-error' ).forEach( function ( span ) { span.textContent = ''; } );
    return tr;
  }

  function newRollCell() {
    var wrap = document.createElement( 'tbody' );
    wrap.innerHTML = '<tr>' + rollCellTpl + '</tr>';
    return wrap.querySelector( '.dt-cutsize__roll-cell' );
  }

  // rolls[r] = { cell: <td>, rows: [<tr>, ...] }, in on-screen order.
  var rolls = [ { cell: firstRollCell, rows: [ firstRow ] } ];

  function render() {
    rolls.forEach( function ( roll ) {
      roll.rows.forEach( function ( tr ) { body.appendChild( tr ); } ); // re-attaches or reorders in place
      if ( roll.rows[ 0 ].firstElementChild !== roll.cell ) {
        roll.rows[ 0 ].insertBefore( roll.cell, roll.rows[ 0 ].firstChild );
      }
      roll.cell.setAttribute( 'rowspan', String( roll.rows.length ) );
    } );
    rolls.forEach( function ( roll, r ) {
      // The roll number is a label, not an input: which roll this is
      // follows from its position, so it is never out of step with
      // the table after an add or a remove.
      var num = roll.cell.querySelector( '.dt-cutsize__roll-num' );
      if ( num ) num.textContent = String( r + 1 );
      roll.rows.forEach( function ( tr, c ) {
        var size = tr.querySelector( '.dt-cutsize__size' );
        var qty  = tr.querySelector( '.dt-cutsize__cutqty' );
        if ( size ) size.name = 'dt_cut_rows[' + r + '][' + c + '][size]';
        if ( qty ) qty.name = 'dt_cut_rows[' + r + '][' + c + '][qty]';
      } );
    } );
    box.classList.toggle( 'dt-cutsize--multi', rolls.length > 1 );
    validateAll();
  }

  function validateRoll( rows ) {
    var sum = 0, any = false;
    rows.forEach( function ( tr ) {
      var size = parseFloat( tr.querySelector( '.dt-cutsize__size' ).value ) || 0;
      var qtyEl = tr.querySelector( '.dt-cutsize__cutqty' );
      var qty   = qtyEl ? ( parseInt( qtyEl.value, 10 ) || 0 ) : 1;
      // Cuts are entered in mm only; the unit select is kept optional
      // here so any legacy markup still resolves rather than throwing.
      var unitEl = tr.querySelector( '.dt-cutsize__unit' );
      if ( size > 0 && qty > 0 ) { any = true; sum += toMm( size, unitEl ? unitEl.value : 'mm' ) * qty; }
    } );
    var invalid = any && currentMax > 0 && sum > currentMax;
    rows.forEach( function ( tr, idx ) {
      tr.classList.toggle( 'dt-cutsize__row--invalid', invalid );
      var error = tr.querySelector( '.dt-cutsize__row-error' );
      if ( ! error ) return;
      error.textContent = ( invalid && idx === rows.length - 1 )
        ? 'Cuts add up to ' + ( Math.round( sum * 10 ) / 10 ) + 'mm, wider than the roll (' + currentMax + 'mm)'
        : '';
    } );
    return ! invalid;
  }

  // A roll counts as "being cut" once it has a size in it. Empty rolls are
  // dropped server-side, so they must not count against the order quantity
  // here either, or a half-filled table would block the button for nothing.
  function cutRollCount() {
    var n = 0;
    rolls.forEach( function ( roll ) {
      var any = roll.rows.some( function ( tr ) {
        return ( parseFloat( tr.querySelector( '.dt-cutsize__size' ).value ) || 0 ) > 0;
      } );
      if ( any ) n++;
    } );
    return n;
  }

  function validateAll() {
    var ok = true;
    rolls.forEach( function ( roll ) { if ( ! validateRoll( roll.rows ) ) ok = false; } );

    var cut   = cutRollCount();
    var total = getQty();
    if ( allocNote ) {
      allocNote.classList.toggle( 'dt-cutsize__alloc--bad', cut > total );
      allocNote.textContent = cut > total
        ? 'You have entered cut sizes for ' + cut + ' rolls but are only ordering ' + total + '. Remove a roll, or order more.'
        : '';
    }
    if ( cut > total ) ok = false;

    // Nothing to add a roll for once every ordered roll has one.
    if ( addRollBtn ) addRollBtn.disabled = rolls.length >= total;

    const submit = form.querySelector( 'button[type="submit"], .single_add_to_cart_button' );
    if ( submit ) submit.disabled = ! ok;
    return ok;
  }

  /* Preset sizes are a shortcut into the same size inputs, never a
     separate source of truth: a click fills the box the customer last
     touched, else the first empty one, else a fresh cut row. Everything
     downstream (validation, what gets posted) is unchanged. */
  var lastSize = null;
  body.addEventListener( 'focusin', function ( e ) {
    if ( e.target.classList.contains( 'dt-cutsize__size' ) ) lastSize = e.target;
  } );

  function presetTarget() {
    if ( lastSize && body.contains( lastSize ) && ! lastSize.value ) return lastSize;
    var empty = null;
    rolls.forEach( function ( roll ) {
      roll.rows.forEach( function ( tr ) {
        var input = tr.querySelector( '.dt-cutsize__size' );
        if ( ! empty && input && ! input.value ) empty = input;
      } );
    } );
    if ( empty ) return empty;
    var roll = rolls[ rolls.length - 1 ];
    var row  = newRow();
    roll.rows.push( row );
    render();
    return row.querySelector( '.dt-cutsize__size' );
  }

  function syncPresets() {
    box.querySelectorAll( '.dt-cutsize__preset' ).forEach( function ( btn ) {
      var size = parseInt( btn.dataset.size, 10 ) || 0;
      btn.disabled = currentMax > 0 && size > currentMax;
    } );
  }

  box.addEventListener( 'click', function ( e ) {
    var btn = e.target.closest( '.dt-cutsize__preset' );
    if ( ! btn ) return;
    var target = presetTarget();
    if ( ! target ) return;
    target.value = btn.dataset.size;
    target.focus();
    validateAll();
  } );

  syncPresets();

  body.addEventListener( 'input', validateAll );
  body.addEventListener( 'change', validateAll );

  body.addEventListener( 'click', function ( e ) {
    const addBtn = e.target.closest( '.dt-cutsize__addcut' );
    if ( addBtn ) {
      const tr   = addBtn.closest( 'tr' );
      const roll = rolls.find( function ( r ) { return -1 !== r.rows.indexOf( tr ); } );
      if ( roll ) {
        roll.rows.push( newRow() );
        render();
      }
      return;
    }
    const removeBtn = e.target.closest( '.dt-cutsize__remove' );
    if ( removeBtn ) {
      const row  = removeBtn.closest( 'tr' );
      const roll = rolls.find( function ( r ) { return -1 !== r.rows.indexOf( row ); } );
      if ( ! roll ) return;
      if ( roll.rows.length > 1 ) {
        roll.rows = roll.rows.filter( function ( tr ) { return tr !== row; } );
        row.remove();
        render();
      } else {
        // Only cut on this roll: clear it, the roll's row always stays.
        row.querySelector( '.dt-cutsize__size' ).value = '';
        const qtyEl = row.querySelector( '.dt-cutsize__cutqty' );
        if ( qtyEl ) qtyEl.value = '1';
        validateAll();
      }
      return;
    }
    const removeRollBtn = e.target.closest( '.dt-cutsize__removegroup' );
    if ( removeRollBtn ) {
      if ( rolls.length <= 1 ) return; // always keep at least one roll
      const cell = removeRollBtn.closest( '.dt-cutsize__roll-cell' );
      const idx  = rolls.findIndex( function ( r ) { return r.cell === cell; } );
      if ( idx === -1 ) return;
      rolls[ idx ].rows.forEach( function ( tr ) { tr.remove(); } );
      rolls.splice( idx, 1 );
      render();
    }
  } );

  if ( addRollBtn ) {
    addRollBtn.addEventListener( 'click', function () {
      if ( rolls.length >= getQty() ) return;
      rolls.push( { cell: newRollCell(), rows: [ newRow() ] } );
      render();
    } );
  }

  const qtyInput = form.querySelector( '.quantity input[type="number"], input.qty' );
  if ( qtyInput ) {
    qtyInput.addEventListener( 'input', validateAll );
    qtyInput.addEventListener( 'change', validateAll );
  }

  // Variable products: max width follows the selected variation, and
  // WooCommerce may itself rewrite the qty input's value on variation change.
  const variationsForm = document.querySelector( 'form.variations_form' );
  if ( variationsForm && Object.keys( widthMap ).length ) {
    const varIdInput = variationsForm.querySelector( 'input.variation_id, input[name="variation_id"]' );
    if ( varIdInput && typeof jQuery !== 'undefined' ) {
      jQuery( varIdInput ).on( 'change', function () {
        const vid = parseInt( this.value, 10 );
        currentMax = ( vid && widthMap[ vid ] ) ? parseInt( widthMap[ vid ], 10 ) : 0;
        if ( maxNote ) {
          if ( currentMax > 0 ) {
            maxNote.style.display = '';
            maxNote.innerHTML = 'Maximum cuts total up to <strong>' + currentMax + 'mm</strong>.';
          } else {
            maxNote.style.display = 'none';
          }
        }
        syncPresets();
        validateAll();
      } );
    }
    if ( typeof jQuery !== 'undefined' ) {
      jQuery( variationsForm ).on( 'show_variation reset_data', function () {
        setTimeout( validateAll, 0 );
      } );
    }
  }

  render();
}
