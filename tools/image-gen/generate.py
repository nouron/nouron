#!/usr/bin/env python3
"""
Nouron image asset generator.

Usage:
    python generate.py <category> [options]

Examples:
    python generate.py buildings
    python generate.py advisors --overwrite
    python generate.py buildings --file command_center --dry-run
    python generate.py buildings --quality high --output-format webp
    python generate.py tiles --resize 96x96
"""

import argparse
import base64
import io
import json
import os
import re
import sys
from pathlib import Path

from dotenv import load_dotenv
from openai import OpenAI
from PIL import Image

SCRIPT_DIR = Path(__file__).parent
PROJECT_ROOT = SCRIPT_DIR.parent.parent
PROMPTS_DIR = PROJECT_ROOT / ".prompts" / "images"
OUTPUT_BASE = PROJECT_ROOT / "public" / "img"
BASE_PROMPT_FILE = PROMPTS_DIR / "basic-design.prompt.md"

# Aspect ratio → OpenAI size
# gpt-image-2 supports: 1024x1024, 1024x1536, 1536x1024, auto
RATIO_TO_SIZE = {
    "square": "1024x1024",
    "portrait": "1024x1536",
    "landscape": "1536x1024",
}


def parse_prompt_file(path: Path) -> dict:
    """Extract prompt text and directives from a .prompt.md file."""
    text = path.read_text(encoding="utf-8").strip()
    size = RATIO_TO_SIZE["square"]
    prompt_lines = []

    for line in text.splitlines():
        line = line.strip()
        if not line:
            continue
        m = re.match(r"set\s+the\s+aspect\s+ratio\s+to\s+(\d+):(\d+)", line, re.IGNORECASE)
        if m:
            w, h = int(m.group(1)), int(m.group(2))
            ratio = w / h
            if ratio < 0.95:
                size = RATIO_TO_SIZE["portrait"]
            elif ratio > 1.05:
                size = RATIO_TO_SIZE["landscape"]
            else:
                size = RATIO_TO_SIZE["square"]
        else:
            prompt_lines.append(line)

    return {"prompt": " ".join(prompt_lines), "size": size}


def build_combined_prompt(subject: str, base: str | None) -> str:
    if not base:
        return subject
    return f"{subject}, {base}"


def generate_image(
    client: OpenAI,
    prompt: str,
    size: str,
    model: str,
    quality: str = "auto",
    output_format: str = "webp",
    output_compression: int | None = None,
) -> bytes:
    kwargs = dict(
        model=model,
        prompt=prompt,
        size=size,
        n=1,
        quality=quality,
        output_format=output_format,
    )
    if output_compression is not None and output_format in ("webp", "jpeg"):
        kwargs["output_compression"] = output_compression
    response = client.images.generate(**kwargs)
    return base64.b64decode(response.data[0].b64_json)


def main():
    load_dotenv(SCRIPT_DIR / ".env")

    parser = argparse.ArgumentParser(description="Generate Nouron image assets via OpenAI")
    parser.add_argument("category", help="Prompt subdirectory (e.g. buildings, advisors)")
    parser.add_argument("--file", help="Process only this prompt file (without extension)")
    parser.add_argument("--overwrite", action="store_true", help="Overwrite existing images")
    parser.add_argument("--dry-run", action="store_true", help="Print what would be generated, no API calls")
    parser.add_argument(
        "--quality",
        choices=["low", "medium", "high", "auto"],
        default="auto",
        help="Image quality (gpt-image-2: low/medium/high/auto, default: auto)",
    )
    parser.add_argument(
        "--output-format",
        choices=["webp", "png", "jpeg"],
        default="webp",
        help="Output file format (default: webp per asset spec)",
    )
    parser.add_argument(
        "--compression",
        type=int,
        metavar="0-100",
        help="Output compression for webp/jpeg (0=lossless, 100=max, default: API default)",
    )
    parser.add_argument(
        "--resize",
        metavar="WxH",
        help="Resize output to WxH pixels after generation (e.g. 96x96). Uses Lanczos resampling.",
    )
    parser.add_argument(
        "--no-base-prompt",
        action="store_true",
        help="Skip the base design prompt (use subject prompt only). Auto-applied when category has _base.prompt.md set to 'none'.",
    )
    args = parser.parse_args()

    api_key = os.environ.get("OPENAI_API_KEY")
    if not api_key and not args.dry_run:
        print("ERROR: OPENAI_API_KEY not set in tools/image-gen/.env", file=sys.stderr)
        sys.exit(1)

    model = os.environ.get("OPENAI_IMAGE_MODEL", "gpt-image-2")

    prompt_dir = PROMPTS_DIR / args.category
    if not prompt_dir.is_dir():
        print(f"ERROR: Prompt directory not found: {prompt_dir}", file=sys.stderr)
        sys.exit(1)

    # Per-category config: _config.json overrides CLI defaults for api_size and resize.
    # CLI flags take precedence over _config.json.
    cat_cfg = {}
    config_file = prompt_dir / "_config.json"
    if config_file.exists():
        cat_cfg = json.loads(config_file.read_text(encoding="utf-8"))

    def parse_resize(val: str) -> tuple[int, int]:
        try:
            w, h = val.lower().split("x")
            return (int(w), int(h))
        except ValueError:
            print(f"ERROR: resize must be WxH (e.g. 96x96), got: {val}", file=sys.stderr)
            sys.exit(1)

    resize_to = None
    if args.resize:
        resize_to = parse_resize(args.resize)
    elif cat_cfg.get("resize"):
        resize_to = parse_resize(cat_cfg["resize"])

    # CLI --quality overrides _config.json quality
    quality = args.quality if args.quality != "auto" else cat_cfg.get("quality", "auto")

    api_size_override = cat_cfg.get("api_size")

    crop_to = None
    if cat_cfg.get("crop"):
        crop_to = parse_resize(cat_cfg["crop"])

    # Per-category base override: _base.prompt.md in the category dir takes precedence.
    # If it contains only "none", base prompt is skipped entirely.
    category_base_file = prompt_dir / "_base.prompt.md"
    if category_base_file.exists():
        raw = category_base_file.read_text(encoding="utf-8").strip().lower()
        if raw == "none":
            base_prompt = None
        else:
            base_prompt = parse_prompt_file(category_base_file)["prompt"]
    elif args.no_base_prompt:
        base_prompt = None
    else:
        if not BASE_PROMPT_FILE.exists():
            print(f"ERROR: Base prompt not found: {BASE_PROMPT_FILE}", file=sys.stderr)
            sys.exit(1)
        base_prompt = parse_prompt_file(BASE_PROMPT_FILE)["prompt"]

    output_dir = OUTPUT_BASE / f"_{args.category}"
    output_dir.mkdir(parents=True, exist_ok=True)

    if args.file:
        prompt_files = [prompt_dir / f"{args.file}.prompt.md"]
        if not prompt_files[0].exists():
            print(f"ERROR: File not found: {prompt_files[0]}", file=sys.stderr)
            sys.exit(1)
    else:
        prompt_files = sorted(f for f in prompt_dir.glob("*.prompt.md") if f.name != "_base.prompt.md")

    if not prompt_files:
        print(f"No .prompt.md files found in {prompt_dir}")
        sys.exit(0)

    client = OpenAI(api_key=api_key) if not args.dry_run else None

    for prompt_file in prompt_files:
        name = prompt_file.stem.replace(".prompt", "")
        output_path = output_dir / f"{name}.{args.output_format}"

        if output_path.exists() and not args.overwrite:
            print(f"  SKIP  {name} (exists, use --overwrite)")
            continue

        data = parse_prompt_file(prompt_file)
        combined = build_combined_prompt(data["prompt"], base_prompt)
        api_size = api_size_override or data["size"]

        if args.dry_run:
            print(f"  DRY   {name}")
            print(f"        model  : {model}")
            print(f"        size   : {api_size}{f' → resize {resize_to[0]}x{resize_to[1]}' if resize_to else ''}")
            print(f"        quality: {quality}")
            print(f"        format : {args.output_format}")
            print(f"        base   : {'(none)' if not base_prompt else base_prompt[:60] + '...'}")
            print(f"        prompt : {combined[:120]}...")
            continue

        print(f"  GEN   {name} [{api_size}] ... ", end="", flush=True)
        try:
            image_bytes = generate_image(
                client,
                combined,
                api_size,
                model,
                quality=quality,
                output_format=args.output_format,
                output_compression=args.compression,
            )
            if resize_to or crop_to:
                img = Image.open(io.BytesIO(image_bytes))
                if resize_to:
                    img = img.resize(resize_to, Image.LANCZOS)
                if crop_to:
                    cw, ch = crop_to
                    iw, ih = img.size
                    left = (iw - cw) // 2
                    top = (ih - ch) // 2
                    img = img.crop((left, top, left + cw, top + ch))
                buf = io.BytesIO()
                fmt = args.output_format.upper().replace("JPG", "JPEG")
                save_kwargs = {}
                if args.output_format == "webp" and args.compression is not None:
                    save_kwargs["quality"] = args.compression
                img.save(buf, format=fmt, **save_kwargs)
                image_bytes = buf.getvalue()
            output_path.write_bytes(image_bytes)
            suffix = f" (resized to {args.resize})" if resize_to else ""
            if crop_to:
                suffix += f" (cropped to {crop_to[0]}x{crop_to[1]})"
            print(f"saved → {output_path.relative_to(PROJECT_ROOT)}{suffix}")
        except Exception as e:
            print(f"FAILED: {e}", file=sys.stderr)


if __name__ == "__main__":
    main()
