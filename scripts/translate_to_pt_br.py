#!/usr/bin/env python3
"""Translate language files from English to Brazilian Portuguese."""

from __future__ import annotations

import json
import re
import sys
import time
from pathlib import Path

try:
    from deep_translator import GoogleTranslator
except ImportError:
    print("Install: pip3 install deep-translator", file=sys.stderr)
    sys.exit(1)

translator = GoogleTranslator(source="en", target="pt")
LINE_PATTERN = re.compile(r"^(\s*'(?:[^'\\]|\\.)*'\s*=>\s*)'((?:[^'\\]|\\.)*)'(,?\s*)$")


def translate_text(text: str) -> str:
    if not text or not str(text).strip():
        return text
    if re.match(r"^https?://", text):
        return text
    try:
        return translator.translate(text[:4500])
    except Exception as e:
        print(f"  skip: {text[:50]!r} ({e})")
        return text


def translate_json_file(src: Path, dst: Path) -> None:
    data = json.loads(src.read_text(encoding="utf-8"))
    total = len(data)
    translated = {}
    for i, (key, value) in enumerate(data.items(), 1):
        translated[key] = translate_text(value) if isinstance(value, str) else value
        if i % 25 == 0:
            print(f"  {dst.name}: {i}/{total}")
            time.sleep(0.2)
    dst.write_text(json.dumps(translated, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(f"Wrote {dst} ({total} keys)")


def translate_php_lang_file(src: Path, dst: Path) -> None:
    lines = src.read_text(encoding="utf-8").splitlines(keepends=True)
    out = []
    total = sum(1 for line in lines if LINE_PATTERN.match(line.rstrip("\n")))
    done = 0
    for line in lines:
        m = LINE_PATTERN.match(line.rstrip("\n"))
        if not m:
            out.append(line)
            continue
        prefix, value, suffix = m.group(1), m.group(2), m.group(3)
        unescaped = value.replace("\\'", "'")
        translated = translate_text(unescaped).replace("'", "\\'")
        out.append(f"{prefix}'{translated}'{suffix}\n")
        done += 1
        if done % 50 == 0:
            print(f"  {dst.name}: {done}/{total}")
            time.sleep(0.3)
    dst.write_text("".join(out), encoding="utf-8")
    print(f"Wrote {dst} ({done} strings)")


def main() -> None:
    root = Path("/Users/flavio/develop/fleti-log")
    targets = sys.argv[1:] if len(sys.argv) > 1 else ["json", "php"]

    if "json" in targets:
        for app in ["fleti-User-app-release-3.2", "fleti-Driver-app-release-3.2"]:
            src = root / app / "assets/language/en.json"
            dst = root / app / "assets/language/pt.json"
            if src.exists():
                print(f"Translating {src}...")
                translate_json_file(src, dst)

    if "php" in targets:
        src = root / "fleti-admin-new-install-3.2/resources/lang/en/lang.php"
        dst = root / "fleti-admin-new-install-3.2/resources/lang/pt/lang.php"
        if src.exists():
            print(f"Translating {src}...")
            translate_php_lang_file(src, dst)


if __name__ == "__main__":
    main()
