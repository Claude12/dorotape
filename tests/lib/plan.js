'use strict';

const fs = require('fs');
const path = require('path');

const PLAN = path.join(__dirname, '..', '.artifacts', 'plan.json');

/** Read what discover.js worked out. Never throws - specs skip instead. */
function readPlan() {
  try {
    return JSON.parse(fs.readFileSync(PLAN, 'utf8'));
  } catch {
    return { urls: [], woocommerce: null, missing: true };
  }
}

/**
 * Things WordPress prints when something is wrong but the page still returns 200.
 * A fatal error usually 500s, but notices, warnings and deprecations do not -
 * they just appear in the markup, which is exactly what display_errors on a dev
 * box looks like.
 */
const PHP_DIAGNOSTICS = [
  /Fatal error\s*:/i,
  /Parse error\s*:/i,
  /Warning\s*:\s*[^<]{0,80}\bin\b\s+[^\s<]+\.php/i,
  /Notice\s*:\s*[^<]{0,80}\bin\b\s+[^\s<]+\.php/i,
  /Deprecated\s*:\s*[^<]{0,80}\bin\b\s+[^\s<]+\.php/i,
  /There has been a critical error on this website/i,
  /Error establishing a database connection/i,
];

function phpDiagnostics(html) {
  return PHP_DIAGNOSTICS.filter((re) => re.test(html)).map((re) => {
    const m = html.match(re);
    return m[0].replace(/\s+/g, ' ').slice(0, 160);
  });
}

/**
 * Console noise that is not the site's fault, or not worth failing a deploy over.
 * Kept deliberately short - every entry here is a check we are choosing not to do.
 */
const IGNORED_CONSOLE = [
  /favicon/i,
  /third-party cookie/i,
  /was preloaded using link preload but not used/i,
  // The browser logs this for every failed request, with no URL in the text.
  // Requests are watched separately below, where the URL is available.
  /^Failed to load resource:/i,
];

/**
 * Requests whose failure says nothing about a deploy.
 *
 * 401 and 403 from the REST API are the normal answer to a logged-out visitor:
 * plenty of plugins call their own authenticated endpoints on every page and
 * accept the rejection. Failing on those would make the check permanently red
 * on any site with such a plugin, which is most of them.
 */
function ignorableRequest(status, url) {
  if ((status === 401 || status === 403) && /\/wp-json\//.test(url)) return true;
  if (/\/favicon\.ico/.test(url)) return true;
  return false;
}

/**
 * Collect real JS errors and failed requests from a page. Returns an array you
 * assert on after navigation.
 *
 * Uncaught exceptions matter most - one can stop every later script on the page.
 */
function watchConsole(page) {
  const errors = [];

  page.on('console', (msg) => {
    if (msg.type() !== 'error') return;
    const text = msg.text();
    if (IGNORED_CONSOLE.some((re) => re.test(text))) return;
    errors.push(`console: ${text.replace(/\s+/g, ' ').slice(0, 200)}`);
  });

  page.on('pageerror', (err) => {
    errors.push(`uncaught: ${String(err.message).replace(/\s+/g, ' ').slice(0, 200)}`);
  });

  page.on('response', (res) => {
    const status = res.status();
    if (status < 400) return;
    const url = res.url();
    if (ignorableRequest(status, url)) return;
    errors.push(`request: ${status} ${res.request().method()} ${url.slice(0, 180)}`);
  });

  return errors;
}

module.exports = { readPlan, phpDiagnostics, watchConsole };
