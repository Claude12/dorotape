#!/usr/bin/env node
'use strict';

/**
 * monday-sync.js
 *
 * Writes pipeline state from GitHub Actions into a monday.com board.
 * monday is the audit trail; GitHub's own notifications are the real-time alert.
 *
 * Zero npm dependencies. Requires Node 18+ for global fetch (CI should use Node 20).
 * CommonJS deliberately: the repo's package.json has no "type": "module".
 *
 * Config:  .github/monday-config.json   (committed, not secret)
 * Secret:  MONDAY_TOKEN                 (integration user's personal API token)
 *
 * Commands
 *   check                                     Validate token, board, column IDs and every label
 *   branch-created --branch <name>            -> In progress
 *   pr-opened      --branch <name> --pr-url <url>   -> Code review
 *   pr-merged      --branch <name> --pr-url <url>   -> Merged
 *   deployed       --refs <list> --commit <sha> --run-url <url>   -> Deployed to dev
 *   checks-passed  --refs <list> --run-url <url> [--summary <text>] -> QA
 *   blocked        --refs <list> --stage deploy|checks --run-url <url> [--detail <text>] -> Blocked
 *   refs-from-range --from <rev> --to <rev>   Print DR refs found in a commit range
 *
 * Global flags
 *   --dry-run        Print intended writes, make no mutations
 *   --config <path>  Override config location
 *
 * Exit codes
 *   0 success, 1 failure. This script is allowed to fail loudly: it runs in a
 *   separate workflow from deploy.yml, so a non-zero exit here can never fail a
 *   deploy. That is the whole reason for the workflow_run split.
 */

const fs = require('node:fs');
const path = require('node:path');
const { execFileSync } = require('node:child_process');

const API_URL = 'https://api.monday.com/v2';
const DEFAULT_CONFIG = path.join(__dirname, '..', 'monday-config.json');

/* ------------------------------------------------------------------ *
 * arg parsing
 * ------------------------------------------------------------------ */

function parseArgs(argv) {
  const command = argv[2];
  const flags = {};
  for (let i = 3; i < argv.length; i += 1) {
    const token = argv[i];
    if (!token.startsWith('--')) continue;
    const key = token.slice(2);
    const next = argv[i + 1];
    if (next === undefined || next.startsWith('--')) {
      flags[key] = true;
    } else {
      flags[key] = next;
      i += 1;
    }
  }
  return { command, flags };
}

function require_(flags, name) {
  const value = flags[name];
  if (typeof value !== 'string' || value.trim() === '') {
    fail(`Missing required flag --${name}`);
  }
  return value.trim();
}

function fail(message) {
  console.error(`ERROR  ${message}`);
  process.exit(1);
}

function log(message) {
  console.log(message);
}

/* ------------------------------------------------------------------ *
 * config
 * ------------------------------------------------------------------ */

function loadConfig(configPath) {
  const resolved = path.resolve(configPath);
  if (!fs.existsSync(resolved)) fail(`Config not found at ${resolved}`);

  let cfg;
  try {
    cfg = JSON.parse(fs.readFileSync(resolved, 'utf8'));
  } catch (err) {
    fail(`Config at ${resolved} is not valid JSON: ${err.message}`);
  }

  const placeholders = [];
  if (cfg.boardId === 'REPLACE_ME' || !cfg.boardId) placeholders.push('boardId');
  for (const [key, value] of Object.entries(cfg.columns || {})) {
    if (value === 'REPLACE_ME') placeholders.push(`columns.${key}`);
  }
  if (placeholders.length) {
    fail(
      `Config still contains placeholders: ${placeholders.join(', ')}\n` +
      `       Fill these in from the board, then re-run "check".`
    );
  }

  // Column IDs are interpolated into GraphQL, so constrain them.
  for (const [key, value] of Object.entries(cfg.columns || {})) {
    if (value === null) continue;
    if (!/^[A-Za-z0-9_]+$/.test(value)) {
      fail(`columns.${key} is not a valid monday column id: "${value}"`);
    }
  }

  cfg.__path = resolved;
  return cfg;
}

/* ------------------------------------------------------------------ *
 * monday API client
 * ------------------------------------------------------------------ */

function token() {
  const t = process.env.MONDAY_TOKEN;
  if (!t) fail('MONDAY_TOKEN is not set');
  return t;
}

async function gql(cfg, query, variables) {
  let res;
  try {
    res = await fetch(API_URL, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Authorization: token(),
        'API-Version': cfg.apiVersion,
      },
      body: JSON.stringify({ query, variables }),
    });
  } catch (err) {
    throw new Error(`Network error calling monday: ${err.message}`);
  }

  const text = await res.text();
  let body;
  try {
    body = JSON.parse(text);
  } catch {
    throw new Error(`monday returned non-JSON (HTTP ${res.status}): ${text.slice(0, 400)}`);
  }

  // monday frequently returns HTTP 200 with an errors array. Checking res.ok
  // alone is the classic way to make this pipeline lie to you.
  if (body.errors) {
    const detail = body.errors.map((e) => e.message || JSON.stringify(e)).join(' | ');
    throw new Error(`monday GraphQL error (HTTP ${res.status}): ${detail}`);
  }
  if (body.error_message) {
    throw new Error(`monday API error (HTTP ${res.status}): ${body.error_message}`);
  }
  if (!res.ok) {
    throw new Error(`monday HTTP ${res.status}: ${text.slice(0, 400)}`);
  }
  if (!body.data) {
    throw new Error(`monday returned no data: ${text.slice(0, 400)}`);
  }
  return body.data;
}

/* ------------------------------------------------------------------ *
 * board reads
 * ------------------------------------------------------------------ */

async function fetchBoardColumns(cfg) {
  const data = await gql(
    cfg,
    `query ($boardId: ID!) {
       boards(ids: [$boardId]) {
         id
         name
         columns { id title type settings_str }
       }
     }`,
    { boardId: String(cfg.boardId) }
  );
  const board = (data.boards || [])[0];
  if (!board) {
    throw new Error(
      `Board ${cfg.boardId} not found, or the token's user is not subscribed to it. ` +
      `A viewer-seat user cannot write; the integration user needs a member seat.`
    );
  }
  return board;
}

/**
 * Fetch every item plus its ref column, paginated.
 *
 * Deliberately does the ref match in JS rather than with a server-side rule.
 * Two reasons: server-side "contains_text" would match DR-10 when looking for
 * DR-1, and we must see all matches to detect duplicate refs rather than
 * silently taking the first. Fine for a per-project dev board; if this board
 * ever holds thousands of items, swap in a server-side exact rule.
 */
async function fetchAllRefItems(cfg) {
  const refCol = cfg.columns.ref;
  const items = [];
  let cursor = null;
  let page = 0;
  const maxPages = cfg.maxItemPages || 20;

  do {
    page += 1;
    if (page > maxPages) {
      throw new Error(
        `Board has more than ${maxPages * 100} items; raise maxItemPages or ` +
        `switch to a server-side column rule.`
      );
    }

    const data = await gql(
      cfg,
      `query ($boardId: ID!, $cursor: String) {
         boards(ids: [$boardId]) {
           items_page(limit: 100, cursor: $cursor) {
             cursor
             items {
               id
               name
               column_values(ids: ["${refCol}"]) { id text }
             }
           }
         }
       }`,
      { boardId: String(cfg.boardId), cursor }
    );

    const boardsPage = ((data.boards || [])[0] || {}).items_page;
    if (!boardsPage) throw new Error(`Board ${cfg.boardId} returned no items_page`);

    for (const item of boardsPage.items || []) {
      const cv = (item.column_values || [])[0];
      items.push({
        id: item.id,
        name: item.name,
        ref: cv && cv.text ? cv.text.trim() : '',
      });
    }
    cursor = boardsPage.cursor;
  } while (cursor);

  return items;
}

/** Resolve a ref to exactly one item, or throw. */
async function resolveRef(cfg, ref, cache) {
  if (!cache.items) cache.items = await fetchAllRefItems(cfg);
  const wanted = ref.trim().toUpperCase();
  const matches = cache.items.filter((i) => i.ref.toUpperCase() === wanted);

  if (matches.length === 0) {
    throw new Error(`No item on board ${cfg.boardId} has Ref "${ref}"`);
  }
  if (matches.length > 1) {
    const ids = matches.map((m) => `${m.id} (${m.name})`).join(', ');
    throw new Error(
      `Ref "${ref}" is not unique - ${matches.length} items match: ${ids}. ` +
      `Refusing to guess. Fix the duplicate on the board.`
    );
  }
  return matches[0];
}

/* ------------------------------------------------------------------ *
 * board writes
 * ------------------------------------------------------------------ */

async function setColumns(cfg, itemId, values, dryRun) {
  const cleaned = {};
  for (const [colId, value] of Object.entries(values)) {
    if (colId && value !== undefined && value !== null) cleaned[colId] = value;
  }
  if (Object.keys(cleaned).length === 0) return;

  if (dryRun) {
    log(`  DRY-RUN  set item ${itemId}: ${JSON.stringify(cleaned)}`);
    return;
  }

  await gql(
    cfg,
    `mutation ($boardId: ID!, $itemId: ID!, $vals: JSON!) {
       change_multiple_column_values(board_id: $boardId, item_id: $itemId, column_values: $vals) { id }
     }`,
    { boardId: String(cfg.boardId), itemId: String(itemId), vals: JSON.stringify(cleaned) }
  );
  log(`  set item ${itemId}: ${Object.keys(cleaned).join(', ')}`);
}

async function addUpdate(cfg, itemId, body, dryRun) {
  if (!body) return;
  if (dryRun) {
    log(`  DRY-RUN  comment on ${itemId}:\n${indent(body)}`);
    return;
  }
  await gql(
    cfg,
    `mutation ($itemId: ID!, $body: String!) {
       create_update(item_id: $itemId, body: $body) { id }
     }`,
    { itemId: String(itemId), body }
  );
  log(`  commented on item ${itemId}`);
}

function indent(text) {
  return text.split('\n').map((l) => `           ${l}`).join('\n');
}

/* ------------------------------------------------------------------ *
 * ref extraction
 * ------------------------------------------------------------------ */

function refRegex(cfg) {
  return new RegExp(cfg.refPattern || '\\bDR-(\\d+)\\b', 'gi');
}

/** Pull DR refs out of arbitrary text (branch name, commit message). */
function extractRefs(cfg, text) {
  const found = new Set();
  const re = refRegex(cfg);
  let m;
  while ((m = re.exec(text)) !== null) found.add(m[0].toUpperCase());
  return [...found];
}

function refFromBranch(cfg, branch) {
  const refs = extractRefs(cfg, branch);
  if (refs.length === 0) {
    fail(
      `Branch "${branch}" contains no DR ref. Expected something matching ` +
      `${cfg.refPattern} - e.g. feature/DR-1-fix-nav or DR-1-fix-nav. ` +
      `Nothing to track; not an error worth failing a build over if this is intentional.`
    );
  }
  if (refs.length > 1) {
    fail(`Branch "${branch}" contains multiple DR refs (${refs.join(', ')}). Refusing to guess.`);
  }
  return refs[0];
}

function refsFromList(value) {
  return [...new Set(
    value.split(/[\s,]+/).map((r) => r.trim().toUpperCase()).filter(Boolean)
  )];
}

/** Refs mentioned in commit subjects across a range - the multi-ticket deploy case. */
function refsFromRange(cfg, from, to) {
  let out;
  try {
    out = execFileSync('git', ['log', '--format=%s%n%b', `${from}..${to}`], {
      encoding: 'utf8',
      maxBuffer: 20 * 1024 * 1024,
    });
  } catch (err) {
    fail(
      `git log ${from}..${to} failed: ${err.message}\n` +
      `       If the "deployed-dev" tag does not exist yet this is expected on the ` +
      `first run - fall back to HEAD only.`
    );
  }
  return extractRefs(cfg, out);
}

/* ------------------------------------------------------------------ *
 * value builders
 * ------------------------------------------------------------------ */

function statusValue(label) {
  // Always by label, never by index, and never with create_labels_if_missing:
  // a typo must fail loudly rather than invent a new label on the board.
  return { label };
}

function linkValue(url, text) {
  return { url, text: text || url };
}

function nowDateValue() {
  const d = new Date();
  const iso = d.toISOString();
  return { date: iso.slice(0, 10), time: iso.slice(11, 19) }; // UTC
}

/* ------------------------------------------------------------------ *
 * commands
 * ------------------------------------------------------------------ */

async function cmdCheck(cfg) {
  log(`Config:  ${cfg.__path}`);
  log(`Board:   ${cfg.boardId}`);
  log(`API ver: ${cfg.apiVersion}\n`);

  const board = await fetchBoardColumns(cfg);
  log(`Board reachable: "${board.name}"\n`);

  const byId = new Map(board.columns.map((c) => [c.id, c]));
  let problems = 0;

  log('Columns');
  for (const [key, id] of Object.entries(cfg.columns)) {
    if (id === null) {
      log(`  -  ${key.padEnd(14)} (not configured, optional writes skipped)`);
      continue;
    }
    const col = byId.get(id);
    if (!col) {
      log(`  X  ${key.padEnd(14)} ${id}  NOT FOUND ON BOARD`);
      problems += 1;
    } else {
      log(`  ok ${key.padEnd(14)} ${id}  "${col.title}" (${col.type})`);
    }
  }

  // Verify every label string we might write actually exists on its column.
  const labelChecks = [
    ['status', Object.values(cfg.labels || {})],
    ['deployResult', Object.values(cfg.deployResultLabels || {})],
    ['checksResult', Object.values(cfg.checksResultLabels || {})],
  ];

  log('\nStatus labels');
  for (const [colKey, wanted] of labelChecks) {
    const colId = cfg.columns[colKey];
    if (!colId) {
      log(`  -  ${colKey}: not configured, skipped`);
      continue;
    }
    const col = byId.get(colId);
    if (!col) {
      problems += 1;
      continue;
    }
    let present = [];
    try {
      const settings = JSON.parse(col.settings_str || '{}');
      present = Object.values(settings.labels || {});
    } catch {
      log(`  X  ${colKey}: could not parse settings_str`);
      problems += 1;
      continue;
    }
    for (const label of wanted) {
      if (present.includes(label)) {
        log(`  ok ${colKey}: "${label}"`);
      } else {
        log(`  X  ${colKey}: "${label}" MISSING (board has: ${present.join(', ')})`);
        problems += 1;
      }
    }
  }

  const items = await fetchAllRefItems(cfg);
  const seen = new Map();
  for (const item of items) {
    if (!item.ref) continue;
    const key = item.ref.toUpperCase();
    seen.set(key, (seen.get(key) || 0) + 1);
  }
  const dupes = [...seen.entries()].filter(([, n]) => n > 1);

  log(`\nItems: ${items.length} total, ${seen.size} with a Ref`);
  if (dupes.length) {
    for (const [ref, n] of dupes) log(`  X  duplicate Ref "${ref}" on ${n} items`);
    problems += dupes.length;
  } else {
    log('  ok no duplicate Refs');
  }

  log('');
  if (problems > 0) {
    fail(`${problems} problem(s) found. Fix these before wiring CI.`);
  }
  log('All checks passed. Safe to wire into Actions.');
}

/** Apply one state transition across one or more refs, continuing past failures. */
async function applyToRefs(cfg, refs, dryRun, build) {
  const cache = {};
  const failures = [];

  for (const ref of refs) {
    log(`${ref}`);
    try {
      const item = await resolveRef(cfg, ref, cache);
      const { values, comment } = build(item, ref);
      await setColumns(cfg, item.id, values, dryRun);
      await addUpdate(cfg, item.id, comment, dryRun);
    } catch (err) {
      log(`  FAILED  ${err.message}`);
      failures.push(`${ref}: ${err.message}`);
    }
  }

  if (failures.length) {
    console.error(`\n${failures.length} of ${refs.length} ref(s) failed:`);
    for (const f of failures) console.error(`  - ${f}`);
    process.exit(1);
  }
  log(`\nDone: ${refs.length} ref(s) updated.`);
}

async function cmdBranchCreated(cfg, flags, dryRun) {
  const branch = require_(flags, 'branch');
  const ref = refFromBranch(cfg, branch);
  await applyToRefs(cfg, [ref], dryRun, () => ({
    values: {
      [cfg.columns.status]: statusValue(cfg.labels.inProgress),
      [cfg.columns.branch]: branch,
    },
  }));
}

async function cmdPrOpened(cfg, flags, dryRun) {
  const branch = require_(flags, 'branch');
  const prUrl = require_(flags, 'pr-url');
  const ref = refFromBranch(cfg, branch);
  await applyToRefs(cfg, [ref], dryRun, () => ({
    values: {
      [cfg.columns.status]: statusValue(cfg.labels.codeReview),
      [cfg.columns.branch]: branch,
      [cfg.columns.pr]: linkValue(prUrl, 'PR'),
    },
  }));
}

async function cmdPrMerged(cfg, flags, dryRun) {
  const branch = require_(flags, 'branch');
  const prUrl = require_(flags, 'pr-url');
  const ref = refFromBranch(cfg, branch);
  await applyToRefs(cfg, [ref], dryRun, () => ({
    values: {
      [cfg.columns.status]: statusValue(cfg.labels.merged),
      [cfg.columns.pr]: linkValue(prUrl, 'PR'),
    },
    comment:
      'Merged to main. Not deployed yet - dev deploys only run when the ' +
      'Version line in style.css is bumped.',
  }));
}

async function cmdDeployed(cfg, flags, dryRun) {
  const refs = refsFromList(require_(flags, 'refs'));
  const commit = require_(flags, 'commit');
  const runUrl = require_(flags, 'run-url');
  const short = commit.slice(0, 7);

  await applyToRefs(cfg, refs, dryRun, () => ({
    values: {
      [cfg.columns.status]: statusValue(cfg.labels.deployedToDev),
      [cfg.columns.commit]: short,
      [cfg.columns.deployedAt]: nowDateValue(),
      [cfg.columns.deployResult]: cfg.columns.deployResult
        ? statusValue(cfg.deployResultLabels.success)
        : null,
      [cfg.columns.checksResult]: cfg.columns.checksResult
        ? statusValue(cfg.checksResultLabels.notRun)
        : null,
      [cfg.columns.lastRun]: cfg.columns.lastRun ? linkValue(runUrl, 'Run log') : null,
    },
    comment:
      `Deployed to dev at commit ${short}.\n` +
      `Dev: ${cfg.devUrl}\n` +
      `Run log: ${runUrl}\n\n` +
      (refs.length > 1
        ? `This deploy shipped ${refs.length} tickets together: ${refs.join(', ')}.`
        : ''),
  }));
}

async function cmdChecksPassed(cfg, flags, dryRun) {
  const refs = refsFromList(require_(flags, 'refs'));
  const runUrl = require_(flags, 'run-url');
  const summary = typeof flags.summary === 'string' ? flags.summary : '';

  await applyToRefs(cfg, refs, dryRun, () => ({
    values: {
      [cfg.columns.status]: statusValue(cfg.labels.qa),
      [cfg.columns.checksResult]: cfg.columns.checksResult
        ? statusValue(cfg.checksResultLabels.passed)
        : null,
      [cfg.columns.lastRun]: cfg.columns.lastRun ? linkValue(runUrl, 'Run log') : null,
    },
    comment:
      `Automated checks passed on dev. Ready for your manual QA.\n` +
      `Dev: ${cfg.devUrl}\n` +
      `Run log: ${runUrl}` +
      (summary ? `\n\n${summary}` : ''),
  }));
}

async function cmdBlocked(cfg, flags, dryRun) {
  const refs = refsFromList(require_(flags, 'refs'));
  const stage = require_(flags, 'stage');
  const runUrl = require_(flags, 'run-url');
  const detail = typeof flags.detail === 'string' ? flags.detail : '';

  if (stage !== 'deploy' && stage !== 'checks') {
    fail(`--stage must be "deploy" or "checks", got "${stage}"`);
  }

  const deployFailed = stage === 'deploy';

  // The whole point of splitting these: the comment must make it obvious
  // whether the code reached dev or not.
  const comment = deployFailed
    ? `BLOCKED - deploy to dev FAILED. The code did not reach dev; ` +
      `the site is still running the previous version.\n` +
      `Run log: ${runUrl}` + (detail ? `\n\n${detail}` : '')
    : `BLOCKED - deploy to dev SUCCEEDED, but automated checks FAILED. ` +
      `The new code IS live on dev and may be broken.\n` +
      `Dev: ${cfg.devUrl}\n` +
      `Run log: ${runUrl}` + (detail ? `\n\n${detail}` : '');

  await applyToRefs(cfg, refs, dryRun, () => ({
    values: {
      [cfg.columns.status]: statusValue(cfg.labels.blocked),
      [cfg.columns.deployResult]: cfg.columns.deployResult
        ? statusValue(deployFailed ? cfg.deployResultLabels.failed : cfg.deployResultLabels.success)
        : null,
      [cfg.columns.checksResult]: cfg.columns.checksResult
        ? statusValue(deployFailed ? cfg.checksResultLabels.notRun : cfg.checksResultLabels.failed)
        : null,
      [cfg.columns.lastRun]: cfg.columns.lastRun ? linkValue(runUrl, 'Run log') : null,
    },
    comment,
  }));
}

function cmdRefsFromRange(cfg, flags) {
  const from = require_(flags, 'from');
  const to = flags.to && typeof flags.to === 'string' ? flags.to : 'HEAD';
  const refs = refsFromRange(cfg, from, to);
  process.stdout.write(refs.join('\n') + (refs.length ? '\n' : ''));
}

/* ------------------------------------------------------------------ *
 * main
 * ------------------------------------------------------------------ */

const USAGE = `
monday-sync.js <command> [flags]

  check
  branch-created  --branch <name>
  pr-opened       --branch <name> --pr-url <url>
  pr-merged       --branch <name> --pr-url <url>
  deployed        --refs <list> --commit <sha> --run-url <url>
  checks-passed   --refs <list> --run-url <url> [--summary <text>]
  blocked         --refs <list> --stage deploy|checks --run-url <url> [--detail <text>]
  refs-from-range --from <rev> [--to <rev>]

  --dry-run           print writes, mutate nothing
  --config <path>     override .github/monday-config.json

  MONDAY_TOKEN must be set.
`;

async function main() {
  const { command, flags } = parseArgs(process.argv);
  if (!command || command === 'help' || flags.help) {
    process.stdout.write(USAGE);
    process.exit(command ? 0 : 1);
  }

  const cfg = loadConfig(typeof flags.config === 'string' ? flags.config : DEFAULT_CONFIG);
  const dryRun = flags['dry-run'] === true;
  if (dryRun) log('DRY RUN - no mutations will be sent\n');

  switch (command) {
    case 'check':            await cmdCheck(cfg); break;
    case 'branch-created':   await cmdBranchCreated(cfg, flags, dryRun); break;
    case 'pr-opened':        await cmdPrOpened(cfg, flags, dryRun); break;
    case 'pr-merged':        await cmdPrMerged(cfg, flags, dryRun); break;
    case 'deployed':         await cmdDeployed(cfg, flags, dryRun); break;
    case 'checks-passed':    await cmdChecksPassed(cfg, flags, dryRun); break;
    case 'blocked':          await cmdBlocked(cfg, flags, dryRun); break;
    case 'refs-from-range':  cmdRefsFromRange(cfg, flags); break;
    default:
      process.stderr.write(`Unknown command "${command}"\n${USAGE}`);
      process.exit(1);
  }
}

main().catch((err) => {
  console.error(`ERROR  ${err.message}`);
  process.exit(1);
});
