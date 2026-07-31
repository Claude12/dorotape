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
 * Entries are namespaced by check, e.g. "a11y /shop/" and "markup /shop/".
 */

const keyFor = (check, url) => `${check} ${url}`;

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
  current[keyFor(check, url)] = entries;

  // Sorted keys keep the diff readable when the baseline is refreshed.
  const sorted = {};
  for (const k of Object.keys(current).sort()) sorted[k] = current[k];

  fs.mkdirSync(path.dirname(FILE), { recursive: true });
  fs.writeFileSync(FILE, JSON.stringify(sorted, null, 2) + '\n');
}

/** Entries present now that were not recorded for this page. */
function newSince(check, url, entries) {
  const known = new Set(load()[keyFor(check, url)] || []);
  return entries.filter((e) => !known.has(e));
}

/** An axe violation's identity: the rule, and where it is. Message text drifts. */
const fingerprint = (violation, node) => `${violation.id} @ ${node.target.join(' ')}`;

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

module.exports = { load, save, newSince, axeFingerprints, axeDescribe, RECORDING, FILE };
