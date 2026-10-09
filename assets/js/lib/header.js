/**
 * Site header behaviour.
 *
 * One module for the whole header, replacing the scaffold's mobile-nav.js,
 * dropdown-nav.js and header-search.js. Those three each owned a slice of the
 * old markup and each attached their own document-level click listener, so a
 * tap anywhere on the page ran three separate handlers to close things. The
 * new header has a single open/closed state, so it needs a single owner.
 *
 * Three behaviours:
 *   1. Solid-on-scroll, matching the design's `scrollY > 40` threshold.
 *   2. The drawer, shared by the menu button and the phone search button.
 *   3. The menu row's panels (dropdowns and mega panels), for pointer,
 *      touch and keyboard.
 *   4. The drawer's accordion.
 */

const SCROLL_THRESHOLD = 40;

// Matches the `desktop` tier in abstracts/_mixins.scss, where the menu row
// appears and the drawer is hidden by CSS.
const DESKTOP_BREAKPOINT = 1024;

// Hover delays. Opening waits a moment so a pointer crossing the row on its
// way to the search box does not flash every panel; closing waits longer so
// a diagonal move from the label into the panel survives a brief exit.
const HOVER_OPEN_DELAY = 80;
const HOVER_CLOSE_DELAY = 220;
// A mega group is chosen when the pointer rests on it, not as it passes over
// it heading for the links on the right.
const GROUP_INTENT_DELAY = 120;

// Hooks are js- classes, never styled; state is is- classes, which the SCSS
// reads (layout/_header.scss). Keeping the two apart means a restyle can
// rename a BEM class without breaking the behaviour, and the reverse.
const STATE_SOLID = 'is-solid';
const STATE_DRAWER_OPEN = 'is-drawer-open';
const STATE_OPEN = 'is-open';
const STATE_ACTIVE = 'is-active';

export function initHeader() {
  const header = document.querySelector('.js-site-header');

  if (!header) {
    return;
  }

  initScrollState(header);
  initDrawer(header);
  initPanels(header);
  initShortcuts(header);
  initDrawerAccordion(header);
}

/**
 * Add .is-solid once the page has scrolled past the threshold.
 *
 * The read is deferred to an animation frame because scroll fires far more
 * often than the page paints, and window.scrollY forces a layout flush. One
 * read per frame is all the class can ever act on.
 */
function initScrollState(header) {
  let ticking = false;

  function apply() {
    ticking = false;
    header.classList.toggle(
      STATE_SOLID,
      window.scrollY > SCROLL_THRESHOLD
    );
  }

  window.addEventListener(
    'scroll',
    function () {
      if (!ticking) {
        ticking = true;
        window.requestAnimationFrame(apply);
      }
    },
    { passive: true }
  );

  // Set the initial state: a reload part-way down the page, or a link to an
  // anchor, both land already scrolled.
  apply();
}

/**
 * The mobile drawer.
 *
 * Both buttons open the same panel. The search button additionally focuses
 * the field, which is the only difference between them: on a phone the drawer
 * is where the search lives, so a separate search panel would be a second
 * thing to dismiss for no gain.
 */
function initDrawer(header) {
  const drawer = header.querySelector('.js-header-drawer');
  const menuToggle = header.querySelector('.js-header-menu-toggle');
  const searchToggle = header.querySelector('.js-header-search-toggle');
  const toggles = [menuToggle, searchToggle].filter(Boolean);

  if (!drawer || !toggles.length) {
    return;
  }

  function isOpen() {
    return header.classList.contains(STATE_DRAWER_OPEN);
  }

  function setOpen(open, focusSearch) {
    header.classList.toggle(STATE_DRAWER_OPEN, open);
    toggles.forEach(function (button) {
      button.setAttribute('aria-expanded', open ? 'true' : 'false');
      // The menu button's icon changes to a cross, so its label has to change
      // with it. Both strings come from the markup, where they are
      // translatable.
      const label = button.getAttribute(open ? 'data-label-open' : 'data-label-closed');
      if (label) {
        button.setAttribute('aria-label', label);
      }
    });

    if (!open) {
      return;
    }

    if (focusSearch) {
      // FiboSearch builds its input from its own script, so the field may not
      // exist at boot. Looked up on open, and the theme's fallback field is
      // matched too, for when the plugin is off.
      const field = drawer.querySelector(
        '.dgwt-wcas-search-input, .site-header__search-field'
      );
      if (field) {
        field.focus();
      }
    }
  }

  toggles.forEach(function (button) {
    button.addEventListener('click', function () {
      setOpen(!isOpen(), button === searchToggle);
    });
  });

  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape' || !isOpen()) {
      return;
    }

    // The first Escape belongs to FiboSearch, to dismiss its suggestions.
    // Closing the drawer underneath them would take the results away before
    // the user had chosen one.
    const suggestions = document.querySelector('.dgwt-wcas-suggestions-wrapp');
    if (suggestions && suggestions.offsetParent !== null) {
      return;
    }

    setOpen(false);
    if (menuToggle) {
      menuToggle.focus();
    }
  });

  // Close on a click outside the header. FiboSearch appends its suggestions
  // to <body>, so a click on a result is technically outside and would tear
  // the drawer down mid-click.
  document.addEventListener('click', function (e) {
    if (!isOpen() || header.contains(e.target)) {
      return;
    }
    if (
      e.target.closest &&
      e.target.closest('.dgwt-wcas-suggestions-wrapp, .dgwt-wcas-details-wrapp')
    ) {
      return;
    }
    setOpen(false);
  });

  // Rotating a tablet past the desktop breakpoint hides the drawer in CSS while
  // the open class stays behind, so the next tap on the menu button would
  // close something already invisible and appear to do nothing.
  const wide = window.matchMedia('(min-width: ' + DESKTOP_BREAKPOINT + 'px)');
  const onChange = function (e) {
    if (e.matches) {
      setOpen(false);
    }
  };

  if (wide.addEventListener) {
    wide.addEventListener('change', onChange);
  } else if (wide.addListener) {
    // Safari below 14.
    wide.addListener(onChange);
  }
}

/**
 * Panels on the menu row.
 *
 * Every label with children is a <button> (inc/header-menu.php), so a click,
 * a tap, Enter and Space all arrive here as one click event. Hover opens too,
 * for a mouse, but never on its own for touch: a tap fires mouseenter first,
 * and opening on that would make the click that follows close the panel
 * again. Only one panel is open at a time.
 */
function initPanels(header) {
  const nav = header.querySelector('.js-header-nav');

  if (!nav) {
    return;
  }

  const triggers = Array.prototype.slice.call(nav.querySelectorAll('.js-nav-trigger'));

  if (!triggers.length) {
    return;
  }

  let openItem = null;
  let hoverTimer = 0;
  // The last pointer type seen, so hover handlers can stand aside for touch.
  let pointerType = 'mouse';

  nav.addEventListener(
    'pointerdown',
    function (e) {
      pointerType = e.pointerType || 'mouse';
    },
    true
  );
  nav.addEventListener(
    'pointerover',
    function (e) {
      pointerType = e.pointerType || 'mouse';
    },
    true
  );

  function itemOf(trigger) {
    return trigger.parentElement;
  }

  function triggerOf(item) {
    return item.querySelector(':scope > .js-nav-trigger');
  }

  function setOpen(item, open) {
    const trigger = triggerOf(item);
    item.classList.toggle(STATE_OPEN, open);
    trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
  }

  function open(item) {
    if (openItem && openItem !== item) {
      setOpen(openItem, false);
    }
    setOpen(item, true);
    openItem = item;
  }

  function close() {
    if (openItem) {
      setOpen(openItem, false);
      openItem = null;
    }
  }

  function isMouse() {
    return pointerType === 'mouse' || pointerType === 'pen';
  }

  triggers.forEach(function (trigger) {
    const item = itemOf(trigger);

    trigger.addEventListener('click', function (e) {
      window.clearTimeout(hoverTimer);
      // A mouse has usually opened the panel by hover before the click lands,
      // so for a mouse the click only ever opens. Keyboard clicks (detail 0)
      // and taps toggle.
      if (isMouse() && e.detail > 0) {
        open(item);
        return;
      }
      if (openItem === item) {
        close();
      } else {
        open(item);
      }
    });

    item.addEventListener('mouseenter', function () {
      if (!isMouse()) {
        return;
      }
      window.clearTimeout(hoverTimer);
      // Already showing a panel: move straight to the next one, as a menu
      // bar does, rather than closing and reopening with a delay.
      hoverTimer = window.setTimeout(
        function () {
          open(item);
        },
        openItem ? 0 : HOVER_OPEN_DELAY
      );
    });

    item.addEventListener('mouseleave', function () {
      if (!isMouse()) {
        return;
      }
      window.clearTimeout(hoverTimer);
      hoverTimer = window.setTimeout(function () {
        if (openItem === item) {
          close();
        }
      }, HOVER_CLOSE_DELAY);
    });

    item.querySelectorAll('.js-mega-group').forEach(function (group) {
      initMegaGroup(item, group);
    });
  });

  /*
   * A mega panel shows one group's links at a time. Resting the pointer on a
   * group, or tabbing to it, shows that group. A group with its own archive
   * is a link: with a mouse that is simply a link to follow, but on touch
   * there is no hover, so the first tap shows the group and only a second
   * tap on the already-shown group follows it.
   */
  function initMegaGroup(item, group) {
    const tab = group.querySelector(':scope > .js-mega-tab');

    if (!tab) {
      return;
    }

    let intent = 0;

    function activate() {
      item.querySelectorAll('.js-mega-group').forEach(function (other) {
        const active = other === group;
        const otherTab = other.querySelector(':scope > .js-mega-tab');
        other.classList.toggle(STATE_ACTIVE, active);
        if (otherTab && otherTab.tagName === 'BUTTON') {
          otherTab.setAttribute('aria-expanded', active ? 'true' : 'false');
        }
      });
    }

    tab.addEventListener('mouseenter', function () {
      if (!isMouse()) {
        return;
      }
      window.clearTimeout(intent);
      intent = window.setTimeout(activate, GROUP_INTENT_DELAY);
    });

    tab.addEventListener('mouseleave', function () {
      window.clearTimeout(intent);
    });

    tab.addEventListener('focus', activate);

    // Whether the group was already shown when the finger went down. A tap
    // focuses the link before its click fires, and focus shows the group, so
    // by click time every tap would look like a second tap.
    let wasActive = false;
    tab.addEventListener('pointerdown', function () {
      wasActive = group.classList.contains(STATE_ACTIVE);
    });

    tab.addEventListener('click', function (e) {
      if (tab.tagName === 'A' && !isMouse() && !wasActive) {
        e.preventDefault();
      }
      wasActive = true;
      activate();
    });
  }

  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape' || !openItem) {
      return;
    }
    const trigger = triggerOf(openItem);
    close();
    trigger.focus();
  });

  // Tabbing out of the row closes whatever it left open.
  nav.addEventListener('focusout', function (e) {
    if (openItem && e.relatedTarget && !openItem.contains(e.relatedTarget)) {
      close();
    }
  });

  document.addEventListener('click', function (e) {
    if (openItem && !openItem.contains(e.target)) {
      close();
    }
  });
}

/**
 * Category shortcuts that do not fit.
 *
 * CSS hides them by letting them wrap onto a clipped second line, but a
 * clipped link can still take focus. Any link not on the first line is made
 * invisible to the keyboard and to screen readers, and the check reruns
 * whenever the row changes width.
 */
function initShortcuts(header) {
  const list = header.querySelector('.js-nav-shortcuts');

  if (!list || !window.ResizeObserver) {
    return;
  }

  const items = Array.prototype.slice.call(list.children);

  function apply() {
    const top = list.getBoundingClientRect().top;
    items.forEach(function (item) {
      const hidden = item.getBoundingClientRect().top - top > 1;
      item.style.visibility = hidden ? 'hidden' : '';
    });
  }

  new window.ResizeObserver(apply).observe(list);
}

/**
 * The drawer's accordion.
 *
 * Each section is a button followed by its list; CSS reads the button's
 * aria-expanded to show the list, so this only has to flip that attribute.
 * Opening a section closes its siblings, at either level, which keeps the
 * drawer short enough to scan on a phone. Closing a section also closes the
 * groups inside it, so it reopens folded rather than as it was left.
 */
function initDrawerAccordion(header) {
  const drawer = header.querySelector('.js-header-drawer');

  if (!drawer) {
    return;
  }

  drawer.addEventListener('click', function (e) {
    const toggle = e.target.closest('.js-drawer-toggle');

    if (!toggle || !drawer.contains(toggle)) {
      return;
    }

    const expand = toggle.getAttribute('aria-expanded') !== 'true';
    const list = toggle.closest('ul');

    function collapse(button) {
      button.setAttribute('aria-expanded', 'false');
      button.parentElement.querySelectorAll('.js-drawer-toggle').forEach(function (inner) {
        inner.setAttribute('aria-expanded', 'false');
      });
    }

    if (expand && list) {
      list.querySelectorAll(':scope > li > .js-drawer-toggle').forEach(function (sibling) {
        if (sibling !== toggle) {
          collapse(sibling);
        }
      });
    }

    if (expand) {
      toggle.setAttribute('aria-expanded', 'true');
    } else {
      collapse(toggle);
    }
  });
}
