#!/usr/bin/env python3
"""
build_wc_import.py  —  Kryptronic ecom_prod → WooCommerce product import CSV.

Inputs (same directory):
  ecom_prod_dataexport.csv
  ecom_pricemap_dataexport.csv
  ecom_cat_dataexport.csv
  ecom_inventory_dataexport.csv
  ecom_prodfilter_dataexport.csv

Outputs:
  wc_products_import.csv   — ready for WooCommerce › Products › Import
  wc_import_warnings.csv   — rows that need manual review before importing

Usage:
  python3 build_wc_import.py
  python3 build_wc_import.py --image-base-url https://old.dorotape.co.uk/catalog/product/

Notes:
  - Filtered to DEFAULT multisite only (CRAFTSTICK excluded).
  - Duplicate-SKU rows where all names share a :: prefix → WooCommerce variable + variations.
  - Duplicate-SKU rows where names are unrelated → simple products, disambiguated SKU, flagged.
  - regprice tier format "1-24:10.90;25:9.81" → base price = first tier; full string saved to
    meta:_price_tiers for later dynamic pricing plugin configuration.
  - Zero-price products imported with price blank + meta:_price_poa=1; Sage will supply prices.
  - Stock: imported with manage_stock=no; Woosage/Storehub will own stock after go-live.
  - Images: filename only in source; prepend --image-base-url so WC importer can fetch them.
  - Sage accounting fields preserved as meta: fields for Woosage mapping.
"""

import argparse
import re
import sys
from pathlib import Path

import pandas as pd

ENCODING = "latin-1"
HERE = Path(__file__).parent

# Full URL required by WooCommerce CSV importer — local paths are not supported.
# Set to "" to skip images (import products first, images via separate re-import).
# Once FTP images land at wp-content/uploads/dorotape-migration-images/, set to the
# full site URL, e.g. "https://www.dorotape.co.uk/wp-content/uploads/dorotape-migration-images/"
DEFAULT_IMAGE_BASE = ""


# ── data loading ──────────────────────────────────────────────────────────────

def load(filename: str) -> pd.DataFrame:
    return pd.read_csv(HERE / filename, dtype=str, keep_default_na=False, encoding=ENCODING)


# ── price helpers ─────────────────────────────────────────────────────────────

def parse_base_price(regprice: str) -> str:
    """
    Kryptronic regprice: "1-24:10.90;25:9.81" (tiered) or "38.75" (flat).
    Returns the qty-1 price as "10.90", or "" if blank/zero.
    """
    s = regprice.strip()
    if not s:
        return ""
    # flat price
    if ":" not in s:
        try:
            v = float(s)
            return f"{v:.2f}" if v else ""
        except ValueError:
            return ""
    # tiered: take everything after the last ":" in the first segment
    first_segment = s.split(";")[0].strip()
    price_part = first_segment.split(":")[-1].strip()
    try:
        v = float(price_part)
        return f"{v:.2f}" if v else ""
    except ValueError:
        return ""


# ── category helpers ──────────────────────────────────────────────────────────

def build_cat_lookups(cat_df: pd.DataFrame) -> tuple[dict, dict]:
    id_name   = dict(zip(cat_df["id"], cat_df["name"]))
    id_parent = dict(zip(cat_df["id"], cat_df["catparent"]))
    return id_name, id_parent


def cat_path(cat_id: str, id_name: dict, id_parent: dict) -> str | None:
    cat_id = cat_id.strip()
    if not cat_id or cat_id not in id_name:
        return None
    parts: list[str] = []
    current = cat_id
    for _ in range(10):
        name = id_name.get(current, "")
        if not name:
            break
        parts.insert(0, name)
        parent = id_parent.get(current, "")
        if not parent:
            break
        current = parent
    return " > ".join(parts) if parts else None


def xcat_to_wc(xcat: str, id_name: dict, id_parent: dict) -> str:
    """Comma-separated Kryptronic xcat IDs → WooCommerce "A > B, C > D" string."""
    ids = [c.strip() for c in xcat.split(",") if c.strip()]
    paths = [p for i in ids if (p := cat_path(i, id_name, id_parent))]
    return ", ".join(paths)


# ── filter / attribute helpers ────────────────────────────────────────────────

# Map filterslot number to a human attribute name used in WooCommerce
SLOT_TO_ATTR = {
    "1": "Adhesive Type",
    "2": "Material Grade",
    "3": "Suitability",
    "4": "Colour Family",
    "5": "Finish",
}

FILTER_COLS = [
    "xprodfilterone",
    "xprodfiltertwo",
    "xprodfilterthree",
    "xprodfilterfour",
    "xprodfilterfive",
]


def build_filter_lookup(flt_df: pd.DataFrame) -> dict:
    """Returns {filter_id: filtername}."""
    return dict(zip(flt_df["id"], flt_df["filtername"]))


def filter_values(row: pd.Series, flt_lookup: dict) -> dict[str, str]:
    """
    Returns {attr_name: "Val1, Val2"} for each non-blank filter slot on this row.
    Attribute name comes from SLOT_TO_ATTR; values are comma-joined filter names.
    """
    result: dict[str, str] = {}
    for col in FILTER_COLS:
        raw = row.get(col, "").strip()
        if not raw:
            continue
        ids = [i.strip() for i in raw.split(",") if i.strip()]
        names = [flt_lookup.get(i, i) for i in ids]
        # slot is the digit suffix of the column name (one→1 etc.) – easier via lookup
        slot = str(FILTER_COLS.index(col) + 1)
        attr_name = SLOT_TO_ATTR.get(slot, f"Filter {slot}")
        result[attr_name] = ", ".join(names)
    return result


# ── variant-detection helpers ─────────────────────────────────────────────────

_DIM_RE = re.compile(
    r"::\s*(.+?)\s*$",   # everything after " :: " at end of name
)


def extract_variant_label(name: str) -> str | None:
    """Return "625mm Width" from "ASLAN 85K :: 625mm Width", else None."""
    m = _DIM_RE.search(name)
    return m.group(1).strip() if m else None


def base_name(name: str) -> str:
    """Return "ASLAN 85K" from "ASLAN 85K :: 625mm Width"."""
    parts = name.split("::", 1)
    return parts[0].strip()


def classify_group(group: pd.DataFrame) -> str:
    """
    Returns:
      "variants"   – all rows share a :: prefix → WC variable + variations
      "mixed"      – some have ::, base names match  → same; generic row = parent description
      "different"  – names are unrelated → separate simple products, flag for review
    """
    names = group["name"].tolist()
    labels = [extract_variant_label(n) for n in names]
    bases  = [base_name(n) for n in names]

    if all(l is not None for l in labels):
        # Every name has a :: suffix
        return "variants"

    if any(l is not None for l in labels):
        # Some have ::, check if non-:: names equal the base of :: names
        bb = {b for b, l in zip(bases, labels) if l is not None}
        plain = {n.strip() for n, l in zip(names, labels) if l is None}
        if plain.issubset(bb):
            return "mixed"   # plain entries are just the parent description row

    return "different"


# ── WooCommerce CSV row builders ──────────────────────────────────────────────

MAX_ATTR_SLOTS = 6  # 1 variant dimension + 5 filter slots


def _attr_columns() -> list[str]:
    cols = []
    for i in range(1, MAX_ATTR_SLOTS + 1):
        cols += [
            f"Attribute {i} name",
            f"Attribute {i} value(s)",
            f"Attribute {i} visible",
            f"Attribute {i} global",
        ]
    return cols


WC_COLUMNS = [
    "Type",
    "SKU",
    "Name",
    "Published",
    "Is featured?",
    "Visibility in catalog",
    "Short description",
    "Description",
    "Tax status",
    "Tax class",
    "In stock?",
    "Stock",
    "Backorders allowed?",
    "Sold individually?",
    "Sale price",
    "Regular price",
    "Categories",
    "Tags",
    "Shipping class",
    "Images",
    "Parent",
    "Upsells",
    "Cross-sells",
    *_attr_columns(),
    # meta fields for Sage / pricing
    "meta:_price_tiers",
    "meta:_price_poa",
    "meta:_sage_income_code",
    "meta:_sage_pri",
    "meta:_sage_alt",
    "meta:_sage_primod",
    "meta:_sage_altmod",
    "meta:_sage_class_id",    # ecom_inventory.acctsysprice — Kryptronic accounting class ID
    "meta:_sage_price_tier",  # resolved from ecom_acctsysclasses: Retail or Wholesale
    "meta:_manufacturer",
    "meta:_kryp_seourl",
    "meta:_kryp_prodnum",
]


def _blank_row() -> dict:
    return {c: "" for c in WC_COLUMNS}


def _common_fields(r: pd.Series, img_base: str, id_name: dict, id_parent: dict,
                   flt_lookup: dict, inv_lookup: dict | None = None,
                   acct_tier: dict | None = None) -> dict:
    """Fields common to all product types (simple and parent)."""
    row = _blank_row()
    row["Published"]             = "1"
    row["Is featured?"]          = "0"
    row["Visibility in catalog"] = "visible"
    row["Short description"]     = r.get("descshort", "").strip()
    row["Description"]           = r.get("desclong", "").strip()
    row["Tax status"]            = "taxable"
    row["Tax class"]             = ""
    row["In stock?"]             = "1"
    row["Stock"]                 = ""   # Woosage will own stock
    row["Backorders allowed?"]   = "0"
    row["Sold individually?"]    = "0"
    row["Categories"]            = xcat_to_wc(r.get("xcat", ""), id_name, id_parent)
    row["Images"]                = _image_url(r.get("imglg", ""), img_base)
    row["Upsells"]               = r.get("xupsell", "").strip()

    # Sage / accounting meta — acctsyspri/alt are blank in ecom_prod; class ID comes from inventory
    row["meta:_sage_income_code"] = r.get("acctsysincome", "").strip()
    row["meta:_sage_pri"]         = r.get("acctsyspri", "").strip()
    row["meta:_sage_alt"]         = r.get("acctsysalt", "").strip()
    row["meta:_sage_primod"]      = r.get("acctsysprimod", "").strip()
    row["meta:_sage_altmod"]      = r.get("acctsysaltmod", "").strip()
    if inv_lookup:
        inv_row = inv_lookup.get(r.get("prodnum", "").strip(), {})
        class_id = inv_row.get("acctsysprice", "").strip()
        row["meta:_sage_class_id"] = class_id
        if acct_tier and class_id:
            row["meta:_sage_price_tier"] = acct_tier.get(class_id, "")
    row["meta:_manufacturer"]     = r.get("manufacturer", "").strip()
    row["meta:_kryp_seourl"]      = r.get("seourl", "").strip()
    row["meta:_kryp_prodnum"]     = r.get("prodnum", "").strip()

    return row


def _set_price(row: dict, regprice: str) -> None:
    base = parse_base_price(regprice)
    row["Regular price"]   = base
    row["meta:_price_tiers"] = regprice.strip()
    row["meta:_price_poa"]   = "" if base else "1"


def _image_url(filename: str, base: str) -> str:
    f = filename.strip()
    if not f or not base.strip():
        return ""
    return base.rstrip("/") + "/" + f


def _pack_attributes(row: dict, variant_attr: tuple | None,
                     filter_attrs: dict[str, str]) -> None:
    """
    Fills Attribute N name / value(s) / visible / global columns.
    variant_attr = (name, value) or None.
    filter_attrs  = {attr_name: "val1, val2"}.
    """
    slot = 1
    if variant_attr:
        name, value = variant_attr
        row[f"Attribute {slot} name"]     = name
        row[f"Attribute {slot} value(s)"] = value
        row[f"Attribute {slot} visible"]  = "1"
        row[f"Attribute {slot} global"]   = "1"
        slot += 1
    for attr_name, attr_values in filter_attrs.items():
        if slot > MAX_ATTR_SLOTS:
            break
        row[f"Attribute {slot} name"]     = attr_name
        row[f"Attribute {slot} value(s)"] = attr_values
        row[f"Attribute {slot} visible"]  = "1"
        row[f"Attribute {slot} global"]   = "1"
        slot += 1


def emit_simple(r: pd.Series, sku_override: str | None,
                pm_lookup: dict, img_base: str,
                id_name: dict, id_parent: dict,
                flt_lookup: dict, inv_lookup: dict | None = None,
                acct_tier: dict | None = None) -> dict:
    row = _common_fields(r, img_base, id_name, id_parent, flt_lookup, inv_lookup, acct_tier)
    row["Type"] = "simple"
    row["SKU"]  = sku_override or r["prodnum"].strip()
    row["Name"] = r["name"].strip()
    regprice = pm_lookup.get(r["xpricemap"], "")
    _set_price(row, regprice)
    flt = filter_values(r, flt_lookup)
    _pack_attributes(row, None, flt)
    return row


def emit_variable_parent(r: pd.Series, parent_name: str, all_variant_labels: list[str],
                         img_base: str, id_name: dict, id_parent: dict,
                         flt_lookup: dict, inv_lookup: dict | None = None,
                         acct_tier: dict | None = None) -> dict:
    """Parent row for a WooCommerce variable product."""
    row = _common_fields(r, img_base, id_name, id_parent, flt_lookup, inv_lookup, acct_tier)
    row["Type"]           = "variable"
    row["SKU"]            = r["prodnum"].strip()
    row["Name"]           = parent_name
    row["Regular price"]  = ""
    row["Sale price"]     = ""
    row["meta:_price_tiers"] = ""
    row["meta:_price_poa"]   = ""
    flt = filter_values(r, flt_lookup)
    # Pack variant attribute with all values (for WC to register the attribute)
    all_values = " | ".join(all_variant_labels)
    _pack_attributes(row, ("Size / Width", all_values), flt)
    return row


def emit_variation(r: pd.Series, parent_sku: str, variant_label: str,
                   pm_lookup: dict, img_base: str,
                   id_name: dict, id_parent: dict,
                   flt_lookup: dict) -> dict:
    """Child variation row."""
    row = _blank_row()
    row["Type"]    = "variation"
    row["SKU"]     = f"{parent_sku}-{re.sub(r'[^A-Za-z0-9]', '', variant_label)}"
    row["Name"]    = r["name"].strip()
    row["Parent"]              = parent_sku
    row["Published"]           = "1"
    row["Visibility in catalog"] = "visible"
    row["In stock?"]           = "1"
    row["Stock"]             = ""
    row["Backorders allowed?"] = "0"
    row["Images"]            = _image_url(r.get("imglg", ""), img_base)
    regprice = pm_lookup.get(r["xpricemap"], "")
    _set_price(row, regprice)
    row["meta:_kryp_prodnum"] = r.get("prodnum", "").strip()
    _pack_attributes(row, ("Size / Width", variant_label), {})
    return row


# ── main ──────────────────────────────────────────────────────────────────────

def main() -> None:
    ap = argparse.ArgumentParser(description="Build WooCommerce product import CSV")
    ap.add_argument("--image-base-url", default=DEFAULT_IMAGE_BASE,
                    help="Base URL prepended to image filenames (default: %(default)s)")
    args = ap.parse_args()
    img_base = args.image_base_url.strip()
    if img_base:
        img_base = img_base.rstrip("/") + "/"

    print("Loading tables …")
    prod   = load("ecom_prod_dataexport.csv")
    pm     = load("ecom_pricemap_dataexport.csv")
    cat    = load("ecom_cat_dataexport.csv")
    inv    = load("ecom_inventory_dataexport.csv")
    flt    = load("ecom_prodfilter_dataexport.csv")
    acctcl = load("ecom_acctsysclasses_dataexport.csv")

    # ── filter to DEFAULT ─────────────────────────────────────────────────────
    prod_def = prod[prod["xmultisite"] == "DEFAULT"].copy().reset_index(drop=True)
    pm_def   = pm[pm["channelid"] == "DEFAULT"].copy()
    cat_def  = cat[
        cat["xmultisite"].str.contains("DEFAULT", na=False) | (cat["xmultisite"] == "")
    ].copy()

    print(f"  ecom_prod (DEFAULT): {len(prod_def)} rows, {prod_def['prodnum'].nunique()} unique SKUs")

    # ── build lookups ─────────────────────────────────────────────────────────
    pm_lookup  = dict(zip(pm_def["id"], pm_def["regprice"]))
    id_name, id_parent = build_cat_lookups(cat_def)
    flt_lookup = build_filter_lookup(flt)
    # ecom_acctsysclasses: id → acctnamepri (Retail / Wholesale)
    acct_tier = dict(zip(acctcl["id"], acctcl["acctnamepri"]))

    # Inventory keyed by prodnum — for Sage class ID + resolved pricing tier
    inv_lookup = {
        row["prodnum"].strip(): row.to_dict()
        for _, row in inv.iterrows()
        if row["prodnum"].strip()
    }

    # ── process groups ────────────────────────────────────────────────────────
    output_rows: list[dict] = []
    warnings: list[dict]    = []

    grouped = prod_def.groupby("prodnum", sort=False)

    for prodnum, group in grouped:
        group = group.reset_index(drop=True)
        n = len(group)

        if n == 1:
            r = group.iloc[0]
            output_rows.append(
                emit_simple(r, None, pm_lookup, img_base, id_name, id_parent, flt_lookup, inv_lookup, acct_tier)
            )
            continue

        # ── multi-row: classify ───────────────────────────────────────────────
        kind = classify_group(group)

        if kind == "different":
            # Excluded pending SKU review — logged to wc_import_warnings.csv only.
            # These rows are NOT added to the import CSV until sku_review.csv is returned.
            for i, (_, r) in enumerate(group.iterrows(), start=1):
                warnings.append({
                    "original_sku": prodnum,
                    "assigned_sku": f"{prodnum}-v{i}",
                    "name": r["name"],
                    "reason": "Excluded pending review in sku_review.csv. "
                              "Return the reviewed file to include these products.",
                })
            continue

        # kind == "variants" or "mixed"
        # Determine parent name and per-row variant labels
        labels = [extract_variant_label(r["name"]) for _, r in group.iterrows()]
        bases  = [base_name(r["name"]) for _, r in group.iterrows()]

        if kind == "mixed":
            # Row(s) without :: are the generic/parent description row
            parent_candidate = next(
                (r for _, r in group.iterrows() if extract_variant_label(r["name"]) is None),
                group.iloc[0]
            )
            parent_name = parent_candidate["name"].strip()
            variation_rows = [(r, l) for (_, r), l in zip(group.iterrows(), labels) if l is not None]
        else:
            # All rows have :: — derive parent name from common base
            parent_name = bases[0]  # they all share the same base
            variation_rows = [(r, l) for (_, r), l in zip(group.iterrows(), labels)]

        all_variant_labels = [l for _, l in variation_rows]

        # Use first variation row for parent-level description / category / images
        parent_source = variation_rows[0][0]
        output_rows.append(
            emit_variable_parent(
                parent_source, parent_name, all_variant_labels,
                img_base, id_name, id_parent, flt_lookup, inv_lookup, acct_tier,
            )
        )
        for var_row, label in variation_rows:
            output_rows.append(
                emit_variation(var_row, str(prodnum), label,
                               pm_lookup, img_base, id_name, id_parent, flt_lookup)
            )

    # ── write output ──────────────────────────────────────────────────────────
    out_df = pd.DataFrame(output_rows, columns=WC_COLUMNS)
    out_path = HERE / "wc_products_import.csv"
    out_df.to_csv(out_path, index=False, encoding="utf-8")
    print(f"\nWrote {len(out_df)} rows → {out_path}")

    # summary breakdown
    type_counts = out_df["Type"].value_counts()
    for t, c in type_counts.items():
        print(f"  {t:12s}: {c}")

    priced     = (out_df["Regular price"].str.strip() != "").sum()
    zero_price = (out_df["meta:_price_poa"] == "1").sum()
    print(f"\n  With price      : {priced}")
    print(f"  Price-on-app    : {zero_price}  (meta:_price_poa=1)")

    if warnings:
        warn_path = HERE / "wc_import_warnings.csv"
        pd.DataFrame(warnings).to_csv(warn_path, index=False, encoding="utf-8")
        print(f"\n  ⚠  {len(warnings)} rows need manual SKU review → {warn_path}")
    else:
        print("\n  No SKU conflicts found.")

    print("\nDone. Next steps:")
    print("  1. Confirm --image-base-url matches the live Kryptronic image path.")
    print("  2. Review wc_import_warnings.csv and reassign SKUs as needed.")
    print("  3. WooCommerce › Products › Import › wc_products_import.csv")
    print("     ✓ Tick 'Update existing products' if running a re-import.")


if __name__ == "__main__":
    main()
