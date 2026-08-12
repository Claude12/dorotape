'use strict';

/**
 * Which site the checks point at, resolved the same way everywhere.
 *
 * This used to live in discover.js alone, which is how the suite came to have two
 * answers to one question: discover.js resolved the site from the environment,
 * playwright.config.js read it back out of the plan file, and when the plan was
 * left over from a previous run the two disagreed without saying so. A run then
 * reported confidently about the wrong site. Both now call in here.
 */

const fs = require('fs');
const path = require('path');

/**
 * Load a gitignored .env from the repo root, for running things by hand.
 *
 * Real environment variables always win, so this cannot override what CI passed
 * in: in Actions the file does not exist and the repo's configured variables are
 * the only source. Dependency-free on purpose, because this runs as a pretest
 * step, before anything can rely on a package being installed.
 */
function loadDotEnv() {
  const file = path.join(__dirname, '..', '..', '.env');
  if (!fs.existsSync(file)) return;

  for (const raw of fs.readFileSync(file, 'utf8').split('\n')) {
    const line = raw.trim();
    if (!line || line.startsWith('#')) continue;

    const eq = line.indexOf('=');
    if (eq === -1) continue;

    const key = line.slice(0, eq).trim().replace(/^export\s+/, '');
    if (!key || key in process.env) continue;

    let value = line.slice(eq + 1).trim();
    if (/^(".*"|'.*')$/s.test(value)) value = value.slice(1, -1);
    process.env[key] = value;
  }
}

const trim = (url) => String(url || '').replace(/\/$/, '');

/**
 * SITE_URL first, then DEV_SITE_URL. Returns '' when neither is set.
 *
 * There is deliberately no committed default. Both are environment variables
 * precisely so that a copy of this suite cannot inherit a previous project's site
 * and report a confident green about somebody else's pages.
 */
function siteFromEnv() {
  loadDotEnv();
  return trim(process.env.SITE_URL || process.env.DEV_SITE_URL);
}

const HOW_TO_SET = 'Set one of:\n' +
  '  SITE_URL=https://example.dev npm test\n' +
  '  DEV_SITE_URL in a .env at the repo root (see .env.example)\n' +
  '  the DEV_SITE_URL repository variable, in CI';

/**
 * The site the tests should actually open, given the environment and the plan.
 *
 * The environment wins. A plan naming a different site is a stale plan, and its
 * URL list belongs to that other site, so continuing with either answer would be
 * wrong: the env's site checked against the plan's page list. That is a hard
 * error rather than a preference, because the failure it replaces was silent.
 */
function resolveBaseUrl(plan) {
  const env = siteFromEnv();
  const planned = trim(plan && plan.site);

  if (env && planned && env !== planned) {
    throw new Error(
      `The site to check and the discovery plan disagree.\n` +
      `  environment: ${env}\n` +
      `  plan.json:   ${planned}\n` +
      `plan.json is left over from a run against a different site, and its page ` +
      `list does not describe ${env}.\n` +
      `Run \`npm test\`, which regenerates the plan, or \`node discover.js\` first.`
    );
  }

  return env || planned;
}

module.exports = { loadDotEnv, siteFromEnv, resolveBaseUrl, HOW_TO_SET };
