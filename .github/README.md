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

after every deploy   ┐
every weekday 07:17  ┴→ weighted progress bar, written onto the board
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
| `workflows/monday-tracking.yml` | writes ticket state to the board, runs the site checks, recalculates progress |
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

Name it **`<Project> Dev Tracker`** — `Dorotape Dev Tracker`, `Acme Dev Tracker`.
They then sort together in monday and read as pipeline boards rather than client
work. Nothing in the code reads the board *name*, only `boardId`, so renaming a
board can never break the pipeline.

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

**Any column id may be `null`**, which means "this board does not have that
column": the write is skipped and `check` reports it as optional rather than
missing. `size` and `weight` are null until step 7. `branch` and `commit` are
null on this board on purpose — the PR link already leads to both, so they were
two columns of developer detail on a board other people read. The pipeline still
takes `--branch` and `--commit`; it just does not mirror them onto the board.

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

## 7. Turn on the progress bar

Optional. Skip it and everything above still works — `progress` logs a SKIP and
exits 0 until the board has the columns.

```bash
export MONDAY_TOKEN=...
node .github/scripts/monday-sync.js setup-progress --dry-run  # see what it will make
node .github/scripts/monday-sync.js setup-progress            # make it
# paste the two ids it prints into monday-config.json
node .github/scripts/monday-sync.js check                     # validates both
node .github/scripts/monday-sync.js progress --dry-run        # see the figures
node .github/scripts/monday-sync.js progress                  # write them onto the board
```

It creates two columns and skips either one that already exists, so it is safe
to re-run:

| Column | Type | Contents |
| --- | --- | --- |
| **Size** | Status | one label per key in `progress.weights` — `S` `M` `L` `XL` |
| **Weight** | Numbers | left blank; derived from Size on every run |

**Do not add the Size column through the monday UI.** That is the whole reason
this command exists. The UI gives you no say over which index each label gets,
and a label landing at index 5 — monday's blank slot — makes every *unsized*
ticket report that size and quietly earn points for work nobody has done. The
API lets the indexes be stated outright, so `setup-progress` places them
contiguously from 0 and then reads back what monday actually stored to confirm
5 is still free. If it is not, it says so and tells you to delete the column
before sizing anything.

Five sizes is the ceiling for the same reason; a sixth would have to sit at 5.

### Where the bar is written

Two places, each switched off on its own in `progress`:

| | `progress` key | off with |
| --- | --- | --- |
| **Board description** — the text box under the board title | `boardDescription` | `false` |
| **A progress item** — a row on the board | `itemName` | `""` |

They are not redundant. The description is always on screen and costs nobody a
notification, which makes it the right home for a number a client glances at.
The item is a row, so its updates land in people's feeds and stack up into a
history you can scroll back through — the description keeps no history at all,
because a board only stores one and every run overwrites it.

Turn both off and `check` fails rather than running silently: a bar with nowhere
to go is a config mistake, not a mode.

**Clearing `itemName` does not delete the item.** The item is also how the item
is kept out of its own totals — matched on that name — so an emptied `itemName`
leaves the old row on the board being counted as an ordinary unsized ticket.
Delete the row when you turn it off.

The item goes in its own group at the top of the board, named by
`progress.groupName` (`"Progress"` here). The first run creates that group above
every existing one and puts the item straight into it; from then on the pipeline
renames the item in place, and moves it back if someone drags it into the ticket
list.

That group is not decoration. **monday has no mutation that reorders an item
within a group** — `create_item` takes `relative_to`, `move_item_to_group` moves
between groups, and nothing repositions an existing row. A group is the only way
to pin the bar above the tickets. It also reads better: the bar is not a ticket
and should not sit in a list of them.

Set `groupName` to `""` and the item lands wherever monday puts it, which is the
bottom.

Neither surface is rewritten when nothing has changed, so a nightly run on a
quiet week is silent. The comparison deliberately ignores the "Recalculated …"
footer, which would otherwise differ every single run and defeat the check.

### How the number is worked out

```
S = 1    M = 2    L = 5    XL = 8       points
```

Near-Fibonacci on purpose: nobody argues about whether something is a 3 or a 4,
and an XL reads as eight small jobs rather than "a bit more than L".

**Two figures are reported, never one, and never added together.** Completed is
set by hand after sign-off, so counting only that shows a build sitting at 20%
when most of it is finished and waiting on QA. Counting *Deployed to dev* and
*QA* as done tells a client something is finished that nobody has checked. So
the bar reports both — `21% · 10/47 pts · 55% on dev` — and gives no partial
credit to anything in flight.

Three consequences worth knowing before you show it to anyone:

- **Unsized tickets are counted nowhere.** They have no weight, so they cannot
  sit anywhere on the bar. The update says how many there are on every run,
  because a progress bar that quietly leaves work out is worse than none.
- **The bar can go backwards.** Adding a ticket grows the denominator. That is
  correct — the finish line did move — which is why the raw `10/47 pts` is shown
  next to the percentage and not hidden behind it.
- **Without a Cancelled label the bar can never reach 100%.** Abandoned tickets
  stay in the denominator forever. `progress.excludedLabels` already lists
  `Cancelled`, and until that label exists on the board `check` reports it as a
  warning rather than an error — naming a label the board does not have excludes
  nothing, which is harmless.

  There is no mutation that adds a label to an existing Status column —
  `change_column_metadata` has no `labels` property, only `title` and
  `description`. The one API route is `create_labels_if_missing: true` on a
  write to a real item, and it does not let you name the index.

  **Measured, not assumed: monday appends past the highest index rather than
  filling a gap.** Probed on a throwaway board whose Status column had 0–4 and
  6–8 taken and 5 free; the new label went to 9. So the index-5 hazard does not
  apply here, and the label can be added by writing `Cancelled` to any item and
  clearing it again. Do it on the progress item — it is excluded from the totals
  by name before its status is ever read, so nothing it briefly carries can move
  the bar.

  Run `check` afterwards anyway. It reads the stored indexes back, and it costs
  a second to confirm what a future monday release might change.

Everything above is config, in `monday-config.json` under `progress` — the
weights, which labels count as done, which count as on-dev, which are excluded,
the item's name and the bar's width.

### When it recalculates

After every deploy, and on a schedule — `17 5 * * 1-5`, which is 07:17 SAST on
weekdays. The schedule is not optional decoration: **Completed is set by hand,
and nothing in git happens when you set it**, so without a clock the bar would
sit stale for days after a round of sign-offs.

There is also a **Run workflow** button on the monday tracking workflow for
recalculating on the spot, with a checkbox to post an update even when the
figures have not moved. Ordinarily an update is only posted when the headline
figures change, so a nightly run on a quiet board writes nothing.

Two things about GitHub schedules: they only run on the default branch, and
GitHub **disables them after 60 days with no commits**. A quiet project stops
reporting progress silently — that is the first thing to check if the bar
goes stale.

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
node .github/scripts/monday-sync.js setup-progress [--size-title <t>] [--weight-title <t>]
node .github/scripts/monday-sync.js progress    [--force]
node .github/scripts/monday-sync.js refs-from-range --from deployed-dev --to HEAD
```

`--dry-run` works on all of them and prints every intended write without sending
one — the fastest way to see what a command would do to a client's board.

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
- **Progress is recomputed from the board every time, never accumulated.** That
  is what makes it safe for the concurrency group to drop a queued run — the
  next one produces the same answer from scratch — and it means the bar
  self-corrects the moment you fix a Size, with no state to reset.

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
| Progress job logs SKIP every night | `columns.size` is `null` — see step 7 |
| Bar stale for weeks, no runs listed | GitHub disabled the schedule after 60 quiet days. Push anything, or use Run workflow |
| Progress never reaches 100% | Abandoned tickets are still in the denominator — set them to `Cancelled`, or add that label if the board has not got one |
| Two progress items on the board | Something starts with the same name. The script refuses to guess; delete the extra |
| Board description never updates | `progress.boardDescription` is `false`. `check` prints which surfaces are on |
| Board description wiped something you wrote by hand | It is rewritten in full every run. Put standing notes on a pinned item, not there |
| Weight column keeps changing back | It is derived from Size and rewritten on every run. Edit Size, not Weight |

Nothing here fails silently on purpose except the missing-token case, which is
what lets you copy the pipeline in before the board exists.
