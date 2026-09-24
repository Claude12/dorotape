/**
 * Category filter bar: auto-apply on change.
 */

export function initFilterBar() {
  // Not the shop page's sort form: inc/shop.php marks that one and
  // assets/js/lib/shop-filters.js submits it, with the fragment that puts the
  // customer back on the grid.
  const bar = document.querySelector( '.dt-filter-bar:not([data-shop-sort])' );
  if ( ! bar ) return;
  bar.classList.add( 'dt-filter-bar--auto' ); // hides the no-JS Apply button
  bar.querySelectorAll( '.dt-filter-bar__select' ).forEach( function ( sel ) {
    sel.addEventListener( 'change', function () { bar.submit(); } );
  } );
}
