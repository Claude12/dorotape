# Dorotape

Custom WooCommerce theme for [dorotape.co.uk](https://dorotape.co.uk) —
self-adhesive films and wrapping solutions. Built by Altitude Marketing.

Built on [Underscores (`_s`)](https://underscores.me/): classic PHP templates,
ACF PRO, Classic Editor, no page builder. The stylesheet is hand-written, not
compiled, so see [Build step](#build-step) before you go looking for one.

**One exception: Cart and Checkout are the WooCommerce blocks.** See
[Cart and checkout](#cart-and-checkout). It is the single most load-bearing fact
about this theme and the easiest one to miss.

### Which editor a page gets

Classic for normal pages, blocks for the WooCommerce ones that need them. This
is enforced in [inc/cleanup.php](inc/cleanup.php), not by a plugin: the block
editor is off for every post type, and switched back on per post for Cart and
Checkout only.

| Page | Editor | |
| --- | --- | --- |
| Cart, Checkout | blocks | genuinely block built |
| My account | classic | it is `[woocommerce_my_account]`, a shortcode |
| Shop | classic | no content, WooCommerce renders it |
| Wishlist | classic | a YITH shortcode |
| Privacy Policy, Refund and Returns | classic | unused core boilerplate |
| anything new | classic | the default |

Four of those pages contain block comments because WordPress wraps a lone
shortcode in one, or because core created the page. That is not the same as
being block built, so the rule tests for Woo's cart and checkout blocks rather
than for any block at all.

---

## Layout

| Path | |
| --- | --- |
| `*.php` (root) | `_s` template hierarchy — `header.php`, `footer.php`, `single.php`, `archive.php`, `page.php`, `search.php`, `404.php` |
| `template-parts/` | reusable template fragments |
| `inc/` | all theme logic, each file `require`d from `functions.php` |
| `acf-json/` | ACF field groups, synced to disk |
| `css/`, `js/` | `scaffold.css` and `scaffold.js` — the bulk of the front end |
| `style.css` | theme header plus hand-written styles. **The `Version:` line triggers deploys** |
| `bin/` | CLI scripts. `store-settings.php` is the record of the WooCommerce settings this project chose. See [Store settings](#store-settings) |
| `dorotape-migration/` | one-off scripts and data from the Kryptronic migration |
| `tests/` | automated site checks — [tests/README.md](tests/README.md) |
| `.github/` | deploy and monday board pipeline — [.github/README.md](.github/README.md) |
| `.env.example` | template for local runs of the checks and pipeline scripts. Copy to `.env`, which is gitignored |

### `inc/`

| File | |
| --- | --- |
| `setup.php` | theme supports, menus, enqueues |
| `cleanup.php` | strips WordPress defaults |
| `admin.php` | admin-side tweaks |
| `woocommerce.php` | all Woo integration — via hooks, not template overrides |
| `pricing.php` | price calculation |
| `poa.php` | price-on-application products |
| `cutsize.php` | cut-to-size ordering |
| `quickadd.php` | quick add to cart |
| `purchase-order.php` | PO number at checkout, stored where the Sage sync reads it |
| `pay-on-account.php` | gates the Invoice gateway on an approved-for-credit user flag |
| `collection.php` | the Ready for Collection journey, plus `emails/` for its email class |
| `template-tags.php`, `template-functions.php` | `_s` stock, extended |

WooCommerce is customised through hooks in `inc/woocommerce.php`. There is no
`woocommerce/` override directory in the theme, and adding one is a decision to
make deliberately rather than by accident.

## Cart and checkout

Both pages are the **block** versions, not the classic shortcodes. The Checkout
page content is `<!-- wp:woocommerce/checkout -->`, and the front end is React.

This is not a detail, it is the thing that decides how half of the store work has
to be built:

- **Classic checkout hooks do not fire.** `woocommerce_after_order_notes`,
  `woocommerce_checkout_fields`, `woocommerce_before_checkout_form` and the rest
  of that family produce nothing. Code using them looks correct in review, passes
  a syntax check, and silently does not exist on the page.
- **Adding a field means
  [`woocommerce_register_additional_checkout_field()`](https://developer.woocommerce.com/docs/cart-and-checkout-additional-checkout-fields/)**
  (WooCommerce 8.9+). `inc/purchase-order.php` is the worked example, including
  where the value lands in the database and how it reaches Sage.
- **Registered fields render on the order confirmation page only.** Admin,
  emails and My Account are yours to add. It is easy to believe a field works
  because it appeared at checkout and on the thank-you page.
- **Third-party plugins that say "adds a checkout field" usually mean classic.**
  Check before trusting one. Woosage's own PO field is exactly this: it ships a
  block-compatible method and the line that would hook it up is commented out.

**My Account is not affected.** It is the `[woocommerce_my_account]` shortcode,
so the `woocommerce_account_*` hooks, endpoints and template overrides all work
normally there. The block rules above apply to Cart and Checkout only.

Before building anything that touches checkout, confirm which checkout you are
building for.

## Local development

The theme lives at:

```
/Applications/XAMPP/xamppfiles/htdocs/dorotape_wordpresscms/wp-content/themes/dorotape
```

Edit and refresh. There is nothing to run.

### Build step

There isn't one, despite appearances. `package.json` is stock `_s` and its
`watch` / `compile:css` scripts point at a `sass/` directory **that does not
exist in this repo**. They also depend on node-sass 7, which will not install on
current Node. `style.css` and `css/scaffold.css` are edited directly.

`tests/` deliberately has its own `package.json` for exactly this reason — so
the check suite installs without dragging in the dead `_s` toolchain.

## Store settings

WooCommerce keeps its settings in the options table, so they are not in git and a
deploy does not carry them. Configured by hand they have to be repeated
identically on local, dev and live.

`bin/store-settings.php` is the record of the ones this project decided on, with
the reason for each written next to it. Run it on any environment to see what
differs there, or to bring it into line:

```bash
php bin/store-settings.php            # show what differs, change nothing
php bin/store-settings.php --apply    # write the differences
```

`bin/shipping-collection.php` works the same way and covers the one piece of
shipping configuration the collection journey depends on:

```bash
php bin/shipping-collection.php           # show the current zones and methods
php bin/shipping-collection.php --apply   # add Local Pickup if it is missing
```

It adds a single method and does not design the zone layout, which is a
commercial decision and its own ticket. If a Local Pickup already exists
anywhere, it does nothing.

It is not a mirror of every WooCommerce setting, only of the decisions. Anything
absent from the file is left alone, so an admin changing something else in the UI
is never overwritten.

CLI only, and `bin/.htaccess` denies the folder over HTTP. Both, because deploy
puts it inside `public_html`.

## Deploying

Dev deploys itself when the `Version:` line in `style.css` changes on `main`:

```
push to main (style.css) → Deploy on version bump → git pull on the dev server
                                                  → monday tracking → site checks
                                                  → weighted progress bar
```

**Bumping the version is the deploy button.** Change that line only when you
mean to ship. `.cursorrules` reserves it for the project owner.

Production is manual and stays that way.

After a deploy lands, [tests/](tests/) runs against dev — pages load clean, no
JS errors, no new accessibility violations, sane markup, and a product still
reaches checkout. Pass moves the tickets to QA; fail moves them to Blocked with
a comment stating whether the code actually reached dev, because "the deploy
broke" and "the deploy worked and the site is broken" need different reactions.

The board also carries a **weighted progress bar** — every ticket has a Size
(S/M/L/XL), progress is points rather than ticket counts, and it recalculates
after each deploy and on a weekday schedule. It is written straight onto the
board, in the description under the board title and on a progress item, so there
is no separate report to host or keep in sync. Setup is step 7 of
[.github/README.md](.github/README.md).

Setting all of this up on another project: [.github/README.md](.github/README.md).
Nothing in it is specific to this site — every value that points at an
environment is a GitHub variable, so a copy cannot inherit this project's URL or
server path.

## Two things worth knowing

**Everything tracked here is served from the webroot.** Deploy is `git pull`
inside `public_html`, so any committed file is reachable over HTTP unless
something stops it. `tests/` ships an `.htaccess` denying access for that
reason. `dorotape-migration/` does not have one, and it holds 108 CSV exports
of the client's old CMS — worth addressing.

**`.cursorrules` describes a structure this repo does not have.** It specifies
`/templates`, `/partials`, `/assets/js`, `/assets/scss`, `/assets/css`; the repo
actually uses `template-parts/`, `js/`, `css/` and no SCSS at all. Treat its
conventions (BEM, PHP typing, flag-before-creating) as live, and its paths as
aspirational until someone reconciles them.

## Conventions

See [.cursorrules](.cursorrules). The short version: BEM for all new selectors,
`js-` prefixed hooks that are never styled, `is-`/`has-` for state, vanilla JS,
`strict_types=1` and full type hints in PHP, theme-slug prefixes on every
function and hook, no hardcoded URLs, no debug code committed.

Commits follow conventional prefixes: `feature` / `bugfix` / `style` /
`refactor` / `chore`.

## Licence

Proprietary. See [LICENSE](LICENSE).
