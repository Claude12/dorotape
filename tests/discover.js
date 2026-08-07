#!/usr/bin/env node
'use strict';

/**
 * Works out what to check, before Playwright starts.
 *
 * Runs as a pretest step rather than a Playwright globalSetup because spec files
 * are loaded synchronously - they cannot await a sitemap fetch while declaring
 * their tests. So this writes .artifacts/plan.json and the specs just read it.
 *
 * Nothing here is specific to this site. Point SITE_URL at any WordPress install.
 */

const fs = require('fs');
const path = require('path');

/**
 * Load a gitignored .env from the repo root, for running this by hand.
 *
 * Real environment variables always win, so this cannot override what CI
 * passed in - in Actions the file does not exist and the repo's configured
 * variables are the only source. Dependency-free on purpose: this runs as a
 * pretest step, before anything can rely on a dotenv package being installed.
 */
function loadDotEnv() {
  const file = path.join(__dirname, '..', '.env');
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
loadDotEnv();

/**
 * Which site to check. SITE_URL first, then DEV_SITE_URL.
 *
 * There is deliberately no committed default. Both are environment variables
 * precisely so that a copy of this suite cannot inherit a previous project's
 * site and report a confident green about somebody else's pages.
 */
function resolveSite() {
  const url = process.env.SITE_URL || process.env.DEV_SITE_URL;
  if (url) return url;

  console.error(
    'No site to check. Set one of:\n' +
    '  SITE_URL=https://example.dev npm test\n' +
    '  DEV_SITE_URL in a .env at the repo root (see .env.example)\n' +
    '  the DEV_SITE_URL repository variable, in CI'
  );
  process.exit(1);
}

const SITE = resolveSite().replace(/\/$/, '');
/**
 * How many pages a run looks at.
 *
 * A normal run samples, because it has to finish while somebody is waiting for
 * it. `npm run baseline` raises this to visit everything it can list, and that
 * difference is deliberate: a template's record is only as complete as the
 * pages it was recorded from. Record from ten products and a feature that only
 * some products have - a tier pricing table, a gallery - is missing from the
 * record, so the first run that samples a product carrying one reports it as
 * new. Recording from all of them makes any later sample a subset.
 */
const SAMPLE = Number(process.env.SAMPLE_SIZE || 24);

// How many rows a REST listing is asked for. A listing that comes back this
// full is a listing that has been truncated, and the group behind it is bigger
// than anything we are going to look at.
const LISTING_LIMIT = 30;
const OUT = path.join(__dirname, '.artifacts', 'plan.json');

async function get(url) {
  try {
    const res = await fetch(url, {
      redirect: 'follow',
      headers: { 'User-Agent': 'wp-site-check/1.0' },
    });
    return res.ok ? await res.text() : null;
  } catch {
    return null;
  }
}

async function getJson(url) {
  try {
    const res = await fetch(url, { headers: { 'User-Agent': 'wp-site-check/1.0' } });
    if (!res.ok) return null;
    return await res.json();
  } catch {
    return null;
  }
}

const locs = (xml) => [...xml.matchAll(/<loc>\s*([^<\s]+)\s*<\/loc>/g)].map((m) => m[1]);

/**
 * WordPress core has served /wp-sitemap.xml since 5.5. Yoast and RankMath
 * replace it with /sitemap_index.xml, so try both before giving up.
 */
async function findSitemaps() {
  for (const candidate of ['/wp-sitemap.xml', '/sitemap_index.xml', '/sitemap.xml']) {
    const xml = await get(SITE + candidate);
    if (xml && xml.includes('<loc>')) return { index: candidate, children: locs(xml) };
  }
  return null;
}

const countOf = (groups) =>
  Object.fromEntries(Object.entries(groups).map(([k, v]) => [k, v.length]));

/**
 * Spread the sample across post types rather than taking the first N of one.
 *
 * Returns which group each URL came from as well as the URL. The baseline needs
 * it: a page sampled out of thousands cannot be keyed the same way as a page
 * that is checked in full. See lib/baseline.js.
 */
function spread(groups, total) {
  const out = [];
  const names = Object.keys(groups).filter((k) => groups[k].length);
  if (!names.length) return out;

  const perGroup = Math.max(1, Math.floor(total / names.length));
  for (const name of names) {
    // Sort before striding. Neither the REST API nor a sitemap promises a
    // stable order, so an unsorted stride quietly picks a different sample on
    // every run, and two runs of this check then have nothing in common to
    // compare. Sorted, the same catalogue always yields the same sample.
    const urls = [...groups[name]].sort();
    // Even stride through the group, so it is not just the newest posts.
    const stride = Math.max(1, Math.floor(urls.length / perGroup));
    for (let i = 0; out.length < total && i < urls.length; i += stride) {
      out.push({ url: urls[i], group: name });
    }
  }
  return out.slice(0, total);
}

/**
 * Fallback when there is no sitemap - which is the normal case on a dev site.
 * WordPress disables /wp-sitemap.xml entirely when "Discourage search engines"
 * is switched on, so the environment this check exists to watch is precisely the
 * one least likely to have a sitemap. The REST API is on by default and gives
 * the same information.
 */
async function collectFromRest() {
  const groups = {};

  // Names of groups where the listing came back full, so the site holds an
  // unknown number more. Worth knowing: a group we have seen all of can be
  // recorded page by page, and one we have not has to be recorded per template.
  const capped = [];

  for (const [name, endpoint] of [
    ['pages', `/wp-json/wp/v2/pages?per_page=${LISTING_LIMIT}&status=publish`],
    ['posts', `/wp-json/wp/v2/posts?per_page=${LISTING_LIMIT}&status=publish`],
    ['products', `/wp-json/wc/store/v1/products?per_page=${LISTING_LIMIT}`],
  ]) {
    const list = await getJson(SITE + endpoint);
    if (!Array.isArray(list) || !list.length) continue;
    groups[name] = list.map((item) => item.link || item.permalink).filter(Boolean);
    if (list.length >= LISTING_LIMIT) capped.push(name);
  }

  return { groups, capped };
}

/**
 * Shop pages are not in any REST listing and are often excluded from sitemaps
 * (WooCommerce marks cart and checkout noindex), so probe the usual paths.
 */
async function findShopPages() {
  const out = [];
  for (const p of ['/shop/', '/cart/', '/checkout/', '/my-account/']) {
    const res = await fetch(SITE + p, { method: 'HEAD', redirect: 'follow' }).catch(() => null);
    if (res && res.ok) out.push(p);
  }
  return out;
}

async function collectUrls() {
  const found = await findSitemaps();

  if (!found) {
    const { groups, capped } = await collectFromRest();
    const total = Object.values(groups).reduce((n, g) => n + g.length, 0);
    if (!total) return { picks: [], note: 'no sitemap and no REST listings' };
    return {
      picks: spread(groups, SAMPLE),
      groups: countOf(groups),
      capped,
      note: 'via REST API (no sitemap)',
    };
  }

  const groups = {};

  // A sitemap index points at child sitemaps; a flat sitemap points at pages.
  const children = found.children.filter((u) => /\.xml($|\?)/i.test(u));
  if (!children.length) {
    groups.all = found.children;
  } else {
    for (const child of children.slice(0, 12)) {
      const xml = await get(child);
      if (!xml) continue;
      const name = child.split('/').pop().replace(/\.xml.*$/, '');
      const pages = locs(xml).filter((u) => !/\.xml($|\?)/i.test(u));
      if (pages.length) groups[name] = pages;
    }
  }

  // A sitemap lists everything, so nothing here is truncated the way a REST
  // listing is.
  return { picks: spread(groups, SAMPLE), groups: countOf(groups), capped: [], note: `via ${found.index}` };
}

/**
 * Which of the checked URLs stand in for pages nobody is going to check.
 *
 * A group whose every URL is in the sample is checked in full: there is no
 * unseen remainder, so each page can be held to its own record. A group we took
 * ten of thirty from is a sample, and today's ten are not tomorrow's - so the
 * pages in it are recorded against their group instead, and the record covers
 * the whole type rather than the handful that happened to be visited.
 *
 * A capped group counts as sampled even when every URL we know of is checked,
 * because the cap means the listing was cut short and there are more pages we
 * never saw. Without this, a baseline run - which deliberately visits every
 * listed URL - would decide the catalogue was fully covered and go back to
 * recording products one by one, which is the thing being fixed.
 *
 * Returns pathname -> group name, for the sampled ones only. lib/baseline.js
 * reads it; nothing else needs to care.
 */
function templatesFor(picks, counts, capped) {
  const sampled = {};
  for (const p of picks) sampled[p.group] = (sampled[p.group] || 0) + 1;

  const partial = new Set(capped || []);
  const out = {};
  for (const p of picks) {
    if (partial.has(p.group) || sampled[p.group] < (counts || {})[p.group]) {
      out[new URL(p.url).pathname] = p.group;
    }
  }
  return out;
}

/**
 * Is WooCommerce here, and is there something we can actually buy?
 * Feature-detected via the Store API, so this file stays site-agnostic.
 */
async function findWooProduct() {
  const probe = await getJson(`${SITE}/wp-json/wc/store/v1/products?per_page=1`);
  if (!Array.isArray(probe)) return null; // not a WooCommerce site

  // A simple product needs no variation picking, so prefer one. Fall back to a
  // variable product and let the spec choose the first option in each dropdown.
  for (const query of ['type=simple&per_page=20', 'per_page=20']) {
    const list = await getJson(`${SITE}/wp-json/wc/store/v1/products?${query}`);
    if (!Array.isArray(list)) continue;
    const usable = list.find((p) => p.is_purchasable && p.is_in_stock && p.permalink);
    if (usable) {
      return {
        id: usable.id,
        name: usable.name,
        type: usable.type,
        path: new URL(usable.permalink).pathname,
      };
    }
  }
  return { none: true };
}

(async () => {
  const { picks, groups, capped, note } = await collectUrls();
  const woo = await findWooProduct();
  const shop = woo ? await findShopPages() : [];

  // The front page is always worth checking, and is the one URL every site has.
  const paths = picks.map((p) => new URL(p.url).pathname);
  const plan = {
    site: SITE,
    generatedAt: new Date().toISOString(),
    source: note || '',
    counts: groups || {},
    urls: [...new Set(['/', ...paths, ...shop])],
    templates: templatesFor(picks, groups, capped),
    woocommerce: woo,
  };

  fs.mkdirSync(path.dirname(OUT), { recursive: true });
  fs.writeFileSync(OUT, JSON.stringify(plan, null, 2) + '\n');

  // Clearing the baseline belongs here rather than in the specs. Recording adds
  // to what is already on file, so without a clear a refresh could only grow:
  // anything genuinely fixed would stay on the accepted list and keep being
  // forgiven long after it stopped being true. Doing it once, before any test
  // starts, also means parallel workers cannot wipe each other's findings.
  if (process.env.RESET_BASELINE === '1') {
    require('./lib/baseline').reset();
  }

  const templated = Object.keys(plan.templates).length;

  console.log(`Site:  ${SITE}`);
  console.log(`Pages: ${plan.urls.length}${note ? ` (${note})` : ''}`);
  if (groups) {
    for (const [k, v] of Object.entries(groups)) console.log(`         ${k}: ${v} found`);
  }
  if (templated) console.log(`Known: ${templated} sampled, so baselined per template`);
  if (process.env.RESET_BASELINE === '1') console.log('Base:  cleared, ready to record');
  if (!woo) console.log('Woo:   not a WooCommerce site - the shop journey will skip');
  else if (woo.none) console.log('Woo:   present, but nothing purchasable found - the shop journey will skip');
  else console.log(`Woo:   ${woo.type} product #${woo.id} "${woo.name}" at ${woo.path}`);
})();
