/**
 * Shop page filters.
 *
 * The leaf category pages filter in the browser because the whole category is
 * already rendered. The shop has 995 products, so it filters on the server and
 * the panel is a real form: this file only makes that form pleasanter, and
 * everything it does still works with it switched off.
 *
 * Three jobs. It folds `filter_x[]=a&filter_x[]=b`, which is the only shape a
 * checkbox form can submit, into the `filter_x=a,b` WooCommerce reads and the
 * URL a customer would want to send someone. It closes the panel into its
 * drawer on narrow screens, which the markup cannot ship closed because
 * without a script there would be no way to open it again. And it applies a
 * filter as soon as it is ticked, but only where the panel is a sidebar:
 * inside the drawer, auto-submitting would close it after every single tick.
 */

/**
 * Submit a filter form as a clean URL, landing on the results.
 *
 * The `#products` fragment is the whole of the scroll behaviour. The band
 * carries that id, so the browser puts the customer on the grid rather than at
 * the top of a page whose first screen is the banner they have already read.
 *
 * @param {HTMLFormElement} form The form to submit.
 */
function go(form) {
  const url = new URL(form.getAttribute('action') || window.location.href, window.location.href);
  const params = new URLSearchParams();
  const grouped = new Map();

  new FormData(form).forEach((value, key) => {
    const text = String(value);

    if (key.slice(-2) === '[]') {
      const name = key.slice(0, -2);
      if (!grouped.has(name)) grouped.set(name, []);
      grouped.get(name).push(text);
      return;
    }

    if (text !== '') params.set(key, text);
  });

  grouped.forEach((values, name) => {
    if (values.length) params.set(name, values.join(','));
  });

  const query = params.toString();

  window.location.href = url.origin + url.pathname + (query ? `?${query}` : '') + '#products';
}

/**
 * The sort control above the grid.
 *
 * Its own form, so that it keeps working while the panel is shut. It carries
 * the active filters as hidden fields, so sorting never clears them.
 *
 * @param {HTMLFormElement} sort The toolbar form.
 */
function initSort(sort) {
  // The same class the category filter bar uses to hide its own Apply button
  // once there is a script to submit on change.
  sort.classList.add('dt-filter-bar--auto');

  sort.addEventListener('submit', (event) => {
    event.preventDefault();
    go(sort);
  });

  sort.querySelectorAll('select').forEach((select) => {
    select.addEventListener('change', () => go(sort));
  });
}

/**
 * The filter panel.
 *
 * @param {HTMLElement} aside The panel's container.
 */
function initPanel(aside) {
  const form = aside.querySelector('form');

  if (!form) return;

  const toggle = document.querySelector('[data-shop-filter-toggle]');
  const apply = form.querySelector('.category-filters__apply');

  // Shipped open so that a customer with no JavaScript can reach it. Now that
  // there is a button to open it again, it can close.
  aside.classList.remove('is-open');

  /*
   * Whether the panel is currently the drawer rather than the sidebar.
   *
   * Read off the toggle's own visibility instead of a matchMedia() copy of the
   * breakpoint, so the two can never drift apart: the toggle is display: none
   * from the width the panel becomes a sidebar, and a hidden element has no
   * offsetParent.
   */
  const isDrawer = () => Boolean(toggle) && toggle.offsetParent !== null;

  // Auto-submit replaces the Apply button, but only where it is an
  // improvement. In the drawer every tick would close the panel, so the button
  // stays and the customer chooses when to apply.
  const syncMode = () => {
    if (apply) apply.hidden = !isDrawer();
  };

  // The toggle is revealed before the first syncMode(), because isDrawer()
  // reads its visibility: asked while it still carried the markup's hidden
  // attribute it would answer no on every screen, and the drawer would open
  // with no Apply button and no auto-submit to stand in for one.
  if (toggle) {
    toggle.hidden = false;
    toggle.setAttribute('aria-expanded', 'false');

    toggle.addEventListener('click', () => {
      const open = aside.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  syncMode();
  window.addEventListener('resize', syncMode);

  form.addEventListener('submit', (event) => {
    event.preventDefault();
    go(form);
  });

  form.querySelectorAll('input[type="checkbox"]').forEach((box) => {
    box.addEventListener('change', () => {
      if (!isDrawer()) go(form);
    });
  });
}

export function initShopFilters() {
  const sort = document.querySelector('[data-shop-sort]');
  const aside = document.querySelector('[data-shop-filters]');

  if (sort) initSort(sort);
  if (aside) initPanel(aside);
}
