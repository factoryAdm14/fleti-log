#!/usr/bin/env python3
"""Deploy admin hotfix files via FTP (credentials from DEPLOYMENT_CREDENTIALS.local.md)."""

from __future__ import annotations

import ftplib
import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
ADMIN = ROOT / "fleti-admin-new-install-3.2"
CREDS = ROOT / "DEPLOYMENT_CREDENTIALS.local.md"

FILES = [
    "config/app.php",
    "public/assets/admin-module/css/fleti-admin-modern.css",
    "Modules/AdminModule/Resources/views/partials/_footer.blade.php",
    "Modules/AuthManagement/Resources/views/login.blade.php",
    "resources/views/update/update-software.blade.php",
]


def parse_creds() -> dict[str, str]:
    text = CREDS.read_text(encoding="utf-8")
    section = text.split("### FTP", 1)[1].split("###", 1)[0]
    return {
        "host": re.search(r"- Host: (.+)", section).group(1).strip(),
        "user": re.search(r"- User: (.+)", section).group(1).strip(),
        "password": re.search(r"- Password: (.+)", section).group(1).strip(),
    }


def ftp_host(creds: dict[str, str]) -> str:
    ssh_section = CREDS.read_text(encoding="utf-8").split("### SSH", 1)[1]
    ip = re.search(r"- Host: (.+)", ssh_section).group(1).strip()
    return ip


def ensure_dirs(ftp: ftplib.FTP, remote_path: str) -> None:
    ftp.cwd("/")
    for part in remote_path.split("/")[:-1]:
        try:
            ftp.cwd(part)
        except ftplib.error_perm:
            ftp.mkd(part)
            ftp.cwd(part)
    ftp.cwd("/")


def upload_file(ftp: ftplib.FTP, local: Path, remote: str) -> None:
    ensure_dirs(ftp, remote)
    with local.open("rb") as handle:
        ftp.storbinary(f"STOR {remote}", handle)
    print(f"uploaded: {remote}")


def main() -> int:
    if not CREDS.exists():
        print(f"Missing {CREDS}", file=sys.stderr)
        return 1

    creds = parse_creds()
    ftp = ftplib.FTP(ftp_host(creds), timeout=60)
    ftp.login(creds["user"], creds["password"])

    for rel in FILES:
        local = ADMIN / rel
        if not local.exists():
            print(f"missing local file: {local}", file=sys.stderr)
            return 1
        upload_file(ftp, local, rel.replace("\\", "/"))

    ftp.quit()
    print("FTP deploy complete.")
    print("Run on server: cd public_html && grep -q '^SOFTWARE_VERSION=' .env || echo 'SOFTWARE_VERSION=3.2' >> .env")
    print("Then: php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
