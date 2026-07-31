#!/usr/bin/env node
'use strict';

/**
 * Turn a Playwright run into a few lines for the monday comment.
 *
 * Leads with a checklist of what was checked, one row per suite, so the ticket
 * says what was covered and not merely that a number went green. Then, if
 * anything failed, what broke - whoever reads the ticket should not have to
 * open the run log to find that out.
 *
 * One row per suite rather than per test on purpose: 52 lines of "/about/ ok"
 * is not a checklist anybody reads. The counts carry the detail.
 *
 * Prints nothing useful if the run produced no results.json - which is itself
 * worth saying rather than hiding.
 */

const path = require('path');

const RESULTS = path.join(__dirname, '.artifacts', 'results.json');
const MAX_LISTED = 8;
const ANSI = new RegExp(String.fromCharCode(27) + '\\[[0-9;]*m', 'g');

// Emoji rather than "[x]" or the ☑/☒ glyphs: a monday update is plain text, so
// brackets stay brackets, and the dingbat boxes render as thin grey outlines
// that are hard to tell apart at a glance. These carry colour on their own.
const BOX = { passed: '✅', failed: '❌', skipped: '⬜' };

let report;
try {
  report = require(RESULTS);
} catch {
  console.log('The check produced no results file, so it probably never ran. See the run log.');
  process.exit(0);
}

const failures = [];
const groups = new Map(); // suite title -> tallies, in report order

function tally(title) {
  if (!groups.has(title)) groups.set(title, { passed: 0, failed: 0, skipped: 0 });
  return groups.get(title);
}

(function walk(suite, file, title) {
  const where = suite.file || file;
  // The file-level suite carries the filename; the describe() inside it carries
  // the readable title. Prefer the latter, and fall back so a spec with no
  // describe() still lands under something recognisable.
  const label = suite.title && suite.title !== path.basename(where || '')
    ? suite.title
    : title || path.basename(where || '', '.spec.js');

  for (const spec of suite.specs || []) {
    const counts = tally(label);

    for (const t of spec.tests || []) {
      if (t.status === 'skipped') { counts.skipped++; continue; }
      if (spec.ok) { counts.passed++; continue; }
      counts.failed++;

      const last = (t.results || []).slice(-1)[0] || {};
      // The specs put the useful part first and a blank line before Playwright's
      // own expect() diff, so stop at the blank line and skip the diff entirely.
      const lines = ((last.error || {}).message || '').replace(ANSI, '').split('\n');
      const start = lines.findIndex((l) => l.trim());
      const body = start === -1
        ? ['failed']
        : lines.slice(start, lines.indexOf('', start) === -1 ? undefined : lines.indexOf('', start));

      failures.push({
        check: path.basename(where || '', '.spec.js'),
        title: spec.title,
        message: body.map((l) => l.trim()).join(' ').replace(/^Error:\s*/, '').slice(0, 300),
      });
    }
  }

  (suite.suites || []).forEach((s) => walk(s, where, label));
})({ suites: report.suites || [] });

const stats = report.stats || {};
const total = (stats.expected || 0) + (stats.unexpected || 0) + (stats.skipped || 0);

// Checklist first, on both outcomes. On a red run it shows what still held,
// which is most of the value - "markup broke, the shop journey did not" is a
// different morning from "something went red".
//
// No column padding: monday renders an update as HTML, so a run of spaces
// collapses to one and the alignment never survives the trip. Single spaces.
for (const [name, c] of groups) {
  const ran = c.passed + c.failed;
  const state = c.failed ? 'failed' : ran === 0 ? 'skipped' : 'passed';
  const count = ran === 0 ? 'skipped' : `${c.passed}/${ran}`;
  const note = c.skipped && ran ? ` (${c.skipped} skipped)` : '';
  console.log(`${BOX[state]} ${name} ${count}${note}`);
}

if (!failures.length) {
  console.log(`\n${total} checks passed in ${Math.round((stats.duration || 0) / 1000)}s.`);
  process.exit(0);
}

console.log(`\n${failures.length} of ${total} checks failed:`);
for (const f of failures.slice(0, MAX_LISTED)) {
  console.log(`- [${f.check}] ${f.title}: ${f.message}`);
}
if (failures.length > MAX_LISTED) {
  console.log(`- ...and ${failures.length - MAX_LISTED} more. See the run log.`);
}
