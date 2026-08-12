'use strict';

const fs = require('fs');
const path = require('path');
const { defineConfig, devices } = require('@playwright/test');
const { resolveBaseUrl } = require('./lib/env');

// discover.js runs as the pretest step and writes this.
let plan = {};
try {
  plan = JSON.parse(fs.readFileSync(path.join(__dirname, '.artifacts', 'plan.json'), 'utf8'));
} catch { /* specs skip themselves when the plan is missing */ }

const CI = !!process.env.CI;

module.exports = defineConfig({
  testDir: __dirname,
  testMatch: 'specs/*.spec.js',
  outputDir: '.artifacts/output',
  fullyParallel: true,
  forbidOnly: CI,
  // One retry absorbs a single slow response on a shared dev box. A check that
  // cries wolf gets ignored, and an ignored check is worse than no check.
  retries: CI ? 1 : 0,
  workers: CI ? 2 : 4,
  timeout: 45_000,
  expect: { timeout: 10_000 },
  reporter: [
    ['list'],
    ['json', { outputFile: '.artifacts/results.json' }],
    ['html', { outputFolder: '.artifacts/report', open: 'never' }],
  ],
  use: {
    // The environment wins over the plan file, and a disagreement throws. The
    // other way round, a stale .artifacts/plan.json silently redirected a whole
    // run at the site it was generated from while the log said otherwise.
    baseURL: resolveBaseUrl(plan),
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'off',
    ignoreHTTPSErrors: true,
    userAgent: 'wp-site-check/1.0 (Playwright)',
  },
  projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
});
