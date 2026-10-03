// Fallback sticky header height, for the moment before the header is in the
// DOM or if it ever stops being sticky. Everything should prefer
// headerOffset() below, which measures the real thing.
export const HEADER_SCROLL_OFFSET = 123;

/**
 * How much of the top of the screen the sticky header is covering, right now.
 *
 * Measured rather than tuned. The header is 166px on a desktop and shorter on
 * a phone, and the constant above had drifted behind a redesign, so anchored
 * content was landing 43px underneath it. Reading the height keeps every
 * module that scrolls something into place correct at all three breakpoints
 * and after the next header change.
 */
export function headerOffset() {
  const header = document.querySelector('.js-site-header');

  if (!header) return HEADER_SCROLL_OFFSET;

  const position = window.getComputedStyle(header).position;

  // A header that scrolls away with the page is not covering the target.
  if (position !== 'sticky' && position !== 'fixed') return 0;

  return Math.round(header.getBoundingClientRect().height);
}

function smoothScroll() {
  const scrollToTop = document.getElementById('scroll-to-top');

  // In-page links: smooth scroll with the header offset, then focus.
  //
  // Delegated, so links added after load are covered too. Links other code
  // already answers are left alone: [data-goto] (header.js scrolls and closes
  // the menu), WooCommerce's product tabs and its reviews link (both switch a
  // tab before scrolling, and a second scroll here fought them), and anything
  // acting as a tab or a toggle.
  const owned = '[data-goto], .wc-tabs a, .woocommerce-review-link, [role="tab"], [aria-controls]';
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

  document.addEventListener('click', (e) => {
    const link = e.target.closest ? e.target.closest('a[href^="#"]') : null;
    if (!link || e.defaultPrevented || link.matches(owned)) return;

    const hash = link.getAttribute('href');

    // A bare "#" is a script-driven button, never a destination: stop the
    // browser snapping to the top.
    if (hash === '#') {
      e.preventDefault();
      return;
    }

    // querySelector throws SyntaxError for CSS-invalid IDs (spaces, colons,
    // leading digits), so guard against one bad link crashing all others.
    let target;
    try {
      target = document.querySelector(hash);
    } catch {
      return;
    }
    if (!target) return;

    e.preventDefault();

    const top = target.getBoundingClientRect().top + window.scrollY - headerOffset();
    window.scrollTo({ top, behavior: reduceMotion.matches ? 'auto' : 'smooth' });

    // Move focus with the view. Without it keyboard and screen reader users
    // stay on the link: the skip link scrolled to the content and the next
    // Tab carried on through the header. preventScroll keeps the smooth scroll.
    if (!target.matches('a[href], button, input, select, textarea, [tabindex]')) {
      target.setAttribute('tabindex', '-1');
    }
    target.focus({ preventScroll: true });
  });

  if (!scrollToTop) return;

  // Show/hide scroll-to-top button, passive + rAF so classList.toggle
  // runs at most once per animation frame, not on every scroll event.
  let rafPending = false;
  window.addEventListener('scroll', () => {
    if (rafPending) return;
    rafPending = true;
    requestAnimationFrame(() => {
      scrollToTop.classList.toggle('show', window.scrollY > 2000);
      rafPending = false;
    });
  }, { passive: true });

  scrollToTop.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
}

export default smoothScroll;
