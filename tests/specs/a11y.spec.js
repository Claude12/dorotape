'use strict';

const { test, expect } = require('@playwright/test');
const AxeBuilder = require('@axe-core/playwright').default;
const { readPlan } = require('../lib/plan');
const baseline = require('../lib/baseline');

const plan = readPlan();

test.describe('accessibility', () => {
  test.skip(plan.missing, 'no plan.json - run `node discover.js` first');

  for (const url of plan.urls) {
    test(`${url}`, async ({ page }) => {
      await page.goto(url, { waitUntil: 'domcontentloaded' });
      await page.waitForLoadState('load').catch(() => {});

      const results = await new AxeBuilder({ page })
        .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'])
        .analyze();

      const found = baseline.axeFingerprints(results);

      if (baseline.RECORDING) {
        baseline.save('a11y', url, found);
        test.info().annotations.push({ type: 'baseline', description: `${found.length} recorded` });
        return;
      }

      const added = baseline.newSince('a11y', url, found);
      expect(
        added,
        `New accessibility violations on ${url}:\n${baseline.axeDescribe(results, added)}\n\n` +
          'If these are intentional, refresh with `npm run baseline`.'
      ).toEqual([]);
    });
  }
});
