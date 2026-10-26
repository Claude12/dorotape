/**
 * Structure, Suitability, Advantages and Downloads beside a product's
 * description.
 *
 * inc/product-description.php prints every section under its own heading,
 * with the tab bar hidden, so without this file the description reads top to
 * bottom (Site Edits, October 2026). This shows the bar, hides the headings
 * the tabs now say, and shows one section at a time, with the arrow keys
 * moving between tabs as they do in any tab list.
 */

const STATE_ENHANCED = 'is-enhanced';

export function initDescriptionTabs() {
  document.querySelectorAll('.js-description-tabs').forEach(initTabs);
}

function initTabs(root) {
  const list = root.querySelector('[role="tablist"]');
  const tabs = Array.from(root.querySelectorAll('[role="tab"]'));

  if (!list || !tabs.length) {
    return;
  }

  const panelOf = (tab) => document.getElementById(tab.getAttribute('aria-controls'));

  function select(tab, focus) {
    tabs.forEach((other) => {
      const on = other === tab;
      const panel = panelOf(other);

      other.setAttribute('aria-selected', on ? 'true' : 'false');
      other.tabIndex = on ? 0 : -1;

      if (panel) {
        panel.hidden = !on;
      }
    });

    if (focus) {
      tab.focus();
    }
  }

  tabs.forEach((tab, index) => {
    const panel = panelOf(tab);

    if (panel) {
      panel.setAttribute('role', 'tabpanel');
      panel.setAttribute('aria-labelledby', tab.id);
      panel.tabIndex = 0;
    }

    tab.addEventListener('click', () => select(tab, false));

    tab.addEventListener('keydown', (event) => {
      const last = tabs.length - 1;
      const next = {
        ArrowRight: index === last ? 0 : index + 1,
        ArrowLeft: 0 === index ? last : index - 1,
        Home: 0,
        End: last,
      }[event.key];

      if (undefined !== next) {
        event.preventDefault();
        select(tabs[next], true);
      }
    });
  });

  select(tabs.find((tab) => 'true' === tab.getAttribute('aria-selected')) || tabs[0], false);

  list.hidden = false;
  root.classList.add(STATE_ENHANCED);
}
