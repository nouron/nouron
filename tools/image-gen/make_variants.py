#!/usr/bin/env python3
"""
Nouron image variant generator.

Takes a full-resolution master image (as produced by generate.py) and
derives right-sized WebP variants for each place the asset is actually
displayed, so the browser never live-scales a 1024-1536px source down to a
44px avatar (which is what causes the grainy/aliased look on fine ink-line
art).

Usage:
    python make_variants.py advisors
    python make_variants.py characters --file bartender
    python make_variants.py advisors --overwrite
"""

import argparse
import json
import sys
from pathlib import Path

from PIL import Image, ImageFilter

SCRIPT_DIR = Path(__file__).parent
PROJECT_ROOT = SCRIPT_DIR.parent.parent
IMG_DIR = PROJECT_ROOT / "public" / "img"
VARIANTS_DIR = SCRIPT_DIR / "variants"


def load_variants_config(category: str) -> dict:
    config_file = VARIANTS_DIR / f"{category}.json"
    if not config_file.exists():
        print(f"ERROR: No variants config at {config_file}", file=sys.stderr)
        sys.exit(1)
    return json.loads(config_file.read_text(encoding="utf-8"))["variants"]


def find_master(category: str, name: str) -> Path | None:
    """Prefer a raw generator output (public/img/_<category>/), fall back to
    whatever is currently published (covers assets that predate this tool)."""
    raw_dir = IMG_DIR / f"_{category}"
    for ext in ("webp", "png"):
        candidate = raw_dir / f"{name}.{ext}"
        if candidate.exists():
            return candidate

    published_dir = IMG_DIR / category
    for ext in ("webp", "png"):
        candidate = published_dir / f"{name}.{ext}"
        if candidate.exists():
            return candidate

    return None


def cover_resize(img: Image.Image, target_w: int, target_h: int) -> Image.Image:
    """Resize + center-crop to exactly target_w x target_h, like CSS
    object-fit: cover — keeps the subject's aspect ratio, no squashing."""
    src_w, src_h = img.size
    scale = max(target_w / src_w, target_h / src_h)
    new_w, new_h = round(src_w * scale), round(src_h * scale)
    img = img.resize((new_w, new_h), Image.LANCZOS)
    left = (new_w - target_w) // 2
    top = (new_h - target_h) // 2
    return img.crop((left, top, left + target_w, top + target_h))


def make_variant(master: Image.Image, size: str) -> Image.Image:
    w, h = (int(v) for v in size.lower().split("x"))
    img = cover_resize(master.convert("RGBA"), w, h)
    # Fine ink-line art loses contrast when downscaled; a light unsharp pass
    # brings edges back without introducing halos at these target sizes.
    return img.filter(ImageFilter.UnsharpMask(radius=1.2, percent=60, threshold=2))


def main():
    parser = argparse.ArgumentParser(description="Derive right-sized WebP variants from Nouron image masters")
    parser.add_argument("category", help="Category, e.g. advisors, characters (needs _variants.json)")
    parser.add_argument("--file", help="Process only this asset (without extension)")
    parser.add_argument("--overwrite", action="store_true", help="Overwrite existing variant files")
    parser.add_argument("--dry-run", action="store_true", help="List what would be generated, no writes")
    args = parser.parse_args()

    variants = load_variants_config(args.category)
    published_dir = IMG_DIR / args.category
    published_dir.mkdir(parents=True, exist_ok=True)

    if args.file:
        names = [args.file]
    else:
        raw_dir = IMG_DIR / f"_{args.category}"
        # Non-empty suffixes (e.g. "_sm", "_lg") mark derived output files, not base
        # character names — skip them here or "bartender_sm.webp" gets treated as
        # its own character and spawns "bartender_sm_sm.webp" etc.
        derived_suffixes = [s for s in variants if s]

        def is_base_name(stem: str) -> bool:
            return not any(stem.endswith(s) for s in derived_suffixes)

        seen = set()
        if raw_dir.is_dir():
            seen.update(p.stem for p in raw_dir.glob("*") if p.suffix.lower() in (".webp", ".png"))
        if published_dir.is_dir():
            seen.update(
                p.stem for p in published_dir.glob("*")
                if p.suffix.lower() in (".webp", ".png") and is_base_name(p.stem)
            )
        names = sorted(seen)

    if not names:
        print(f"No source images found for category '{args.category}'")
        sys.exit(0)

    for name in names:
        master_path = find_master(args.category, name)
        if master_path is None:
            print(f"  SKIP  {name} (no master found)")
            continue

        master = Image.open(master_path)

        for suffix, spec in variants.items():
            out_path = published_dir / f"{name}{suffix}.webp"
            if out_path.exists() and not args.overwrite:
                print(f"  SKIP  {out_path.relative_to(PROJECT_ROOT)} (exists, use --overwrite)")
                continue

            if args.dry_run:
                print(f"  DRY   {out_path.relative_to(PROJECT_ROOT)}  <- {master_path.relative_to(PROJECT_ROOT)} @ {spec['size']}")
                continue

            variant = make_variant(master, spec["size"])
            variant.save(out_path, format="WEBP", quality=90, method=6)
            print(f"  OK    {out_path.relative_to(PROJECT_ROOT)}  <- {master_path.relative_to(PROJECT_ROOT)} @ {spec['size']}")


if __name__ == "__main__":
    main()
