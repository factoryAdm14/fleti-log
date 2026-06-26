#!/usr/bin/env python3
"""Shared FTP helpers for Fleti production deploy scripts."""

from __future__ import annotations

import ftplib
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
ADMIN = ROOT / "fleti-admin-new-install-3.2"
CREDS = ROOT / "DEPLOYMENT_CREDENTIALS.local.md"


def parse_ftp_creds() -> dict[str, str]:
    text = CREDS.read_text(encoding="utf-8")
    section = text.split("### FTP", 1)[1].split("###", 1)[0]
    ssh_section = text.split("### SSH", 1)[1]
    return {
        "host": re.search(r"- Host: (.+)", ssh_section).group(1).strip(),
        "user": re.search(r"- User: (.+)", section).group(1).strip(),
        "password": re.search(r"- Password: (.+)", section).group(1).strip(),
    }


def connect_ftp() -> ftplib.FTP:
    creds = parse_ftp_creds()
    ftp = ftplib.FTP(creds["host"], timeout=120)
    ftp.login(creds["user"], creds["password"])
    return ftp


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
