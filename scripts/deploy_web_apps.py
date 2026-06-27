#!/usr/bin/env python3
"""Build and deploy Flutter web apps (cliente + motorista) to fleti.com.br."""

from __future__ import annotations

import argparse
import ftplib
import os
import re
import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
CREDS = ROOT / "DEPLOYMENT_CREDENTIALS.local.md"

APPS = {
    "client": {
        "dir": ROOT / "apps/client_web_flutter",
        "remote": "client",
        "base_href": "/client/",
    },
    "driver": {
        "dir": ROOT / "apps/driver_web_flutter",
        "remote": "driver",
        "base_href": "/driver/",
    },
}


def parse_ftp() -> dict[str, str]:
    text = CREDS.read_text(encoding="utf-8")
    ftp_section = text.split("### FTP", 1)[1].split("###", 1)[0]
    ssh_section = text.split("### SSH", 1)[1].split("###", 1)[0]
    user = re.search(r"- User: (.+)", ftp_section).group(1).strip()
    return {
        "host": re.search(r"- Host: (.+)", ssh_section).group(1).strip(),
        "user": user,
        "password": re.search(r"- Password: (.+)", ftp_section).group(1).strip(),
    }


def build_app(name: str, base_href: str) -> None:
    app_dir = APPS[name]["dir"]
    print(f"Building {name} web...")
    subprocess.run(
        ["flutter", "build", "web", "--release", f"--base-href={base_href}"],
        cwd=app_dir,
        check=True,
    )


def ftp_makedirs(ftp: ftplib.FTP, remote_dir: str) -> None:
    ftp.cwd("/")
    for part in remote_dir.split("/"):
        if not part:
            continue
        try:
            ftp.cwd(part)
        except ftplib.error_perm:
            ftp.mkd(part)
            ftp.cwd(part)
    ftp.cwd("/")


def ensure_parent_dirs(ftp: ftplib.FTP, remote_path: str) -> None:
    parent = "/".join(remote_path.split("/")[:-1])
    if parent:
        ftp_makedirs(ftp, parent)


def upload_tree(ftp: ftplib.FTP, local: Path, remote: str) -> None:
    for path in local.rglob("*"):
        if path.is_dir():
            continue
        rel = path.relative_to(local).as_posix()
        remote_path = f"{remote}/{rel}"
        ensure_parent_dirs(ftp, remote_path)
        with path.open("rb") as f:
            ftp.storbinary(f"STOR {remote_path}", f)
        print(f"  uploaded: {remote_path}")


def deploy_apps(names: list[str]) -> None:
    creds = parse_ftp()
    ftp = ftplib.FTP(creds["host"], timeout=180)
    ftp.login(creds["user"], creds["password"])

    for name in names:
        build_dir = APPS[name]["dir"] / "build/web"
        remote = APPS[name]["remote"]
        print(f"Deploying {name} -> {remote}")
        upload_tree(ftp, build_dir, remote)

    ftp.quit()


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--build-only", action="store_true")
    parser.add_argument("--deploy-only", action="store_true")
    parser.add_argument("--app", choices=["client", "driver", "both"], default="both")
    args = parser.parse_args()

    names = ["client", "driver"] if args.app == "both" else [args.app]

    if not args.deploy_only:
        for name in names:
            build_app(name, APPS[name]["base_href"])

    if not args.build_only:
        if not CREDS.exists():
            print(f"Missing {CREDS}", file=sys.stderr)
            return 1
        deploy_apps(names)
        print("Deploy complete.")
        print("URLs: https://fleti.com.br/client/  |  https://fleti.com.br/driver/")

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
