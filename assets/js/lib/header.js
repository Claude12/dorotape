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
    if (open) {
      buildDrawerMenu(header, drawer);
    }
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
 * The drawer's menu, copied from the menu row.
 *
 * The server prints the menu once, as the row (inc/header-menu.php). The
 * drawer shows the same tree as an accordion, so it is built from the row
 * the first time the drawer opens; until then it costs the page nothing.
 * The drawer can only be opened from script, so nothing is lost without it.
 *
 * Row to drawer:
 *   top-level link                  link
 *   dropdown or mega panel          toggle, then its "View all" link and
 *                                   its entries
 *   mega group with links           toggle, then its "View all" link (the
 *                                   pane's heading link) and its links
 *   mega group without links        link (or plain text with no page)
 */
function buildDrawerMenu(header, drawer) {
  const source = header.querySelector('.js-header-nav .site-header__nav-list');

  if (!source || drawer.querySelector('.site-header__drawer-list')) {
    return;
  }

  const chevronSource = source.querySelector('.site-header__nav-chevron');
  let count = 0;

  function el(tag, className) {
    const node = document.createElement(tag);
    if (className) {
      node.className = className;
    }
    return node;
  }

  function text(node) {
    return node.textContent.replace(/\s+/g, ' ').trim();
  }

  // A link with the same destination, and the same target, rel, title and
  // aria-current the row's link was printed with.
  function link(from, className) {
    const a = el('a', className);
    ['href', 'target', 'rel', 'title', 'aria-current'].forEach(function (name) {
      if (from.hasAttribute(name)) {
        a.setAttribute(name, from.getAttribute(name));
      }
    });
    a.textContent = text(from);
    return a;
  }

  function item(nodes, className) {
    const li = el('li', className);
    li.append.apply(li, nodes);
    return li;
  }

  // A toggle and the list it opens: [button, ul].
  function section(title, className, overview, entries, depth) {
    const id = 'drawer-sub-' + ++count;
    const button = el('button', className + ' site-header__drawer-toggle js-drawer-toggle');
    const label = el('span');
    const list = el('ul', 'site-header__drawer-submenu site-header__drawer-submenu--' + depth);

    button.type = 'button';
    button.setAttribute('aria-expanded', 'false');
    button.setAttribute('aria-controls', id);
    label.textContent = title;
    button.append(label);

    if (chevronSource) {
      const chevron = chevronSource.cloneNode(true);
      chevron.setAttribute('class', 'site-header__drawer-chevron');
      button.append(chevron);
    }

    list.id = id;

    if (overview) {
      list.append(item([link(overview, 'site-header__drawer-sublink site-header__drawer-sublink--overview')]));
    }

    entries.forEach(function (nodes) {
      list.append(item(nodes, 'site-header__drawer-subitem'));
    });

    return [button, list];
  }

  function group(row) {
    const tab = row.querySelector(':scope > .site-header__mega-tab');
    const links = row.querySelectorAll('.site-header__mega-links a');

    if (!tab) {
      return null;
    }

    if (!links.length) {
      if (tab.tagName === 'A') {
        return [link(tab, 'site-header__drawer-sublink')];
      }
      const span = el('span', 'site-header__drawer-sublink');
      span.textContent = text(tab);
      return [span];
    }

    return section(
      text(tab),
      'site-header__drawer-sublink',
      row.querySelector('.site-header__overview--head'),
      Array.prototype.map.call(links, function (a) {
        return [link(a, 'site-header__drawer-sublink')];
      }),
      1
    );
  }

  const list = el('ul', 'site-header__drawer-list');
  list.id = 'primary-menu-drawer';

  Array.prototype.forEach.call(source.children, function (row) {
    const li = el('li', 'site-header__drawer-item');
    const trigger = row.querySelector(':scope > .js-nav-trigger');
    const panel = row.querySelector(':scope > .js-nav-panel');

    // Same accent and current tab as the row. breadcrumb-route.js moves
    // is-current by data-menu-item, on both copies.
    if (row.classList.contains('site-header__nav-item--mega')) {
      li.classList.add('site-header__drawer-item--mega');
    }
    if (row.classList.contains('is-current')) {
      li.classList.add('is-current');
    }
    if (row.hasAttribute('data-menu-item')) {
      li.setAttribute('data-menu-item', row.getAttribute('data-menu-item'));
    }

    if (!trigger || !panel) {
      const a = row.querySelector(':scope > a');
      if (a) {
        li.append(link(a, 'site-header__drawer-link'));
      }
      list.append(li);
      return;
    }

    const groups = panel.querySelectorAll('.site-header__mega-groups > li');
    const entries = groups.length
      ? Array.prototype.map.call(groups, group).filter(Boolean)
      : Array.prototype.map.call(panel.querySelectorAll('.site-header__dropdown-list > li > a'), function (a) {
        return [link(a, 'site-header__drawer-sublink')];
      });

    li.append.apply(
      li,
      section(
        text(trigger),
        'site-header__drawer-link',
        panel.querySelector(':scope > .site-header__overview, :scope > .site-header__mega-rail > .site-header__overview'),
        entries,
        0
      )
    );
    list.append(li);
  });

  const nav = el('nav');
  const label = source.closest('nav');
  nav.setAttribute('aria-label', label ? label.getAttribute('aria-label') || '' : '');
  nav.append(list);

  const utility = drawer.querySelector('.site-header__drawer-utility');
  if (utility) {
    utility.before(nav);
  } else {
    (drawer.firstElementChild || drawer).append(nav);
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
 * Quick links that do not fit.
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
