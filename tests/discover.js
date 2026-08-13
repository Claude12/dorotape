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
const { siteFromEnv, HOW_TO_SET } = require('./lib/env');

const SITE = siteFromEnv();
if (!SITE) {
  console.error(`No site to check. ${HOW_TO_SET}`);
  process.exit(1);
}
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
//
// It is a cap on the *listing*, not on the sample, so raising SAMPLE_SIZE does
// not see past it: `npm run baseline` asks for 500 pages and still records from
// at most 30 products, however many the shop has. See tests/README.md.
const LISTING_LIMIT = 30;

// Listings are asked for in a pinned order, because the default is not one.
//
// Both the WordPress and the Store API default to newest-first, so an uncapped
// listing is stable but a *capped* one is a moving window: the 30 newest of 995
// products is a different 30 after anything adds a product, which on this site
// a Sage sync does. spread() then sorts and strides through that window, and a
// stride landing on every third row of a list that shifted by one lands on
// entirely different rows - measured on dev, one added product changed 8 of the
// 8 sampled products. Two runs of the check then have no page in common, and
// the a11y baseline is compared against pages it was never recorded from, so
// known faults on newly-sampled products arrive looking like regressions.
//
// Ascending id is arbitrary but fixed, which is the only property needed here.
const LISTING_ORDER = 'orderby=id&order=asc';
const OUT = path.join(__dirname, '.artifacts', 'plan.json');

/**
 * Every HTTP probe discovery makes, and what came back.
 *
 * A run that finds nothing used to report only that it found nothing, and that
 * one sentence covers causes with nothing in common: the REST API switched off,
 * the site mid-deploy, or the host refusing the machine the check runs on. On
 * 12 August 2026 it was the last of those - the suite found nothing from a
 * GitHub runner while passing from a laptop minutes later - and the board could
 * not have said so, because the status codes were thrown away at the two
 * functions below.
 *
 * So every attempt is recorded with its status, and its body when the answer was
 * not the one wanted. A 403 and a 200 carrying a bot-check page are different
 * problems, and neither looks like a sitemap that is simply absent.
 */
const probes = [];

// Enough of a body to recognise what answered: a WAF page, a login form, a
// hosting holding page. Not enough to fill a monday comment.
const SNIPPET = 80;

function probe(url, status, body) {
  const entry = { url: url.startsWith(SITE) ? url.slice(SITE.length) || '/' : url, status };
  if (body) entry.body = String(body).replace(/\s+/g, ' ').trim().slice(0, SNIPPET);
  probes.push(entry);
}

/**
 * One line naming what answered and how, short enough to survive the trip to
 * monday. summarise.js takes a failure message up to its first blank line and
 * truncates at 300 characters, so this is built here, once, rather than
 * assembled in the spec where the budget is easy to lose track of.
 */
function probesSummary() {
  // Identical answers are collapsed, and query strings dropped. Three sitemap
  // candidates all answering 403 is one fact, not three, and spelling it out
  // three times used up the 300 characters before reaching the REST endpoints -
  // which are the ones that decide whether anything is discovered at all. The
  // full list, query strings and all, stays in plan.probes for the run log.
  const groups = new Map();

  for (const p of probes) {
    const key = `${p.status}|${p.body || ''}`;
    if (groups.has(key)) {
      groups.get(key).also++;
    } else {
      groups.set(key, { url: p.url.split('?')[0], status: p.status, body: p.body, also: 0 });
    }
  }

  return [...groups.values()]
    .map(
      (p) =>
        `${p.url}${p.also ? ` (+${p.also} more)` : ''} ${p.status}` +
        `${p.body ? ` "${p.body.slice(0, 40)}"` : ''}`
    )
    .join('; ');
}

async function get(url) {
  try {
    const res = await fetch(url, {
      redirect: 'follow',
      headers: { 'User-Agent': 'wp-site-check/1.0' },
    });
    const text = await res.text().catch(() => '');
    probe(url, res.status, res.ok ? '' : text);
    return res.ok ? text : null;
  } catch (e) {
    probe(url, e.code || e.name || 'failed', e.message);
    return null;
  }
}

async function getJson(url) {
  try {
    const res = await fetch(url, { headers: { 'User-Agent': 'wp-site-check/1.0' } });
    const text = await res.text().catch(() => '');

    if (!res.ok) {
      probe(url, res.status, text);
      return null;
    }

    // A 200 that is not JSON is the case worth separating out. res.json() threw
    // here and the throw was swallowed as a null, so an endpoint answering with
    // a challenge page, a redirect to a holding page or a PHP fatal was
    // indistinguishable from one that returned an empty list.
    try {
      const json = JSON.parse(text);
      // Recorded on success too, with no body. An endpoint that answers 200 with
      // an empty list is a site with nothing published; one that is never
      // reached at all is a site that refused us. The summary has to be able to
      // show which, so a successful probe cannot be silent.
      probe(url, res.status);
      return json;
    } catch {
      probe(url, `${res.status} not JSON`, text);
      return null;
    }
  } catch (e) {
    probe(url, e.code || e.name || 'failed', e.message);
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
    // compare.
    //
    // Sorting is necessary and not sufficient. It fixes the order of whatever
    // arrived, and says nothing about whether the same rows arrive: a capped
    // listing in the API's default newest-first order is a window that moves,
    // and sorting a window that moved gives a stable order over different
    // content. That is what LISTING_ORDER is for. Both are needed - the pin
    // fixes which rows, the sort fixes where in the stride they land.
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
    ['pages', `/wp-json/wp/v2/pages?per_page=${LISTING_LIMIT}&status=publish&${LISTING_ORDER}`],
    ['posts', `/wp-json/wp/v2/posts?per_page=${LISTING_LIMIT}&status=publish&${LISTING_ORDER}`],
    ['products', `/wp-json/wc/store/v1/products?per_page=${LISTING_LIMIT}&${LISTING_ORDER}`],
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
  //
  // Pinned for the same reason as the listings: unpinned, this is whichever
  // product was added most recently, so the shop check buys a different thing
  // each run and a failure in it cannot be reproduced by running it again.
  for (const query of [`type=simple&per_page=20&${LISTING_ORDER}`, `per_page=20&${LISTING_ORDER}`]) {
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
    // How many pages discovery actually turned up, NOT how many will be visited.
    // The two differ by the front page and the shop paths, which are added here
    // whether or not anything was found. Recorded because a run that discovered
    // nothing still visits '/' and, before specs/discovery.spec.js existed, went
    // green off that one page and reported the site as checked.
    discovered: paths.length,
    // What answered, and how. Read by specs/discovery.spec.js so a run that
    // discovers nothing says why on the board rather than only that it happened.
    probes,
    probesSummary: probesSummary(),
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
  if (!plan.discovered) {
    console.log(
      'WARN:  discovery found no pages, so only the front page would be checked.\n' +
      '       specs/discovery.spec.js fails on this rather than letting the run go green.'
    );
    // Printed in full here, where there is no length limit, as well as going to
    // the board in the shortened form. The run log is where somebody looks once
    // the board has told them to.
    for (const p of plan.probes) {
      console.log(`       ${p.url} -> ${p.status}${p.body ? `  ${p.body}` : ''}`);
    }
  }
  if (groups) {
    for (const [k, v] of Object.entries(groups)) console.log(`         ${k}: ${v} found`);
  }
  if (templated) console.log(`Known: ${templated} sampled, so baselined per template`);
  if (process.env.RESET_BASELINE === '1') console.log('Base:  cleared, ready to record');
  if (!woo) console.log('Woo:   not a WooCommerce site - the shop journey will skip');
  else if (woo.none) console.log('Woo:   present, but nothing purchasable found - the shop journey will skip');
  else console.log(`Woo:   ${woo.type} product #${woo.id} "${woo.name}" at ${woo.path}`);
})();
