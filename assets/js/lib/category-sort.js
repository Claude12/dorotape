/**
 * The leaf category's Sort by.
 *
 * The shop sorts on the server, because it pages through the catalogue. A
 * leaf category is in the page whole (inc/category-filters.php), so sorting
 * it is putting the same cards back in a different order: no reload, and the
 * filters, the Show more batch and the URL all carry on from where they were.
 *
 * It runs before the filter panel, so a shared link with ?orderby= is already
 * in order when the panel reads the cards. After that the panel is told about
 * each change with a `dt:sorted` event on the grid.
 */

/** Read a numeric data attribute, or null when it is empty. */
function num(el, name) {
  const raw = el.getAttribute(name);
  if (raw === null || raw === '') return null;
  const value = parseFloat(raw);
  return Number.isNaN(value) ? null : value;
}

/**
 * Compare two cards for one order.
 *
 * Mirrors what WooCommerce does on the shop: sales and date high to low,
 * price by the lowest price going up and by the highest coming down. Ties,
 * and the default order, fall back to the order the server sent.
 */
function comparator(orderby) {
  return (a, b) => {
    let diff = 0;

    if (orderby === 'popularity') diff = b.sales - a.sales;
    else if (orderby === 'date') diff = b.date - a.date;
    else if (orderby === 'price' || orderby === 'price-desc') {
      const pa = orderby === 'price' ? a.min : a.max;
      const pb = orderby === 'price' ? b.min : b.max;

      // No price (price on application) goes last in both directions.
      if (pa === null || pb === null) diff = (pa === null) - (pb === null);
      else diff = orderby === 'price' ? pa - pb : pb - pa;
    }

    return diff || a.index - b.index;
  };
}

export function initCategorySort() {
  const bar = document.querySelector('[data-filter-sort]');
  const grid = document.querySelector('[data-filter-grid]');
  if (!bar || !grid) return;

  const select = bar.querySelector('[data-filter-orderby]');
  if (!select) return;

  const cards = Array.prototype.map.call(grid.querySelectorAll('[data-filter-item]'), (el, index) => ({
    el,
    index,
    sales: num(el, 'data-sales') || 0,
    date: num(el, 'data-date') || 0,
    min: num(el, 'data-price-min'),
    max: num(el, 'data-price-max'),
  }));

  if (cards.length < 2) return;

  // The cards share their list with the empty message, so they go back in
  // front of whatever follows the last of them rather than at the end.
  const list = cards[0].el.parentNode;
  const after = cards[cards.length - 1].el.nextSibling;

  const sort = (orderby) => {
    const sorted = cards.slice().sort(comparator(orderby));
    const fragment = document.createDocumentFragment();
    sorted.forEach((card) => fragment.appendChild(card.el));
    list.insertBefore(fragment, after);
  };

  const syncUrl = (orderby) => {
    if (!window.history || !window.history.replaceState) return;

    const params = new URLSearchParams(window.location.search);
    if (orderby === select.options[0].value) params.delete('orderby');
    else params.set('orderby', orderby);

    const query = params.toString();
    window.history.replaceState(
      null,
      '',
      window.location.pathname + (query ? '?' + query : '') + window.location.hash
    );
  };

  // A shared or reloaded link keeps its order.
  const initial = new URLSearchParams(window.location.search).get('orderby');
  if (initial && select.querySelector(`option[value="${CSS.escape(initial)}"]`)) {
    select.value = initial;
    sort(initial);
  }

  select.addEventListener('change', () => {
    sort(select.value);
    syncUrl(select.value);
    grid.dispatchEvent(new CustomEvent('dt:sorted'));
  });

  bar.hidden = false;
}
