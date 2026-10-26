/**
 * Category buttons over the Case Studies grid.
 *
 * inc/blocks/case-studies-block.php prints the buttons hidden, with each
 * project's category slug on its list item, so without this file the grid
 * shows every project (Site Edits, October 2026). Every project is in the
 * page already, so a press hides the others rather than loading anything.
 */

const STATE_CURRENT = 'is-current';
const STATE_FILTERED = 'is-filtered';

export function initCaseFilter() {
  document.querySelectorAll('.js-case-filter').forEach(initFilter);
}

function initFilter(bar) {
  const grid = bar.parentElement.querySelector('.case-studies-block__grid');

  if (!grid) {
    return;
  }

  const buttons = bar.querySelectorAll('[data-filter]');
  const items = grid.querySelectorAll('[data-category]');

  function apply(slug) {
    buttons.forEach((button) => {
      const on = button.dataset.filter === slug;

      button.classList.toggle(STATE_CURRENT, on);
      button.setAttribute('aria-pressed', on ? 'true' : 'false');
    });

    items.forEach((item) => {
      item.hidden = '' !== slug && item.dataset.category !== slug;
    });

    // A wide project takes two places, so in a filtered grid of two or three
    // it left holes. Filtered, every project takes one.
    grid.classList.toggle(STATE_FILTERED, '' !== slug);
  }

  buttons.forEach((button) => {
    button.addEventListener('click', () => apply(button.dataset.filter));
  });

  bar.hidden = false;
}
