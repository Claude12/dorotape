# Site check

A general WordPress site check. It is not tied to this site: point `SITE_URL`
at any WordPress install and it works out what to check for itself.

It runs after a deploy lands on dev and reports the result into the monday
board — pass moves the tickets to QA, fail moves them to Blocked.

## What it checks

| Check | What fails |
| --- | --- |
| `pages` | A page returns 400+, prints a PHP warning/notice/fatal, has an uncaught JS error, or a request on the page fails |
| `markup` | A page loses its `<title>`, its single `<h1>`, its canonical link, or its meta description |
| `a11y` | axe finds a WCAG 2.1 AA violation that was not there before |
| `shop` | A product cannot be added to the cart, or checkout does not render |

The shop check runs **only** where WooCommerce is installed. On a plain
WordPress site it skips itself and says so.

**It never places an order.** It stops once the checkout form and its
place-order button are on screen. This runs against a client's dev site, and a
check that leaves real orders behind is a check people switch off.

## Baselines, and why

`fixtures/baseline.json` records the accessibility violations and missing
markup that already exist. The check fails on **new** findings only.

Every real site starts with hundreds of these — this one starts with 191 axe
violations, mostly colour contrast, and no meta description on any page.
Failing on all of them would make the check red from day one, and a check
that is always red gets ignored. An ignored check is worse than no check.

So the baseline is what "already broken" looks like, committed to the repo, and
the check watches for things getting worse. When you fix some of them, or you
accept a new one on purpose:

```bash
npm run baseline      # re-records, then commit the diff
```

The diff shows exactly what changed, which is the point — accepting a new
violation should be a visible decision, not a silent one.

## Running it

```bash
npm install
npx playwright install chromium

npm test                                    # against dev
SITE_URL=https://example.test npm test      # against anything else
npm run report                              # open the HTML report
```

Which site it checks: `SITE_URL` if set, otherwise `DEV_SITE_URL` — from a
gitignored `.env` at the theme root locally (copy [`../.env.example`](../.env.example)),
or the repository variable in CI. There is no final fallback: with neither set
it stops and names them, rather than quietly checking whichever site was
hardcoded last. That matters most for a copy of this suite on a new project,
where a default would mean checking the *previous* client's site and reporting a
confident green. Full setup: [`.github/README.md`](../.github/README.md).

`npm test` runs `discover.js` first (as `pretest`), which writes
`.artifacts/plan.json`: the URLs to check and which product to buy.

## How the URLs are chosen

`discover.js` samples up to `SAMPLE_SIZE` (default 24) URLs, spread evenly
across post types rather than taking the newest of one:

1. `/wp-sitemap.xml`, then `/sitemap_index.xml`, then `/sitemap.xml`.
2. If there is no sitemap — the REST API (`/wp-json/wp/v2/pages`, `/posts`,
   and the WooCommerce Store API).
3. Plus `/shop/`, `/cart/`, `/checkout/`, `/my-account/` where they exist.

The fallback matters more than it sounds. WordPress switches off
`/wp-sitemap.xml` entirely when "Discourage search engines from indexing this
site" is on, which is the correct setting for a dev site — so the environment
this check exists to watch is precisely the one least likely to have a sitemap.
Dev has none.

## Ignored noise

`lib/plan.js` ignores 401 and 403 from `/wp-json/` endpoints. Plugins routinely
call their own authenticated REST endpoints on every page and accept the
rejection — YITH Wishlist does it here on every single page load. Failing on
those would make the check permanently red on most WordPress sites.

Everything in that ignore list is a check being deliberately given up. Keep it
short.

## Notes

- `node_modules/` and `.artifacts/` are gitignored. Deploy is `git pull` on the
  server, so anything committed here lands inside `public_html` — which is also
  why `.htaccess` in this folder denies all HTTP access.
- CI installs with `npm ci`, so `package-lock.json` is committed.
- This has its own `package.json`, separate from the theme's. The theme's stock
  `_s` one pins node-sass 7, which will not install on current Node.
