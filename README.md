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
| `tests/` | automated site checks — [tests/README.md](tests/README.md) |
| `.github/` | deploy and monday board pipeline — [.github/README.md](.github/README.md) |
| `.env.example` | template for local runs of the checks and pipeline scripts. Copy to `.env`, which is gitignored |
| `.htaccess` | denies raw data files over HTTP. See [Client data is not kept here](#client-data-is-not-kept-here) |

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
| `shipping.php` | the delivery tariff, plus `class-dorotape-shipping-tariff.php` charging it |
| `collection.php` | the Ready for Collection journey, plus `emails/` for its email class |
| `address-book.php` | saved addresses: storage and the read/write API |
| `address-book-account.php` | the My Account screen for managing them |
| `address-book-checkout.php` | picking one at checkout |
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

## The address book

Trade customers deliver to several sites and invoice to a head office, so they
need more than WooCommerce's one billing and one shipping address.

Built in the theme rather than with a plugin, because the Sage address mapping in
Stage 3 has to read whatever this stores, and a plugin's internal structure is
not a contract.

**The storage shape is the part to be careful with.** One user meta key,
`_dt_address_book`, holds an array keyed by a generated address id. Each entry
has a `label`, a `type` of `billing` or `shipping`, and WooCommerce's own address
field names unprefixed, so an entry can be handed straight to `set_billing_*` or
`set_shipping_*` with no translation layer. Only `label` and `type` are
guaranteed: the rest of the keys come from
`WC()->countries->get_address_fields()`, so they follow the store's settings.
Company is switched off on this site, phone is on for both types. Read the keys
you find rather than the ones you expect.

The customer's WooCommerce billing and shipping addresses are untouched and stay
the defaults. "Make default" copies a saved address onto them.

**Known limitation at checkout.** Picking a saved address sets it on the order,
but the address inputs on the page do not repopulate to match. Changing what the
visible form shows means JavaScript driving the `wc/store/cart` data store, and
the theme has no build step. The mitigation is that each option label carries the
full address, so the customer can see what they picked. Worth revisiting if
checkout ever gets a bundled script.

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

WooCommerce keeps its settings in the options table, not in git, so a deploy does
not carry them. On this project the database itself is moved by hand between
environments, so the settings travel with it and there is nothing to re-apply
after a deploy.

What follows is the record of what was decided and why, since the database says
what a value is but never why it is that. Anything not listed was left alone.

**Accounts** - registration is on, at My Account and at checkout, and the
customer chooses their own password rather than being emailed a generated one.
The checkout login reminder is on because 10,242 customers came across from
Kryptronic; without it a returning customer meets a blank checkout, registers
again, and the duplicate carries none of their history or pricing.

**Payments** - Invoice Gateway on as "Pay on account" with 30 day terms, gated
per customer in `inc/pay-on-account.php`. Cash on Delivery retitled "Pay on
collection", restricted to Local Pickup and off for virtual orders, so it cannot
be chosen for a delivery. BACS and Cheque off. Invoice Gateway's own purchase
order field is deliberately off: `inc/purchase-order.php` already collects one
and stores it where Sage reads it, and enabling both would show the customer two
boxes and write the second where nothing looks.

**Email** - sender `sales@dorotape.co.uk` for both the visible from-address and
wp-mail-smtp's own, which overrides it otherwise. Internal copies go to the same
address. Branding is the site logo and the brand pink; WooCommerce's colour sync
is off, because this is a classic theme with no `theme.json` and the sync would
re-derive WooCommerce's default purple over it.

**VAT** (DR-2) - 20 percent standard rate named "VAT", on `GB` and on `IM`. Prices
are entered and displayed excluding tax with the suffix `ex VAT`, and tax is based
on the delivery address. Every one of those values is the Kryptronic setting of
the same meaning rather than a decision taken here, and
`dorotape-migration/store_tax_rates.php` names the setting beside each one. The
rate itself is confirmed from a credit note carrying 39.60 of tax on 198.00.

Two country decisions are worth stating because they are invisible in the admin
screen. The Isle of Man is a separate country to WooCommerce but sits inside the
UK VAT area, so it carries the rate; a `GB`-only setup would ship Isle of Man
orders with no VAT. Jersey and Guernsey are outside the UK VAT area and carry no
rate, so the Channel Islands are absent by intent. Everywhere else is an export,
zero rated, which is why there is no catch-all row. `inc VAT` exists as a
Kryptronic string but is deliberately unused: it is the suffix the old site would
have shown had display been inclusive, and it was not.

**Shipping** (DR-3) - the published tariff, transcribed from the two Kryptronic
custom shipping scripts and cross-checked against the live Delivery page, which
agrees with them exactly.

The rates are in `inc/shipping.php`, not in WooCommerce settings, and that split is
deliberate: the theme holds the prices and WooCommerce holds only the geography.
Three parts of the tariff cannot be expressed by a native shipping method at all.
Above the free-delivery threshold the *set of options changes* rather than a price,
so on UK mainland economy delivery disappears and the before-noon upgrade drops
from 19.25 to 7.50; Flat Rate has one cost per instance and no view of the
subtotal. There are two separate tariffs chosen per product with different
thresholds, 150 against 12, and Free Shipping allows one minimum per instance.
Under 1.00 the main tariff ships free as a sample. Keeping the numbers in the theme
also means a deploy carries them, where the options table does not.

Seven zones, created by `dorotape-migration/store_shipping_zones.php`. Order
matters, because WooCommerce takes the first zone that matches: the two
postcode-scoped GB zones sit above plain GB, or every Highlands and Northern
Ireland order would price as mainland. The Highlands list is the carrier-standard
one, expanded to explicit outcode wildcards rather than WooCommerce postcode
ranges, whose matcher compares differing-length postcodes inconsistently. It is
ours rather than Michael's: Kryptronic held no postcode list, it showed a dropdown
and let the customer pick their own region.

Which part of the tariff a zone charges is a setting on the method instance, not
the zone's name, so renaming a zone in the admin cannot quietly change what it
costs to deliver there.

Europe is the old site's own list of 18 countries, not WooCommerce's `EU`
continent and not the EU. Gibraltar, San Marino and Switzerland are on it; Poland,
Czechia, Hungary, Finland and the Baltics are not. It is who Doro Tape has shipped
to. The continent would have added Norway, Iceland, Turkey and a dozen more that
the old site quoted as worldwide.

Two things changed rather than moved across. Collection is still WooCommerce's own
Local Pickup, because `inc/collection.php` and the whole DR-11 journey identify a
collection order by the `local_pickup` method id, and a lookalike rate from the
tariff class would read as a delivery and stop the Ready for Collection flow
firing with nothing on screen to show it. But it moved off the catch-all zone onto
UK mainland only, which is what the old tariff offered; it had been offering
collection from Leicestershire to every country on earth. Orders already placed
keep the method stored on them, so history and the collection journey are
unaffected.

The postage tariff is in place but currently unreachable, and that is correct
rather than broken. All 313 `UK_POSTAGE` rows in the inventory export are
CraftStick items, and the CraftStick range was not migrated: no SKU begins `CS-`,
no product title or attribute term mentions it. Note the join is
`inventory.prodnum` to the product's `_kryp_prodnum` meta and not the SKU, because
the SKUs here were reworked for Sage and only 286 of 2,233 still match a Kryptronic
inventory id. A product goes on the `postage` shipping class only when every one of
its inventory rows is `UK_POSTAGE`; 283 products are mixed, and in each of those
the postage row is a CraftStick sheet while the item that actually exists here is
the roll. Importing CraftStick later and re-running the seeder assigns them.

One assumption needs Michael, because the data cannot answer it. The old site
chose a tariff per product, so a basket mixing a roll and a sheet has no defined
answer. `dorotape_package_tariff()` uses the postage tariff only when *everything*
in the basket is a small item. That is the safe direction; the reverse would let
one sticker carry a roll of tape to the Highlands for a pound.

Rates were verified through real zone matching rather than by calling the tariff
functions, across 19 cases covering every zone and every band, including two
negative controls: Aberdeen city `AB10` and Perth `PH1` must fall to mainland and
not be swept up by the `AB` and `PH` outcodes.

Two things are still outstanding here and need the client: the BACS bank details,
and a card gateway. No card gateway of any kind is installed, so a customer who
is not approved for credit and is not collecting has no way to pay.

### Transactional email

The sender is `sales@dorotape.co.uk`, for both the address customers see and the
inbox the shop's own copies arrive in. That is not a guess: the old Kryptronic
system carried 49 email templates, and it was the sender on every customer one
and the recipient on every internal one.

Two things about this are easy to get wrong.

**wp-mail-smtp overrides WooCommerce, not the other way round.** It hooks
`wp_mail_from` at `PHP_INT_MAX`, and out of the box `from_email_force` is on with
`from_email` defaulting to `admin_email`. Activating the plugin without setting
its own sender silently replaces the WooCommerce from address on every email.
Both senders are set, which is why the plugin's own is recorded above
alongside WooCommerce's.

**Email colours resync from the theme unless told not to.** WooCommerce
re-derives them on `customize_save_after` and on any global styles save. This is
a classic theme with no `theme.json`, so what it derives is WooCommerce's own
purple. `woocommerce_email_auto_sync_with_theme` is off, and has to stay off, or
saving anything in the Customizer un-brands every email.

**Delivery credentials are not in this repo, and not in the database.** Deploy is
`git pull` inside `public_html`, and the database gets exported by All-in-One WP
Migration. `wp-config.php` is the one file that is neither, so wp-mail-smtp reads
its SMTP settings from constants defined there:

```php
define( 'WPMS_ON', true );
define( 'WPMS_MAILER', 'smtp' );
define( 'WPMS_SMTP_HOST', '' );
define( 'WPMS_SMTP_PORT', 587 );
define( 'WPMS_SSL', 'tls' );      // 'ssl' on port 465
define( 'WPMS_SMTP_AUTH', true );
define( 'WPMS_SMTP_USER', '' );
define( 'WPMS_SMTP_PASS', '' );
```

Until those exist, mail goes out through PHP `mail()` unauthenticated, which on a
live site means order emails are filed as spam or dropped, because the sending
server has no right to send as `dorotape.co.uk`. WP Mail SMTP's own Email Test
is the quickest way to see whether a given environment can send at all.

`admin_email` is deliberately left alone. WordPress emails a confirmation link to
the new address and does not switch until someone clicks it, so no script can
honestly claim to have changed it. Every WooCommerce recipient is set explicitly
so that nothing depends on it in the meantime, and changing it is a go-live step
done by hand.

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

## Client data is not kept here

**Everything tracked here is served from the webroot.** Deploy is `git pull`
inside `public_html`, so any committed file is reachable over HTTP unless
something stops it. `tests/` ships an `.htaccess` denying access for that reason,
and the theme-root `.htaccess` is the backstop for raw data files anywhere here.

`dorotape-migration/` held the Kryptronic migration: one-off scripts alongside
113 CSV exports of the client's old CMS, among them 10,358 customer rows with
email addresses, password hashes, phone numbers and postal addresses. It had no
deny rule, so all of it was served from the webroot on every deployed site.

It now lives **outside the repository and outside the webroot**, at
`~/dorotape-private/dorotape-migration/` on the machine that ran the migration.
Nothing in the theme reads it: it was scripts that had already been run, plus
their input. Three things keep it out:

- `.gitignore` refuses `dorotape-migration/`, `*.csv`, `*.sql` and `*.xlsx`
- the theme-root `.htaccess` denies that path, and denies raw data extensions
  anywhere in the theme, for servers carrying an untracked or stale copy
- this section, so the next person does not have to reconstruct the reason

**If you need migration data, copy the one file you need to somewhere outside
the repo.** Do not put it back.

**The removal does not clear git history.** The files are gone from the working
tree and from every future clone's checkout, but they remain in earlier commits.
Clearing that needs a history rewrite and a force push, and is tracked
separately.

## One more thing worth knowing

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
