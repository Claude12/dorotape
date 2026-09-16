function animations() {
  const DEFAULTS = {
    offset: 0.1, // IntersectionObserver threshold
    // Triggers 50px before the element's top edge reaches the bottom of the
    // viewport would, matching the design's FadeIn, so a section starts
    // rising once it is properly on screen rather than at its first pixel.
    rootMargin: '0px 0px -50px 0px',
    once: true, // animate only once (set false to re-animate on re-entry)
  };

  function numAttr(el, attr) {
    const val = parseFloat(el.getAttribute(attr));
    return isNaN(val) ? undefined : val;
  }

  function trigger(el) {
    const delay = numAttr(el, 'animate-delay');
    const duration = numAttr(el, 'animate-duration');

    if (delay !== undefined) {
      el.style.animationDelay = `${delay}ms`;
    }
    if (duration !== undefined) {
      el.style.animationDuration = `${duration}ms`;
    }

    el.classList.add('animated');
  }

  // ─── IntersectionObserver path (modern browsers) ──────────
  function initObserver(elements) {
    // One observer per distinct threshold, shared by every element that
    // uses it, instead of an observer per element.
    const observers = new Map();

    elements.forEach((el) => {
      const offset = numAttr(el, 'animate-offset');
      const threshold = offset !== undefined ? offset : DEFAULTS.offset;

      if (!observers.has(threshold)) {
        observers.set(threshold, new IntersectionObserver((entries, obs) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              trigger(entry.target);
              if (DEFAULTS.once) {
                obs.unobserve(entry.target);
              }
            } else if (!DEFAULTS.once) {
              // re-entering: remove class so it can replay
              entry.target.classList.remove('animated');
            }
          });
        }, { threshold, rootMargin: DEFAULTS.rootMargin }));
      }

      observers.get(threshold).observe(el);
    });
  }

  // ─── Fallback for browsers without IntersectionObserver ───
  function isInViewport(el) {
    const rect = el.getBoundingClientRect();
    const wh = window.innerHeight || document.documentElement.clientHeight;
    const ww = window.innerWidth || document.documentElement.clientWidth;
    const offset = numAttr(el, 'animate-offset') ?? DEFAULTS.offset;
    const visH = rect.height * offset;
    const visW = rect.width * offset;
    return (
      rect.top + visH < wh &&
      rect.left + visW < ww &&
      rect.bottom - visH > 0 &&
      rect.right - visW > 0
    );
  }

  function legacyCheck(elements) {
    elements.forEach((el) => {
      if (!el.classList.contains('animated') && isInViewport(el)) {
        trigger(el);
      }
    });
  }

  function initLegacy(elements) {
    legacyCheck(elements); // check on load

    let tid;
    const onScrollOrResize = () => {
      clearTimeout(tid);
      tid = setTimeout(() => legacyCheck(elements), 50);
    };

    window.addEventListener('scroll', onScrollOrResize);
    window.addEventListener('resize', onScrollOrResize);
  }

  // ─── Init ─────────────────────────────────────────────────
  const elements = Array.from(document.querySelectorAll('[animate]'));

  if (!elements.length) return;

  if ('IntersectionObserver' in window) {
    initObserver(elements);
  } else {
    initLegacy(elements);
  }
}

export default animations;
