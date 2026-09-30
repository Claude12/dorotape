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
  const diagram     = box.querySelector( '.dt-cutsize__diagram' );
  const diagramRows = box.querySelector( '.dt-cutsize__diagram-rolls' );
  const form        = box.closest( 'form.cart' );
  if ( ! body || ! form ) return;

  var widthMap = {};
  try { widthMap = JSON.parse( box.dataset.maxWidths || '{}' ); } catch { /* malformed data attribute, keep the empty default above */ }
  var currentMax = parseInt( box.dataset.maxWidth, 10 ) || 0;

  /* One roll to describe, not a roll per unit ordered. Set by PHP on a product
     priced by the metre (or by a stepped quantity), where the quantity box
     holds a length rather than a count of rolls, so "roll 2 of 50" means
     nothing and the server will not split the add either. */
  const singleRoll = '1' === box.dataset.singleRoll;

  /* Everything this script writes into the page is editable in Theme Settings >
     Cut Sizes and arrives on the box as JSON, beside the roll widths. The
     defaults repeat the shipped wording so the box still reads correctly if the
     attribute is missing or malformed. */
  var STR = {
    left:      '%1$smm of the %2$smm width left over.',
    over:      '%smm more than the roll holds.',
    rowError:  'Cuts add up to %1$smm, wider than the roll (%2$smm)',
    rollLeft:  '%smm left',
    rollOver:  '%smm over',
    tooMany:   'You have entered cut sizes for %1$s rolls but are only ordering %2$s. Remove a roll, or order more.',
    rollLabel: 'Roll %s'
  };
  try {
    var fromPhp = JSON.parse( box.dataset.strings || '{}' );
    Object.keys( STR ).forEach( function ( k ) {
      if ( 'string' === typeof fromPhp[ k ] && fromPhp[ k ] ) STR[ k ] = fromPhp[ k ];
    } );
  } catch { /* malformed data attribute, keep the defaults above */ }

  /* PHP's placeholders, so one field reads the same whether the server or this
     script fills it in: %1$s..%9$s by position, and a bare %s or %d taking the
     next argument in turn. */
  function fmt( tpl ) {
    var args = Array.prototype.slice.call( arguments, 1 );
    var next = 0;
    return String( tpl ).replace( /%(\d+)\$s|%[sd]/g, function ( whole, pos ) {
      var v = pos ? args[ parseInt( pos, 10 ) - 1 ] : args[ next++ ];
      return undefined === v ? whole : String( v );
    } );
  }

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

  /* How many rolls the table may grow to. Ordering 50 metres is not an order
     for 50 rolls, so in single-roll mode the quantity has no say in it. */
  function maxRolls() {
    return singleRoll ? 1 : getQty();
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

  /* The roll quick add is working on. Everything a customer can do to say
     "I am on this roll now" keeps it up to date: typing in a size, adding a
     cut, adding a roll, or a quick add that has run out of room and moved on. */
  var activeRoll = 0;

  /* Quick add moves down the table to sit above whichever roll that is,
     instead of staying at the top where a customer cutting their third roll
     could not see it (Scott, 25 Sept: "when starting cuts on the next roll,
     the feature automatically bounces above the next roll"). It is the same
     element and the same buttons, carried into a row of its own. */
  const presetBar = box.querySelector( '.dt-cutsize__presets' );
  var presetRow   = null;

  if ( presetBar ) {
    var columns = box.querySelectorAll( '.dt-cutsize__table thead th' ).length || 4;
    presetRow   = document.createElement( 'tr' );
    presetRow.className = 'dt-cutsize__presetrow';
    var presetCell = document.createElement( 'td' );
    presetCell.className = 'dt-cutsize__presetrow-cell';
    presetCell.colSpan   = columns;
    presetCell.appendChild( presetBar );
    presetRow.appendChild( presetCell );
  }

  function rollIndexOfRow( tr ) {
    for ( var i = 0; i < rolls.length; i++ ) {
      if ( -1 !== rolls[ i ].rows.indexOf( tr ) ) return i;
    }
    return -1;
  }

  function placePresets() {
    if ( ! presetRow ) return;
    if ( activeRoll >= rolls.length ) activeRoll = rolls.length - 1;
    if ( activeRoll < 0 ) activeRoll = 0;
    body.insertBefore( presetRow, rolls[ activeRoll ].rows[ 0 ] );
  }

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
    placePresets();
    validateAll();
  }

  /* One roll's cuts, in the order they were entered: what the customer has
     typed, read straight off the inputs so the picture, the "left on the
     roll" figure and what gets posted can never disagree. */
  function readCuts( rows ) {
    var cuts = [], sum = 0;
    rows.forEach( function ( tr ) {
      var sizeEl = tr.querySelector( '.dt-cutsize__size' );
      var qtyEl  = tr.querySelector( '.dt-cutsize__cutqty' );
      var size   = parseFloat( sizeEl ? sizeEl.value : '' ) || 0;
      var qty    = qtyEl ? ( parseInt( qtyEl.value, 10 ) || 0 ) : 1;
      var unitEl = tr.querySelector( '.dt-cutsize__unit' );
      if ( size > 0 && qty > 0 ) {
        var mm = toMm( size, unitEl ? unitEl.value : 'mm' );
        cuts.push( { mm: mm, qty: qty, total: mm * qty } );
        sum += mm * qty;
      }
    } );
    return { cuts: cuts, sum: sum };
  }

  function round1( n ) {
    return Math.round( n * 10 ) / 10;
  }

  /* The client's own example: "610, 305 and 150 leaves 155". The number is
     shown twice on purpose, as text beside the boxes being typed into and
     as the pale end of the roll bar, because it is the thing customers get
     wrong and the video showed the machine displaying it too. */
  function renderDiagram() {
    if ( ! diagram || ! diagramRows ) return;

    // Without a stated roll width there is nothing to draw the cuts against.
    if ( ! ( currentMax > 0 ) ) {
      diagram.hidden = true;
      rolls.forEach( function ( roll ) {
        var left = roll.cell.querySelector( '.dt-cutsize__roll-left' );
        if ( left ) left.textContent = '';
      } );
      return;
    }

    diagram.hidden = false;
    diagramRows.textContent = '';

    rolls.forEach( function ( roll, index ) {
      var read = readCuts( roll.rows );
      var over = read.sum > currentMax;
      var left = Math.max( 0, currentMax - read.sum );

      var leftEl = roll.cell.querySelector( '.dt-cutsize__roll-left' );
      if ( leftEl ) {
        leftEl.textContent = over
          ? fmt( STR.rollOver, round1( read.sum - currentMax ) )
          : fmt( STR.rollLeft, round1( left ) );
        leftEl.classList.toggle( 'dt-cutsize__roll-left--bad', over );
      }

      var row = document.createElement( 'div' );
      row.className = 'dt-cutsize__bar-row';

      if ( ! singleRoll ) {
        var label = document.createElement( 'span' );
        label.className = 'dt-cutsize__bar-label';
        label.textContent = fmt( STR.rollLabel, index + 1 );
        row.appendChild( label );
      }

      var bar = document.createElement( 'div' );
      bar.className = 'dt-cutsize__bar' + ( over ? ' dt-cutsize__bar--over' : '' );
      bar.setAttribute( 'role', 'img' );

      // One block per physical cut, which is what the roll actually comes
      // back as. Beyond a dozen the blocks are too thin to read, so past
      // that each size becomes a single block covering all its cuts.
      var pieces = 0;
      read.cuts.forEach( function ( cut ) { pieces += cut.qty; } );
      var perPiece = pieces > 0 && pieces <= 12;
      var described = [];

      read.cuts.forEach( function ( cut ) {
        var blocks = perPiece ? cut.qty : 1;
        var each   = perPiece ? cut.mm : cut.total;
        described.push( ( cut.qty > 1 ? cut.qty + ' x ' : '' ) + round1( cut.mm ) + 'mm' );
        for ( var i = 0; i < blocks; i++ ) {
          var piece = document.createElement( 'span' );
          piece.className = 'dt-cutsize__bar-piece';
          // Over-long cut lists still have to fit the bar, so the scale is
          // the roll width or the cuts, whichever is larger.
          piece.style.flexGrow = String( each );
          piece.textContent = round1( each ) + 'mm';
          bar.appendChild( piece );
        }
      } );

      if ( left > 0 ) {
        var rest = document.createElement( 'span' );
        rest.className = 'dt-cutsize__bar-rest';
        rest.style.flexGrow = String( left );
        rest.textContent = fmt( STR.rollLeft, round1( left ) );
        bar.appendChild( rest );
      }

      bar.setAttribute(
        'aria-label',
        ( singleRoll ? 'Roll of ' : 'Roll ' + ( index + 1 ) + ' of ' ) + currentMax + 'mm: '
          + ( described.length ? described.join( ', ' ) : 'no cuts yet' )
          + ( over
            ? ', ' + round1( read.sum - currentMax ) + 'mm more than the roll holds'
            : ', ' + round1( left ) + 'mm left' )
      );

      row.appendChild( bar );
      diagramRows.appendChild( row );
    } );
  }
  function validateRoll( rows ) {
    // Cuts are entered in mm only; readCuts() keeps the optional legacy unit
    // select resolving rather than throwing.
    var read    = readCuts( rows );
    var sum     = read.sum;
    var any     = read.cuts.length > 0;
    var invalid = any && currentMax > 0 && sum > currentMax;
    rows.forEach( function ( tr, idx ) {
      tr.classList.toggle( 'dt-cutsize__row--invalid', invalid );
      var error = tr.querySelector( '.dt-cutsize__row-error' );
      if ( ! error ) return;
      error.textContent = ( invalid && idx === rows.length - 1 )
        ? fmt( STR.rowError, Math.round( sum * 10 ) / 10, currentMax )
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
    var total = maxRolls();

    /* Per roll the note warns about describing more rolls than are on order.
       With one roll that cannot happen, so it carries the thing that can: how
       much of the width is still free. That reading lived in the Roll column,
       which single-roll mode hides, and the diagram's pale end is too thin to
       read off once only a few mm are left. */
    var note = { text: '', bad: false };
    if ( singleRoll ) {
      var free = rolls.length ? rollLeft( rolls[ 0 ] ) : Infinity;
      if ( isFinite( free ) && readCuts( rolls[ 0 ].rows ).sum > 0 ) {
        note = free < 0
          ? { text: fmt( STR.over, round1( -free ) ), bad: true }
          : { text: fmt( STR.left, round1( free ), currentMax ), bad: false };
      }
    } else if ( cut > total ) {
      note = { text: fmt( STR.tooMany, cut, total ), bad: true };
      ok = false;
    }

    if ( allocNote ) {
      allocNote.classList.toggle( 'dt-cutsize__alloc--bad', note.bad );
      allocNote.textContent = note.text;
    }

    renderDiagram();

    // Nothing to add a roll for once every ordered roll has one.
    if ( addRollBtn ) addRollBtn.disabled = rolls.length >= total;

    const submit = form.querySelector( 'button[type="submit"], .single_add_to_cart_button' );
    if ( submit ) submit.disabled = ! ok;
    return ok;
  }

  /* Preset sizes are a shortcut into the same size inputs, never a separate
     source of truth. A click fills the box the customer last touched, else the
     first empty box on the roll they are working on, else a fresh cut row on
     that same roll.

     The roll matters, and used to be ignored: the search started at roll 1
     every time, so a second 610 added while working on roll 2 jumped back up
     to roll 1 (Scott, 25 Sept). Cuts now stay on the roll in hand for as long
     as it has the width for them, which is also how customers read it: two
     610s come off one 1220 roll, and only the third starts the next one. */
  var lastSize = null;
  body.addEventListener( 'focusin', function ( e ) {
    if ( ! e.target.classList.contains( 'dt-cutsize__size' ) ) return;
    lastSize = e.target;
    var index = rollIndexOfRow( e.target.closest( 'tr' ) );
    if ( -1 !== index && index !== activeRoll ) {
      activeRoll = index;
      placePresets();
    }
  } );

  // What is left of a roll's width. No stated width means no limit to test.
  function rollLeft( roll ) {
    if ( ! ( currentMax > 0 ) ) return Infinity;
    return currentMax - readCuts( roll.rows ).sum;
  }

  function presetTarget( sizeMm ) {
    if ( activeRoll >= rolls.length ) activeRoll = rolls.length - 1;
    if ( activeRoll < 0 ) activeRoll = 0;

    // The roll in hand first, then the ones after it, then a new roll if the
    // order is for more rolls than the table shows.
    var index = -1;
    for ( var i = activeRoll; i < rolls.length; i++ ) {
      // A hair of tolerance, so 2 x 610 still counts as fitting 1220.
      if ( rollLeft( rolls[ i ] ) + 0.001 >= sizeMm ) {
        index = i;
        break;
      }
    }

    if ( -1 === index && rolls.length < maxRolls() ) {
      rolls.push( { cell: newRollCell(), rows: [ newRow() ] } );
      index = rolls.length - 1;
    }

    // Nowhere left to put it: keep it on the roll in hand, where the
    // "wider than the roll" message explains itself.
    if ( -1 === index ) index = activeRoll;

    activeRoll = index;
    var roll   = rolls[ index ];

    if (
      lastSize && body.contains( lastSize ) && ! lastSize.value
      && rollIndexOfRow( lastSize.closest( 'tr' ) ) === index
    ) {
      render();
      return lastSize;
    }

    var empty = null;
    roll.rows.forEach( function ( tr ) {
      var input = tr.querySelector( '.dt-cutsize__size' );
      if ( ! empty && input && ! input.value ) empty = input;
    } );

    if ( empty ) {
      render();
      return empty;
    }

    var row = newRow();
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
    var target = presetTarget( parseInt( btn.dataset.size, 10 ) || 0 );
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
      const index = rollIndexOfRow( tr );
      if ( -1 !== index ) {
        activeRoll = index;
        rolls[ index ].rows.push( newRow() );
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
      if ( activeRoll >= idx ) activeRoll = Math.max( 0, activeRoll - 1 );
      render();
    }
  } );

  if ( addRollBtn ) {
    addRollBtn.addEventListener( 'click', function () {
      if ( rolls.length >= maxRolls() ) return;
      rolls.push( { cell: newRollCell(), rows: [ newRow() ] } );
      activeRoll = rolls.length - 1;
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
