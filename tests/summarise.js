#!/usr/bin/env node
'use strict';

/**
 * Turn a Playwright run into a few lines for the monday comment.
 *
 * Whoever reads the ticket should know what broke without opening the run log,
 * so this leads with the failures. It prints nothing useful if the run did not
 * produce results.json - that is itself worth saying rather than hiding.
 */

const path = require('path');

const RESULTS = path.join(__dirname, '.artifacts', 'results.json');
const MAX_LISTED = 8;
const ANSI = new RegExp(String.fromCharCode(27) + '\\[[0-9;]*m', 'g');

let report;
try {
  report = require(RESULTS);
} catch {
  console.log('The check produced no results file, so it probably never ran. See the run log.');
  process.exit(0);
}

const failures = [];

(function walk(suite, file) {
  const where = suite.file || file;
  for (const spec of suite.specs || []) {
    if (spec.ok) continue;
    for (const t of spec.tests || []) {
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
  (suite.suites || []).forEach((s) => walk(s, where));
})({ suites: report.suites || [] });

const stats = report.stats || {};
const total = (stats.expected || 0) + (stats.unexpected || 0) + (stats.skipped || 0);

if (!failures.length) {
  console.log(`${total} checks passed in ${Math.round((stats.duration || 0) / 1000)}s.`);
  process.exit(0);
}

console.log(`${failures.length} of ${total} checks failed:`);
for (const f of failures.slice(0, MAX_LISTED)) {
  console.log(`- [${f.check}] ${f.title}: ${f.message}`);
}
if (failures.length > MAX_LISTED) {
  console.log(`- ...and ${failures.length - MAX_LISTED} more. See the run log.`);
}
