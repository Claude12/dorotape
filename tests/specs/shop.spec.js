'use strict';

const { test, expect } = require('@playwright/test');
const { readPlan, phpDiagnostics, watchConsole } = require('../lib/plan');

const plan = readPlan();
const woo = plan.woocommerce;

/**
 * The buying journey, as far as it can go without placing an order.
 *
 * This runs only where WooCommerce is actually installed - discover.js
 * feature-detects it through the Store API, so on a plain WordPress site the
 * whole file skips and says why.
 *
 * It deliberately stops at checkout rendering. Nothing here submits an order:
 * this runs against a client's dev site, and a test that leaves real orders
 * behind is a test people switch off.
 */
test.describe('shop journey', () => {
  test.skip(plan.missing, 'no plan.json - run `node discover.js` first');
  test.skip(!woo, 'not a WooCommerce site');
  test.skip(!!(woo && woo.none), 'WooCommerce is present but nothing purchasable was found');

  test('add to cart, reach checkout', async ({ page }) => {
    const consoleErrors = watchConsole(page);

    await page.goto(woo.path, { waitUntil: 'domcontentloaded' });

    // A variable product needs its options chosen before the button enables.
    if (woo.type !== 'simple') {
      for (const select of await page.locator('form.variations_form select').all()) {
        const values = await select.locator('option').evaluateAll((opts) =>
          opts.map((o) => o.value).filter(Boolean)
        );
        if (values.length) await select.selectOption(values[0]);
      }
    }

    const addToCart = page
      .locator('button.single_add_to_cart_button, .wp-block-button.wc-block-components-product-button button')
      .first();
    await expect(addToCart, `no add-to-cart button on ${woo.path}`).toBeVisible();
    await expect(addToCart).toBeEnabled();
    await addToCart.click();

    // Ask the Store API what is in the cart rather than watching for a toast.
    // page.request shares the browser's cookies, so it sees the same session,
    // and the answer is unambiguous - no guessing at theme notice markup.
    await expect
      .poll(
        async () => {
          const res = await page.request.get('/wp-json/wc/store/v1/cart');
          if (!res.ok()) return -1;
          return (await res.json()).items_count;
        },
        { message: `"${woo.name}" never reached the cart`, timeout: 20_000 }
      )
      .toBeGreaterThan(0);

    await page.goto('/cart/', { waitUntil: 'domcontentloaded' });
    await expect(
      page.getByText(woo.name, { exact: false }).first(),
      `cart page does not show "${woo.name}"`
    ).toBeVisible({ timeout: 15_000 });

    await page.goto('/checkout/', { waitUntil: 'domcontentloaded' });

    // Checkout is the page most likely to break silently - it is a block-based
    // React form on modern Woo, so an empty <div> still returns 200.
    const checkoutReady = page
      .locator('form.woocommerce-checkout, .wc-block-checkout, #billing_email, #email')
      .first();
    await expect(checkoutReady, 'checkout did not render a form').toBeVisible({ timeout: 20_000 });

    const placeOrder = page.locator(
      '#place_order, button.wc-block-components-checkout-place-order-button'
    );
    await expect(placeOrder.first(), 'checkout has no place-order button').toBeVisible({
      timeout: 20_000,
    });
    // Deliberately not clicked. See the note at the top of this file.

    const diagnostics = phpDiagnostics(await page.content());
    expect(diagnostics, `PHP diagnostics at checkout:\n${diagnostics.join('\n')}`).toEqual([]);
    expect(consoleErrors, `JS errors during the shop journey:\n${consoleErrors.join('\n')}`).toEqual([]);
  });
});
