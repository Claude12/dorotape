'use strict';

const { test, expect } = require('@playwright/test');
const { readPlan } = require('../lib/plan');
const baseline = require('../lib/baseline');

const plan = readPlan();

/**
 * The markup a page needs to be findable and to make sense: a title, one main
 * heading, a canonical URL, a meta description. Nothing opinionated about
 * keyword density - only things whose absence is a plain mistake.
 *
 * Baselined like accessibility, for the same reason: a site that has never had
 * an SEO plugin configured is missing all of these everywhere, and a check that
 * is red on every page from day one teaches people to ignore it. What matters
 * for a deploy is a page that had these and now does not.
 */
/**
 * Read everything in one evaluate rather than through locators.
 *
 * A locator's getAttribute auto-waits for the element to appear, so asking a
 * page for a canonical tag it does not have blocks until the test times out -
 * and "missing" is exactly the case being looked for here. In the DOM, absent
 * is just null, immediately.
 */
async function inspect(page) {
  return page.evaluate(() => {
    const issues = [];
    const attr = (sel, name) => {
      const el = document.querySelector(sel);
      return el ? (el.getAttribute(name) || '').trim() : '';
    };

    if (!document.title.trim()) issues.push('no-title');

    const h1s = document.querySelectorAll('h1').length;
    // Zero means nothing tells a reader or a crawler what the page is about.
    // More than one means the document outline is ambiguous.
    if (h1s === 0) issues.push('no-h1');
    else if (h1s > 1) issues.push(`multiple-h1 (${h1s})`);

    if (!attr('link[rel="canonical"]', 'href')) issues.push('no-canonical');
    if (!attr('meta[name="description"]', 'content')) issues.push('no-meta-description');

    return issues;
  });
}

test.describe('basic SEO markup', () => {
  test.skip(plan.missing, 'no plan.json - run `node discover.js` first');

  for (const url of plan.urls) {
    test(`${url}`, async ({ page }) => {
      await page.goto(url, { waitUntil: 'domcontentloaded' });
      const issues = await inspect(page);

      if (baseline.RECORDING) {
        baseline.save('markup', url, issues);
        test.info().annotations.push({ type: 'baseline', description: `${issues.length} recorded` });
        return;
      }

      const added = baseline.newSince('markup', url, issues);
      expect(
        added,
        `New markup problems on ${url}: ${added.join(', ')}\n` +
          'If these are intentional, refresh with `npm run baseline`.'
      ).toEqual([]);
    });
  }
});
