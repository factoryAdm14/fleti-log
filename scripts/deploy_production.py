#!/usr/bin/env python3
"""
Deploy Laravel admin changes to fleti.com.br via FTP + SSH post-steps.

Usage:
  python3 scripts/deploy_production.py
  python3 scripts/deploy_production.py --since 3c5bda5
  python3 scripts/deploy_production.py --dry-run
"""

from __future__ import annotations

import argparse
import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
ADMIN = ROOT / "fleti-admin-new-install-3.2"
CREDS = ROOT / "DEPLOYMENT_CREDENTIALS.local.md"

SKIP_PREFIXES = (
    "tests/",
    ".env",
    ".git",
)
SKIP_FILES = {
    "phpunit.xml",
    ".env.example",
    ".gitignore",
}


def collect_files(since: str) -> list[str]:
    result = subprocess.run(
        ["git", "diff", "--name-only", since, "HEAD", "--", "fleti-admin-new-install-3.2"],
        cwd=ROOT,
        capture_output=True,
        text=True,
        check=True,
    )
    files: list[str] = []
    for line in result.stdout.splitlines():
        line = line.strip()
        if not line.startswith("fleti-admin-new-install-3.2/"):
            continue
        rel = line.removeprefix("fleti-admin-new-install-3.2/")
        if rel in SKIP_FILES or any(rel.startswith(p) for p in SKIP_PREFIXES):
            continue
        if (ADMIN / rel).is_file():
            files.append(rel)
    return sorted(set(files))


def main() -> int:
    parser = argparse.ArgumentParser(description="Deploy Fleti admin to production")
    parser.add_argument("--since", default="3c5bda5", help="Git ref to diff from")
    parser.add_argument("--dry-run", action="store_true", help="List files only")
    args = parser.parse_args()

    if not CREDS.exists():
        print(f"Missing {CREDS}", file=sys.stderr)
        return 1

    files = collect_files(args.since)
    if not files:
        print("No deployable files found.")
        return 0

    print(f"Deploying {len(files)} files (since {args.since})...")
    if args.dry_run:
        for path in files:
            print(path)
        return 0

    sys.path.insert(0, str(Path(__file__).resolve().parent))
    from deploy_ftp_common import connect_ftp, upload_file

    ftp = connect_ftp()
    for rel in files:
        upload_file(ftp, ADMIN / rel, rel)
    ftp.quit()
    print("FTP upload complete.")

    from ssh_post_deploy import run_post_deploy

    return run_post_deploy(migrate=True)


if __name__ == "__main__":
    raise SystemExit(main())
