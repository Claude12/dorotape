# Dorotape

Custom WooCommerce theme for [dorotape.co.uk](https://dorotape.co.uk) -
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
| Delivery, Returns, Privacy, Terms | classic | migrated policy copy, DR-6 |
| anything new | classic | the default |

Four of those pages contain block comments because WordPress wraps a lone
shortcode in one, or because core created the page. That is not the same as
being block built, so the rule tests for Woo's cart and checkout blocks rather
than for any block at all.

### Policy pages (DR-6)

Four pages, published from the Kryptronic CMS export by
`dorotape-migration/store_policy_pages.php`: Delivery Information (726 words),
Returns and Refund Policy (553), Privacy Policy (1,086) and Terms and Conditions
of Business (2,567). Returns and Privacy adopt the drafts that WooCommerce and
WordPress ship on their own slugs rather than adding a second page on the same
subject, because WordPress points its privacy tools at that specific post id.
`woocommerce_terms_page_id` and `wp_page_for_privacy_policy` are set.

The copy is the client's and moved verbatim. Nothing was reworded, no figure was
updated, and the typos were left alone and reported instead. The markup around it
had to change, because it could not ship as it stood.

The export is Windows-1252. Read as UTF-8 every price on the delivery page becomes
a replacement character and the page publishes "?150". The seeder converts, and
checks for the pound signs afterwards rather than assuming the conversion worked.

The markup carried real defects, not merely dated style. `<h2 style=color:#009ee3;">`
has no opening quote, so the value runs to a stray closing one: PHP's own
`strip_tags` reads it as an unterminated attribute and swallows the rest of the
document, which is why the terms page first measured zero words on 18,000
characters. Lists sat inside paragraphs, every list item had its own `<ul>` so a
four-point list rendered as four lists, seven closers were mis-nested as
`</ul></li>`, five `<li>` and one `<p>` were never closed, ampersands were bare,
and the delivery page carried a `<style>` block of `#data` id selectors plus stray
`</head>` and `<body>` tags mid-content. The terms page marked its 19 clause
headings as bold-underlined paragraphs, so the document had no outline at all;
they are now real headings.

The cleaning is a sequence of narrow named rules, so a surprise in the source
shows up as one rule not firing rather than as silent damage to the copy, and the
result is then re-serialised through libxml to close whatever the author left
open. The seeder refuses to write if any defect survives its own checks, which is
how two broken regexes were caught rather than published.

Presentational colour was dropped rather than translated, since hardcoded brand
hex in post content is what makes a later restyle expensive. The delivery table's
styling moved to `.dt-policy-table` in `css/scaffold.css`. Its hover state was
white on `#009ee3`, a contrast ratio of 2.3:1; it now tints the row background and
leaves the text alone, and all three colour pairs pass.

Verified by comparing the visible text of each source page against the published
page word by word: all four are identical in vocabulary and in order, so the
migration provably changed markup and not words.

Worth knowing, and flagged to the client rather than fixed here: the delivery page
does not mention the sub-1.00 free sample band or the postage tariff, both of
which the old site's shipping scripts do apply, so the published page was already
an incomplete description of what the old site charged. The privacy page also
carries four typos ("an order for too", "relevent", "platfroms", "data base") and
names Meta and Sage, which is a data-protection statement worth a read rather than
a copy-paste.

### Navigation

Three menu locations: Primary Navigation, Secondary Navigation and Footer
Navigation. Only two have menus in them.

Primary holds the category menu the client built. It is a four item stub down one
branch (Applications > Retail Display > Gold Films / Silver Films) against an
intended eight top level categories, and filling it out is DR-33, which is waiting
on the client to say which categories go where.

Secondary holds the top menu: My account, Wishlist, Cart. Built by hand in the
admin, like every other piece of database state. Checkout is deliberately not in
it, because it is reached from the cart and a header link to it drops a customer on
an empty checkout. Labels match the page titles rather than being reworded; "Basket"
reads better on a UK trade site but the cart page, the cart block and every
WooCommerce notice all say "Cart".

Footer is empty. The policy pages are its natural home when the client is ready
for that.

An unassigned location renders nothing. This matters more than it sounds:
`wp_nav_menu()`'s default `fallback_cb` is `wp_page_menu()`, which lists every
published page alphabetically, so the two empty slots were putting Cart, Checkout,
My account, Wishlist and all four policy pages into the header on every page. It
looked like a menu nobody could edit, because it was not a menu at all, and there
was nothing in Appearance > Menus to change. All three calls now pass
`'fallback_cb' => false`, wrapped in `has_nav_menu()` so an empty slot does not
leave a bare `<nav>` landmark behind either.

The top menu is a real menu and not markup in `header.php` for the same reason.
The complaint that started this was that those links could not be found or changed
in the CMS; hardcoding them would have recreated exactly that.

Menus live in the database and the deploy only carries theme files, so a menu built
by hand on one site does not travel to the others. It gets rebuilt in the admin on
each site, or carried with the database. There was a provisioning script for this
at one point; it was dropped because the database is moved wholesale between
environments anyway, so a script that reproduced one menu earned its keep only
until the first migration.

Two header defects came out of the same work.

The mobile menu could not be opened at all. `navigation.js` finds the toggle button
through `#site-navigation` and will not look anywhere else, so the button has to
live inside `.nav-primary`; the mobile breakpoint was hiding that whole element,
button included. It now hides the list and leaves the nav, and the opened list
drops out of the header row as an absolutely positioned panel rather than being
wedged into it.

A `#secondary-menu` block in `css/scaffold.css` has been deleted. Every rule in it
was of the shape `#secondary-menu ul li a`, which assumes `#secondary-menu` is a
wrapper containing a list. `header.php` passes `'container' => false`, so
`#secondary-menu` is the list, and those selectors matched nothing. Two
declarations were live and both did harm: a stray padding, and an ID weight
`display: none` under 768px which hid the account links on mobile and could not be
overridden by any class rule. That is what made the account links measure as
present in the DOM while being invisible on a phone.

---

## Layout

| Path | |
| --- | --- |
| `*.php` (root) | `_s` template hierarchy - `header.php`, `footer.php`, `single.php`, `archive.php`, `page.php`, `search.php`, `404.php` |
| `template-parts/` | reusable template fragments |
| `inc/` | all theme logic, each file `require`d from `functions.php` |
| `acf-json/` | ACF field groups, synced to disk |
| `css/`, `js/` | `scaffold.css` and `scaffold.js` - the bulk of the front end |
| `style.css` | theme header plus hand-written styles. **The `Version:` line triggers deploys** |
| `tests/` | automated site checks - [tests/README.md](tests/README.md) |
| `.github/` | deploy and monday board pipeline - [.github/README.md](.github/README.md) |
| `.env.example` | template for local runs of the checks and pipeline scripts. Copy to `.env`, which is gitignored |
| `.htaccess` | denies raw data files over HTTP. See [Client data is not kept here](#client-data-is-not-kept-here) |

### `inc/`

| File | |
| --- | --- |
| `setup.php` | theme supports, menus, enqueues |
| `cleanup.php` | strips WordPress defaults |
| `admin.php` | admin-side tweaks |
| `woocommerce.php` | all Woo integration - via hooks, not template overrides |
| `stock.php` | the availability line: per-product lead times and out-of-stock wording |
| `pricing.php` | price calculation |
| `poa.php` | price-on-application products |
| `cutsize.php` | cut-to-size ordering |
| `quickadd.php` | quick add to cart |
| `reorder.php` | teaches core's Order again about cut sizes and quantity steps |
| `purchase-order.php` | PO number at checkout, stored where the Sage sync reads it |
| `vat.php` | VAT number at checkout, its format check, and the VIES lookup |
| `pay-on-account.php` | gates the Invoice gateway on an approved-for-credit user flag |
| `credit-limit.php` | reads the Sage figures and works out whether an account is over its limit or on hold. Decides nothing |
| `account-pending.php` | what happens when it is: card methods go, the order is taken and held in its own status |
| `shipping.php` | the delivery tariff, plus `class-dorotape-shipping-tariff.php` charging it |
| `collection.php` | the Ready for Collection journey, plus `emails/` for its email class |
| `dispatch.php` | what Completed means, and which of the two emails a customer gets for it |
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

### Re-ordering a past order (DR-29)

WooCommerce ships an Order again button, so this looked like a ticket about
whether core was good enough. It is not. Core rebuilds the basket in
`WC_Cart_Session::populate_cart_from_order()`, and that method knows nothing
about the two things this shop puts on a line. Measured on a real completed
order before anything was changed:

| | lines | rolls | cut to size |
| --- | --- | --- | --- |
| ordered | 2 | 75 | 2 |
| re-ordered by core | 1 | 50 | 0 |

Two faults produced that, and only one of them is obvious. Core seeds each
line's cart item data as an empty array, and nothing but the
`woocommerce_order_again_cart_item_data` filter can fill it, so the cut sizes
were dropped. Then, because both lines now carried identical empty cart item
data, `generate_cart_id()` handed them the same key and the second assignment
overwrote the first rather than adding to it, so 25 rolls disappeared as well.
The customer was told "1 item from your previous order is currently
unavailable" and "The cart has been filled with the items from your previous
order" at the same time. Neither described what had happened.

Carrying the note across fixes both, because the note is part of the hash
`generate_cart_id()` builds: distinct cut patterns give distinct keys, so the
lines stay apart without any further work.

The third fault is the quantity step. A past order can hold a quantity the step
rule would now refuse, either because the step was added or changed since, or
because staff raised the order in admin where the rule does not apply. Core's
response is to drop the line and report the product as "currently unavailable",
which is untrue: the product is available, the quantity is stale. So
`inc/pricing.php`'s step validator stands down during a re-order and
`inc/reorder.php` rounds the quantity up instead, never down, and says what it
changed. It is a basket rather than a purchase, so a customer who wanted less
can still edit it.

The button is also added to the orders list, not just the single order view
where core puts it, since re-ordering the same consumables is the common case
here and core's placement costs a page load to reach a button you cannot see.

`tests/php/probe-orderagain.php` is the check, so the numbers above can be
re-run rather than remembered:

```
php tests/php/probe-orderagain.php
```

It finds its own WordPress and its own products rather than hardcoding either,
so it runs on any copy of the site. It builds real orders, calls core's private
`populate_cart_from_order()` through reflection rather than paraphrasing it,
follows the round trip as far as the cut sizes landing back on the new order,
and deletes every order and user it made, including when a check fails. It
exits non-zero on failure.

Two things in it are deliberate. It carries a control, because the fix relaxes
the step validator during a re-order and a fix that had quietly deleted that
rule altogether would otherwise look like a pass. And it was run with the fix
disabled before being trusted: 6 of its 10 checks fail in that state, which is
how you know it is testing something.

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

`tests/` deliberately has its own `package.json` for exactly this reason - so
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
be chosen for a delivery. BACS and Cheque off, and off for good: the decision on
DR-4 was to match the old site, and the Kryptronic gateway export holds 162 rows
without a single bank transfer among them, so there are no details to migrate and
none to ask for. Invoice Gateway's own purchase
order field is deliberately off: `inc/purchase-order.php` already collects one
and stores it where Sage reads it, and enabling both would show the customer two
boxes and write the second where nothing looks.

### Credit limits and held orders (DR-10)

Three separate questions, deliberately in three separate places.
`pay-on-account.php` answers "is this account allowed credit at all", which
decides whether the Pay on account method exists for this customer.
`credit-limit.php` answers "is this account over its limit, or on hold", and
answers nothing else: it reads the Sage figures and does the arithmetic.
`account-pending.php` decides what to do about it. The split is not tidiness for
its own sake. The arithmetic has now outlived two opposite policies built on top
of it, so it is kept where a third reversal cannot reach it.

**The behaviour is the client's, and it reversed on 11 August 2026.** The first
build removed the Pay on account method from an over-limit customer and pointed
them at paying by card, which is what he asked for on 10 August. The next morning:
"we are not supposed to take orders with card payments from customers with account
balances that are over their limit (or would take them over their credit limit)".
The reason matters more than the instruction. A card payment settles the order, not
the account, so it leaves the overdue balance where it was and adds more goods on
top. Paying by card was never the way round the limit; it was the thing they are
not allowed to do.

So it is now the **card** methods that are removed and the account one that stays,
and the order is taken: "it would be better to accept the order with a flag at our
end and a message to the customer, something like 'Thank you for your order, which
is on our system pending an account payment being made'". That sentence is used
word for word, on the order received page, in the My Account order view and in the
email.

It covers two situations, not one. "This is also true in the case of a customer
being on hold due to an overdue payment even though they may still have credit
left on their account." So an account can be comfortably inside its limit and
still be held, and the two facts are read separately, on-hold first.

**"Card" is defined by exclusion.** A held customer is offered Pay on account and
nothing else. A list of card gateway ids would be wrong twice: the site has no
card gateway connected yet (DR-4, Opayo), so the list would be empty today, and it
would silently stop matching the day a different one was installed. Excluding
everything is also the more faithful reading, because any other method at checkout
takes money for this order when the money is supposed to go to the account. Widen
it with `dorotape_account_pending_gateways` if BACS or similar should ever be let
through. Two guards worth knowing about: if the account method is not in the list
at all, nothing is removed, because a checkout with no payment methods is a dead
end; and admin is left alone, so a shop manager taking a phone order sees
everything.

**The flag is a custom order status**, `wc-dt-acct-pending`, "Pending account
payment". A status shows in the admin filter row, the status column, the count
badges and the customer's own order list for free, where a meta key would have to
be gone looking for. Underneath it, meta records which of the two reasons applied
and an order note records the figures as they stood, because both outlive the
status being changed when the order is released.

The status **belongs to `bp-custom-order-status-for-woocommerce`**, the same as
Ready for Collection, and is created in the CMS rather than registered in
`account-pending.php`. One mechanism for one concept, and the client can see and
change their own furniture without a deploy. The theme was registering it itself
for about a day; the argument for moving it is the same argument used for the top
menu.

The price of that is that **the status is database state and does not deploy.**
Creating it is a blocking step on any new environment, and the settings matter as
much as the slug:

| Setting | Value | Why |
| --- | --- | --- |
| Slug | `dt-acct-pending` | exactly this, and it cannot be changed afterwards |
| Paid Status | **off** | on, and the plugin adds it to `woocommerce_order_is_paid_statuses` and stamps `date_paid`. The whole meaning of the status is that a payment has not been made |
| Edit Mode | **on** | a held order has not been paid for or picked, so accounts may need to take a line off it before releasing it |
| Email Notification | **off** | the theme sends the customer email through `WC_Email`, and the plugin's own would be a second copy |

`dorotape_account_pending_status_exists()` reports whether the row is there, an
admin notice says so if it is not, and the gateway filter falls back to the
gateway's own status rather than ours. That last one is not tidiness:
`WC_Abstract_Order::set_status()` silently substitutes `pending` for an unknown
status, and pending shows a Pay button, so a missing row would put a card payment
in front of exactly the order that must not have one. Blocking the card methods
still happens either way, because that rule is the client's and does not get to
depend on a database row.

The slug is abbreviated for two independent reasons: `post_status` is a
`varchar(20)` and WooCommerce prefixes its statuses with `wc-`, so
`wc-dt-account-pending` is 21 characters and would be truncated on write; and the
plugin's own slug field allows 17. `dt-acct-pending` is 15, which clears both.

One thing was lost in the move. The plugin `array_merge`s its statuses onto the end
of `wc_order_statuses`, so this one now sits after Cancelled and Refunded rather
than next to On hold where it belongs by meaning. It reads a little like an end
state in the dropdown. Not worth a filter fighting the plugin over.

It is deliberately **not** in `woocommerce_order_is_paid_statuses`, which is the
opposite of the call made for Ready for Collection: the entire meaning of this
status is that a payment has not been made. There are now two ways to get that
wrong, since the plugin's Paid Status setting reaches the same filter, which is
why it is in the table above. It is also not in
`woocommerce_valid_order_statuses_for_payment`, so no Pay button appears against a
held order in My Account. There is nothing for the customer to pay there, and the
button would invite exactly the card payment the client has just ruled out.

**Every email has to be wired by hand**, and this is the part of a custom status
that fails silently. WooCommerce's transactional emails each list the exact
transitions they fire on, and a custom status is in none of those lists, so the
default is not a slightly wrong email but no email at all. The status plugin has
its own email feature, which is left off: it fires on
`woocommerce_order_status_changed` rather than through `WC_Emails`, so it does not
use the templates, the settings screen or the wording of every other email on the
site. Four
things are missing until they are connected, and only the first is obvious:

- the customer's confirmation when the order is held, which is the theme's own
  email in `inc/emails/class-dorotape-email-account-pending.php`
- the office's New Order email, so a held order is not invisible internally
- the customer's Processing email when the order is **released**, which is the one
  most easily missed, because by then everything looks normal
- the office's Cancelled email, so a held order written off is not the single kind
  of cancellation nobody is told about

The three WooCommerce ones are pointed at the new transitions rather than copied
into the theme, so they keep the one set of settings a shop manager can edit and
their wording cannot drift from the email the same customer gets on an ordinary
order. The list is `dorotape_account_pending_email_transitions()`; Completed needs
nothing, because that email fires on a bare status action WooCommerce already
knows.

**The order lands in the status through Invoice Gateway's own filter**,
`igfw_invoice_gateway_default_order_status`, which the plugin applies to its
configured status before setting it. One hook, rather than a fight with whatever
the gateway does next. The trade-off is that a different account gateway would not
fire it. That is left as a known limit rather than guarded against, because every
fallback worth having involves intercepting status changes the shop's own staff
make, and getting that wrong is worse than the problem.

Invoice Gateway also prints its configured instructions on the order received
page. Those are plugin settings, not code, and they need reading now that the
status means something different from "awaiting invoice payment".

**None of this can be exercised on dev yet.** No user has a credit limit, a
balance or a hold flag, none is approved for pay on account, and Woosage is not
installed, so every path is currently switched off by its own guards. The filters
`dorotape_credit_limit`, `dorotape_credit_balance`, `dorotape_account_on_hold`,
`dorotape_credit_cart_total` and `dorotape_account_pending_reason` are how to
drive it before Sage is connected.

**The figures are Woosage's, not ours.** The connector already syncs all three
out of Sage onto the user as `woosage_credit_limit`, `woosage_account_balance` and
`woosage_account_on_hold`, so nothing here keeps a second copy in ACF. This is
worth knowing before DR-21 is picked up, because that ticket was written as "pull
credit limits from Sage into ACF" and most of it turns out to be done already, by
the plugin. The on-hold flag in particular means the second half of this feature
needed no new field at all.

Two things to check against real Sage data, neither of which can be settled from
the plugin source: that a positive `account_balance` means money owed, and so
eats into the limit, and that the limit should be measured against the gross
basket total rather than the goods total. Both are filterable
(`dorotape_credit_balance`, `dorotape_credit_cart_total`) so neither needs a code
change to correct.

An unset limit means "Sage has not told us", not "a limit of zero", and nothing
is enforced in that case. A limit of literally zero is a real limit and is
enforced. Getting that the wrong way round would take pay on account away from
every approved customer the moment the file shipped. The on-hold flag reads the
same way: Woosage casts it to a boolean before storing, so it arrives as `1` or as
an empty string, and an empty string, a missing key and a missing connector are all
"not on hold". The alternative holds every order on the site.

Woosage's own credit feature is a separate thing, and it is now close enough to
this one to be worth watching. It checks with Sage *after* the order is placed,
moving it through `wc-pending-checks` to either its normal status or
`wc-failed-checks`. This one decides before the order is placed and then holds it,
which is the same shape of outcome by a different route. They do not collide as
things stand, because that feature is off, but switching it on would mean two
mechanisms holding the same order for the same reason and reporting it in two
places.

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

The country rules above are read off the old site's 3,016 orders rather than its
settings table, and the two disagree. `core_country_dataexport.csv` lists a 20
percent rate against 75 countries including the United States, Australia and
Japan; replicating it would charge UK VAT worldwide. The orders show what was
actually done: UK at 20 percent on goods and on delivery, exports zero rated. The
six UK orders that carry no tax are not exemptions, they are zero value orders,
every one with a `0.00` subtotal, so the UK is 100 percent consistent. Four of the
eighteen export orders were charged 20 percent, and those are a bug on the old
site rather than a rule to carry over: both Iceland orders have `taxcreftotal`
equal to `taxctotal`, meaning the VAT was refunded in full, so the same mistake
was made twice to the same customer. Verified across 33 cases covering twelve
destinations: England, Scotland, the Highlands, Northern Ireland and the Isle of
Man at 20 percent on both goods and delivery, Jersey, Guernsey, Ireland, Sweden,
France, Iceland and the United States at zero, every display setting matching
`core_settings_dataexport.csv`, and the VAT line itemised and labelled "VAT".

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

One thing is still outstanding here and needs the client: a card gateway. None of
any kind is installed, so a customer who is not approved for credit and is not
collecting has no way to pay. That is not a side effect of BACS being off, which
was settled deliberately; it is the whole of what is missing, and it is checkable
rather than assumed. Put a product in a cart, choose a delivery rate, and the
store offers no payment method at all. Choose Local Pickup on the same cart and
it offers Pay on collection.

The old site's other live methods are deliberately not being reproduced, and the
order counts are why. Across 3,016 orders from May 2025 to July 2026, SagePay
Form took 3,009. Zero balance took 5, "call me and I will pay by phone" took 1,
purchase on account took 1, and the emailed payment link and PayPal Express took
none at all. PayPal Express was switched on the whole time and never used,
because the SagePay option was itself labelled "Credit Card, Debit Card or
PayPal", so PayPal sat inside the card flow rather than beside it. Opayo is the
same shape. So the ask is one card gateway, not a set of alternative routes that
took two orders between them in fifteen months.

### Order stage notifications (DR-30)

Spec 5.8 asks for a notification at three stages: order received, ready for
collection, and dispatched. Two of them were already built. The third turned out
to be a wording problem rather than a missing stage.

**Order received** needs nothing new, and that is a checked statement rather than
an assumption. Every status an order can land in straight after checkout already
sends the customer something:

| First status | What sends | Reached by |
| --- | --- | --- |
| `processing` | WooCommerce's Processing email | a card payment, Pay on collection, and Pay on account for an account in good standing (`igfw_default_order_status` is `processing`) |
| `dt-acct-pending` | `Dorotape_Email_Account_Pending` | Pay on account when the account is over its limit or on hold |
| `on-hold` | WooCommerce's On hold email | BACS and Cheque, both off, but registered |
| `failed` | WooCommerce's Failed email | a declined card |
| `pending` | nothing, correctly | the customer left the gateway without paying. There is no order to confirm |

`wc-pending-checks` and `wc-failed-checks` look like a gap in that list and are
not one. They are Woosage's, not ours, and so is `WC_Pending_Checks_Email`. The
status and the email are gated on the same `additional_checks_enabled()` setting,
and the hook that would set the status is commented out in the plugin, so that
route is dormant at both ends. If it is ever switched on, the email comes with
it, and writing our own would be a second copy of somebody else's.

**Ready for collection** is DR-14, in `inc/collection.php`.

**Dispatched has no status of its own, on purpose.** Sage already owns the
signal. When goods leave, Sage pushes the order back through the Woosage REST
API, which does exactly this:

```php
$order->set_status( 'completed', 'Order marked as completed (dispatched) in Sage.', true );
```

So Completed *is* dispatched here, set by the warehouse's own system rather than
by anybody clicking in WP admin. A Dispatched status would be a second, emptier
version of a fact Sage is already reporting, and every order would have to be
dragged through it by hand to keep the two in step.

What was actually wrong was the message. Completed arrives on both routes, and
WooCommerce 11 ships that email as "Your order from Dorotape is on its way!" over
"Good things are heading your way!" - a courier update, sent to a customer who
may have just carried the order out of the building. So `inc/dispatch.php` splits
it:

- **a delivery order** gets the theme's own dispatch email, and *not*
  WooCommerce's completed one. This is also where DR-31's courier tracking
  belongs; it hooks `woocommerce_email_order_meta`, which the template fires.
- **a collection order** keeps WooCommerce's email, with the subject and heading
  corrected to say the order is complete and thank them for collecting it.
- **an order with no shipping line at all** keeps WooCommerce's email untouched.
  Nothing was dispatched, so claiming a van exists would be worse than the stock
  wording being bland.

Never both emails. One message per stage is the whole requirement.

Two details in there are load-bearing. The corrections to WooCommerce's email
only apply when its wording is still the stock wording, checked against
`get_default_subject()`, so a shop manager who writes their own subject keeps it
rather than having it silently replaced on half the orders. And the filter that
holds WooCommerce's email back returns the shop's own setting untouched when
there is no order to judge, because the same filter runs on the settings screen,
where answering "disabled" would show a manager an email switched off that they
never switched off.

### Stock and availability (DR-8)

Sage owns stock, so `_manage_stock` is off on every product and everything reads
in stock. WooCommerce only prints an availability line when it has a figure, a
backorder or an out-of-stock to report, so with stock management off it prints
nothing and the customer is told nothing about lead times.

The old site had exactly the same model - 1,373 of its 1,510 inventory rows were
`LOCALUNL`, local and unlimited, and only 44 were genuinely tracked - and solved
it with wording rather than inventory. So this is messaging, not stock control.
Two strings per item, and `inc/stock.php` carries both across:

| Old field | Meta | Shown |
| --- | --- | --- |
| `customdispover` | `_dt_stock_note` | always, for something orderable but not on the shelf. Lead times: "2-3 days" on 88 items, "Delivery in 10-12 days" on 16 |
| `customdispoos` | `_dt_out_of_stock_note` | instead of "Out of stock" when the product is out of stock: "Back in stock soon", "COMING SOON" |

Both are editable per product under Product data → Inventory, and per variation on
the variation's own Inventory panel. A variation with an empty field inherits the
parent's, so a range with one lead time has one field to change. `store_stock_messages.php`
seeded 124 parent products and left 232 variations to inherit rather than writing
the same sentence into all 356 - otherwise editing a product would stop changing
its sizes. Four prodnums had no product here to attach to: `SAV150M` and three
Cover Styl' colours.

Out of stock replaces the standard wording; everything else is appended to it. The
replacement is right because "Back in stock soon" is phrased to stand in for "Out
of stock" rather than follow it. Appending is right everywhere else because a real
figure is information the note does not carry - which means the day a Sage sync
starts writing levels, the level leads the line on its own and nothing here needs
changing.

Nothing global needed porting. `woocommerce_stock_format` is already `''`, which
prints the exact figure, and that is what `ecom.showinstocklevel = 1` did. There
was no low stock threshold at all on the old site (`ecom.lowinventoryalertstatus = 0`),
and WooCommerce only invents a low stock message if that same option is set to
`low_amount`, which it is not; the admin low stock *email* was switched off to
match, while the out-of-stock email stays on.

The one thing that did not come across is back-in-stock notifications, which the
old site had on (`ecom.notifystock = 1`). WooCommerce core has no such feature and
this is a plugin decision, not theme code - the same shape of decision as DR-27
and DR-28. It matters because six products carry an out-of-stock note that says
"Click to receive an in stock email notification", and there is nothing to click.
Latent rather than live, since with stock management off nothing ever goes out of
stock, but it needs settling before any product is marked out of stock by hand.

CSS is in `scaffold.css`, and the availability line has three states rather than
two. `get_availability_class()` returns `in-stock` for every purchasable product
whether or not it printed a figure, so the class alone cannot tell "12 in stock"
from a bare lead time; `inc/stock.php` adds `dt-stock-note-only` for the second
case and the stylesheet colours it neutral, because a 10-12 day wait should not
borrow the in-stock green. Each state also has its own left rule, so the line
still reads in greyscale.

Verified across 21 cases, with the in-stock ones read back out of the real product
page fetched over HTTPS rather than by calling the theme's own functions: all five
availability strings and all three out-of-stock strings render, variation
inheritance and per-variation override both work in each direction, a product
without a note renders no line at all, and a product without an out-of-stock note
still says "Out of stock".

### Discount codes (DR-25)

The old site had 46 codes, 19 of them still active. Fourteen are live here. The
five left out are not oversights: `WEAREMAY100` was 100 percent off including
delivery, a staff or test code and the one thing on the list that must not exist
on a live site, and `BLOOM10`, `CRFTY10`, `FEELTHEHEAT` and `PINK10` were marked
`xmultisite = CRAFTSTICK`, so they belong to the other storefront whose range was
never migrated. Note the direction of that flag: an empty `xmultisite` means every
storefront, not none, so the seven codes with a blank value do apply here.

Nothing about these codes was invented. Every value, product list and expiry comes
from `ecom_discounts_dataexport.csv`, read through the export's own column
definitions rather than by inference.

| Code | Effect | Applies to |
| --- | --- | --- |
| `BLACK20` | 20% | 1 product |
| `BOURNESAVE` | 5% | everything except 19 products |
| `COLLECTION`, `FREEDELIVERYPLEASE`, `FREESHIPPING` | free delivery | everything |
| `GIVENAMM10` | 10% | 27 products |
| `GRIFFINSAVE` | 15% | 1 product |
| `HERECOMESTHESUN` | 10% | everything |
| `Hello stranger` | 10% | everything except 52 products |
| `INKSAVE` | 8% | 3 products |
| `LEADBITTERSAVE` | 5% | 1 product |
| `QMW10` | 10% | 1 product |
| `SAVEONSL99` | 10% | 1 product |
| `STAHLS50` | £50 | 65 products, expired 2022-12-31 |

`STAHLS50` arrives dead and is meant to. It expired years ago, WooCommerce refuses
it on that basis, and keeping it costs nothing while deleting it would lose the
record of what was offered.

Two columns look like settings and are not. `codetype` reads as "Expiring" or
"Non-Expiring", which sounds like a usage limit; Kryptronic's own definition is
that an expiring code switches itself off "after a code's value is empty", so it
counts down a balance gift-certificate style. Every code in the file is a
percentage or free delivery, so no balance ever empties and the flag never fires.
`FREESHIPPING` is marked Expiring and is still active after years of use, which is
the proof. Mapping it to a usage limit of 1 would have burned each code on its
first customer. **No usage limits are set.** If any of these should be
one-per-customer that is Michael's call and a one-field change. `startdate` is
similar: only `STAHLS50` has one, and it expired too, so there was nothing for a
scheduling feature to gate and none was built.

Every code is marked individual use, because the old checkout had a single
discount code field and only one code could ever apply to an order.

**The product lists were the hard part.** `xprod` holds Kryptronic product codes
(`Aslan-S69-0625`, `ESM2_Inks`), while the site carries `_kryp_prodnum`, which is a
different column of the same export. Each reference takes two hops: `xprod` against
`ecom_inventory.id` or `ecom_prod.id`, then that row's `prodnum` against
`_kryp_prodnum`. A direct `xprod` to `prodnum` match was tried as a third route and
recovered nothing, so the id space is the only way through. Lists are then reduced
to parent products, because `WC_Coupon::is_valid_for_product()` checks the parent
id as well as the variation's own, so naming the parent already covers every size
under it.

An unresolved reference is only dangerous in one direction. Losing an entry from an
include list makes a coupon apply to less than it did, which costs nobody anything.
Losing one from an **exclude** list makes it apply to more, which is a discount the
client never agreed to. So every unresolved exclusion is checked against the live
catalogue by code prefix, and the seeder imports a coupon as a draft rather than
published if anything plausibly matches, because a draft coupon cannot be redeemed.
In the event none needed holding back: `Hello stranger` has 74 references to
products that no longer exist here and none of them resolve to anything on sale, and
`HERECOMESTHESUN`'s single exclusion is a Silhouette Cameo 4 that was never
migrated, so it is simply 10% off everything.

Four references land on products that are in the **trash**. Nobody can buy those, so
the coupons are safe today, but restoring one of those products would quietly drop
it out of an exclusion list: `Hotronix-Fusion-IQ-Heatpress` (`BLACK20`), `C11810S`
and `C11809S` (`Hello stranger`), `4221-Silver-Carbon` (`STAHLS50`).

**Free delivery needed a code change, not just a coupon.** WooCommerce implements a
free-shipping coupon by unlocking `WC_Shipping_Free_Shipping`, a separate method
that has to be added to every zone. The Doro Tape tariff is the only thing rating
these baskets, so without that method present, `FREESHIPPING`, `FREEDELIVERYPLEASE`
and `COLLECTION` would have applied to the order, shown as redeemed, and changed
nothing the customer pays. `Dorotape_Shipping_Tariff::calculate_shipping()` now asks
`dorotape_shipping_is_free_by_coupon()` and zeroes its rates directly, which keeps
one method per zone with nothing to remember to add to a new one. Every option drops
to zero rather than just the cheapest, matching what the old site's `FREESHIPPING`
did, so a code holder gets the before-noon service for nothing rather than the
standard one. Worth knowing before the codes go out.

One thing needs a mention rather than code. `prodlimit = E` excluded both the listed
products and any product flagged "Disable Discount Code Usage"; that flag is set on
exactly one old product, `GIFTCERTIFICATE`, which is not on the new site.

Rebuild with `dorotape-migration/store_coupons.php`, dry run by default. Verified
across 26 cases by applying each code to a real cart and reading the discount off
the totals rather than off the coupon object, since a coupon with all the right
fields that still discounts nothing was the exact failure this ticket turned up:
every code discounts the right amount, include and exclude lists are both enforced,
a variation of a listed parent is covered, a second code is refused while one is
applied, the expired code is rejected, all three free-delivery codes zero the three
tariff rates while leaving Local pickup and the rate list untouched, delivery is
charged again once the code is removed, and none of the five deliberately omitted
codes exist.

### VAT numbers (DR-12)

The spec asks for VAT exemption on a validated business VAT number. That cannot
happen on this site, and it is worth saying plainly rather than building a switch
that can never fire. VAT is charged to `GB` and `IM` only, and a domestic UK
business-to-business supply is never zero rated for holding a VAT number.
Everywhere else is already zero rated by the DR-2 rate table, so there is no VAT
left for a number to remove. The number never changes what anybody pays.

It is still worth collecting, for two reasons. A zero rated export has to be
evidenced to HMRC, and the customer's registration is part of that evidence.
And Woosage reads `vat_number` off the order and uses it to pick the Sage tax
code: when the number's first two characters differ from its `local_country_code`
setting and the line carries no tax, the line posts as **T4**, the EC zero rated
code, instead of the default. So this field decides how these sales land on the
VAT return.

That Woosage rule drives two design decisions that look arbitrary otherwise.

The country prefix is mandatory. Woosage takes the code from `substr($vat, 0, 2)`,
so a number typed without its prefix hands it two digits, which never equal `GB`,
and every such order would be coded T4 whether it deserved it or not. Rejecting a
prefix-less number at checkout is what stops that.

The field is offered on EU destinations only, all 27 member states and nothing
else. Woosage's test is "not GB" rather than "in the EC", so a Norwegian or Swiss
number would also produce T4, which is wrong because neither is in the EC.
Limiting where the field appears is the cheapest way to keep T4 honest without
patching the plugin.

The field is registered through `woocommerce_register_additional_checkout_field()`,
the same route as the PO number and for the same reason: checkout is block based
and the classic hooks do not fire. Its conditional visibility is a JSON Schema
rule evaluated against WooCommerce's `DocumentObject`, reading "hidden when the
delivery country is not an EU member state", so it appears and disappears as the
customer changes country with no JavaScript of ours. The value is stored under
Woosage's own `vat_number` key rather than a `_dt_` one, so it reaches Sage with
no mapping code. Woosage has no VAT field of its own, only a `TODO make
compatible with other VAT plugins`, so unlike the PO field there is nothing to
guard against.

Validation is split in two, deliberately. The format check is offline, instant,
and blocking: it normalises punctuation and case so a number copied off a
letterhead is accepted, then tests the body against the published format for its
prefix. The VIES lookup is advisory and never touches checkout. It runs on a
scheduled single event after the order is placed, because a lookup that crosses
the internet to a service that is regularly slow and occasionally down would
otherwise mean the European Commission's uptime decides whether Doro Tape can take
an order. The worst a VIES outage can do here is leave a verdict unfilled.

Every VIES failure reads as "unavailable" rather than "invalid", including the
ones that arrive as a `200` with a `userError` of `MS_UNAVAILABLE` or `TIMEOUT`.
"We could not check" and "this is fake" are very different things to tell the
accounts team, and reading the second for the first would accuse a real business
of fraud because one member state's server was having a bad afternoon. `GB` and
`XI` numbers are reported as "cannot be checked automatically" for the same
reason: GB left VIES after Brexit, so its absence is not evidence of anything. The
verdict is written to order meta and to a dated order note, which is where anyone
investigating an order looks first.

Verified across 57 cases: the field registers and is conditional, its schema is
valid draft-07, it is offered to DE, FR, IE and SE and withheld from GB, IM, JE,
US, NO, CH and an unset country, all 27 member states are covered, eight real
numbers in seven formats are accepted including one pasted with spaces, five
malformed ones are refused including a prefix-less number, the empty value passes
because the field is optional, all six VIES failure modes read as unavailable, a
withheld business name is not printed as `---`, an order carries the number where
Woosage would find it, the cron run records the verdict and the note, and a GB
number comes back unsupported rather than invalid.

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

**A deploy only moves theme code.** Menus, plugins, uploads, WooCommerce settings
and the two custom order statuses live in the database and travel by All-in-One WP
Migration or by hand. `dt-acct-pending` is the one to watch, because the code that
depends on it deploys and it does not: see the settings table under [Credit limits
and held orders](#credit-limits-and-held-orders-dr-10), and expect the admin
notice if it is missing.

After a deploy lands, [tests/](tests/) runs against dev - pages load clean, no
JS errors, no new accessibility violations, sane markup, and a product still
reaches checkout. Pass moves the tickets to QA; fail moves them to Blocked with
a comment stating whether the code actually reached dev, because "the deploy
broke" and "the deploy worked and the site is broken" need different reactions.

The board also carries a **weighted progress bar** - every ticket has a Size
(S/M/L/XL), progress is points rather than ticket counts, and it recalculates
after each deploy and on a weekday schedule. It is written straight onto the
board, in the description under the board title and on a progress item, so there
is no separate report to host or keep in sync. Setup is step 7 of
[.github/README.md](.github/README.md).

Setting all of this up on another project: [.github/README.md](.github/README.md).
Nothing in it is specific to this site - every value that points at an
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
