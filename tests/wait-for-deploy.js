#!/usr/bin/env node
'use strict';

/**
 * Wait until the site is actually serving the code that was just deployed.
 *
 * The deploy and the checks are two workflows. The deploy finishes, the checks
 * start, and on three occasions (7, 10 and 11 August 2026) the checks came back
 * red on the front page alone with "Execution context was destroyed, most likely
 * because of a navigation" or a flat 45 second timeout, then passed on a re-run of
 * the identical commit. Nothing was wrong with the commit. The board was told the
 * deploy might be broken, twice, and a person had to go and prove it was not.
 *
 * The symptom to notice is that those runs checked exactly one URL. Discovery had
 * not reached the site either, so the plan held only the front page, and the run
 * was one page's worth of failure dressed up as a verdict on the deploy. Which
 * means a fix that only made the tests wait would still leave discovery sampling
 * a site that was not ready.
 *
 * So this runs before discovery, and it asks the one question that has a definite
 * answer: is the theme's own style.css, fetched over HTTP, the version we just
 * pushed? Nothing else is a reliable proxy. An HTTP 200 on the front page arrives
 * long before PHP is serving new code, and a git reset on the server says nothing
 * about what opcache is still holding.
 *
 *   SITE_URL / DEV_SITE_URL   which site (lib/env.js resolves it)
 *   THEME_SLUG                theme directory name, for the style.css path
 *   EXPECT_VERSION            the Version: value to wait for
 *   WAIT_SECONDS              how long to keep trying, default 180
 *
 * Exits 0 as soon as the versions match, and 1 with the two versions printed if
 * the time runs out. A non-zero exit here means the deploy did not land, which is
 * a real failure and should be reported as one - not quietly tested around.
 */

const fs = require('fs');
const path = require('path');
const { siteFromEnv, HOW_TO_SET } = require('./lib/env');

const SITE = siteFromEnv();
if (!SITE) {
  console.error(`No site to wait for. ${HOW_TO_SET}`);
  process.exit(1);
}

const SLUG = process.env.THEME_SLUG || path.basename(path.join(__dirname, '..'));
const WAIT = Number(process.env.WAIT_SECONDS || 180) * 1000;
const EVERY = 5000;

/** The Version: line out of a theme header. */
function versionOf(css) {
  const m = /^[ \t\/*#@]*Version:\s*(.+)$/im.exec(String(css || ''));
  return m ? m[1].trim() : '';
}

const expected = process.env.EXPECT_VERSION
  ? process.env.EXPECT_VERSION.trim()
  : versionOf(fs.readFileSync(path.join(__dirname, '..', 'style.css'), 'utf8'));

if (!expected) {
  console.error(
    'No version to wait for. Set EXPECT_VERSION, or make sure style.css has a Version: line.'
  );
  process.exit(1);
}

const url = `${SITE}/wp-content/themes/${SLUG}/style.css`;

async function servedVersion() {
  try {
    // cache-bust, or a CDN in front of dev answers with the file we are waiting
    // to stop being served.
    const res = await fetch(`${url}?deploy-check=${Date.now()}`, {
      cache: 'no-store',
      headers: { 'User-Agent': 'wp-site-check/1.0', 'Cache-Control': 'no-cache' },
    });
    if (!res.ok) return { error: `HTTP ${res.status}` };
    return { version: versionOf(await res.text()) };
  } catch (e) {
    return { error: e.code || e.message };
  }
}

(async () => {
  const started = Date.now();
  console.log(`Waiting for ${SITE} to serve theme ${SLUG} version ${expected}`);

  let attempt = 0;
  let last = '';

  while (Date.now() - started < WAIT) {
    attempt++;
    const { version, error } = await servedVersion();
    const elapsed = Math.round((Date.now() - started) / 1000);

    if (version === expected) {
      console.log(`Serving ${expected} after ${elapsed}s (${attempt} attempt${attempt === 1 ? '' : 's'}).`);
      process.exit(0);
    }

    const now = error ? `unreachable: ${error}` : version ? `version ${version}` : 'no Version: line';
    if (now !== last) {
      console.log(`  ${String(elapsed).padStart(3)}s  ${now}`);
      last = now;
    }

    await new Promise((r) => setTimeout(r, EVERY));
  }

  const { version, error } = await servedVersion();
  console.error(
    `\nGave up after ${Math.round(WAIT / 1000)}s.\n` +
    `  expected: ${expected}\n` +
    `  serving:  ${error ? `unreachable (${error})` : version || 'no Version: line'}\n` +
    `  url:      ${url}\n` +
    'The deploy has not reached the site, so there is nothing worth checking yet. ' +
    'Look at the deploy run before the checks.'
  );
  process.exit(1);
})();
