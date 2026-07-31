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
 *
 *   Two cases exit 0 without writing anything, deliberately, because they are
 *   "nothing to do here" rather than "something broke":
 *     - the config still has REPLACE_ME placeholders, or MONDAY_TOKEN is unset
 *       (tracking not wired up yet)
 *     - the branch carries no DR ref (chore/*, dependabot/*, ...)
 *   "check" is the exception: it fails loudly on an unconfigured board, since
 *   telling you the board is fine when it is not is the one thing it must never do.
 */

const fs = require('node:fs');
const path = require('node:path');
const { execFileSync } = require('node:child_process');

const API_URL = 'https://api.monday.com/v2';
const DEFAULT_CONFIG = path.join(__dirname, '..', 'monday-config.json');

/**
 * What each configured column must actually BE on the board.
 *
 * This exists because a column id that resolves is not the same as a column
 * that accepts what we write to it. A Text column called "Deployed" will pass
 * an existence check and then reject {date, time} on the first real deploy.
 * The whole value of "check" is that a green run means the next deploy works.
 */
const EXPECTED_COLUMN_TYPES = {
  ref:           { types: ['text'],           writes: 'plain text, e.g. "DR-7"' },
  status:        { types: ['status'],         writes: 'a status label' },
  branch:        { types: ['text'],           writes: 'plain text, the branch name' },
  pr:            { types: ['link'],           writes: '{url, text} - must be a Link column' },
  commit:        { types: ['text'],           writes: 'plain text, the short SHA' },
  deployedAt:    { types: ['date'],           writes: '{date, time} - must be a Date column' },
  deployResult:  { types: ['status'],         writes: 'a status label' },
  checksResult:  { types: ['status'],         writes: 'a status label' },
  lastRun:       { types: ['link'],           writes: '{url, text} - must be a Link column' },
};

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

function loadConfig(configPath, strict) {
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
    const message =
      `Config still contains placeholders: ${placeholders.join(', ')}\n` +
      `       Fill these in from the board, then re-run "check".`;
    // "check" is being asked "is the board ready?" - it must answer no, loudly.
    // Everything else is running inside CI on a repo that simply is not wired
    // up yet, and must not paint the Actions tab red for that.
    if (strict) fail(message);
    cfg.__unconfigured = message;
    return cfg;
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
 * discovery
 * ------------------------------------------------------------------ */

/**
 * Column ids are API-side identifiers like "text_mkr7k2". They are not the
 * column titles and they are not visible anywhere in the monday web UI, so
 * "read them off the board" is not a thing you can actually do. This command
 * reads them from the API and prints a config block ready to paste in.
 */
async function cmdDiscover(cfg, flags) {
  const boardId = typeof flags.board === 'string'
    ? flags.board
    : (cfg.boardId && cfg.boardId !== 'REPLACE_ME' ? cfg.boardId : null);

  if (!boardId) {
    const data = await gql(
      cfg,
      `query {
         boards(limit: 100, state: active, order_by: created_at) {
           id name workspace { id name }
         }
       }`,
      {}
    );
    const boards = data.boards || [];
    if (!boards.length) {
      log('No boards visible to this token. Is the integration user subscribed to the board?');
      return;
    }
    log(`${boards.length} board(s) visible to this token:\n`);
    for (const b of boards) {
      const ws = b.workspace ? `${b.workspace.name} (ws ${b.workspace.id})` : 'no workspace';
      log(`  ${String(b.id).padEnd(14)} ${b.name}`);
      log(`  ${''.padEnd(14)} ${ws}`);
    }
    log('\nRe-run with --board <id> to dump that board\'s column ids.');
    return;
  }

  const board = await fetchBoardColumns({ ...cfg, boardId });
  log(`Board ${boardId}: "${board.name}"\n`);
  log('Columns');
  for (const c of board.columns) {
    log(`  ${String(c.id).padEnd(22)} ${String(c.type).padEnd(10)} "${c.title}"`);
    if (c.type === 'status') {
      let labels = [];
      try {
        labels = Object.values(JSON.parse(c.settings_str || '{}').labels || {});
      } catch { /* not worth failing discovery over */ }
      if (labels.length) log(`  ${''.padEnd(22)} labels: ${labels.join(' | ')}`);
    }
  }

  // Best-effort mapping by type and title. Anything it cannot place is left as
  // REPLACE_ME rather than guessed - a wrong id here writes to the wrong column.
  const wants = [
    ['ref',        'text',   /\b(ref|ticket|key|id)\b/i],
    ['status',     'status', /status|state|stage/i],
    ['branch',     'text',   /branch/i],
    ['pr',         'link',   /\b(pr|pull)/i],
    ['commit',     'text',   /commit|sha/i],
    ['deployedAt', 'date',   /deploy|shipped|released/i],
  ];

  const guessed = {};
  const used = new Set();
  for (const [key, type, titleRe] of wants) {
    const byBoth = board.columns.find(
      (c) => c.type === type && titleRe.test(c.title) && !used.has(c.id)
    );
    const onlyType = board.columns.filter((c) => c.type === type && !used.has(c.id));
    const pick = byBoth || (onlyType.length === 1 ? onlyType[0] : null);
    if (pick) {
      guessed[key] = pick.id;
      used.add(pick.id);
    } else {
      guessed[key] = 'REPLACE_ME';
    }
  }

  log('\nSuggested "columns" block - CHECK the titles above before pasting:');
  log(JSON.stringify({ boardId: String(boardId), columns: guessed }, null, 2));

  const unresolved = Object.entries(guessed).filter(([, v]) => v === 'REPLACE_ME');
  if (unresolved.length) {
    log(`\n${unresolved.length} column(s) could not be matched: ${unresolved.map(([k]) => k).join(', ')}`);
    log('Pick them from the list above by hand.');
  }
  log('\nThen run: node .github/scripts/monday-sync.js check');
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

/**
 * Ref for a branch, or null if the branch simply is not tracked.
 *
 * Returns null rather than failing: chore/*, dependabot/* and main itself carry
 * no DR ref, and a red X on every one of those pushes trains you to ignore the
 * Actions tab - which costs you the one signal that matters when a real deploy
 * breaks. Two refs on one branch is still a hard failure: that is ambiguous
 * intent, not absent intent.
 */
function refFromBranch(cfg, branch) {
  const refs = extractRefs(cfg, branch);
  if (refs.length === 0) return null;
  if (refs.length > 1) {
    fail(`Branch "${branch}" contains multiple DR refs (${refs.join(', ')}). Refusing to guess.`);
  }
  return refs[0];
}

/** Shared exit for the "nothing to track here" case. */
function skipUntracked(branch) {
  log(`SKIP  Branch "${branch}" carries no DR ref - nothing to track.`);
  process.exit(0);
}

function refsFromList(value) {
  return [...new Set(
    value.split(/[\s,]+/).map((r) => r.trim().toUpperCase()).filter(Boolean)
  )];
}

function revExists(rev) {
  try {
    execFileSync('git', ['rev-parse', '--verify', '--quiet', `${rev}^{commit}`], {
      stdio: 'ignore',
    });
    return true;
  } catch {
    return false;
  }
}

/**
 * Refs mentioned across a commit range - the multi-ticket deploy case.
 *
 * If `from` does not resolve, this reports only `to` instead of failing. That
 * is exactly the first-deploy case: the deployed-dev marker does not exist yet,
 * and reporting the one commit we know shipped beats reporting nothing.
 */
function refsFromRange(cfg, from, to) {
  const range = revExists(from) ? `${from}..${to}` : `${to}~1..${to}`;
  if (!revExists(from)) {
    console.error(`NOTE  "${from}" not found - first deploy? Reporting ${to} only.`);
  }
  let out;
  try {
    out = execFileSync('git', ['log', '--format=%s%n%b', range], {
      encoding: 'utf8',
      maxBuffer: 20 * 1024 * 1024,
    });
  } catch (err) {
    fail(`git log ${range} failed: ${err.message}`);
  }
  return extractRefs(cfg, out);
}

/* ------------------------------------------------------------------ *
 * value builders
 * ------------------------------------------------------------------ */

/**
 * Build the column_values payload from logical column names.
 *
 * Takes [key, value] pairs keyed by the names in config.columns, not raw ids,
 * and drops any pair whose column is unconfigured (null). Building the object
 * with computed keys directly would turn every unconfigured column into the
 * literal key "null" - and three of them would collide into one.
 */
function columnValues(cfg, pairs) {
  const out = {};
  for (const [key, value] of pairs) {
    const colId = cfg.columns[key];
    if (!colId || value === undefined || value === null) continue;
    out[colId] = value;
  }
  return out;
}

function statusValue(label) {
  // Always by label, never by index, and never with create_labels_if_missing:
  // a typo must fail loudly rather than invent a new label on the board.
  return { label };
}

function linkValue(url, text) {
  return { url, text: text || url };
}

/**
 * Date column payload. UTC on purpose: monday stores date-column times in UTC
 * and converts to the account timezone for display, so sending local time here
 * would double-shift it. Verify this on the first real deploy - if the board
 * shows the wrong hour, this is the line to look at.
 */
function nowDateValue() {
  const iso = new Date().toISOString();
  return { date: iso.slice(0, 10), time: iso.slice(11, 19) };
}

/**
 * Human-readable timestamp for update bodies. Unlike the date column there is
 * no conversion happening to a comment, so it says which zone it is in.
 */
function localStamp(cfg) {
  const tz = cfg.timezone || 'Europe/London';
  try {
    const s = new Intl.DateTimeFormat('en-GB', {
      timeZone: tz, dateStyle: 'medium', timeStyle: 'short', hourCycle: 'h23',
    }).format(new Date());
    return `${s} (${tz})`;
  } catch {
    return `${new Date().toISOString().slice(0, 19).replace('T', ' ')} (UTC)`;
  }
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
      continue;
    }

    // Existence is not enough - the column has to accept what we write to it.
    const expected = EXPECTED_COLUMN_TYPES[key];
    if (expected && !expected.types.includes(col.type)) {
      log(
        `  X  ${key.padEnd(14)} ${id}  "${col.title}" is type "${col.type}", ` +
        `expected ${expected.types.map((t) => `"${t}"`).join(' or ')}\n` +
        `     ${''.padEnd(14)} this column receives ${expected.writes}`
      );
      problems += 1;
      continue;
    }
    log(`  ok ${key.padEnd(14)} ${id}  "${col.title}" (${col.type})`);
  }

  // Two logical columns pointing at one physical column type-check perfectly and
  // then overwrite each other on every write, last one wins. Copy-paste while
  // filling in six ids by hand is exactly how this happens.
  const byColId = new Map();
  for (const [key, id] of Object.entries(cfg.columns)) {
    if (id === null) continue;
    if (!byColId.has(id)) byColId.set(id, []);
    byColId.get(id).push(key);
  }
  for (const [id, keys] of byColId) {
    if (keys.length > 1) {
      const title = byId.has(id) ? `"${byId.get(id).title}"` : id;
      log(`  X  ${keys.join(' + ')} all point at ${title} - they would overwrite each other`);
      problems += 1;
    }
  }

  // A Date column with time switched off silently drops the time half.
  const dateColId = cfg.columns.deployedAt;
  const dateCol = dateColId ? byId.get(dateColId) : null;
  if (dateCol && dateCol.type === 'date') {
    let hasTime = true;
    try {
      hasTime = JSON.parse(dateCol.settings_str || '{}').time !== false;
    } catch { /* settings unreadable - not worth failing over */ }
    if (!hasTime) {
      log(`  !  deployedAt     time appears disabled; only the date will show.`);
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

    // monday uses index 5 as a status column's empty slot. An item with no
    // status set reports whatever label occupies index 5, so putting a real
    // label there makes every untouched ticket silently claim that state -
    // a brand new ticket reading "QA" with nobody having done anything.
    // Verified on this board: with "QA" at index 5, an item with value=null
    // returned text="QA"; after freeing index 5 the same item returned null.
    try {
      const labelsByIndex = JSON.parse(col.settings_str || '{}').labels || {};
      if (labelsByIndex['5']) {
        log(
          `  X  ${colKey}: "${labelsByIndex['5']}" sits at index 5, monday's empty slot - ` +
          `every item with no status set will read as "${labelsByIndex['5']}".\n` +
          `     Rebuild the column with index 5 left unlabelled.`
        );
        problems += 1;
      }
    } catch { /* settings already reported unparseable above */ }
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
  if (!ref) skipUntracked(branch);
  await applyToRefs(cfg, [ref], dryRun, () => ({
    values: columnValues(cfg, [
      ['status', statusValue(cfg.labels.inProgress)],
      ['branch', branch],
    ]),
  }));
}

async function cmdPrOpened(cfg, flags, dryRun) {
  const branch = require_(flags, 'branch');
  const prUrl = require_(flags, 'pr-url');
  const ref = refFromBranch(cfg, branch);
  if (!ref) skipUntracked(branch);
  await applyToRefs(cfg, [ref], dryRun, () => ({
    values: columnValues(cfg, [
      ['status', statusValue(cfg.labels.codeReview)],
      ['branch', branch],
      ['pr', linkValue(prUrl, 'PR')],
    ]),
  }));
}

async function cmdPrMerged(cfg, flags, dryRun) {
  const branch = require_(flags, 'branch');
  const prUrl = require_(flags, 'pr-url');
  const ref = refFromBranch(cfg, branch);
  if (!ref) skipUntracked(branch);
  await applyToRefs(cfg, [ref], dryRun, () => ({
    values: columnValues(cfg, [
      ['status', statusValue(cfg.labels.merged)],
      ['pr', linkValue(prUrl, 'PR')],
    ]),
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
    values: columnValues(cfg, [
      ['status', statusValue(cfg.labels.deployedToDev)],
      ['commit', short],
      ['deployedAt', nowDateValue()],
      ['deployResult', statusValue(cfg.deployResultLabels.success)],
      ['checksResult', statusValue(cfg.checksResultLabels.notRun)],
      ['lastRun', linkValue(runUrl, 'Run log')],
    ]),
    comment:
      `Deployed to dev at commit ${short} on ${localStamp(cfg)}.\n` +
      `Dev: ${cfg.devUrl}\n` +
      `Run log: ${runUrl}` +
      (refs.length > 1
        ? `\n\nThis deploy shipped ${refs.length} tickets together: ${refs.join(', ')}.`
        : ''),
  }));
}

async function cmdChecksPassed(cfg, flags, dryRun) {
  const refs = refsFromList(require_(flags, 'refs'));
  const runUrl = require_(flags, 'run-url');
  const summary = typeof flags.summary === 'string' ? flags.summary : '';

  await applyToRefs(cfg, refs, dryRun, () => ({
    values: columnValues(cfg, [
      ['status', statusValue(cfg.labels.qa)],
      ['checksResult', statusValue(cfg.checksResultLabels.passed)],
      ['lastRun', linkValue(runUrl, 'Run log')],
    ]),
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
    values: columnValues(cfg, [
      ['status', statusValue(cfg.labels.blocked)],
      ['deployResult', statusValue(
        deployFailed ? cfg.deployResultLabels.failed : cfg.deployResultLabels.success
      )],
      ['checksResult', statusValue(
        deployFailed ? cfg.checksResultLabels.notRun : cfg.checksResultLabels.failed
      )],
      ['lastRun', linkValue(runUrl, 'Run log')],
    ]),
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

  discover        [--board <id>]   list boards, or dump one board's column ids
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

const COMMANDS = new Set([
  'discover', 'check', 'branch-created', 'pr-opened', 'pr-merged',
  'deployed', 'checks-passed', 'blocked', 'refs-from-range',
]);

async function main() {
  const { command, flags } = parseArgs(process.argv);
  if (!command || command === 'help' || flags.help) {
    process.stdout.write(USAGE);
    process.exit(command ? 0 : 1);
  }

  // Validate the command before the "not configured yet" guards below, or a
  // typo in a workflow exits 0 and looks like a successful skip until the day
  // the board is wired up.
  if (!COMMANDS.has(command)) {
    process.stderr.write(`Unknown command "${command}"\n${USAGE}`);
    process.exit(1);
  }

  // "check" is the readiness probe, so it alone treats an unconfigured board
  // as a failure. Every other command is running unattended in CI.
  const strict = command === 'check';
  const cfg = loadConfig(typeof flags.config === 'string' ? flags.config : DEFAULT_CONFIG, strict);

  // refs-from-range only reads git and config.refPattern - it never touches
  // monday, and the workflow calls it before deciding whether there is anything
  // to report. Gating it on board readiness would break that ordering.
  const needsBoard = command !== 'refs-from-range';
  // discover is the thing you run to FIND the ids, so requiring them first
  // would be circular. It needs the token, nothing else.
  const needsConfig = needsBoard && command !== 'discover';

  if (needsConfig && cfg.__unconfigured) {
    log(`SKIP  ${cfg.__unconfigured}`);
    log('Tracking is not wired up yet - nothing written, exiting 0.');
    process.exit(0);
  }
  if (needsBoard && !process.env.MONDAY_TOKEN) {
    if (strict || command === 'discover') fail('MONDAY_TOKEN is not set');
    log('SKIP  MONDAY_TOKEN is not set - nothing written, exiting 0.');
    process.exit(0);
  }

  const dryRun = flags['dry-run'] === true;
  if (dryRun) log('DRY RUN - no mutations will be sent\n');

  switch (command) {
    case 'discover':         await cmdDiscover(cfg, flags); break;
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
