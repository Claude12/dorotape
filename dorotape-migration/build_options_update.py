#!/usr/bin/env python3
"""
build_options_update.py
Convert 890 zero-price simple products into properly-priced WooCommerce
variable products using ecom_prodoptions pricing data.

Outputs TWO files (import them in order):
  wc_options_parents.csv    — updates existing simple→variable  (tick "Update existing")
  wc_options_variations.csv — creates new variation rows        (un-tick "Update existing")

Attribute strategy:
  1 option group  → attribute name = group name (e.g. "1370mm wide")
                    values = option labels (e.g. "per metre", "per 25m roll")
  2+ option groups → attribute name = "Size / Format"
                    values = "1372mm wide — Part Roll 5m" (group name + option label)
"""

import re
import sys
from pathlib import Path
import pandas as pd

ENCODING = "latin-1"
HERE = Path(__file__).parent

UNRESOLVABLE = {'CS-HTV-PRINT', 'CS-EFX-ROL', 'rj30bundle'}


# ── helpers ───────────────────────────────────────────────────────────────────

def load(filename):
    return pd.read_csv(HERE / filename, dtype=str, keep_default_na=False, encoding=ENCODING)


def parse_optsel(optsel_str):
    """Return list of (label, price_str) from a pipe-delimited optsel string."""
    items = []
    for line in optsel_str.replace('\r\n', '\n').replace('\r', '\n').split('\n'):
        line = line.strip()
        if not line:
            continue
        parts = line.split('|')
        label = parts[0].strip()
        price_raw = parts[1].strip() if len(parts) > 1 else '0'
        try:
            price = f"{float(price_raw):.2f}"
        except ValueError:
            price = ''
        if label:
            items.append((label, price))
    return items


_MAX_SLUG = 28

def slugify(s):
    return re.sub(r'[^a-z0-9]+', '-', s.lower()).strip('-')

def clean_group_name(name):
    """Strip boilerplate from option group names; fall back if slug > 28 chars."""
    name = re.sub(r'\s*[-–]\s*please select.*$', '', name, flags=re.IGNORECASE)
    name = re.sub(r'\s*:+\s*$', '', name).strip()
    # WooCommerce enforces a 28-char slug limit on attribute names
    if len(slugify(name)) > _MAX_SLUG:
        return "Size / Format"
    return name


def make_variation_sku(base_sku, index):
    """Deterministic variation SKU: SKU-sz01, SKU-sz02, ..."""
    return f"{base_sku}-sz{index:02d}"


# ── main ──────────────────────────────────────────────────────────────────────

def main():
    print("Loading tables …")
    opts   = load("ecom_prodoptions_dataexport.csv")
    inv    = load("ecom_inventory_dataexport.csv")
    imp    = load("wc_products_import.csv")
    prod   = load("ecom_prod_dataexport.csv")

    # Option group lookups
    opts_row  = {r['id'].strip(): r for _, r in opts.iterrows()}

    # Products in the original import that are POA (zero-price, not yet priced)
    poa_skus = set(imp[imp['meta:_price_poa'] == '1']['SKU'].str.strip())

    # Products with resolvable option groups
    inv_poa = inv[
        inv['prodnum'].isin(poa_skus) &
        (inv['xprodoptions'].str.strip() != '')
    ].copy()

    # Keep only rows where ALL referenced groups are in the export
    def fully_resolvable(xpo):
        ids = [x.strip() for x in xpo.split(',') if x.strip()]
        return all(i in opts_row and i not in UNRESOLVABLE for i in ids)

    inv_poa = inv_poa[inv_poa['xprodoptions'].apply(fully_resolvable)].copy()
    print(f"  Resolvable products: {len(inv_poa)}")

    # Build lookup: prodnum → original import row (for categories, descriptions etc.)
    imp_by_sku = {r['SKU'].strip(): r for _, r in imp.iterrows()}

    # ── CSV columns ───────────────────────────────────────────────────────────
    PARENT_COLS = [
        'Type', 'SKU', 'Name', 'Published', 'Visibility in catalog',
        'Short description', 'Description',
        'Tax status', 'Tax class', 'In stock?', 'Stock',
        'Backorders allowed?', 'Sold individually?',
        'Regular price', 'Sale price', 'Categories', 'Images',
        'Attribute 1 name', 'Attribute 1 value(s)',
        'Attribute 1 visible', 'Attribute 1 global',
        'meta:_price_poa', 'meta:_price_tiers',
        'meta:_sage_income_code', 'meta:_sage_class_id', 'meta:_manufacturer',
        'meta:_kryp_prodnum',
    ]

    VAR_COLS = [
        'Type', 'SKU', 'Name', 'Published', 'Visibility in catalog',
        'In stock?', 'Stock', 'Backorders allowed?',
        'Regular price', 'Sale price', 'Parent',
        'Attribute 1 name', 'Attribute 1 value(s)',
        'Attribute 1 visible', 'Attribute 1 global',
        'meta:_price_tiers', 'meta:_kryp_prodnum',
    ]

    # ── Group all inventory rows by prodnum ──────────────────────────────────
    # A product can have multiple inventory entries (e.g. roll options + sheet options).
    # Merge all unique option groups across all inventory rows for the same prodnum.
    from collections import defaultdict, OrderedDict
    prodnum_groups: dict[str, list[str]] = defaultdict(list)
    prodnum_inv_row: dict[str, object]   = {}   # first inv row per prodnum (for meta)

    for _, inv_row in inv_poa.iterrows():
        sku = inv_row['prodnum'].strip()
        if sku not in prodnum_inv_row:
            prodnum_inv_row[sku] = inv_row
        for gid in [x.strip() for x in inv_row['xprodoptions'].split(',')
                    if x.strip() and x.strip() in opts_row]:
            if gid not in prodnum_groups[sku]:
                prodnum_groups[sku].append(gid)

    parent_rows = []
    var_rows    = []
    skipped     = []

    for sku, grp_ids in prodnum_groups.items():
        inv_row = prodnum_inv_row[sku]

        if not grp_ids:
            skipped.append((sku, 'no valid groups'))
            continue

        # Parse all options across groups
        # each entry: (attr_value_label, price, group_name)
        all_opts = []
        for gid in grp_ids:
            g = opts_row[gid]
            gname = clean_group_name(g['name'])
            items = parse_optsel(g['optsel'])
            for label, price in items:
                all_opts.append((gname, label, price))

        if not all_opts:
            skipped.append((sku, 'empty optsel'))
            continue

        # Determine attribute strategy
        n_groups = len(grp_ids)
        if n_groups == 1:
            # Single group: use group name as attribute name, option labels as values
            attr_name   = clean_group_name(opts_row[grp_ids[0]]['name']) or "Size / Format"
            attr_values = [(label, price) for _, label, price in all_opts]
        else:
            # Multiple groups: flatten to "Size / Format" with combined labels
            attr_name = "Size / Format"
            seen_labels = set()
            attr_values = []
            for gname, label, price in all_opts:
                combined = f"{gname} — {label}" if gname else label
                # de-dupe (shouldn't happen but be safe)
                if combined not in seen_labels:
                    attr_values.append((combined, price))
                    seen_labels.add(combined)

        # WooCommerce CSV importer splits Attribute N value(s) on comma (parse_comma_field),
        # NOT on | (WC_DELIMITER — that is for local/text attributes only).
        all_attr_value_str = ", ".join(v for v, _ in attr_values)

        # Get original import row for descriptions / categories / meta
        src = imp_by_sku.get(sku, {})

        # ── PARENT row ────────────────────────────────────────────────────────
        p = {c: '' for c in PARENT_COLS}
        p['Type']                   = 'variable'
        p['SKU']                    = sku
        p['Name']                   = src.get('Name', inv_row.get('name', sku))
        p['Published']              = '1'
        p['Visibility in catalog']  = 'visible'
        p['Short description']      = src.get('Short description', '')
        p['Description']            = src.get('Description', '')
        p['Tax status']             = 'taxable'
        p['Tax class']              = ''
        p['In stock?']              = '1'
        p['Stock']                  = ''
        p['Backorders allowed?']    = '0'
        p['Sold individually?']     = '0'
        p['Regular price']          = ''
        p['Sale price']             = ''
        p['Categories']             = src.get('Categories', '')
        p['Images']                 = ''
        p['Attribute 1 name']       = attr_name
        p['Attribute 1 value(s)']   = all_attr_value_str
        p['Attribute 1 visible']    = '1'
        p['Attribute 1 global']     = '1'
        p['meta:_price_poa']        = ''
        p['meta:_price_tiers']      = ''
        p['meta:_sage_income_code'] = src.get('meta:_sage_income_code', '')
        p['meta:_sage_class_id']    = src.get('meta:_sage_class_id', '')
        p['meta:_manufacturer']     = src.get('meta:_manufacturer', '')
        p['meta:_kryp_prodnum']     = sku
        parent_rows.append(p)

        # ── VARIATION rows ────────────────────────────────────────────────────
        for idx, (attr_val, price) in enumerate(attr_values, start=1):
            v = {c: '' for c in VAR_COLS}
            v['Type']                   = 'variation'
            v['SKU']                    = make_variation_sku(sku, idx)
            v['Name']                   = f"{p['Name']} - {attr_val}"
            v['Published']              = '1'
            v['Visibility in catalog']  = 'visible'
            v['In stock?']              = '1'
            v['Stock']                  = ''
            v['Backorders allowed?']    = '0'
            v['Regular price']          = price
            v['Sale price']             = ''
            v['Parent']                 = sku
            v['Attribute 1 name']       = attr_name
            v['Attribute 1 value(s)']   = attr_val
            v['Attribute 1 visible']    = '1'
            v['Attribute 1 global']     = '1'
            v['meta:_price_tiers']      = ''
            v['meta:_kryp_prodnum']     = sku
            var_rows.append(v)

    # ── write ─────────────────────────────────────────────────────────────────
    parents_path = HERE / "wc_options_parents.csv"
    vars_path    = HERE / "wc_options_variations.csv"

    pd.DataFrame(parent_rows, columns=PARENT_COLS).to_csv(
        parents_path, index=False, encoding='utf-8')
    pd.DataFrame(var_rows, columns=VAR_COLS).to_csv(
        vars_path, index=False, encoding='utf-8')

    print(f"\nWrote {len(parent_rows)} parent rows  → {parents_path.name}")
    print(f"Wrote {len(var_rows)} variation rows → {vars_path.name}")
    print(f"Skipped: {len(skipped)}")

    # ── summary ───────────────────────────────────────────────────────────────
    var_counts = pd.DataFrame(var_rows)['Parent'].value_counts()
    dist = var_counts.value_counts().sort_index()
    print("\nVariations per product:")
    for n_var, count in dist.items():
        print(f"  {n_var:2d} variation(s): {count} products")

    priced_vars = sum(1 for r in var_rows if r['Regular price'] not in ('', '0.00', '0'))
    zero_vars   = len(var_rows) - priced_vars
    print(f"\nVariation prices: {priced_vars} with price, {zero_vars} still zero/blank")

    if skipped:
        print(f"\nSkipped products:")
        for sku, reason in skipped:
            print(f"  {sku}: {reason}")


if __name__ == '__main__':
    main()
