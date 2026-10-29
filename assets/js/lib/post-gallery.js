/**
 * Article photos.
 *
 * A blog article shows one photo large with every photo as a thumbnail
 * beside it (single.php). The large one moves on to the next photo every
 * 5 seconds, and picking a thumbnail shows that photo. The thumbnails are
 * links to the full-size files, so without this script they still open the
 * photo.
 *
 * As with the testimonials (lib/testimonial.js), it holds still while the
 * pointer or keyboard focus is on it and never starts for visitors who have
 * asked their system for reduced motion. The thumbnails still work for them.
 */

const INTERVAL = 5000;

const STATE_ACTIVE = 'is-active';

export function initPostGallery() {
  document.querySelectorAll('[data-post-gallery]').forEach(initGallery);
}

function initGallery(gallery) {
  const photos = gallery.querySelectorAll('.post-gallery__photo');
  const thumbs = gallery.querySelectorAll('.post-gallery__thumb');

  if (photos.length < 2 || photos.length !== thumbs.length) {
    return;
  }

  let current = 0;
  let timer = null;
  let hovered = false;
  let focused = false;

  function show(index) {
    current = index;

    photos.forEach((photo, i) => {
      photo.hidden = i !== index;
    });

    thumbs.forEach((thumb, i) => {
      thumb.classList.toggle(STATE_ACTIVE, i === index);

      if (i === index) {
        thumb.setAttribute('aria-current', 'true');
      } else {
        thumb.removeAttribute('aria-current');
      }
    });
  }

  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

  function stop() {
    window.clearInterval(timer);
    timer = null;
  }

  // (Re)start the clock, so a photo picked by hand gets its full 5 seconds.
  function start() {
    stop();

    if (hovered || focused || reducedMotion.matches) {
      return;
    }

    timer = window.setInterval(() => {
      show((current + 1) % photos.length);
    }, INTERVAL);
  }

  thumbs.forEach((thumb, index) => {
    thumb.addEventListener('click', (event) => {
      // Let a new tab or window open the file as the link says.
      if (event.metaKey || event.ctrlKey || event.shiftKey || event.button !== 0) {
        return;
      }

      event.preventDefault();
      show(index);
      start();
    });
  });

  gallery.addEventListener('mouseenter', () => {
    hovered = true;
    stop();
  });

  gallery.addEventListener('mouseleave', () => {
    hovered = false;
    start();
  });

  gallery.addEventListener('focusin', () => {
    focused = true;
    stop();
  });

  gallery.addEventListener('focusout', (event) => {
    if (!gallery.contains(event.relatedTarget)) {
      focused = false;
      start();
    }
  });

  reducedMotion.addEventListener('change', start);

  start();
}
