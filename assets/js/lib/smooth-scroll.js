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

  // Anchor links: native smooth scroll with header offset.
  // Exclude [data-goto] links: header.js owns those with its own scroll + menu-close logic.
  document.querySelectorAll('a[href^="#"]:not([data-goto])').forEach((link) => {
    link.addEventListener('click', (e) => {
      const hash = link.getAttribute('href');

      // Always prevent default for hash links so the browser doesn't
      // perform its own instant jump (including the bare "#" snap-to-top).
      e.preventDefault();

      if (hash === '#') return;

      // querySelector throws SyntaxError for CSS-invalid IDs (spaces, colons,
      // leading digits), so guard against one bad link crashing all others.
      let target;
      try {
        target = document.querySelector(hash);
      } catch {
        return;
      }
      if (!target) return;

      const top = target.getBoundingClientRect().top + window.scrollY - headerOffset();
      window.scrollTo({ top, behavior: 'smooth' });
    });
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
