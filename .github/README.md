# The pipeline

A ticket moves through a monday board on its own, driven by what actually
happens in git:

```
branch pushed        → In progress
PR opened            → Code review
PR merged            → Merged
Version: bumped      → deploy runs → Deployed to dev
site checks pass     → QA
site checks fail     → Blocked   (comment says whether the code reached dev)
deploy fails         → Blocked   (comment says it did not)
```

You set **Completed** by hand. Production is manual and stays manual.

Everything here is site-agnostic. Copying this onto another project is
configuration only — if you find yourself editing a file in `workflows/`,
something has been hardcoded that should not have been.

---

## Contents

| Path | |
| --- | --- |
| `workflows/deploy.yml` | pulls the theme onto dev when `Version:` changes on `main`. Never talks to monday |
| `workflows/monday-tracking.yml` | writes ticket state to the board and runs the site checks |
| `scripts/monday-sync.js` | all monday API work. No dependencies — plain Node 20 |
| `monday-config.json` | the board's *shape*: id, column ids, labels, ticket prefix. Committed, not secret |
| `../tests/` | the site checks — see [tests/README.md](../tests/README.md) |
| `../.env.example` | template for running the scripts by hand |

---

# Putting this on a new project

## 1. Copy the files

Copy `.github/` and `tests/` into the new theme repo, plus `.env.example` and
the `.env` lines from `.gitignore`.

The theme must be a **git working copy on the dev server**, because deploying is
`git pull` there. Check that it is, and note what the remote is called:

```bash
ssh user@devserver 'git -C /path/to/theme remote -v'
```

## 2. Duplicate the monday board

Duplicate the board *structure only* — you do not want the old tickets.

Then rediscover the ids, because **every column id changes when a board is
duplicated**. `text_mm5sds27` on one board is not `text_mm5sds27` on another,
and nothing warns you: writes go to the old board or fail with a shrug.

```bash
export MONDAY_TOKEN=...
node .github/scripts/monday-sync.js discover --board <new-board-id>
```

Paste the output into `monday-config.json`, then set by hand:

| Key | What to set |
| --- | --- |
| `boardId` | the new board id |
| `refPattern` | the new ticket prefix, e.g. `"\\bAC-(\\d+)\\b"` |
| `timezone` | **your own monday profile timezone** — see the traps below |

There is deliberately **no site URL in this file**. It describes the board. See
step 4.

Then verify, before trusting any of it:

```bash
node .github/scripts/monday-sync.js check
```

This confirms every column exists, is the right *type*, that no two entries
point at the same column, that the status labels match exactly, and that
monday's index-5 slot is free. It fails loudly rather than inventing anything.

### Three traps that cost real time here

**Status label index 5 must be empty.** monday treats index 5 as a Status
column's blank slot, so a label sitting there is what *every unset item*
reports. The first build of this board had `QA` at index 5, and every new ticket
read as already in QA while its stored value was still null. The index is not
visible in the UI — only the API shows it. `check` refuses to pass if it is
occupied.

**`timezone` must match your own monday profile.** Date columns are stored in
UTC and monday renders them in each *viewer's* profile timezone, so this only
affects the human-readable stamp in comments. Set it wrong and the "Deployed at"
column and the comment beside it disagree by hours. Read yours with
`me { time_zone_identifier }`.

**The token's user needs a member seat.** A viewer seat reads fine and fails
every write — the failure looks like a permissions error on a column, not a
seat problem.

## 3. Secrets

Repo → Settings → Secrets and variables → Actions → **Secrets**

| Secret | What |
| --- | --- |
| `MONDAY_TOKEN` | monday API token, from a member-seat user |
| `SSH_HOST` | dev server hostname |
| `SSH_USER` | dev server SSH user |
| `SSH_PRIVATE_KEY` | private key for that user |
| `SSH_PORT` | SSH port |

## 4. Variables

Same page, the **Variables** tab — *not* Secrets. The workflows read `vars.*`,
so anything added as a secret is invisible to them and reads as unset.

| Variable | What | If unset |
| --- | --- | --- |
| `DEV_SITE_URL` | full dev site URL, e.g. `https://example.dev` | **site checks fail with a named error** |
| `DEV_THEME_PATH` | absolute path to the theme's git working copy on the server | **deploy stops before touching the server** |
| `DEV_GIT_REMOTE` | remote name *in the server's clone* | `origin` |
| `DEV_GIT_BRANCH` | branch the server pulls | `main` |

`DEV_SITE_URL` and `DEV_THEME_PATH` have no defaults on purpose, and this is the
single most important thing on this page.

**Values that point at an environment must not travel with a copy of the repo.**
An unset variable fails loudly and tells you its name. A stale committed one
succeeds — at the wrong target. On shared hosting that means project B's theme
deploying into project A's `public_html`, or the new project's board reporting a
confident green about the old project's pages. Unset is a five-second fix;
wrong-and-green is discovered by the client.

`DEV_GIT_REMOTE` catches people out: it is whatever the remote is called *in the
server's clone*, which is often not `origin`.

## 5. Record the site's baseline

The checks compare against a recorded baseline, not against zero — every real
site starts with hundreds of accessibility violations, and a suite that is red
on day one gets ignored by week two. See [tests/README.md](../tests/README.md).

```bash
cp .env.example .env          # set DEV_SITE_URL in it
cd tests
npm install
npx playwright install chromium
npm run baseline              # writes fixtures/baseline.json
npm test                      # should now be green
```

Commit `fixtures/baseline.json`. Expect it to be large.

## 6. Test it end to end

1. Push a branch with a ticket ref in the name, e.g. `feature/AC-1-thing`
   → ticket moves to **In progress**.
2. Open a PR → **Code review**. (Draft PRs are ignored on purpose.)
3. Merge → **Merged**.
4. Bump `Version:` in `style.css` on `main` → deploy runs → tickets go to
   **Deployed to dev**, then **QA** or **Blocked**.

A branch with no ticket ref is fine — the jobs find nothing and no-op quietly.

---

# Configuration, and where each value lives

Three places, and the split is deliberate:

| | Lives in | Travels with a copy of the repo? |
| --- | --- | --- |
| Board *shape* — column ids, labels, ticket prefix | `monday-config.json`, committed | Yes — you re-point it at the new board once |
| Environment — URLs, server paths | GitHub **Variables** | **No** — set per project, or it fails |
| Credentials — token, SSH key | GitHub **Secrets** | **No** |

**Why not put everything in a `.env`?** Because CI cannot win either way. A
committed `.env` is a leaked credential — and since deploy is `git pull` inside
`public_html`, it is a leaked credential *served over HTTP*. A gitignored `.env`
is not in the checkout, so Actions cannot read it. Secrets and Variables are the
only mechanism that is both private and available to a workflow.

`.env` is therefore for **local runs only**. `.env.example` documents every name
in one place; copy it to `.env` and fill in what you need. Both
`scripts/monday-sync.js` and `tests/discover.js` load it themselves, with no
dependency, and **real environment variables always win** — so the file can
never quietly override what CI passed in. In Actions it simply does not exist.

---

# Running the scripts by hand

All of these need `MONDAY_TOKEN`, from `.env` or the environment.

```bash
node .github/scripts/monday-sync.js check                       # validate config against the board
node .github/scripts/monday-sync.js discover --board <id>       # print column ids for a board
node .github/scripts/monday-sync.js branch-created --branch <name>
node .github/scripts/monday-sync.js pr-opened   --branch <name> --pr-url <url> [--pr-number <n>] [--pr-author <login>]
node .github/scripts/monday-sync.js pr-merged   --branch <name> --pr-url <url> [--pr-number <n>] [--pr-author <login>]
node .github/scripts/monday-sync.js deployed    --refs "AC-1 AC-2" --commit <sha> --run-url <url>
node .github/scripts/monday-sync.js checks-passed --refs "AC-1" --run-url <url> --summary "..."
node .github/scripts/monday-sync.js blocked     --refs "AC-1" --stage deploy|checks --run-url <url>
node .github/scripts/monday-sync.js refs-from-range --from deployed-dev --to HEAD
```

`refs-from-range` is how a deploy finds *every* ticket that shipped, not just
the one that happened to bump the version. On the first ever deploy the
`deployed-dev` tag does not exist yet, and it reports only the target commit.

---

# How it works, and why it is split in two

`deploy.yml` does one thing: get code onto dev. It never talks to monday. It
writes a small `deploy-outcome` artifact saying what it did, and
`monday-tracking.yml` picks that up afterwards via `workflow_run`.

That split is the point. A monday outage, an expired token or a wrong column id
**cannot turn a working deploy red**. Tracking is reporting; it must never be
able to break the thing it reports on.

It also means the board can tell two different stories apart. "The deploy
failed" and "the deploy worked and the site is broken" arrive as *different*
comments, because they need different reactions — and a single red tick cannot
tell you which one you are looking at.

Some details that are load-bearing:

- **`workflow_run`'s `github.sha` is the default branch head, not the commit
  that deployed.** Everything downstream pins to `head_sha` instead.
- **The `deployed-dev` tag advances only after monday confirms the write.** If
  the write fails the marker stays put and the next deploy re-reports the range,
  rather than losing those tickets silently.
- **monday writes are serialised** by a concurrency group. Two runs racing on
  the tag would give one of them the wrong range.
- **Generated text goes in through `env:`, never `${{ }}` inside `run:`.** A
  check summary contains backticks and quotes; interpolating it into a shell
  command hands those straight to bash.
- **A closed-but-unmerged PR does nothing.** The ticket is still live work and
  moving it would lose that.

---

# When something does not work

| Symptom | Cause |
| --- | --- |
| Board never updates, Actions all green | `MONDAY_TOKEN` missing → the jobs log `SKIP` and exit 0 by design |
| Every write fails on permissions | Token's user has a viewer seat, not member |
| Every ticket reads as already in QA | A status label is sitting at index 5 — run `check` |
| Writes go nowhere after duplicating a board | Column ids changed — run `discover` again |
| Deploy fails with `DEV_THEME_PATH is not set` | It is set as a Secret, not a Variable |
| Deploy runs, server unchanged | `DEV_GIT_REMOTE` is not `origin` in the server's clone |
| Site checks fail with `DEV_SITE_URL is not set` | Same — Variables tab, not Secrets |
| Deploy never triggers | The `Version:` line in `style.css` did not change on `main` |

Nothing here fails silently on purpose except the missing-token case, which is
what lets you copy the pipeline in before the board exists.
