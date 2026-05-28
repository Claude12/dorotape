#!/usr/bin/env python3
"""
Kryptronic ecom_prod -> WooCommerce migration helper.

Step 1 of the migration: read the Kryptronic product export SAFELY and report
the real shape of the catalogue. Run this BEFORE attempting any import.

Why this script exists:
  * The export is NOT clean UTF-8. It is Latin-1 / CP1252 (micron 'µm', '°',
    '£' etc.). pandas.read_csv() with default encoding CRASHES on it.
  * The raw file looks like ~68k lines, but that is newlines INSIDE the HTML
    'desclong' column. Parsed correctly it is ~1,399 rows / ~1,053 SKUs.
  * 'sortprice' may NOT be the authoritative price -- 'xpricemap' is populated
    on nearly every row, so real prices likely live in ecom_pricemap. Export
    that table too and join on it before trusting prices.

Usage:
    python3 analyze_kryptronic_products.py /path/to/ecom_prod_dataexport.csv
"""

import sys
import pandas as pd

ENCODING = "latin-1"  # IMPORTANT: do not change to utf-8, the file will crash


def load(path: str) -> pd.DataFrame:
    # dtype=str + keep_default_na=False => blanks stay '' instead of becoming NaN,
    # which keeps SKUs/descriptions intact and predictable.
    return pd.read_csv(path, dtype=str, keep_default_na=False, encoding=ENCODING)


def report(df: pd.DataFrame) -> None:
    n = len(df)
    print("=" * 60)
    print("KRYPTRONIC ecom_prod  -  CATALOGUE SHAPE")
    print("=" * 60)
    print(f"Rows (real products):      {n}")
    print(f"Columns:                   {len(df.columns)}")
    print(f"Unique SKUs (prodnum):     {df['prodnum'].nunique()}")
    print(f"Blank SKUs:                {(df['prodnum'].str.strip() == '').sum()}")

    dupes = n - df['prodnum'].nunique()
    print(f"Duplicate-SKU rows:        {dupes}  (investigate: multisite? true dupes?)")

    print("-" * 60)
    opt = (df['xprodoptions'].str.strip() != '').sum()
    print(f"Products WITH options:     {opt}   -> WooCommerce VARIABLE products")
    print(f"Products WITHOUT options:  {n - opt}   -> WooCommerce SIMPLE products")

    print("-" * 60)
    pmap = (df['xpricemap'].str.strip() != '').sum()
    print(f"Rows linked to pricemap:   {pmap}")
    print("  -> If high, EXPORT ecom_pricemap and join for true prices.")
    print(f"  sortprice blanks:        {(df['sortprice'].str.strip() == '').sum()}")
    print(f"  sortprice sample:        {df['sortprice'].head(6).tolist()}")

    print("-" * 60)
    # Image coverage (WooCommerce needs full URLs, these are just filenames)
    has_img = (df['imglg'].str.strip() != '').sum()
    print(f"Rows with a large image:   {has_img}  (imglg = filename only)")
    print(f"  image sample:            {df[df['imglg'].str.strip()!='']['imglg'].head(3).tolist()}")
    print("  -> Prepend your media base URL before import.")

    print("-" * 60)
    print("Sage / accounting columns present (PRESERVE, do not drop):")
    sage_cols = [c for c in df.columns if c.startswith("acctsys")]
    print("  " + ", ".join(sage_cols))
    print("=" * 60)


def duplicate_skus(df: pd.DataFrame, limit: int = 10) -> None:
    dup_mask = df['prodnum'].duplicated(keep=False) & (df['prodnum'].str.strip() != '')
    if dup_mask.any():
        print("\nSample duplicate SKUs (first {} groups):".format(limit))
        sample = df[dup_mask].sort_values('prodnum')[['prodnum', 'name', 'xmultisite']]
        print(sample.head(limit * 2).to_string(index=False))


if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Usage: python3 analyze_kryptronic_products.py <ecom_prod.csv>")
        sys.exit(1)
    frame = load(sys.argv[1])
    report(frame)
    duplicate_skus(frame)