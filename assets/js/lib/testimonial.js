/**
 * Testimonial rotation.
 *
 * Moves the testimonial block (inc/blocks/testimonial-block.php) on to the
 * next quote every 7 seconds, matching the design, and lets the bars under
 * the quote pick one directly.
 *
 * The design rotates unconditionally. Here it holds still while the pointer
 * or keyboard focus is inside the block, so nobody loses a quote halfway
 * through reading it, and never starts for visitors who have asked their
 * system for reduced motion. The bars still work for them.
 */

const INTERVAL = 7000;

const STATE_ACTIVE = 'is-active';

export function initTestimonials() {
  document.querySelectorAll('.js-testimonial').forEach(initTestimonial);
}

function initTestimonial(block) {
  const slides = block.querySelectorAll('.js-testimonial-slide');
  const dots = block.querySelectorAll('.js-testimonial-dot');

  if (slides.length < 2) {
    return;
  }

  let current = 0;
  let timer = null;
  let hovered = false;
  let focused = false;

  function show(index) {
    current = index;

    slides.forEach((slide, i) => {
      slide.classList.toggle(STATE_ACTIVE, i === index);
    });

    dots.forEach((dot, i) => {
      dot.classList.toggle(STATE_ACTIVE, i === index);
      dot.setAttribute('aria-pressed', i === index ? 'true' : 'false');
    });
  }

  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

  function stop() {
    window.clearInterval(timer);
    timer = null;
  }

  // (Re)start the clock, so a quote picked by hand gets its full 7 seconds.
  function start() {
    stop();

    if (hovered || focused || reducedMotion.matches) {
      return;
    }

    timer = window.setInterval(() => {
      show((current + 1) % slides.length);
    }, INTERVAL);
  }

  dots.forEach((dot, i) => {
    dot.addEventListener('click', () => {
      show(i);
      start();
    });
  });

  block.addEventListener('mouseenter', () => {
    hovered = true;
    stop();
  });

  block.addEventListener('mouseleave', () => {
    hovered = false;
    start();
  });

  block.addEventListener('focusin', () => {
    focused = true;
    stop();
  });

  block.addEventListener('focusout', (event) => {
    if (!block.contains(event.relatedTarget)) {
      focused = false;
      start();
    }
  });

  reducedMotion.addEventListener('change', start);

  start();
}
