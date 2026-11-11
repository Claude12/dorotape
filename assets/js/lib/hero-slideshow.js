/*
 * Hero background slideshow (inc/blocks/hero-block.php, Background Slideshow).
 *
 * The photos behind the hero fade one into the next; the copy never moves.
 * The timing is the progress bar's own CSS animation: when the current bar
 * finishes, the next photo fades in. Holding the bar (animation-play-state)
 * therefore holds the slideshow too, which is how it pauses:
 *
 *   - .is-paused: the visitor pressed the pause button. It stays paused until
 *     they press it again.
 *   - .is-held: the hero is scrolled off screen or the tab is hidden, so the
 *     photos are not changing where nobody can see them.
 *
 * Only the first photo comes with the page. Each later one has its src and
 * srcset in data- attributes, filled in while the photo before it shows, and
 * the slideshow waits for it to load rather than fading to a blank.
 *
 * Each bar is also a button that jumps to its photo. The slideshow then
 * carries on from that photo with a fresh bar; if it was paused, it stays
 * paused on the photo picked.
 *
 * With reduced motion asked for, nothing starts: the first photo stays, and
 * the controls stay hidden. Turning reduced motion on mid-way stops it there.
 */

const FADE = 1400; // Matches --hero-slide-fade in _hero-block.scss.

function fill(img) {
  if (!img || !img.dataset.srcset && !img.dataset.src) {
    return;
  }
  if (img.dataset.srcset) {
    img.srcset = img.dataset.srcset;
    delete img.dataset.srcset;
  }
  if (img.dataset.src) {
    img.src = img.dataset.src;
    delete img.dataset.src;
  }
}

function loaded(img) {
  if (!img || (img.complete && img.naturalWidth)) {
    return Promise.resolve();
  }
  return new Promise((resolve) => {
    img.addEventListener('load', resolve, { once: true });
    // A photo that fails is skipped over rather than holding everything up.
    img.addEventListener('error', resolve, { once: true });
  });
}

function setup(hero) {
  const slides = Array.from(hero.querySelectorAll('.hero-block__slide'));
  const bars = Array.from(hero.querySelectorAll('.hero-block__progress-bar'));
  const buttons = Array.from(hero.querySelectorAll('[data-hero-goto]'));
  const controls = hero.querySelector('[data-hero-controls]');
  const toggle = hero.querySelector('[data-hero-toggle]');
  const motion = window.matchMedia('(prefers-reduced-motion: reduce)');

  if (slides.length < 2 || bars.length !== slides.length || !controls || !toggle) {
    return;
  }

  const imageOf = (i) => slides[i].querySelector('img');
  let current = 0;
  let running = false;

  function setBars() {
    bars.forEach((bar, i) => {
      bar.classList.toggle('is-active', i === current);
      bar.classList.toggle('is-done', i < current);
    });
    buttons.forEach((button, i) => {
      if (i === current) {
        button.setAttribute('aria-current', 'true');
      } else {
        button.removeAttribute('aria-current');
      }
    });
  }

  function restartBar() {
    const bar = bars[current];
    bar.classList.remove('is-active');
    void bar.offsetWidth; // Restart the CSS animation from the start.
    bar.classList.add('is-active');
  }

  // The newest request wins: if a visitor clicks another bar while a photo
  // is still loading, the earlier change is dropped when its photo arrives.
  let request = 0;

  async function goTo(next) {
    if (!running || next === current) {
      return;
    }
    const ticket = ++request;
    const img = imageOf(next);
    fill(img);
    await loaded(img);
    if (!running || ticket !== request || next === current) {
      return;
    }

    // A second change can land mid-fade. Only the photo on screen now stays
    // underneath the new one; anything still fading out from before goes.
    slides.forEach((slide) => slide.classList.remove('is-leaving'));
    const leaving = slides[current];
    leaving.classList.add('is-leaving');
    leaving.classList.remove('is-active');
    slides[next].classList.add('is-active');
    window.setTimeout(() => {
      if (!leaving.classList.contains('is-active')) {
        leaving.classList.remove('is-leaving');
      }
    }, FADE);

    current = next;
    setBars();
    // A fresh six seconds for the new photo. If paused, the bar sits empty
    // and stays held until play is pressed.
    restartBar();
    // Fetch the one after while this one shows.
    fill(imageOf((current + 1) % slides.length));
  }

  function advance() {
    goTo((current + 1) % slides.length);
  }

  function onBarEnd(event) {
    if (event.target === bars[current] && event.animationName === 'hero-block-progress') {
      advance();
    }
  }

  function setPaused(paused) {
    hero.classList.toggle('is-paused', paused);
    toggle.setAttribute('aria-label', paused ? toggle.dataset.labelPlay : toggle.dataset.labelPause);
    toggle.setAttribute('aria-pressed', paused ? 'true' : 'false');
  }

  function start() {
    if (running) {
      return;
    }
    running = true;
    controls.hidden = false;
    setBars();
    restartBar();
    fill(imageOf(1));
  }

  function stop() {
    running = false;
    controls.hidden = true;
    bars.forEach((bar) => bar.classList.remove('is-active', 'is-done'));
  }

  bars.forEach((bar) => bar.addEventListener('animationend', onBarEnd));

  toggle.addEventListener('click', () => setPaused(!hero.classList.contains('is-paused')));

  // Picking a bar shows that photo straight away. The slideshow carries on
  // from there as normal, and a paused one stays paused on the chosen photo.
  buttons.forEach((button) => {
    button.addEventListener('click', () => goTo(Number(button.dataset.heroGoto)));
  });

  let onScreen = true;
  const hold = () => hero.classList.toggle('is-held', !onScreen || document.hidden);
  document.addEventListener('visibilitychange', hold);
  if ('IntersectionObserver' in window) {
    new IntersectionObserver((entries) => {
      onScreen = entries[entries.length - 1].isIntersecting;
      hold();
    }).observe(hero);
  }

  const apply = () => (motion.matches ? stop() : start());
  if (motion.addEventListener) {
    motion.addEventListener('change', apply);
  }
  setPaused(false);
  apply();
}

export function initHeroSlideshow() {
  document.querySelectorAll('[data-hero-slides]').forEach((slides) => {
    const hero = slides.closest('.hero-block');
    if (hero) {
      setup(hero);
    }
  });
}
