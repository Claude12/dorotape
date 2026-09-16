// Sticky header height, shared with shop-archive-scroll.js so both modules
// land content at the same spot just below the header, not two independently
// tuned magic numbers.
export const HEADER_SCROLL_OFFSET = 123;

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

      const top = target.getBoundingClientRect().top + window.scrollY - HEADER_SCROLL_OFFSET;
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
