/**
 * The leaf category filter panel.
 *
 * Every product in the category is already in the page, so filtering is
 * showing and hiding: no fetch, no re-render, no spinner. A click is a class
 * change on the cards that no longer match, which is why the counts and the
 * result line can update on the same frame as the tick.
 *
 * Three things it does that a plain show/hide would not:
 *
 * - The facet counts are live. Each one is the number of products that would
 *   remain if that box were ticked, given everything else already ticked, so
 *   a customer is never offered a filter that leads to an empty grid. A count
 *   of zero disables the control rather than hiding it, because a colour
 *   vanishing from the list as you tick another one is disorienting.
 * - The state is in the URL. A filtered grid can be sent to a colleague or
 *   kept in a tab, and replaceState rather than pushState so the back button
 *   still leaves the category rather than walking back through every tick.
 * - Below the sidebar breakpoint the panel is a drawer. A twelve colour
 *   checkbox list above the grid would push the products off a phone screen,
 *   which is the opposite of what a filter is for.
 * - Filtering from the foot of a long grid brings the results back into view.
 *   Ticking a box at the bottom of fifty four colours cuts the grid to four,
 *   and the customer is left looking at the footer wondering what happened.
 *   See keepResultsInView().
 */

import { headerOffset } from './smooth-scroll';

/** Breathing room between the sticky header and the heading it stops above. */
const SCROLL_GAP = 24;

/** Whether the customer has asked the operating system for less movement. */
function prefersReducedMotion() {
  return (
    typeof window.matchMedia === 'function' &&
    window.matchMedia('(prefers-reduced-motion: reduce)').matches
  );
}

/**
 * Bring the top of the results back on screen, if a filter has left it above.
 *
 * Filtering is instant and in place, which is what makes it feel fast, but it
 * also means a shorter grid pulls the page out from under whoever asked for
 * it: tick Blues at the bottom of fifty four cards and the browser clamps you
 * to the end of a document that is now four cards long, looking at the footer.
 *
 * Three deliberate rules:
 *
 * - It only moves the page when the results heading has been left above the
 *   sticky header. A filter ticked with the grid already in view does not
 *   scroll, so typing in the search box cannot chase the page around: the
 *   first keystroke brings the heading back, and every one after it is a no
 *   op because the heading is already where it belongs.
 * - It measures a frame late, after the browser has repainted and clamped the
 *   scroll position itself. Measuring in the same frame as the hiding reads a
 *   position the browser is about to change, which is how this kind of thing
 *   ends up scrolling when it did not need to.
 * - It is instant rather than smooth for anyone who has asked for reduced
 *   motion, and for a journey of more than two screens. Chrome animates a
 *   thirty thousand pixel scroll at the same speed as a short one, so from
 *   the foot of a long category the smooth version is a second of blur that
 *   reads as the page having crashed. Near is animated, far is a cut.
 *
 * The landing point clears the header as it actually measures, not a number
 * typed in here, so it stays right at all three breakpoints.
 *
 * @param {Element} anchor Element to put just below the header.
 */
function keepResultsInView(anchor) {
  window.requestAnimationFrame(() => {
    const offset = headerOffset() + SCROLL_GAP;
    const top = anchor.getBoundingClientRect().top;

    // Already on screen: leave the page exactly where the customer put it.
    if (top >= offset) return;

    const target = Math.max(0, top + window.scrollY - offset);
    const far = Math.abs(target - window.scrollY) > window.innerHeight * 2;

    window.scrollTo({
      top: target,
      behavior: prefersReducedMotion() || far ? 'auto' : 'smooth',
    });
  });
}

/** Coalesce several changes in one frame into a single pass over the cards. */
function scheduler(run) {
  let queued = false;

  return function schedule() {
    if (queued) return;
    queued = true;
    window.requestAnimationFrame(() => {
      queued = false;
      run();
    });
  };
}

/** Read the panel's controls into the shape the matcher wants. */
function readState(panel) {
  const facets = {};

  panel.querySelectorAll('[data-filter-facet]').forEach((input) => {
    if (!input.checked) return;
    const taxonomy = input.getAttribute('data-filter-facet');
    if (!facets[taxonomy]) facets[taxonomy] = [];
    facets[taxonomy].push(taxonomy + ':' + input.value);
  });

  const search = panel.querySelector('[data-filter-search]');
  const stock = panel.querySelector('[data-filter-stock]');

  return {
    facets,
    stock: !!(stock && stock.checked),
    search: search ? search.value.trim().toLowerCase() : '',
  };
}

/**
 * Does one card match?
 *
 * Values inside one attribute are OR, attributes between themselves are AND:
 * ticking Blue and Red asks for either, ticking Blue and Gloss asks for both.
 * That is what every shop does, and it is the only reading where adding a
 * second colour widens the results rather than emptying them.
 *
 * @param {Object} item   Card record.
 * @param {Object} state  Panel state.
 * @param {string} except Taxonomy to ignore, when counting what a tick would give.
 */
function matches(item, state, except) {
  if (state.stock && item.stock !== 'in') return false;

  if (state.search && item.search.indexOf(state.search) === -1) return false;

  for (const taxonomy in state.facets) {
    if (taxonomy === except) continue;

    const wanted = state.facets[taxonomy];
    let hit = false;

    for (let i = 0; i < wanted.length; i += 1) {
      if (item.facets.indexOf(wanted[i]) !== -1) {
        hit = true;
        break;
      }
    }

    if (!hit) return false;
  }

  return true;
}

/** Push the current state into the address bar, or clear it when empty. */
function syncUrl(state) {
  if (!window.history || !window.history.replaceState) return;

  const params = new URLSearchParams();

  Object.keys(state.facets)
    .sort()
    .forEach((taxonomy) => {
      const values = state.facets[taxonomy].map((token) => token.split(':')[1]);
      params.set(taxonomy.replace(/^pa_/, ''), values.join(','));
    });

  if (state.stock) params.set('in-stock', '1');
  if (state.search) params.set('q', state.search);

  const query = params.toString();
  const url = window.location.pathname + (query ? '?' + query : '') + window.location.hash;

  window.history.replaceState(null, '', url);
}

/** Put a shared or reloaded URL back into the controls, before the first pass. */
function restore(panel) {
  const params = new URLSearchParams(window.location.search);
  if (!params.toString()) return;

  panel.querySelectorAll('[data-filter-facet]').forEach((input) => {
    const taxonomy = input.getAttribute('data-filter-facet').replace(/^pa_/, '');
    const raw = params.get(taxonomy);
    if (!raw) return;
    if (raw.split(',').indexOf(input.value) !== -1) input.checked = true;
  });

  const stock = panel.querySelector('[data-filter-stock]');
  if (stock && params.get('in-stock')) stock.checked = true;

  const search = panel.querySelector('[data-filter-search]');
  if (search && params.get('q')) search.value = params.get('q');
}

export function initCategoryFilters() {
  const aside = document.querySelector('[data-category-filters]');
  if (!aside) return;

  const panel = aside.querySelector('form');
  const grid = document.querySelector('[data-filter-grid]');
  if (!panel || !grid) return;

  /*
   * The tokens are read once, here, rather than off the attributes on every
   * pass. On the largest category that is 93 attribute reads instead of 93
   * per keystroke.
   */
  const items = Array.prototype.map.call(grid.querySelectorAll('[data-filter-item]'), (el) => ({
    el,
    facets: ' ' + (el.getAttribute('data-facets') || '') + ' ',
    stock: el.getAttribute('data-stock') || 'out',
    search: el.getAttribute('data-search') || '',
    shown: true,
  }));

  const empty = document.querySelector('[data-filter-empty]');
  const result = panel.querySelector('[data-filter-result]');
  const clear = panel.querySelector('[data-filter-clear]');
  const toggle = document.querySelector('[data-filter-toggle]');
  const activeCount = toggle ? toggle.querySelector('[data-filter-active]') : null;
  const resultOne = result ? result.getAttribute('data-result-one') || '' : '';
  const resultMany = result ? result.getAttribute('data-result-many') || '' : '';

  /*
   * Where a filtered grid is put back. The band's heading rather than the
   * grid itself, so the customer lands on "Select your colour" and the
   * result count above the cards, which is the sentence that explains why
   * the grid just got shorter.
   */
  const resultsTop = document.querySelector('[data-filter-top]') || grid;

  /*
   * The drawer only exists below the sidebar breakpoint, where the Filters
   * button is visible. offsetParent is null once the CSS has hidden that
   * button, so this stays false on a desktop even if the class is left over
   * from a resize.
   */
  const drawerOpen = () =>
    !!toggle && null !== toggle.offsetParent && aside.classList.contains('is-open');

  // The first pass runs at load, restoring a shared URL. A page that scrolls
  // itself on arrival has taken the customer's place in the document away.
  let settled = false;

  const apply = scheduler(() => {
    const state = readState(panel);
    let shown = 0;

    items.forEach((item) => {
      const show = matches(item, state, null);

      if (show !== item.shown) {
        item.shown = show;
        /*
         * hidden, not a class: a filtered out card must leave the tab order
         * and the accessibility tree, not just stop being painted.
         */
        item.el.hidden = !show;
      }

      if (show) shown += 1;
    });

    // What each unticked box would give, with everything else still applied.
    panel.querySelectorAll('[data-filter-facet]').forEach((input) => {
      const taxonomy = input.getAttribute('data-filter-facet');
      const token = taxonomy + ':' + input.value;
      let count = 0;

      items.forEach((item) => {
        if (item.facets.indexOf(' ' + token + ' ') === -1) return;
        if (matches(item, state, taxonomy)) count += 1;
      });

      const label = input.parentNode.querySelector('[data-filter-count]');
      if (label) label.textContent = String(count);

      // Never disable a ticked box: that would trap the filter on.
      input.disabled = count === 0 && !input.checked;
      input.parentNode.classList.toggle('is-empty', input.disabled);
    });

    if (result && resultMany) {
      const template = 1 === shown && resultOne ? resultOne : resultMany;
      result.textContent = template.replace('{n}', String(shown));
    }
    if (empty) empty.hidden = shown !== 0;

    const active =
      Object.keys(state.facets).length + (state.stock ? 1 : 0) + (state.search ? 1 : 0);

    if (clear) clear.hidden = active === 0;

    if (activeCount) {
      activeCount.hidden = active === 0;
      activeCount.textContent = String(active);
    }

    syncUrl(state);

    /*
     * Not while the drawer is open: on a phone the filters sit above the
     * grid, the customer is still choosing, and the count in front of them
     * has already updated. Closing the drawer is the moment they want the
     * cards, and the toggle handler below scrolls then.
     */
    if (settled && !drawerOpen()) keepResultsInView(resultsTop);

    settled = true;
  });

  restore(panel);

  panel.addEventListener('change', apply);
  panel.addEventListener('input', apply);

  // Nothing to submit to: the server already sent the whole category.
  panel.addEventListener('submit', (event) => event.preventDefault());

  if (clear) {
    clear.addEventListener('click', () => {
      panel.reset();
      apply();
    });
  }

  if (toggle) {
    toggle.hidden = false;
    toggle.addEventListener('click', () => {
      const open = aside.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');

      /*
       * Closing is the phone's version of pressing apply: the filters were
       * chosen behind the drawer, and the grid underneath has changed.
       * Opening gets the same treatment against the panel itself, so the
       * controls cannot unfold somewhere off the top of the screen.
       */
      keepResultsInView(open ? aside : resultsTop);
    });
  }

  // The controls can act now, so they can be shown.
  aside.hidden = false;
  apply();
}
