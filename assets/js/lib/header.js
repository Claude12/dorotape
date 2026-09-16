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
 *   3. Dropdowns on the category row, for pointer and keyboard.
 */

const SCROLL_THRESHOLD = 40;

// Matches the `xl` tier in abstracts/_mixins.scss, where the category row
// appears and the drawer is hidden by CSS.
const XL_BREAKPOINT = 1280;

// Hooks are js- classes, never styled; state is is- classes, which the SCSS
// reads (layout/_header.scss). Keeping the two apart means a restyle can
// rename a BEM class without breaking the behaviour, and the reverse.
const STATE_SOLID = 'is-solid';
const STATE_DRAWER_OPEN = 'is-drawer-open';
const STATE_OPEN = 'is-open';
const STATE_FOCUSED = 'is-focused';

export function initHeader() {
  const header = document.querySelector('.js-site-header');

  if (!header) {
    return;
  }

  initScrollState(header);
  initDrawer(header);
  initDropdowns(header);
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

  // Rotating a tablet past the xl breakpoint hides the drawer in CSS while
  // the open class stays behind, so the next tap on the menu button would
  // close something already invisible and appear to do nothing.
  const wide = window.matchMedia('(min-width: ' + XL_BREAKPOINT + 'px)');
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
 * Dropdowns on the category row.
 *
 * The design has no submenus, but these are WordPress menus and the client's
 * Primary menu already runs three levels deep, so anything they nest has to
 * open rather than silently disappear.
 */
function initDropdowns(header) {
  const nav = header.querySelector('.js-header-nav');

  if (!nav) {
    return;
  }

  const parents = nav.querySelectorAll('.menu-item-has-children');

  if (!parents.length) {
    return;
  }

  parents.forEach(function (item) {
    // Pointer open/close is CSS (:hover). This only adds the keyboard path,
    // plus the .is-focused class that keeps a panel open while tabbing
    // through it.
    const link = item.querySelector(':scope > .site-header__nav-link');

    if (!link) {
      return;
    }

    link.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        item.classList.remove(STATE_OPEN);
        link.focus();
        return;
      }

      if (e.key !== 'Enter' && e.key !== ' ') {
        return;
      }

      // Enter on a parent that points somewhere real should follow the link:
      // a category item links to its own archive, and swallowing that would
      // make the archive unreachable by keyboard.
      const href = link.getAttribute('href');
      if (
        e.key === 'Enter' &&
        !item.classList.contains(STATE_OPEN) &&
        href &&
        href !== '#'
      ) {
        return;
      }

      e.preventDefault();
      item.classList.toggle(STATE_OPEN);
    });
  });

  // Keep the panel open while focus is anywhere inside it.
  nav.addEventListener(
    'focusin',
    function (e) {
      parents.forEach(function (item) {
        item.classList.toggle(STATE_FOCUSED, item.contains(e.target));
      });
    },
    true
  );

  nav.addEventListener('focusout', function (e) {
    if (!nav.contains(e.relatedTarget)) {
      parents.forEach(function (item) {
        item.classList.remove(STATE_FOCUSED, STATE_OPEN);
      });
    }
  });

  document.addEventListener('click', function (e) {
    if (!nav.contains(e.target)) {
      parents.forEach(function (item) {
        item.classList.remove(STATE_OPEN);
      });
    }
  });
}
