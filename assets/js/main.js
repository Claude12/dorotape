import smoothScroll from './lib/smooth-scroll';
import animations from './lib/animations';
import { initHeader } from './lib/header';
import { initTierTable } from './lib/tier-table';
import { initVariationPriceSwap } from './lib/variation-price';
import { initFilterBar } from './lib/filter-bar';
import { initCategoryFilters } from './lib/category-filters';
import { initShopFilters } from './lib/shop-filters';
import { initCutRows } from './lib/cut-size-rows';
import { initQuickAdd } from './lib/quick-add';
import { initQtyStep } from './lib/qty-step';
import { initTestimonials } from './lib/testimonial';

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
  initCategoryFilters,
  initShopFilters,
  initCutRows,
  initQuickAdd,
  initQtyStep,
  initTestimonials,
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
