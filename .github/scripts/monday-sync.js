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
 * Config:  .github/monday-config.json   (committed, not secret) - describes the
 *          BOARD: its id, column ids, labels and ticket prefix.
 * Secret:  MONDAY_TOKEN                 (integration user's personal API token)
 * Var:     DEV_SITE_URL                 (dev site, for the links in comments)
 *
 * Anything pointing at an environment is an environment variable, never
 * committed config, so that a copy of this repo for another project cannot
 * inherit the previous project's URLs. Running by hand, both can come from a
 * gitignored .env at the repo root - see .env.example.
 *
 * Commands
 *   check                                     Validate token, board, column IDs and every label
 *   branch-created --branch <name>            -> In progress
 *   pr-opened      --branch <name> --pr-url <url> [--pr-number <n>] [--pr-author <login>]  -> Code review
 *   pr-merged      --branch <name> --pr-url <url> [--pr-number <n>] [--pr-author <login>]  -> Merged
 *   deployed       --refs <list> --commit <sha> --run-url <url>   -> Deployed to dev
 *   checks-passed  --refs <list> --run-url <url> [--summary <text>] -> QA
 *   blocked        --refs <list> --stage deploy|checks --run-url <url> [--detail <text>] -> Blocked
 *   setup-progress                            Create the Size and Weight columns on the board
 *   progress       [--force]                  Recalculate the weighted progress bar
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
 * Load a gitignored .env from the repo root, for running this by hand.
 *
 * Real environment variables always win, so this can never quietly override
 * what CI passed in - in Actions the file does not exist at all, and the
 * secrets and variables configured there are the only source.
 *
 * Deliberately dependency-free: this script runs in CI with no npm install.
 */
function loadDotEnv() {
  const file = path.join(__dirname, '..', '..', '.env');
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
 * The dev site URL, for the links in monday comments.
 *
 * A repo variable rather than committed config, because it points at an
 * environment: a copy of this repo made for another project must not inherit
 * the previous project's URL. Missing is not fatal here - the link is a
 * convenience, and losing it should not stop a ticket being updated - so warn
 * and drop the line rather than failing a write that is otherwise correct.
 */
function devUrl() {
  const url = process.env.DEV_SITE_URL;
  if (!url) {
    console.error('NOTE  DEV_SITE_URL is not set; omitting the dev link from the comment.');
    return null;
  }
  return url.replace(/\/$/, '');
}

/** A "Dev: <url>" line, or nothing at all when the URL is unknown. */
const devLine = () => {
  const url = devUrl();
  return url ? `Dev: ${url}\n` : '';
};

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
  size:          { types: ['status'],         writes: 'a size label, e.g. "M"' },
  // monday renamed this type from "numeric" to "numbers"; boards built at
  // different times report different strings for the same column.
  weight:        { types: ['numbers', 'numeric'], writes: 'a number derived from Size' },
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
    ['ref',        ['text'],                 /\b(ref|ticket|key|id)\b/i],
    ['status',     ['status'],               /status|state|stage/i],
    ['branch',     ['text'],                 /branch/i],
    ['pr',         ['link'],                 /\b(pr|pull)/i],
    ['commit',     ['text'],                 /commit|sha/i],
    ['deployedAt', ['date'],                 /deploy|shipped|released/i],
    ['size',       ['status'],               /\bsize\b/i],
    ['weight',     ['numbers', 'numeric'],   /weight|points|pts/i],
  ];

  // size and weight only drive the progress bar, and everything else works
  // without them - so an unmatched one becomes null ("configured off") rather
  // than REPLACE_ME, which would stop the whole script until someone noticed.
  // Left as null rather than REPLACE_ME when discovery cannot find them: a board
  // is allowed not to have these. size/weight only drive the progress bar, and
  // branch/commit only mirror detail the PR link already leads to.
  const OPTIONAL = new Set(['size', 'weight', 'branch', 'commit']);

  const guessed = {};
  const used = new Set();
  for (const [key, types, titleRe] of wants) {
    const isType = (c) => types.includes(c.type) && !used.has(c.id);
    const byBoth = board.columns.find((c) => isType(c) && titleRe.test(c.title));
    const onlyType = board.columns.filter(isType);
    const pick = byBoth || (onlyType.length === 1 ? onlyType[0] : null);
    if (pick) {
      guessed[key] = pick.id;
      used.add(pick.id);
    } else {
      guessed[key] = OPTIONAL.has(key) ? null : 'REPLACE_ME';
    }
  }

  log('\nSuggested "columns" block - CHECK the titles above before pasting:');
  log(JSON.stringify({ boardId: String(boardId), columns: guessed }, null, 2));

  const off = Object.entries(guessed).filter(([, v]) => v === null);
  if (off.length) {
    log(`\n${off.map(([k]) => k).join(', ')} came back null - the board has no such column.`);
    log('That only turns the progress bar off; everything else works. Add the columns');
    log('and re-run this if you want it.');
  }

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
         description
         groups { id title position }
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
 *
 * `extraCols` asks for more columns in the same pass - "progress" needs status,
 * size and weight for every item, and pulling them here costs no extra requests.
 * Unconfigured (null) ids are dropped, so callers can pass optional columns
 * without checking first. Every value arrives as `cols[columnId]`, a string.
 */
async function fetchAllRefItems(cfg, extraCols = []) {
  const refCol = cfg.columns.ref;
  // Ids are constrained to [A-Za-z0-9_] by loadConfig, which is what makes them
  // safe to interpolate into the query. Deduped so a caller passing a column we
  // already fetch cannot produce a duplicate id in the list.
  const wanted = [...new Set([refCol, ...extraCols].filter(Boolean))];
  const idList = wanted.map((id) => `"${id}"`).join(', ');
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
               group { id }
               column_values(ids: [${idList}]) { id text }
             }
           }
         }
       }`,
      { boardId: String(cfg.boardId), cursor }
    );

    const boardsPage = ((data.boards || [])[0] || {}).items_page;
    if (!boardsPage) throw new Error(`Board ${cfg.boardId} returned no items_page`);

    for (const item of boardsPage.items || []) {
      const cols = {};
      for (const cv of item.column_values || []) cols[cv.id] = cv.text || '';
      items.push({
        id: item.id,
        name: item.name || '',
        groupId: (item.group || {}).id || null,
        ref: (cols[refCol] || '').trim(),
        cols,
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

/**
 * Rename an item.
 *
 * monday has no rename mutation - the item's title is addressed as the
 * pseudo-column "name" through the ordinary column-values mutation. It is not
 * listed among the board's columns, so it never appears in "discover" and is
 * not something "check" can validate.
 */
async function renameItem(cfg, itemId, name, dryRun) {
  if (dryRun) {
    log(`  DRY-RUN  rename item ${itemId} to "${name}"`);
    return;
  }
  await gql(
    cfg,
    `mutation ($boardId: ID!, $itemId: ID!, $vals: JSON!) {
       change_multiple_column_values(board_id: $boardId, item_id: $itemId, column_values: $vals) { id }
     }`,
    { boardId: String(cfg.boardId), itemId: String(itemId), vals: JSON.stringify({ name }) }
  );
  log(`  renamed item ${itemId}`);
}

/**
 * Write the board's description - the text box under the board title.
 *
 * Verified against the live API: newlines and block-drawing characters survive
 * a round trip unchanged, so the same plain-text body used for item updates
 * renders here as written. Note this is the only surface here that is NOT an
 * item: it notifies nobody and keeps no history, which is exactly why the
 * progress ITEM still exists alongside it. One is a noticeboard, the other is
 * a record.
 */
async function setBoardDescription(cfg, text, dryRun) {
  if (dryRun) {
    log(`  DRY-RUN  set board description:\n${indent(text)}`);
    return;
  }
  await gql(
    cfg,
    `mutation ($boardId: ID!, $text: String!) {
       update_board(board_id: $boardId, board_attribute: description, new_value: $text)
     }`,
    { boardId: String(cfg.boardId), text }
  );
  log(`  updated the board description`);
}

/**
 * Find, or create at the top of the board, the group the progress item lives in.
 *
 * There is no mutation that repositions an existing item - create_item takes
 * relative_to, and move_item_to_group moves between groups, but nothing reorders
 * within one. A group is therefore the only way to pin the bar above the
 * tickets, and it reads better anyway: the bar is not a ticket and should not
 * sit in a list of them.
 *
 * Returns null when groupName is unset, which leaves the item wherever monday
 * puts it - the bottom.
 */
async function ensureProgressGroup(cfg, board, dryRun) {
  const title = String((cfg.progress || {}).groupName || '').trim();
  if (!title) return null;

  const groups = board.groups || [];
  const existing = groups.find((g) => g.title === title);
  if (existing) return existing.id;

  if (dryRun) {
    log(`  DRY-RUN  create group "${title}" at the top of the board`);
    return null;
  }

  // Position is a float, so "above everything" means before the lowest one.
  // A board with no groups at all cannot take relative_to, hence the two forms.
  const first = [...groups].sort((a, b) => parseFloat(a.position) - parseFloat(b.position))[0];
  const data = await gql(
    cfg,
    first
      ? `mutation ($boardId: ID!, $title: String!, $relativeTo: String!) {
           create_group(
             board_id: $boardId, group_name: $title,
             relative_to: $relativeTo, position_relative_method: before_at
           ) { id }
         }`
      : `mutation ($boardId: ID!, $title: String!) {
           create_group(board_id: $boardId, group_name: $title) { id }
         }`,
    first
      ? { boardId: String(cfg.boardId), title, relativeTo: String(first.id) }
      : { boardId: String(cfg.boardId), title }
  );
  const id = ((data || {}).create_group || {}).id;
  if (!id) throw new Error('monday accepted create_group but returned no id');
  log(`  created group "${title}" at the top of the board`);
  return id;
}

async function moveItemToGroup(cfg, itemId, groupId, dryRun) {
  if (dryRun) {
    log(`  DRY-RUN  move item ${itemId} into group ${groupId}`);
    return;
  }
  await gql(
    cfg,
    `mutation ($itemId: ID!, $groupId: String!) {
       move_item_to_group(item_id: $itemId, group_id: $groupId) { id }
     }`,
    { itemId: String(itemId), groupId: String(groupId) }
  );
  log(`  moved item ${itemId} into the progress group`);
}

async function createItem(cfg, name, dryRun, groupId = null) {
  if (dryRun) {
    log(`  DRY-RUN  create item "${name}"${groupId ? ` in group ${groupId}` : ''}`);
    return null;
  }
  const data = await gql(
    cfg,
    groupId
      ? `mutation ($boardId: ID!, $name: String!, $groupId: String!) {
           create_item(board_id: $boardId, item_name: $name, group_id: $groupId) { id }
         }`
      : `mutation ($boardId: ID!, $name: String!) {
           create_item(board_id: $boardId, item_name: $name) { id }
         }`,
    groupId
      ? { boardId: String(cfg.boardId), name, groupId: String(groupId) }
      : { boardId: String(cfg.boardId), name }
  );
  const id = ((data || {}).create_item || {}).id;
  if (!id) throw new Error('monday accepted create_item but returned no id');
  log(`  created item ${id}`);
  return String(id);
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
 * Label for the PR link column: "PR #14 by someone".
 *
 * Number and author are both optional - a Link column with the bare word "PR"
 * is still a working link, and a run that cannot supply them should degrade to
 * that rather than fail or write the string "undefined" onto a client's board.
 */
function prLabel(number, author) {
  const num = String(number || '').replace(/^#/, '').trim();
  const who = String(author || '').trim();
  return `PR${num ? ` #${num}` : ''}${who ? ` by ${who}` : ''}`;
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

/**
 * Validate the progress block. Returns a problem count.
 *
 * Everything here is a config-vs-board mismatch that produces a plausible-
 * looking bar rather than an error: a done label that no longer exists on the
 * status column silently makes progress 0%, and a weight of "5 " (a string)
 * silently makes it NaN. Both look like data, not bugs.
 */
function checkProgressConfig(cfg, byId) {
  const p = cfg.progress || {};
  let problems = 0;

  log('\nProgress');

  if (!cfg.columns.size) {
    log('  -  size column not configured; progress reporting is off and "progress" will skip');
    return 0;
  }
  if (!cfg.columns.weight) {
    log('  !  weight column not configured; the bar still works, but the board');
    log('     itself cannot sum or sort by points');
  }

  const weights = p.weights || {};
  if (!Object.keys(weights).length) {
    log('  X  progress.weights is empty - there is nothing to weigh with');
    problems += 1;
  }
  for (const [label, value] of Object.entries(weights)) {
    if (typeof value !== 'number' || !Number.isFinite(value) || value <= 0) {
      log(`  X  progress.weights["${label}"] is ${JSON.stringify(value)}, expected a positive number`);
      problems += 1;
    }
  }

  const hasItem = Boolean(String(p.itemName || '').trim());
  const hasDescription = p.boardDescription !== false;
  if (hasItem) log(`  ok itemName      "${p.itemName}"`);
  else log('  -  itemName is empty, so no progress item is written');
  log(
    hasDescription
      ? '  ok description   the bar is written to the board description too'
      : '  -  boardDescription is false, so the board description is left alone'
  );
  if (!hasItem && !hasDescription) {
    log('  X  itemName and boardDescription are both off - the bar has nowhere to go');
    problems += 1;
  }

  // The three label lists all name labels on the STATUS column, so verify them
  // against that column rather than trusting the config to agree with itself.
  const statusCol = cfg.columns.status ? byId.get(cfg.columns.status) : null;
  let present = [];
  try {
    present = Object.values(JSON.parse((statusCol || {}).settings_str || '{}').labels || {});
  } catch { /* the label check above has already reported this */ }

  for (const key of ['doneLabels', 'onDevLabels']) {
    const list = p[key] || [];
    if (!list.length) {
      log(`  X  progress.${key} is empty`);
      problems += 1;
      continue;
    }
    for (const label of list) {
      if (present.includes(label)) {
        log(`  ok ${key.padEnd(14)} "${label}"`);
      } else {
        log(`  X  ${key.padEnd(14)} "${label}" is not a label on the status column`);
        problems += 1;
      }
    }
  }

  // Excluded is a warning, not an error, on purpose: naming a label the board
  // does not have yet excludes nothing, which is harmless and self-correcting.
  // It is also the normal state right after copying this onto a new project.
  for (const label of p.excludedLabels || []) {
    if (present.includes(label)) log(`  ok excludedLabels "${label}"`);
    else log(`  !  excludedLabels "${label}" is not on the status column yet, so nothing is excluded`);
  }

  // A label in both lists would be counted as done AND as on-dev, which reads
  // as more than 100% on dev and is impossible to spot from the output.
  const overlap = (p.doneLabels || []).filter((l) => (p.onDevLabels || []).includes(l));
  if (overlap.length) {
    log(`  X  ${overlap.join(', ')} appears in both doneLabels and onDevLabels`);
    problems += 1;
  }

  return problems;
}

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
    // The size labels are the KEYS of progress.weights, not values: the label
    // on the board is what the weight is looked up by.
    ['size', Object.keys((cfg.progress || {}).weights || {})],
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

  problems += checkProgressConfig(cfg, byId);

  const items = await fetchAllRefItems(cfg, [cfg.columns.size]);
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

  if (cfg.columns.size) {
    const prefix = String((cfg.progress || {}).itemName || '').trim();
    const mine = prefix ? items.filter((i) => i.name.startsWith(prefix)) : [];
    const weights = (cfg.progress || {}).weights || {};
    const unsized = items.filter(
      (i) => !mine.includes(i) &&
        !Object.prototype.hasOwnProperty.call(weights, (i.cols[cfg.columns.size] || '').trim())
    );

    if (mine.length > 1) {
      log(`  X  ${mine.length} items start with "${prefix}" - progress cannot pick one`);
      problems += 1;
    } else if (mine.length === 1) {
      log(`  ok progress item ${mine[0].id}`);
    } else {
      log(`  -  no progress item yet; "progress" will create one`);
    }
    // Not a problem - it is the number the bar exists to make visible.
    if (unsized.length) log(`  !  ${unsized.length} item(s) have no Size and sit outside the bar`);
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
      ['pr', linkValue(prUrl, prLabel(flags['pr-number'], flags['pr-author']))],
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
      ['pr', linkValue(prUrl, prLabel(flags['pr-number'], flags['pr-author']))],
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
      devLine() +
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
      devLine() +
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
      devLine() +
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

/* ------------------------------------------------------------------ *
 * progress: board setup
 * ------------------------------------------------------------------ */

/**
 * Create the two columns the progress bar needs.
 *
 * This exists because of index 5. Adding a Status column through the monday UI
 * means letting monday choose where each label sits, and a label landing at
 * index 5 makes every item with no value set report that label - which for a
 * Size column would silently give unsized tickets a weight. Creating the column
 * through the API is the only way to state the indexes outright.
 *
 * Creates nothing that already exists, so it is safe to re-run.
 */
async function cmdSetupProgress(cfg, flags, dryRun) {
  const p = cfg.progress || {};
  const weights = p.weights || {};
  if (!Object.keys(weights).length) fail('progress.weights is empty - nothing to build a Size column from.');

  const sizeTitle = typeof flags['size-title'] === 'string' ? flags['size-title'] : 'Size';
  const weightTitle = typeof flags['weight-title'] === 'string' ? flags['weight-title'] : 'Weight';

  // Indexes stated explicitly, contiguous from 0, and 5 is left alone. Anything
  // beyond five sizes would collide with it, which is a reason to have fewer
  // sizes rather than a reason to use index 5.
  const sizes = Object.keys(weights);
  if (sizes.length > 5) {
    fail(
      `progress.weights has ${sizes.length} sizes; this can only place 5 without ` +
      `using index 5, which monday reserves as a status column's empty slot.`
    );
  }
  const labels = {};
  sizes.forEach((label, i) => { labels[String(i)] = label; });

  const board = await fetchBoardColumns(cfg);
  log(`Board ${cfg.boardId}: "${board.name}"\n`);

  const found = {};
  for (const [key, title, type, defaults] of [
    ['size', sizeTitle, 'status', { labels }],
    ['weight', weightTitle, 'numbers', null],
  ]) {
    const existing = board.columns.find((c) => c.title === title);
    if (existing) {
      log(`  ${title}: already exists (${existing.id}, type "${existing.type}") - leaving it alone`);
      found[key] = existing.id;
      continue;
    }
    if (dryRun) {
      log(`  DRY-RUN  create ${type} column "${title}"` +
        (defaults ? ` with labels ${JSON.stringify(labels)}` : ''));
      found[key] = `<new ${key} id>`;
      continue;
    }
    const data = await gql(
      cfg,
      `mutation ($boardId: ID!, $title: String!, $type: ColumnType!, $defaults: JSON) {
         create_column(board_id: $boardId, title: $title, column_type: $type, defaults: $defaults) {
           id title type settings_str
         }
       }`,
      {
        boardId: String(cfg.boardId),
        title,
        type,
        defaults: defaults ? JSON.stringify(defaults) : null,
      }
    );
    const col = (data || {}).create_column;
    if (!col || !col.id) throw new Error(`monday accepted create_column for "${title}" but returned no id`);
    found[key] = col.id;
    log(`  ${title}: created ${col.id} (${col.type})`);

    // Read back what monday actually stored rather than trusting that it took
    // the indexes we asked for. This is the whole point of the command.
    if (type === 'status') {
      let stored = {};
      try {
        stored = JSON.parse(col.settings_str || '{}').labels || {};
      } catch { /* reported as unverified below */ }
      const at5 = stored['5'];
      if (at5) {
        log(`  X  "${at5}" landed at index 5 despite asking otherwise.`);
        log('     Delete this column and raise it - do NOT start sizing tickets.');
      } else if (Object.keys(stored).length) {
        log(`     indexes: ${Object.entries(stored).map(([i, l]) => `${i}=${l}`).join(' ')} (5 free)`);
      } else {
        log('     could not read the stored indexes back - run "check" before sizing anything.');
      }
    }
  }

  log('\nPaste into monday-config.json under "columns":');
  log(`  "size": ${JSON.stringify(found.size)},`);
  log(`  "weight": ${JSON.stringify(found.weight)}`);
  log('\nThen: node .github/scripts/monday-sync.js check');
}

/* ------------------------------------------------------------------ *
 * progress
 * ------------------------------------------------------------------ */

/**
 * Weighted project progress, written back onto the board.
 *
 * Counting tickets treats "fix a typo" and "build the Sage price sync" as the
 * same unit of work, so the bar lurches and nobody believes it. Every ticket
 * carries a Size, each size is worth some points, and progress is points.
 *
 * Two figures, not one, and this is the part worth defending. "Completed" is
 * set by hand after sign-off, so counting only that would show a build sitting
 * at 20% while most of it is finished and sitting on dev waiting for someone to
 * look at it. Counting dev as done would tell a client something is finished
 * when nobody has checked it. So: no partial credit, and the on-dev figure is
 * reported alongside rather than folded in.
 */

const ICON = { done: '✅', onDev: '🔵', blocked: '❌', other: '⬜', unsized: '❔' };

/**
 * Percentage, rounded, except at the two ends.
 *
 * 99.6% is not finished and 0.4% is not nothing, and on a figure a client reads
 * those two facts are worth more than the half a percent of accuracy given up.
 *
 * `open` says there is outstanding work that is not inside `whole` at all -
 * in practice, tickets carrying no Size. Without it the bar reads 100% while
 * unsized work is still open, because those tickets are in neither figure:
 * part === whole is true and the clamp above never fires. The warning line
 * further down says so in words, but the number is what gets read.
 */
function pct(part, whole, open = false) {
  if (!whole) return 0;
  const rounded = Math.round((part / whole) * 100);
  if (rounded === 100 && (part < whole || open)) return 99;
  if (rounded === 0 && part > 0) return 1;
  return rounded;
}

/**
 * The bar itself: solid for done, shaded for on-dev, light for the rest.
 *
 * A share that is real but tiny gets one cell rather than rounding away to
 * nothing - a bar showing no progress when there is some is the one output
 * here that would actively mislead. The same argument runs the other way at
 * the top end, so the last cell is held back whenever anything is outstanding:
 * 199 of 200 points rounds to a full 20 cells otherwise, and a full bar next
 * to "99%" makes one of the two a liar. `open` is as in pct().
 */
function bar(donePart, devPart, whole, width, open = false) {
  if (!whole) return '░'.repeat(width);
  let done = Math.round((donePart / whole) * width);
  let dev = Math.round((devPart / whole) * width);
  if (donePart > 0 && done === 0) done = 1;
  if (devPart > 0 && dev === 0) dev = 1;
  const cap = donePart + devPart < whole || open ? width - 1 : width;
  while (done + dev > cap) {
    if (dev > 1) dev -= 1;
    else if (done > 1) done -= 1;
    else break; // a width this small has no cell to give; better than repeat(-1)
  }
  return '█'.repeat(done) + '▓'.repeat(dev) + '░'.repeat(width - done - dev);
}

function stageIcon(cfg, label, stats) {
  const p = cfg.progress || {};
  if ((p.doneLabels || []).includes(label)) return ICON.done;
  if ((p.onDevLabels || []).includes(label)) return ICON.onDev;
  if (label === (cfg.labels || {}).blocked) return ICON.blocked;
  if (stats && stats.pts === 0 && stats.unsized === stats.count) return ICON.unsized;
  return ICON.other;
}

function plural(n, word) {
  return `${n} ${word}${n === 1 ? '' : 's'}`;
}

/** The one-line version, which becomes the item's name on the board. */
function progressName(cfg, s) {
  const p = cfg.progress || {};
  if (!s.totalPts) return `${p.itemName} · nothing sized yet`;
  const open = s.unsized > 0;
  return (
    `${p.itemName} ${bar(s.donePts, s.devPts, s.totalPts, 10, open)} ` +
    `${pct(s.donePts, s.totalPts, open)}% · ${s.donePts}/${s.totalPts} pts · ` +
    `${pct(s.donePts + s.devPts, s.totalPts, open)}% on dev`
  );
}

/** The full version, which becomes an update on that item. */
function progressBody(cfg, s) {
  const p = cfg.progress || {};
  const width = Number(p.barWidth) > 0 ? Number(p.barWidth) : 20;
  const lines = [];

  // Unsized tickets sit outside totalPts entirely, so they cannot show on the
  // bar - but they must still stop it reading as finished. See pct().
  const open = s.unsized > 0;

  if (!s.totalPts) {
    lines.push('No ticket on this board has a Size yet, so there is nothing to weigh.');
  } else {
    lines.push(`${bar(s.donePts, s.devPts, s.totalPts, width, open)} ${pct(s.donePts, s.totalPts, open)}%`);
    lines.push('');
    lines.push(`${s.donePts} of ${s.totalPts} points signed off as done.`);
    lines.push(
      `${s.donePts + s.devPts} of ${s.totalPts} points ` +
      `(${pct(s.donePts + s.devPts, s.totalPts, open)}%) ` +
      `are on dev - done, or waiting on manual QA.`
    );
  }

  // Said out loud, every time, because a progress bar that quietly leaves work
  // out is worse than no progress bar. Unsized tickets have no weight, so they
  // cannot sit anywhere on it, and the finish line moves the day they get one.
  // When every sized point is done the two figures above read "7 of 7" beside
  // 99%, which looks like broken arithmetic unless this line says why.
  if (s.unsized) {
    const holding = s.totalPts > 0 && pct(s.donePts, s.totalPts) === 100;
    lines.push(
      `${plural(s.unsized, 'ticket')} ${s.unsized === 1 ? 'carries' : 'carry'} no Size, ` +
      `so that work is counted nowhere above` +
      (holding
        ? `, and the bar stays under 100% until ${s.unsized === 1 ? 'it has' : 'they have'} one.`
        : '.')
    );
  }
  if (s.excluded) {
    lines.push(`${plural(s.excluded, 'ticket')} excluded (${(p.excludedLabels || []).join(', ')}).`);
  }

  // No column padding anywhere below: monday renders an update as HTML, so a
  // run of spaces collapses to one and any alignment is lost in transit.
  lines.push('', 'By stage');
  for (const [label, c] of s.stages) {
    const bits = [plural(c.count, 'ticket')];
    if (c.pts) bits.unshift(plural(c.pts, 'pt'));
    if (c.unsized) bits.push(`${c.unsized} unsized`);
    lines.push(`${stageIcon(cfg, label, c)} ${label} · ${bits.join(', ')}`);
  }

  if (s.blocked.length) {
    lines.push('', 'Needs a decision');
    for (const item of s.blocked) {
      lines.push(`${ICON.blocked} ${item.ref ? `${item.ref} ` : ''}${item.name}`);
    }
  }

  // Sits above the footer on purpose: the change check compares everything
  // before "Sizes:", so editing howTo republishes rather than going unnoticed.
  const howTo = String(p.howTo || '').trim();
  if (howTo) lines.push('', howTo);

  const scale = Object.entries(p.weights || {}).map(([k, v]) => `${k}=${v}`).join(' ');
  lines.push('', `Sizes: ${scale}. Recalculated ${localStamp(cfg)}.`);

  return lines.join('\n');
}

/**
 * Heading for the board description. Derived from itemName rather than being
 * its own config key - one fewer thing to keep in sync, and the two surfaces
 * should agree on what this thing is called. Leading marks like the "▸" that
 * sorts the item to the top are decoration for a row, not for a heading.
 */
function progressTitle(cfg) {
  const raw = String((cfg.progress || {}).itemName || '').replace(/^[^\p{L}\p{N}]+/u, '').trim();
  return raw || 'Project progress';
}

async function cmdProgress(cfg, flags, dryRun) {
  const p = cfg.progress || {};
  const sizeCol = cfg.columns.size;
  const statusCol = cfg.columns.status;
  const weightCol = cfg.columns.weight;

  // Same reasoning as the missing-token skip: a repo can carry this pipeline
  // before the board has a Size column, and a nightly red X for "you have not
  // set this up yet" is how people learn to ignore the Actions tab.
  if (!sizeCol) {
    log('SKIP  columns.size is not configured, so there are no sizes to weigh.');
    log('      Add a Size status column to the board, run "discover", fill it in.');
    process.exit(0);
  }
  if (!statusCol) fail('columns.status must be configured before progress can be reported.');

  const weights = p.weights || {};
  if (!Object.keys(weights).length) fail('progress.weights is empty - nothing to weigh with.');

  // Two surfaces, switched independently. The board description is the text box
  // under the board title - always visible, notifies nobody. The item is a row
  // like any other, so its updates land in people's feeds and build a history.
  // Turn either off and the other still carries the bar; turn both off and
  // there is nothing to write, which is a config mistake rather than a mode.
  const prefix = String(p.itemName || '').trim();
  const wantsDescription = p.boardDescription !== false;
  if (!prefix && !wantsDescription) {
    fail(
      'progress.itemName is empty and progress.boardDescription is false, so there ' +
      'is nowhere to write the bar. Set one of them.'
    );
  }

  const doneLabels = new Set(p.doneLabels || []);
  const devLabels = new Set(p.onDevLabels || []);
  const excludedLabels = new Set(p.excludedLabels || []);

  // Board order for the breakdown. A Status column stores its labels keyed by
  // index, and that order is the pipeline order as drawn on the board; any
  // other order is one we invented.
  const board = await fetchBoardColumns(cfg);
  const statusColumn = board.columns.find((c) => c.id === statusCol);
  const labelOrder = new Map();
  try {
    const labels = JSON.parse((statusColumn || {}).settings_str || '{}').labels || {};
    for (const [index, label] of Object.entries(labels)) labelOrder.set(label, Number(index));
  } catch { /* unordered output beats no output */ }

  const items = await fetchAllRefItems(cfg, [statusCol, sizeCol, weightCol]);

  const s = {
    totalPts: 0, donePts: 0, devPts: 0, unsized: 0, excluded: 0,
    stages: [], blocked: [],
  };
  const stages = new Map();
  const weightFixes = [];
  const found = [];

  for (const item of items) {
    // The progress item lives on the board it describes, so it has to be kept
    // out of its own totals. Matched on a prefix because the rest of the name
    // is the bar, which changes on every run.
    // Guarded on prefix being non-empty: "".startsWith("") is true, so an
    // unset itemName would otherwise swallow every ticket on the board and
    // report 0 points with a straight face.
    if (prefix && item.name.startsWith(prefix)) {
      found.push(item);
      continue;
    }

    const status = (item.cols[statusCol] || '').trim();
    if (excludedLabels.has(status)) {
      s.excluded += 1;
      continue;
    }

    const size = (item.cols[sizeCol] || '').trim();
    const known = Object.prototype.hasOwnProperty.call(weights, size);
    const weight = known ? Number(weights[size]) : null;

    const stage = status || '(no status)';
    if (!stages.has(stage)) stages.set(stage, { pts: 0, count: 0, unsized: 0 });
    const c = stages.get(stage);
    c.count += 1;

    if (weight === null) {
      s.unsized += 1;
      c.unsized += 1;
    } else {
      c.pts += weight;
      s.totalPts += weight;
      if (doneLabels.has(status)) s.donePts += weight;
      else if (devLabels.has(status)) s.devPts += weight;
    }

    if (status && status === (cfg.labels || {}).blocked) s.blocked.push(item);

    // Weight is derived from Size, never edited by hand - it exists so the
    // board itself can sum and sort by it, and so the weighting is visible to
    // whoever reads the board rather than buried in this script.
    if (weightCol) {
      const current = (item.cols[weightCol] || '').trim();
      const wanted = weight === null ? '' : String(weight);
      if (current !== wanted) weightFixes.push({ item, wanted });
    }
  }

  // Before any write: if we cannot tell which item the bar belongs on, stop.
  // Syncing weights first and failing afterwards would leave the board half
  // changed for no benefit.
  if (found.length > 1) {
    fail(
      `${found.length} items on this board start with "${prefix}": ` +
      `${found.map((i) => `${i.id} (${i.name})`).join(', ')}. ` +
      `Refusing to guess which one is the progress item - delete the extras.`
    );
  }

  s.stages = [...stages.entries()].sort((a, b) => {
    const ai = labelOrder.has(a[0]) ? labelOrder.get(a[0]) : Number.MAX_SAFE_INTEGER;
    const bi = labelOrder.has(b[0]) ? labelOrder.get(b[0]) : Number.MAX_SAFE_INTEGER;
    return ai - bi;
  });

  const name = progressName(cfg, s);
  const body = progressBody(cfg, s);

  log(body);
  log('');

  // One mutation per item, sequentially. Fine for a per-project board; if one
  // ever grows past a few hundred tickets this is the loop that will start
  // costing monday API complexity budget.
  //
  // Failures are collected rather than thrown: Weight is a convenience for
  // sorting and summing on the board, and losing the bar - the thing this
  // command exists to produce - because one number would not write is the
  // wrong trade. The run still ends red, at the bottom, so it is not silent.
  const weightFailures = [];
  if (weightFixes.length) {
    log(`Syncing ${plural(weightFixes.length, 'Weight value')} from Size:`);
    for (const fix of weightFixes) {
      try {
        await setColumns(cfg, fix.item.id, { [weightCol]: fix.wanted }, dryRun);
      } catch (err) {
        log(`  FAILED  item ${fix.item.id}: ${err.message}`);
        weightFailures.push(`${fix.item.id} (${fix.item.name}): ${err.message}`);
      }
    }
  }

  if (wantsDescription) {
    // Rewritten in full every run rather than appended to. The description is
    // a statement of where the project is now, not a log - and the board only
    // stores one, so there is nothing to merge with.
    const desc = `${progressTitle(cfg)}\n\n${body}`;

    // The stamp in the footer changes every run, so comparing the whole string
    // would rewrite it nightly for no reason. Compare everything above it.
    const meat = (t) => String(t || '').split('\nSizes:')[0];
    if (meat(board.description) === meat(desc) && flags.force !== true) {
      log('Board description unchanged since the last run; left alone.');
    } else {
      await setBoardDescription(cfg, desc, dryRun);
    }
  }

  if (prefix) {
    let target = found[0] || null;
    let created = false;
    const groupId = await ensureProgressGroup(cfg, board, dryRun);

    if (!target) {
      log(`No item starting with "${prefix}" on the board yet, creating it.`);
      const id = await createItem(cfg, name, dryRun, groupId);
      if (!id) {
        log(`  DRY-RUN  comment on it:\n${indent(body)}`);
        target = null;
      } else {
        target = { id, name };
        created = true;
      }
    } else if (groupId && target.groupId && target.groupId !== groupId) {
      // Self-healing rather than a one-off: someone dragging the bar into the
      // ticket list is the likely cause, and it should not stay there.
      await moveItemToGroup(cfg, target.id, groupId, dryRun);
    }

    if (target) {
      // The name carries the headline figures, so a name that has not changed
      // means the headline has not changed - and a fresh update every night
      // saying the same thing is how an item's update feed becomes unreadable.
      // --force posts regardless.
      const changed = created || flags.force === true || target.name !== name;
      if (changed) await addUpdate(cfg, target.id, body, dryRun);
      else log('Unchanged since the last run; no update posted.');

      if (!created && target.name !== name) await renameItem(cfg, target.id, name, dryRun);
    }
  }

  // Duplicate refs are checked here as well as in "check", because this is the
  // command that runs on a schedule. resolveRef already refuses to write to an
  // ambiguous ref, but that only surfaces mid-deploy, which is the worst moment
  // to find out. Reported after the bar so the bar still gets written.
  const seen = new Map();
  for (const item of items) {
    if (!item.ref || (prefix && item.name.startsWith(prefix))) continue;
    const key = item.ref.toUpperCase();
    if (!seen.has(key)) seen.set(key, []);
    seen.get(key).push(item);
  }
  const dupes = [...seen.entries()].filter(([, list]) => list.length > 1);

  if (weightFailures.length) {
    console.error(`\n${weightFailures.length} Weight value(s) could not be written:`);
    for (const f of weightFailures) console.error(`  - ${f}`);
    console.error('The bar above is still correct - it is computed from Size, not from Weight.');
  }
  if (dupes.length) {
    console.error(`\n${plural(dupes.length, 'duplicate Ref')} on this board:`);
    for (const [ref, list] of dupes) {
      console.error(`  - "${ref}" on ${list.map((i) => `${i.id} (${i.name})`).join(', ')}`);
    }
    console.error(
      'Every deploy touching one of these refs will refuse to write, because the ' +
      'pipeline will not guess which ticket it means. Fix them on the board.'
    );
  }
  if (weightFailures.length || dupes.length) process.exit(1);
}

/**
 * Write the columnHelp text onto the board as column descriptions.
 *
 * Columns are the one place on a board that holds standing instructions safely.
 * The board description is rewritten by every progress run, and an instruction
 * ITEM would be counted as an unsized ticket and nag forever - a description
 * hangs off the column header, where the person filling it in is already
 * looking, and nothing else touches it.
 */
async function cmdSetupHelp(cfg, flags, dryRun) {
  const help = cfg.columnHelp || {};
  const keys = Object.keys(help);
  if (!keys.length) {
    log('SKIP  columnHelp is empty, so there is nothing to write.');
    process.exit(0);
  }

  let wrote = 0;
  let skipped = 0;
  for (const key of keys) {
    const colId = cfg.columns[key];
    const text = String(help[key] || '').trim();
    if (!colId) {
      log(`  -  ${key.padEnd(12)} column is not configured on this board, skipped`);
      skipped += 1;
      continue;
    }
    if (!text) {
      log(`  -  ${key.padEnd(12)} help text is empty, skipped`);
      skipped += 1;
      continue;
    }
    if (dryRun) {
      log(`  DRY-RUN  describe ${key} (${colId}):\n${indent(text)}`);
      continue;
    }
    await gql(
      cfg,
      `mutation ($boardId: ID!, $colId: String!, $text: String!) {
         change_column_metadata(
           board_id: $boardId, column_id: $colId,
           column_property: description, value: $text
         ) { id }
       }`,
      { boardId: String(cfg.boardId), colId, text }
    );
    log(`  ok ${key.padEnd(12)} described`);
    wrote += 1;
  }
  if (!dryRun) log(`\n${wrote} column(s) described, ${skipped} skipped.`);
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
  setup-progress  [--size-title <t>] [--weight-title <t>]   create the Size and Weight columns
  setup-help                       write columnHelp onto the board as column descriptions
  progress        [--force]        recalculate the weighted progress bar
  refs-from-range --from <rev> [--to <rev>]

  --dry-run           print writes, mutate nothing
  --force             progress: post an update even if the figures are unchanged
  --config <path>     override .github/monday-config.json

  MONDAY_TOKEN must be set.
`;

const COMMANDS = new Set([
  'discover', 'check', 'branch-created', 'pr-opened', 'pr-merged',
  'deployed', 'checks-passed', 'blocked', 'setup-progress', 'setup-help',
  'progress', 'refs-from-range',
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
    case 'setup-progress':   await cmdSetupProgress(cfg, flags, dryRun); break;
    case 'setup-help':       await cmdSetupHelp(cfg, flags, dryRun); break;
    case 'progress':         await cmdProgress(cfg, flags, dryRun); break;
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
