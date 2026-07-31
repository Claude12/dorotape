'use strict';

const { test, expect } = require('@playwright/test');
const { readPlan, phpDiagnostics, watchConsole } = require('../lib/plan');

const plan = readPlan();

test.describe('pages load clean', () => {
  test.skip(plan.missing, 'no plan.json - run `node discover.js` first');

  for (const url of plan.urls) {
    test(`${url}`, async ({ page }) => {
      const consoleErrors = watchConsole(page);

      const response = await page.goto(url, { waitUntil: 'domcontentloaded' });
      expect(response, `no response for ${url}`).toBeTruthy();
      expect(response.status(), `${url} returned ${response.status()}`).toBeLessThan(400);

      const html = await page.content();

      // A PHP notice does not change the status code - the page still says 200
      // while printing a warning above the header. Only the markup shows it.
      const diagnostics = phpDiagnostics(html);
      expect(diagnostics, `PHP diagnostics on ${url}:\n${diagnostics.join('\n')}`).toEqual([]);

      // Give deferred and async scripts a moment to run and fail.
      await page.waitForLoadState('load').catch(() => {});
      await page.waitForTimeout(1200);

      expect(consoleErrors, `JS errors on ${url}:\n${consoleErrors.join('\n')}`).toEqual([]);
    });
  }
});
