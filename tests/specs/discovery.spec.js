'use strict';

/**
 * Did this run actually check anything?
 *
 * Every other spec builds its tests by looping over what discovery found. A loop
 * over an empty list declares no tests, and a suite with no tests passes. So the
 * worst outcome the suite had was not a red run, it was a green one: discovery
 * failing to reach the site, the front page alone loading fine, four checks
 * reporting a tick, and the board being told the deploy was verified.
 *
 * These are the assertions nothing else can make, because they are about the run
 * rather than about the site. They belong in a spec and not in discover.js so a
 * bad run still produces a report and still tells the board it failed, instead of
 * the whole workflow dying at the pretest step with no artifact to read.
 */

const { test, expect } = require('@playwright/test');
const { readPlan } = require('../lib/plan');

const plan = readPlan();

test.describe('the run checked something', () => {
  test('discovery produced a plan', () => {
    expect(
      plan.missing,
      'No .artifacts/plan.json. Run `npm test`, which regenerates it, rather than ' +
      '`npx playwright test` on its own.'
    ).toBeFalsy();
  });

  test('discovery named a site', () => {
    expect(plan.site, 'plan.json has no site. Set SITE_URL or DEV_SITE_URL.').toBeTruthy();
  });

  test('discovery found pages beyond the front page', () => {
    // The front page and the shop paths go into plan.urls unconditionally, so
    // plan.urls.length is not the measure here. plan.discovered is.
    expect(
      plan.discovered,
      `Discovery found no pages on ${plan.site} (${plan.source || 'no source'}).\n` +
      'Neither a sitemap nor the REST API returned anything, so the only URL ' +
      'checked was the front page and the other specs had nothing to iterate.\n' +
      'Usually one of: the site was unreachable when discovery ran, the REST API ' +
      'is blocked, or the deploy had not landed yet.'
    ).toBeGreaterThan(0);
  });

  test('a WooCommerce site has something purchasable', () => {
    // Not a shop at all is fine and skips cleanly. A shop with nothing buyable is
    // not fine: it silently skips the entire shop journey, which is the check
    // that covers the money.
    test.skip(!plan.woocommerce, 'not a WooCommerce site');

    expect(
      plan.woocommerce.none,
      `WooCommerce is present on ${plan.site} but the Store API returned nothing ` +
      'purchasable and in stock, so the whole shop journey was skipped.\n' +
      'On a store with a catalogue this is a fault, not an empty state.'
    ).toBeFalsy();
  });
});
