import smoothScroll from './lib/smooth-scroll';
import animations from './lib/animations';
import { initHeader } from './lib/header';
import { initTierTable } from './lib/tier-table';
import { initVariationPriceSwap } from './lib/variation-price';
import { initFilterBar } from './lib/filter-bar';
import { initCutRows } from './lib/cut-size-rows';
import { initQuickAdd } from './lib/quick-add';
import { initQtyStep } from './lib/qty-step';

/*
 * Each feature starts independently. Run bare, a throw in any one of these
 * takes down every later one, which is how a fault in the cut-to-size panel
 * could silently disable the quantity lock on the same page.
 */
const features = [
  smoothScroll,
  animations,
  initHeader,
  initTierTable,
  initVariationPriceSwap,
  initFilterBar,
  initCutRows,
  initQuickAdd,
  initQtyStep,
];

document.addEventListener('DOMContentLoaded', () => {
  features.forEach((init) => {
    try {
      init();
    } catch (e) {
      if (window.console && window.console.error) {
        window.console.error('dorotape: ' + init.name + ' failed', e);
      }
    }
  });
});
