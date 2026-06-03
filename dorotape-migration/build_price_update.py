#!/usr/bin/env python3
"""
build_price_update.py
Diff old vs new pricemap and prodoptions exports, then generate targeted
WooCommerce CSVs covering only what changed.

Outputs:
  wc_price_update_simple.csv    — update Regular price + meta:_price_tiers
                                   on changed simple/variable products
  wc_price_update_variations.csv — update variation prices for changed options groups
  wc_price_update_new.csv        — any new products added since last export

Import order (all with "Update existing products" ticked, except new products):
  1. wc_price_update_simple.csv       (Update ON)
  2. wc_price_update_variations.csv   (Update ON)
  3. wc_price_update_new.csv          (Update OFF — creates new products)
"""

import re
from pathlib import Path
import pandas as pd

ENCODING = "latin-1"
HERE = Path(__file__).parent

UNRESOLVABLE = {'CS-HTV-PRINT', 'CS-EFX-ROL', 'rj30bundle'}


def load(filename):
    return pd.read_csv(HERE / filename, dtype=str, keep_default_na=False, encoding=ENCODING)


def parse_base_price(regprice: str) -> str:
    s = regprice.strip()
    if not s:
        return ""
    if ":" not in s:
        try:
            v = float(s)
            return f"{v:.2f}" if v else ""
        except ValueError:
            return ""
    first = s.split(";")[0].strip()
    price_part = first.split(":")[-1].strip()
    try:
        v = float(price_part)
        return f"{v:.2f}" if v else ""
    except ValueError:
        return ""


def slugify(s):
    return re.sub(r'[^a-z0-9]+', '-', s.lower()).strip('-')


def clean_group_name(name):
    name = re.sub(r'\s*[-–]\s*please select.*$', '', name, flags=re.IGNORECASE)
    name = re.sub(r'\s*:+\s*$', '', name).strip()
    if len(slugify(name)) > 28:
        return "Size / Format"
    return name


def parse_optsel(optsel_str):
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


def make_variation_sku(base_sku, index):
    return f"{base_sku}-sz{index:02d}"


def main():
    print("Loading tables …")
    pm_old  = load("ecom_pricemap_dataexport.csv")
    pm_new  = load("ecom_pricemap_dataexport (1).csv")
    po_old  = load("ecom_prodoptions_dataexport.csv")
    po_new  = load("ecom_prodoptions_dataexport (2).csv")
    prod    = load("ecom_prod_dataexport.csv")
    inv     = load("ecom_inventory_dataexport.csv")
    imp     = load("wc_products_import.csv")   # original import — for category/meta lookups

    prod_def = prod[prod['xmultisite'] == 'DEFAULT'].copy()
    pm_old_def = pm_old[pm_old['channelid'] == 'DEFAULT'].set_index('id')
    pm_new_def = pm_new[pm_new['channelid'] == 'DEFAULT'].set_index('id')
    po_old_idx = po_old.set_index('id')
    po_new_idx = po_new.set_index('id')

    # ── 1. Pricemap diff ──────────────────────────────────────────────────────
    added_pm   = pm_new_def.index.difference(pm_old_def.index)
    removed_pm = pm_old_def.index.difference(pm_new_def.index)
    common_pm  = pm_old_def.index.intersection(pm_new_def.index)
    changed_pm = set(common_pm[
        pm_old_def.loc[common_pm, 'regprice'] != pm_new_def.loc[common_pm, 'regprice']
    ])

    print(f"  pricemap: {len(changed_pm)} prices changed, "
          f"{len(added_pm)} new, {len(removed_pm)} removed")

    # Map pricemap_id → prodnum via ecom_prod.xpricemap
    xpm_to_prodnum = dict(zip(prod_def['xpricemap'].str.strip(), prod_def['prodnum'].str.strip()))
    imp_by_sku = {r['SKU'].strip(): r for _, r in imp.iterrows()}

    # ── 2. Prodoptions diff ───────────────────────────────────────────────────
    common_po   = po_old_idx.index.intersection(po_new_idx.index)
    changed_po  = set(common_po[
        po_old_idx.loc[common_po, 'optsel'] != po_new_idx.loc[common_po, 'optsel']
    ])
    added_po    = set(po_new_idx.index.difference(po_old_idx.index))

    print(f"  prodoptions: {len(changed_po)} groups changed, {len(added_po)} new")

    # ── Build simple/variable price update rows ───────────────────────────────
    SIMPLE_COLS = ['Type', 'SKU', 'Regular price', 'Sale price',
                   'meta:_price_tiers', 'meta:_price_poa']

    simple_rows = []

    for pm_id in changed_pm:
        sku = xpm_to_prodnum.get(pm_id)
        if not sku:
            continue
        new_price_str = pm_new_def.loc[pm_id, 'regprice']
        base_price    = parse_base_price(new_price_str)
        row = {c: '' for c in SIMPLE_COLS}
        row['Type']              = 'simple'
        row['SKU']               = sku
        row['Regular price']     = base_price
        row['Sale price']        = ''
        row['meta:_price_tiers'] = new_price_str.strip()
        row['meta:_price_poa']   = '' if base_price else '1'
        simple_rows.append(row)

    # Handle removed sale entries — clear sale price on the affected product
    for pm_id in removed_pm:
        sku = xpm_to_prodnum.get(pm_id)
        if not sku:
            continue
        row = {c: '' for c in SIMPLE_COLS}
        row['Type']        = 'simple'
        row['SKU']         = sku
        row['Sale price']  = ''  # clears sale
        simple_rows.append(row)

    pd.DataFrame(simple_rows, columns=SIMPLE_COLS).to_csv(
        HERE / 'wc_price_update_simple.csv', index=False, encoding='utf-8')
    print(f"\n  Wrote {len(simple_rows)} rows → wc_price_update_simple.csv")

    # ── Build variation price update rows ─────────────────────────────────────
    # Find which products use at least one changed or new option group
    changed_or_new_po = changed_po | added_po
    opts_new_row = {r['id'].strip(): r for _, r in po_new.iterrows()}

    poa_skus = set(imp[imp['meta:_price_poa'] == '1']['SKU'].str.strip())

    # group inventory by prodnum (same logic as build_options_update.py)
    from collections import defaultdict
    prodnum_groups: dict = defaultdict(list)
    prodnum_inv_row: dict = {}
    inv_poa = inv[inv['prodnum'].isin(poa_skus) & (inv['xprodoptions'].str.strip() != '')]

    for _, inv_row in inv_poa.iterrows():
        sku = inv_row['prodnum'].strip()
        if sku not in prodnum_inv_row:
            prodnum_inv_row[sku] = inv_row
        for gid in [x.strip() for x in inv_row['xprodoptions'].split(',')
                    if x.strip() and x.strip() in opts_new_row
                    and x.strip() not in UNRESOLVABLE]:
            if gid not in prodnum_groups[sku]:
                prodnum_groups[sku].append(gid)

    VAR_COLS = ['Type', 'SKU', 'Regular price', 'Sale price',
                'Parent', 'Attribute 1 name', 'Attribute 1 value(s)',
                'Attribute 1 visible', 'Attribute 1 global',
                'meta:_price_tiers', 'Published', 'Visibility in catalog',
                'In stock?', 'Backorders allowed?']

    var_rows = []

    for sku, grp_ids in prodnum_groups.items():
        # Only process this product if at least one of its groups changed
        if not any(gid in changed_or_new_po for gid in grp_ids):
            continue

        n_groups = len(grp_ids)
        all_opts = []
        for gid in grp_ids:
            g = opts_new_row[gid]
            gname = clean_group_name(g['name'])
            for label, price in parse_optsel(g['optsel']):
                all_opts.append((gname, label, price))

        if not all_opts:
            continue

        if n_groups == 1:
            attr_name   = clean_group_name(opts_new_row[grp_ids[0]]['name']) or "Size / Format"
            attr_values = [(label, price) for _, label, price in all_opts]
        else:
            attr_name = "Size / Format"
            seen = set()
            attr_values = []
            for gname, label, price in all_opts:
                combined = f"{gname} — {label}" if gname else label
                if combined not in seen:
                    attr_values.append((combined, price))
                    seen.add(combined)

        for idx, (attr_val, price) in enumerate(attr_values, start=1):
            v = {c: '' for c in VAR_COLS}
            v['Type']                  = 'variation'
            v['SKU']                   = make_variation_sku(sku, idx)
            v['Regular price']         = price
            v['Sale price']            = ''
            v['Parent']                = sku
            v['Attribute 1 name']      = attr_name
            v['Attribute 1 value(s)']  = attr_val
            v['Attribute 1 visible']   = '1'
            v['Attribute 1 global']    = '1'
            v['meta:_price_tiers']     = ''
            v['Published']             = '1'
            v['Visibility in catalog'] = 'visible'
            v['In stock?']             = '1'
            v['Backorders allowed?']   = '0'
            var_rows.append(v)

    pd.DataFrame(var_rows, columns=VAR_COLS).to_csv(
        HERE / 'wc_price_update_variations.csv', index=False, encoding='utf-8')
    print(f"  Wrote {len(var_rows)} rows → wc_price_update_variations.csv")
    print(f"  Covers {len(set(r['Parent'] for r in var_rows))} products")

    # ── New products from added pricemap entries ──────────────────────────────
    new_prod_rows = []
    for pm_id in added_pm:
        # Skip if it's a promo/sale entry (contains SALE in key)
        if 'SALE' in pm_id.upper() or 'PROMO' in pm_id.upper():
            continue
        xinvid = pm_new_def.loc[pm_id, 'xinvid']
        # Find in ecom_prod
        matches = prod_def[prod_def['xpricemap'] == pm_id]
        if matches.empty:
            print(f"  ⚠  New pricemap entry {pm_id} has no ecom_prod row — skip")
            continue
        r = matches.iloc[0]
        src = imp_by_sku.get(r['prodnum'].strip(), {})
        base_price = parse_base_price(pm_new_def.loc[pm_id, 'regprice'])
        new_prod_rows.append({
            'Type': 'simple', 'SKU': r['prodnum'].strip(),
            'Name': r['name'].strip(),
            'Regular price': base_price,
            'meta:_price_tiers': pm_new_def.loc[pm_id, 'regprice'].strip(),
            'Published': '1',
        })
        print(f"  New product: SKU={r['prodnum'].strip()}  Name={r['name'].strip()}")

    if new_prod_rows:
        pd.DataFrame(new_prod_rows).to_csv(
            HERE / 'wc_price_update_new.csv', index=False, encoding='utf-8')
        print(f"  Wrote {len(new_prod_rows)} rows → wc_price_update_new.csv")
    else:
        print("  No genuinely new products to add.")

    print("\nDone.")
    print("Import order:")
    print("  1. wc_price_update_simple.csv     — Update ON  (updates prices on simple/variable products)")
    print("  2. wc_price_update_variations.csv — Update ON  (updates variation prices)")
    print("  3. wc_price_update_new.csv        — Update OFF (creates any new products)")


if __name__ == '__main__':
    main()
