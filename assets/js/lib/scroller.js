/**
 * Previous and next arrows for a sideways-scrolling strip.
 *
 * The strips themselves are plain CSS (overflow-x with scroll snap), so they
 * swipe on a phone with or without this file. On a small screen nothing said
 * there was more to the side, and with a mouse there was no way to get there
 * at all (Site Edits, October 2026). The arrows fix both.
 *
 * Markup: a `.js-scroller` wrapping one `.js-scroller-track` and a
 * `.js-scroller-prev` / `.js-scroller-next` pair, which can sit anywhere inside
 * it. One press moves the strip on by most of its visible width, so the card
 * cut off at the edge becomes the first one in view. The arrows hide while
 * everything fits, and each one disables at its own end of the strip.
 */

const STATE_HIDDEN = 'is-hidden';

// Pixels of slack at either end before an arrow counts as at its end, for
// the half pixel a fractional card width leaves behind.
const EDGE = 2;

export function initScrollers() {
  document.querySelectorAll('.js-scroller').forEach(initScroller);
}

function initScroller(scroller) {
  const track = scroller.querySelector('.js-scroller-track');
  const prev = scroller.querySelector('.js-scroller-prev');
  const next = scroller.querySelector('.js-scroller-next');

  if (!track || !prev || !next) {
    return;
  }

  const nav = prev.parentElement;

  function update() {
    const max = track.scrollWidth - track.clientWidth;

    nav.classList.toggle(STATE_HIDDEN, max <= EDGE);
    prev.disabled = track.scrollLeft <= EDGE;
    next.disabled = track.scrollLeft >= max - EDGE;
  }

  function move(direction) {
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    track.scrollBy({
      left: direction * track.clientWidth * 0.8,
      behavior: reducedMotion ? 'auto' : 'smooth',
    });
  }

  prev.addEventListener('click', () => move(-1));
  next.addEventListener('click', () => move(1));
  track.addEventListener('scroll', update, { passive: true });

  if ('ResizeObserver' in window) {
    new window.ResizeObserver(update).observe(track);
  } else {
    window.addEventListener('resize', update);
  }

  update();
}
