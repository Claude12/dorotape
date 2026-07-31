# Setting this pipeline up on a new project

What this gives you: a DR-style ticket moves through a monday board on its own —
branch → In progress, PR → Code review, merge → Merged, version bump → Deployed
to dev, automated checks → QA or Blocked. You set Completed by hand. Production
stays manual.

Nothing below requires editing a workflow file. If you find yourself editing
`.github/workflows/`, something has been hardcoded that should not have been.

---

## 1. The repo

Copy `.github/` and `tests/` into the new theme repo.

The theme must be a git working copy **on the dev server**, because deploying is
`git pull` there.

## 2. The monday board

Duplicate the existing board (structure only — you do not want the old tickets).

Then get the new ids, because **every column id changes when a board is
duplicated**. `text_mm5sds27` on one board is not `text_mm5sds27` on another.

```bash
export MONDAY_TOKEN=...
node .github/scripts/monday-sync.js discover --board <new-board-id>
```

Paste the output into `.github/monday-config.json`, then edit by hand:

| Key | What to set |
| --- | --- |
| `boardId` | the new board id |
| `devUrl` | the dev site URL, with scheme |
| `timezone` | **your monday profile timezone** — see below |
| `refPattern` | the new ticket prefix, e.g. `\\bAC-(\\d+)\\b` |

Verify before trusting it:

```bash
node .github/scripts/monday-sync.js check
```

This checks that each column exists, is the right *type*, that no two entries
point at the same column, that the status labels match exactly, and that
monday's index-5 slot is free. It fails loudly rather than inventing anything.

### Two traps that cost real time here

**Status label index 5 must be empty.** monday treats index 5 as a Status
column's blank slot, so a label sitting there is what *every unset item*
reports. The first build of this board had `QA` at index 5, and every new ticket
read as already in QA while its stored value was still null. The index is not
visible in the UI — only the API shows it. `check` now refuses to pass if it is
occupied.

**`timezone` must match your own monday profile.** Date columns are stored in
UTC and monday renders them in each *viewer's* profile timezone. Set this wrong
and the "Deployed at" column and the comment beside it disagree by hours. Read
yours with `me { time_zone_identifier }`.

Also: the token's user needs a **member** seat. A viewer seat reads fine and
fails every write.

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

Same page, the **Variables** tab — not Secrets. The workflows read `vars.`, so a
value added as a secret is invisible to them.

| Variable | What | Default if unset |
| --- | --- | --- |
| `DEV_SITE_URL` | full dev site URL, e.g. `https://example.dev` | **none — deploy checks fail** |
| `DEV_THEME_PATH` | absolute path to the theme's git working copy on the server | **none — deploy fails** |
| `DEV_GIT_REMOTE` | remote name *on the server* | `origin` |
| `DEV_GIT_BRANCH` | branch to pull | `main` |

`DEV_SITE_URL` and `DEV_THEME_PATH` have no defaults deliberately. A default
would mean a copy of this repo with the variable unset silently deploys to, or
checks, the *previous* project — and reports success to the new board.

`DEV_GIT_REMOTE` catches people out: it is whatever the remote is called in the
server's clone, which is often not `origin`. Check with
`git -C <path> remote -v` over SSH.

## 5. Record the site's baseline

The checks compare against a recorded baseline, not against zero — see
[tests/README.md](../tests/README.md) for why.

```bash
cd tests
npm install
npx playwright install chromium
npm run baseline      # writes fixtures/baseline.json
npm test              # should now be green
```

Commit `fixtures/baseline.json`. Expect it to be large; every real site starts
with hundreds of accessibility violations.

## 6. Test it end to end

1. Branch named with a ticket ref, e.g. `feat/AC-1-something` → ticket goes to
   **In progress**.
2. Open a PR → **Code review**. (Draft PRs are ignored on purpose.)
3. Merge → **Merged**.
4. Bump `Version:` in `style.css` on the default branch → deploy runs → tickets
   go to **Deployed to dev**, then **QA** or **Blocked**.

A branch with no ticket ref in its name is fine — the jobs find nothing and
no-op quietly.

---

## Why the workflows are split

`deploy.yml` does one thing: get code onto dev. It never talks to monday. It
writes a small `deploy-outcome` artifact, and `monday-tracking.yml` reads it
afterwards via `workflow_run`.

That split is the point. A monday outage, an expired token or a wrong column id
cannot turn a working deploy red. It also means "the deploy failed" and "the
deploy worked but the site is broken" reach the board as *different* comments —
they need different reactions, and a single red tick cannot tell you which one
you have.
