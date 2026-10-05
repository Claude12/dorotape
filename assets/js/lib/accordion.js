import { headerOffset } from './smooth-scroll';

/**
 * Accordion block: open the item a link points at.
 *
 * Items are native <details>, which open and close on their own. What the
 * browser does not do is open one when the address ends in its id, so a
 * link to /glossary/#surface-energy would land on a closed row. This opens
 * the item (or the item around the target, for an id inside a panel) on
 * load, on hashchange, and before smooth-scroll.js measures an in-page link,
 * and keeps an item in view when opening it closes a long one above.
 */
function openTarget(hash) {
  if (!hash || hash === '#') return null;

  let target;
  try {
    target = document.querySelector(hash);
  } catch {
    return null;
  }
  if (!target) return null;

  const item = target.closest('.accordion-block__item');
  if (!item) return null;

  item.open = true;

  // Skip the scroll reveal on the section around it: mid fade-in-up the
  // section sits 16px low, so the scroll below would be measured short.
  const section = item.closest('[animate]');
  if (section) section.removeAttribute('animate');
  return target;
}

function scrollToTarget(target) {
  const top = target.getBoundingClientRect().top + window.scrollY - headerOffset();
  window.scrollTo({ top, behavior: 'auto' });
}

export function initAccordion() {
  if (!document.querySelector('.accordion-block__item')) return;

  // Arriving on the page with a hash: open the item now, so the browser's own
  // jump lands on it open, then correct for the sticky header once that jump
  // (which happens around load, after this runs) is done.
  const target = openTarget(window.location.hash);
  if (target) {
    const settle = () => requestAnimationFrame(() => scrollToTarget(target));
    if (document.readyState === 'complete') settle();
    else window.addEventListener('load', settle, { once: true });
  }

  window.addEventListener('hashchange', () => {
    const changed = openTarget(window.location.hash);
    if (changed) scrollToTarget(changed);
  });

  // One item open per page (the shared details name): opening an item closes
  // the one that was open, and when that one was above and long, the item
  // just opened is pulled up the page, often out of sight. Bring it back to
  // just under the header. toggle does not bubble, hence the capture.
  document.addEventListener(
    'toggle',
    (e) => {
      const item = e.target;
      if (!item.classList || !item.classList.contains('accordion-block__item') || !item.open) return;
      if (item.getBoundingClientRect().top < headerOffset()) scrollToTarget(item);
    },
    true
  );

  // Capture phase, so the item is open before smooth-scroll.js works out
  // where to scroll to.
  document.addEventListener(
    'click',
    (e) => {
      const link = e.target.closest ? e.target.closest('a[href^="#"]') : null;
      if (link) openTarget(link.getAttribute('href'));
    },
    true
  );
}
