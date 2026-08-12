# Site check

A general WordPress site check. It is not tied to this site: point `SITE_URL`
at any WordPress install and it works out what to check for itself.

It runs after a deploy lands on dev and reports the result into the monday
board: pass moves the tickets to QA, fail moves them to Blocked.

## What it checks

| Check | What fails |
| --- | --- |
| `discovery` | The run did not actually check anything. See below |
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

Every real site starts with hundreds of these. This one starts with mostly
colour contrast, and no meta description on any page. Failing on all of them
would make the check red from day one, and a check that is always red gets
ignored. An ignored check is worse than no check.

So the baseline is what "already broken" looks like, committed to the repo, and
the check watches for things getting worse. When you fix some of them, or you
accept a new one on purpose:

```bash
npm run baseline      # re-records, then commit the diff
```

The diff shows exactly what changed, which is the point: accepting a new
violation should be a visible decision, not a silent one.

### What each entry covers

Pages the check visits in full get an entry of their own, keyed by URL, like
`markup /cart/`.

Products do not. There are more of them than any run is going to open, so a run
samples, and the ten sampled today are not the ten sampled after somebody adds a
product. Those pages are recorded per **template** instead, as `markup ~products`
and `a11y ~products`. Products are the same page with different words in them, so
one entry describes all of them, and adding a product does not make the check red.

Two things follow from that, both deliberate:

- **`npm run baseline` visits every page it can list**, not the sample a normal
  run uses. It is slower on purpose. A template's record is only as complete as
  the pages it was recorded from, and features that only some products have (a
  tier pricing table, a gallery) are missed by a small sample.
- **Selectors are stored with content identifiers removed.** `.post-1023`
  becomes `.post-N`, `a[data-product_id="527"]` becomes `a[data-product_id="N"]`,
  `:nth-child(3)` becomes `:nth-child(n)`. Those parts say which row this is,
  not what is wrong with it, so leaving them in would give the same unchanged
  fault a new identity on every page and after every edit.

`SAMPLE_SIZE=n` overrides how many pages a run opens, if you want a slower and
more thorough check than the one CI runs.

## Running it

```bash
npm install
npx playwright install chromium

npm test                                    # against dev
SITE_URL=https://example.test npm test      # against anything else
npm run report                              # open the HTML report
```

Which site it checks: `SITE_URL` if set, otherwise `DEV_SITE_URL`, from a
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
2. If there is no sitemap, the REST API (`/wp-json/wp/v2/pages`, `/posts`,
   and the WooCommerce Store API).
3. Plus `/shop/`, `/cart/`, `/checkout/`, `/my-account/` where they exist.

The fallback matters more than it sounds. WordPress switches off
`/wp-sitemap.xml` entirely when "Discourage search engines from indexing this
site" is on, which is the correct setting for a dev site, so the environment
this check exists to watch is precisely the one least likely to have a sitemap.
Dev has none.

## A green run that checked nothing

Three things used to let this suite report success without verifying anything.
All three came from the same shape: the checks are generated by looping over what
discovery found, and **a loop over an empty list declares no tests, so the suite
passes.** The worst outcome was never a red run, it was a green one.

**Discovery finding no pages.** `plan.urls` always contains `/`, because every
site has a front page, so a discovery that reached nothing still produced a
one-page plan. One page loaded fine, four checks reported a tick, and the board
was told the deploy was verified. `specs/discovery.spec.js` now fails when
`plan.discovered` is 0, and again when WooCommerce is present but the Store API
returned nothing purchasable, which silently skipped the entire shop journey.

**A stale plan pointing at another site.** `playwright.config.js` took its
`baseURL` from `plan.json` in preference to the environment, so a leftover plan
redirected a whole run at the site it was generated from while the log said
otherwise. The environment now wins, and a plan naming a different site is a hard
error rather than a preference: its page list does not describe the site being
checked, so neither answer is usable. Both `discover.js` and the config resolve
this through `lib/env.js`, so there is one answer to the question instead of two.

**Checking before the deploy landed.** On 7, 10 and 11 August 2026 the checks came
back red on the front page alone, with "Execution context was destroyed" or a flat
timeout, and passed on a re-run of the identical commit. Dev was not serving the
new theme yet. `wait-for-deploy.js` runs before discovery and polls the theme's
own `style.css` over HTTP until its `Version:` matches the commit being checked.
Nothing else is a reliable proxy: the front page returns 200 long before PHP is
serving new code, and a `git reset` on the server says nothing about what opcache
still holds. If the version never appears, no checks run at all and the board is
told the code did not reach dev, rather than that it reached dev and might be
broken.

## Ignored noise

`lib/plan.js` ignores 401 and 403 from `/wp-json/` endpoints. Plugins routinely
call their own authenticated REST endpoints on every page and accept the
rejection. YITH Wishlist does it here on every single page load. Failing on
those would make the check permanently red on most WordPress sites.

Everything in that ignore list is a check being deliberately given up. Keep it
short.

## Notes

- `node_modules/` and `.artifacts/` are gitignored. Deploy is `git pull` on the
  server, so anything committed here lands inside `public_html`, which is also
  why `.htaccess` in this folder denies all HTTP access.
- CI installs with `npm ci`, so `package-lock.json` is committed.
- This has its own `package.json`, separate from the theme's. The theme's stock
  `_s` one pins node-sass 7, which will not install on current Node.
