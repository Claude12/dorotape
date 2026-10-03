/**
 * Toasts: the site's transient feedback.
 *
 * One stack, bottom-right on a desktop and across the bottom of a phone, fed
 * from two places. inc/toast.php hands over whatever WooCommerce queued before
 * the page rendered, which is how a single product's Add to cart is confirmed:
 * that form posts and the page reloads, so by the time anything runs here the
 * message is already written. Everything else happens without a reload, so
 * this file listens for the events WooCommerce and YITH fire and raises the
 * toast itself.
 *
 * Styling is assets/scss/components/_toast.scss.
 */

// How long a toast stays before it leaves on its own. Long enough to read a
// product name twice, which is the longest thing that goes in one.
const LIFETIME = 5000;

// More than this on screen at once is a wall rather than a notification, so
// the oldest leaves early to make room. Four is two lines of reading.
const MAX_VISIBLE = 4;

// 24px lucide, the set the rest of the site draws from.
const ICONS = {
  success: '<path d="M21.801 10A10 10 0 1 1 17 3.335"/><path d="m9 11 3 3L22 4"/>',
  wishlist: '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>',
  error: '<circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/>',
  info: '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>',
};

const CLOSE_ICON = '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>';

function svg(paths, className) {
  return (
    '<svg class="' + className + '" viewBox="0 0 24 24" fill="none" stroke="currentColor"' +
    ' stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' +
    paths +
    '</svg>'
  );
}

/*
 * Text from WooCommerce and YITH arrives as a sentence that may carry a
 * product name, and a product name is editor content. It is written with
 * textContent rather than innerHTML everywhere below; this is only for the
 * curly quotes WooCommerce wraps names in, which arrive as entities.
 */
function decode(text) {
  const el = document.createElement('textarea');
  el.innerHTML = text;
  return el.value;
}

let host = null;

// The last sentence raised, and when. Two routes can describe the same event
// (WooCommerce's jQuery hook and its own queued notice, a double-tapped
// button), and the same sentence twice in a row is a bug on screen rather
// than information.
let lastText = '';
let lastAt = 0;
const REPEAT_WINDOW = 1200;

function getHost() {
  if (host && host.isConnected) return host;

  host = document.querySelector('[data-toast-host]');

  // Every page renders the host from inc/toast.php, but a toast raised on a
  // page that somehow has not (a cached fragment, a template that skipped
  // wp_footer) should still appear rather than throw.
  if (!host) {
    host = document.createElement('div');
    host.className = 'toast-host';
    host.setAttribute('data-toast-host', '');
    host.setAttribute('role', 'status');
    host.setAttribute('aria-live', 'polite');
    document.body.appendChild(host);
  }

  return host;
}

/**
 * Raise one toast.
 *
 * @param {Object} options
 * @param {string} options.text    The sentence. Required.
 * @param {string} [options.title] A short bold line above it.
 * @param {string} [options.type]  success | wishlist | error | info.
 * @param {Object} [options.action] { label, url } for the trailing link.
 * @returns {HTMLElement|null}
 */
export function toast(options) {
  const opts = options || {};
  const text = (opts.text || '').trim();
  if (!text) return null;

  const now = Date.now();
  if (text === lastText && now - lastAt < REPEAT_WINDOW) return null;
  lastText = text;
  lastAt = now;

  const type = ICONS[opts.type] ? opts.type : 'success';
  const stack = getHost();

  const el = document.createElement('div');
  el.className = 'toast toast--' + type;

  let markup =
    svg(ICONS[type], 'toast__icon') +
    '<div class="toast__body">' +
    (opts.title ? '<p class="toast__title"></p>' : '') +
    '<p class="toast__text"></p>' +
    '</div>';

  if (opts.action && opts.action.url && opts.action.label) {
    markup += '<a class="toast__action" href="#"></a>';
  }

  markup +=
    '<button class="toast__close" type="button">' +
    svg(CLOSE_ICON, 'toast__close-icon') +
    '<span class="screen-reader-text"></span>' +
    '</button>' +
    '<span class="toast__timer" aria-hidden="true"></span>';

  el.innerHTML = markup;

  // Everything that came from outside this file goes in as text.
  if (opts.title) el.querySelector('.toast__title').textContent = opts.title;
  el.querySelector('.toast__text').textContent = decode(text);

  const action = el.querySelector('.toast__action');
  if (action) {
    action.href = opts.action.url;
    action.textContent = opts.action.label;
  }

  const close = el.querySelector('.toast__close');
  close.querySelector('.screen-reader-text').textContent = 'Dismiss notification';

  stack.appendChild(el);

  // The entry transition needs the element to have been laid out at its
  // starting position first, or it arrives already in place.
  requestAnimationFrame(() => el.classList.add('is-in'));

  let timer = null;
  let done = false;

  function dismiss() {
    if (done) return;
    done = true;
    window.clearTimeout(timer);
    el.classList.remove('is-in');
    el.classList.add('is-out');

    // transitionend would be the tidy way, but it never fires when the
    // element is in a tab that is not visible, which leaves the stack full
    // of toasts when the person comes back to it.
    window.setTimeout(() => el.remove(), 300);
  }

  function start() {
    window.clearTimeout(timer);
    timer = window.setTimeout(dismiss, LIFETIME);
    el.classList.remove('is-paused');
  }

  function pause() {
    window.clearTimeout(timer);
    el.classList.add('is-paused');
  }

  close.addEventListener('click', dismiss);

  // Reading a toast should not be a race. Hovering it, or tabbing into it,
  // holds it until the pointer or the focus leaves.
  el.addEventListener('mouseenter', pause);
  el.addEventListener('mouseleave', start);
  el.addEventListener('focusin', pause);
  el.addEventListener('focusout', start);

  start();

  // Trim the stack from the top, so what arrived last is what stays.
  const open = stack.querySelectorAll('.toast:not(.is-out)');
  for (let i = 0; i < open.length - MAX_VISIBLE; i += 1) {
    open[i].querySelector('.toast__close').click();
  }

  return el;
}

/*
 * Escape closes the newest toast, which is the one convention every
 * dismissible overlay on the web shares.
 */
function bindEscape() {
  document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    const open = document.querySelectorAll('.toast:not(.is-out)');
    if (!open.length) return;
    open[open.length - 1].querySelector('.toast__close').click();
  });
}

/*
 * Whatever WooCommerce had queued when the page was built. inc/toast.php took
 * these out of the notice queue so they would not also print as a band above
 * the content.
 */
function drainServerQueue() {
  const node = document.querySelector('[data-toast-queue]');
  if (!node) return;

  let queued = [];
  try {
    queued = JSON.parse(node.textContent) || [];
  } catch {
    return;
  }

  const cartUrl = window.dorotapeToastData && window.dorotapeToastData.cartUrl;

  queued.forEach((item) => {
    const text = item.text || '';

    // A basket confirmation is the one that earns an action: it is the only
    // one where the obvious next step is somewhere else. "Coupon applied"
    // and the rest are statements, and a link on them is noise.
    const isCart = /basket|cart/i.test(text) && cartUrl;

    toast({
      text,
      type: 'success',
      action: isCart ? { label: 'View basket', url: cartUrl } : null,
    });
  });
}

/*
 * The product name for a card's Add to cart button, for the toast's sentence.
 * The button itself has no name on it, so it comes from the card around it.
 */
function productNameFor(button) {
  if (!button) return '';
  const card = button.closest('.product-card, li.product, .wc-block-grid__product');
  if (!card) return '';
  const name = card.querySelector('.product-card__name, .woocommerce-loop-product__title, h2, h3');
  return name ? name.textContent.trim() : '';
}

/*
 * WooCommerce and YITH both publish their events through jQuery, which is the
 * only way to hear them: a jQuery-triggered event is not a DOM event and does
 * not reach addEventListener.
 */
function bindWooEvents() {
  const $ = window.jQuery;
  if (!$) return;

  const data = window.dorotapeToastData || {};

  $(document.body).on('added_to_cart', (event, fragments, cartHash, $button) => {
    const name = productNameFor($button && $button[0]);

    toast({
      text: name ? '\u201c' + name + '\u201d has been added to your basket.' : 'Added to your basket.',
      type: 'success',
      action: data.cartUrl ? { label: 'View basket', url: data.cartUrl } : null,
    });
  });

  $(document.body).on('removed_from_cart', () => {
    toast({ text: 'Item removed from your basket.', type: 'info' });
  });

}

/*
 * YITH's wishlist, which does not announce itself the way WooCommerce does.
 *
 * Its current button is a React component talking to the REST API, and it
 * fires none of the jQuery events the older one did. What it does do is write
 * its own message into a feedback box beside the button, so that box is what
 * is watched, and the class it carries says which of the two things happened.
 *
 * Watching the box rather than the network also means this keeps working if
 * the plugin is switched back to its classic renderer, which writes to the
 * same place.
 *
 * The plugin's own sentence is not reused for the two cases that matter. It
 * reads "added to your "My wishlist" list!", naming a list this site never
 * shows the person, where the basket says "has been added to your basket."
 * The wording is rebuilt here so both read the same; anything else the plugin
 * has to say is passed through as it is.
 */
function bindWishlist() {
  const data = window.dorotapeToastData || {};
  const SELECTOR = '.yith-wcwl-add-to-wishlist__feedback';

  /*
   * Which boxes have already been spoken for. A set rather than a class on
   * the node: the node belongs to React, which rewrites its class list on
   * every render, so a mark left there does not survive.
   */
  const seen = new WeakSet();

  const show = (node) => {
    if (seen.has(node)) return;
    seen.add(node);

    const added = node.classList.contains('yith-wcwl-add-to-wishlist__feedback--product_added');
    const removed = node.classList.contains('yith-wcwl-add-to-wishlist__feedback--product_removed');

    // Anything else is the plugin telling you nothing changed, most often
    // that the product is already on the list, so the count stays put.
    if (added || removed) bumpWishlistCount(added ? 1 : -1);

    const text = wishlistMessage(node, added, removed);
    if (!text) return;

    toast({
      text,
      type: added ? 'wishlist' : 'info',
      action: added && data.wishlistUrl ? { label: 'View wishlist', url: data.wishlistUrl } : null,
    });
  };

  /*
   * A page without a wishlist button, which is most of them since
   * functions.php only loads it on the product page, has nothing to hear and
   * is not watched at all. The mounts are the React block's and the classic
   * renderer's wrapper.
   */
  if (!document.querySelector('.yith-add-to-wishlist-button-block, .yith-wcwl-add-to-wishlist')) return;

  // Where there is one, the whole document is watched: the feedback box is
  // rendered on demand, in a popover the plugin appends to <body> rather than
  // inside the button, so watching the button alone hears nothing.
  new MutationObserver((mutations) => {
    mutations.forEach((m) => {
      m.addedNodes.forEach((node) => {
        if (node.nodeType !== 1) return;
        if (node.matches && node.matches(SELECTOR)) show(node);
        else if (node.querySelectorAll) node.querySelectorAll(SELECTOR).forEach(show);
      });
    });
  }).observe(document.body, { childList: true, subtree: true });

  /*
   * And anything already on the page. These boxes are written by script, so
   * in practice there are none this early, but the stylesheet hides every one
   * of them from here on: a box that arrived before the observer did would
   * otherwise be hidden with nothing said in its place.
   */
  document.querySelectorAll(SELECTOR).forEach(show);
}

/*
 * The sentence a wishlist toast reads out.
 *
 * The two cases the site cares about are rebuilt to match the basket's
 * wording. For the rest, the plugin's own sentence is kept, with its
 * exclamation mark settled down to a full stop so it sits with the others.
 */
function wishlistMessage(node, added, removed) {
  if (added || removed) {
    const title = document.querySelector('.product_title');
    const name = title ? title.textContent.trim() : '';
    const where = added ? 'added to' : 'removed from';

    return name
      ? '\u201c' + name + '\u201d has been ' + where + ' your wishlist.'
      : (added ? 'Added to' : 'Removed from') + ' your wishlist.';
  }

  return (node.textContent || '').trim().replace(/!+$/, '.');
}

/*
 * The header's wishlist count, moved by one.
 *
 * The basket count updates itself: WooCommerce replaces it as a cart fragment
 * (dorotape_toast_cart_fragment() in inc/toast.php). YITH has no equivalent,
 * and asking the server for a number nobody is looking at costs a request, so
 * the count is stepped here and the true figure arrives with the next page.
 * It cannot drift far: YITH's button stops offering to add a product that is
 * already on the list.
 */
function bumpWishlistCount(by) {
  document.querySelectorAll('.site-header__utility-count--wishlist').forEach((el) => {
    const next = Math.max(0, (parseInt(el.textContent, 10) || 0) + by);
    el.textContent = String(next);
  });
}

export function initToast() {
  /*
   * The stylesheet hangs the plugin-message rule off this class, so that if
   * this bundle never runs the class is never set and YITH's own feedback
   * stays visible: the person still gets an answer, just not a pretty one.
   * On the root element rather than the body because React owns parts of the
   * body and rewrites class lists inside it.
   */
  document.documentElement.classList.add('has-toast-host');

  bindEscape();
  bindWooEvents();
  bindWishlist();
  drainServerQueue();
}

// The product page's own scripts, and anything else that wants to say
// something, raise a toast through this rather than importing the bundle.
window.dorotapeToast = toast;
