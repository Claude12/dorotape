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
 * Which site to check.
 *
 * SITE_URL wins. Otherwise fall back to devUrl in the project's own monday
 * config - which is per-project and committed, so this stays convenient locally
 * without hardcoding any one site into the test suite. There is deliberately no
 * final default: a copy of this suite with neither set should say so, not
 * quietly check somebody else's site.
 */
function resolveSite() {
  if (process.env.SITE_URL) return process.env.SITE_URL;

  try {
    const cfg = require(path.join(__dirname, '..', '.github', 'monday-config.json'));
    if (cfg.devUrl) return cfg.devUrl;
  } catch { /* no config here - fall through to the error below */ }

  console.error(
    'No site to check.\n' +
    '  Set SITE_URL, e.g. SITE_URL=https://example.dev npm test\n' +
    '  or set "devUrl" in .github/monday-config.json.'
  );
  process.exit(1);
}

const SITE = resolveSite().replace(/\/$/, '');
const SAMPLE = Number(process.env.SAMPLE_SIZE || 24);
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

/** Spread the sample across post types rather than taking the first N of one. */
function spread(groups, total) {
  const out = [];
  const names = Object.keys(groups).filter((k) => groups[k].length);
  if (!names.length) return out;

  const perGroup = Math.max(1, Math.floor(total / names.length));
  for (const name of names) {
    const urls = groups[name];
    // Even stride through the group, so it is not just the newest posts.
    const stride = Math.max(1, Math.floor(urls.length / perGroup));
    for (let i = 0; out.length < total && i < urls.length; i += stride) {
      out.push(urls[i]);
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

  for (const [name, endpoint] of [
    ['pages', '/wp-json/wp/v2/pages?per_page=30&status=publish'],
    ['posts', '/wp-json/wp/v2/posts?per_page=30&status=publish'],
    ['products', '/wp-json/wc/store/v1/products?per_page=30'],
  ]) {
    const list = await getJson(SITE + endpoint);
    if (!Array.isArray(list) || !list.length) continue;
    groups[name] = list.map((item) => item.link || item.permalink).filter(Boolean);
  }

  return groups;
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
    const groups = await collectFromRest();
    const total = Object.values(groups).reduce((n, g) => n + g.length, 0);
    if (!total) return { urls: [], note: 'no sitemap and no REST listings' };
    return { urls: spread(groups, SAMPLE), groups: countOf(groups), note: 'via REST API (no sitemap)' };
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

  return { urls: spread(groups, SAMPLE), groups: countOf(groups), note: `via ${found.index}` };
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
  const { urls, groups, note } = await collectUrls();
  const woo = await findWooProduct();
  const shop = woo ? await findShopPages() : [];

  // The front page is always worth checking, and is the one URL every site has.
  const paths = urls.map((u) => new URL(u).pathname);
  const plan = {
    site: SITE,
    generatedAt: new Date().toISOString(),
    source: note || '',
    counts: groups || {},
    urls: [...new Set(['/', ...paths, ...shop])],
    woocommerce: woo,
  };

  fs.mkdirSync(path.dirname(OUT), { recursive: true });
  fs.writeFileSync(OUT, JSON.stringify(plan, null, 2) + '\n');

  console.log(`Site:  ${SITE}`);
  console.log(`Pages: ${plan.urls.length}${note ? ` (${note})` : ''}`);
  if (groups) {
    for (const [k, v] of Object.entries(groups)) console.log(`         ${k}: ${v} found`);
  }
  if (!woo) console.log('Woo:   not a WooCommerce site - the shop journey will skip');
  else if (woo.none) console.log('Woo:   present, but nothing purchasable found - the shop journey will skip');
  else console.log(`Woo:   ${woo.type} product #${woo.id} "${woo.name}" at ${woo.path}`);
})();
