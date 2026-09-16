/**
 * Category filter bar: auto-apply on change.
 */

export function initFilterBar() {
  const bar = document.querySelector( '.dt-filter-bar' );
  if ( ! bar ) return;
  bar.classList.add( 'dt-filter-bar--auto' ); // hides the no-JS Apply button
  bar.querySelectorAll( '.dt-filter-bar__select' ).forEach( function ( sel ) {
    sel.addEventListener( 'change', function () { bar.submit(); } );
  } );
}
