'use strict';

const fs = require('fs');
const path = require('path');

const FILE = path.join(__dirname, '..', 'fixtures', 'baseline.json');

/**
 * Findings are checked against a baseline, not against zero.
 *
 * A real site always starts with existing accessibility violations and missing
 * markup. Failing on all of them means the check is red from day one and
 * everyone learns to ignore it - and an ignored check is worse than no check.
 * So we record what is already there and fail only on what is new, which is
 * what a deploy check is actually for.
 *
 * The baseline is a committed file. Refresh it deliberately with
 * `npm run baseline`, and the diff shows exactly what changed.
 *
 * Entries are namespaced by check, e.g. "a11y /shop/" and "markup ~products".
 */

const { readPlan } = require('./plan');

// pathname -> template, for URLs discover.js only sampled. Read once: the plan
// is written before any test starts and does not change during a run.
let templates = null;
function templateOf(url) {
  if (!templates) templates = readPlan().templates || {};
  return templates[url];
}

/**
 * Which baseline entry a page is recorded under.
 *
 * A page whose type is checked in full gets its own entry, keyed by URL. There
 * is no unseen remainder behind it, so a per-page record is both precise and
 * stable, and that precision is worth having.
 *
 * A page that is one of a sampled many - ten products out of a catalogue - is
 * recorded against its template instead. Keying those by URL looks tidier and
 * cannot work: the ten products visited today are not the ten visited after
 * somebody adds a product, so every run meets pages it has no record for and
 * reports their long-standing problems as brand new. That is exactly what went
 * wrong here, and it is not fixable by refreshing the file, because the next
 * catalogue change breaks it again. Products sharing a template are the same
 * page with different words in it, so the template is the honest unit.
 */
function keyFor(check, url) {
  const template = templateOf(url);
  return template ? `${check} ~${template}` : `${check} ${url}`;
}

function load() {
  try {
    return JSON.parse(fs.readFileSync(FILE, 'utf8'));
  } catch {
    return {};
  }
}

/**
 * Write one page's entries into the shared baseline file.
 *
 * Re-reads immediately before writing because Playwright runs specs in parallel
 * workers - separate processes, each holding its own copy. Without the re-read
 * the last worker to finish overwrites everyone else's pages. `npm run baseline`
 * also pins --workers=1, but this stays correct either way.
 */
function save(check, url, entries) {
  const current = load();
  const key = keyFor(check, url);

  // Merge rather than replace. A template entry is written once per sampled
  // page, so replacing would leave only whichever product finished last, and a
  // baseline recorded from ten products would describe one. Recording starts
  // from an empty file (see reset), so merging cannot accumulate stale entries.
  const merged = new Set([...(current[key] || []), ...entries]);
  current[key] = [...merged].sort();

  // Sorted keys keep the diff readable when the baseline is refreshed.
  const sorted = {};
  for (const k of Object.keys(current).sort()) sorted[k] = current[k];

  fs.mkdirSync(path.dirname(FILE), { recursive: true });
  fs.writeFileSync(FILE, JSON.stringify(sorted, null, 2) + '\n');
}

/**
 * Empty the baseline, so a refresh records the site as it is now.
 *
 * Called by discover.js under RESET_BASELINE=1, before any test runs. Without
 * it, save() merging would make a refresh additive only, and a fixed violation
 * would stay accepted forever.
 */
function reset() {
  fs.rmSync(FILE, { force: true });
}

/** Entries present now that were not recorded for this page or its template. */
function newSince(check, url, entries) {
  const known = new Set(load()[keyFor(check, url)] || []);
  return entries.filter((e) => !known.has(e));
}

/**
 * Where a violation is, with the parts that identify a row of content removed.
 *
 * axe reports the tightest CSS selector that reaches the element, and on
 * WordPress the tightest selector is usually the one carrying a post id -
 * `.post-1023 > .price` - or an nth-child index counting items in a loop.
 * Both describe which row this is, not what is wrong with it, and both change
 * whenever content does. Left in, the same unchanged fault on the same
 * unchanged template gets a different identity on every page and after every
 * edit, so nothing ever matches what was recorded and the check reports a site
 * full of new problems that are all the same old one.
 */
const locate = (target) =>
  target
    .join(' ')
    // .post-1023, #post-1023, .menu-item-42: a trailing number on a class or
    // id is an object id. This does also flatten the handful of classes where
    // the number means something (.col-6), which costs a little precision -
    // two grid widths would share one entry. Worth it: these entries already
    // describe a whole template, and telling the two apart would mean keeping
    // a list of which numeric suffixes matter, per theme and per plugin.
    .replace(/([#.][A-Za-z_][\w-]*?)-\d+(?=$|[\s>+~:.#[])/g, '$1-N')
    // a[data-product_id="1002"]: when axe can find no useful class it falls
    // back to an attribute, and on a shop the attribute holding a number is
    // almost always an object id. A related-products strip alone produced
    // thirty-three of these, one per product it happened to show that day.
    .replace(/=(["'])\d+\1/g, '=$1N$1')
    // :nth-child(3) counts content, and content moves.
    .replace(/:nth-(child|last-child|of-type|last-of-type)\(\s*\d+\s*\)/g, ':nth-$1(n)');

/** An axe violation's identity: the rule, and where it is. Message text drifts. */
const fingerprint = (violation, node) => `${violation.id} @ ${locate(node.target)}`;

function axeFingerprints(results) {
  const out = [];
  for (const v of results.violations) {
    for (const node of v.nodes) out.push(fingerprint(v, node));
  }
  return [...new Set(out)].sort();
}

/** Turn axe fingerprints back into something readable in a failure message. */
function axeDescribe(results, wanted) {
  const want = new Set(wanted);
  const lines = [];
  for (const v of results.violations) {
    for (const node of v.nodes) {
      if (!want.has(fingerprint(v, node))) continue;
      lines.push(`  ${v.id} (${v.impact || 'unknown'}) - ${v.help}\n    at: ${node.target.join(' ')}`);
    }
  }
  return lines.join('\n');
}

const RECORDING = process.env.UPDATE_BASELINE === '1';

module.exports = { load, save, reset, newSince, axeFingerprints, axeDescribe, RECORDING, FILE };
