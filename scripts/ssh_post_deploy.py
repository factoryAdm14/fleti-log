#!/usr/bin/env python3
"""SSH post-deploy steps for Fleti production (cache, migrate, queue)."""

from __future__ import annotations

import argparse
import re
import sys
from pathlib import Path

import paramiko

ROOT = Path(__file__).resolve().parents[1]
CREDS = ROOT / "DEPLOYMENT_CREDENTIALS.local.md"


def parse_ssh() -> dict[str, str]:
    text = CREDS.read_text(encoding="utf-8")
    section = text.split("### SSH", 1)[1].split("###", 1)[0]
    user = re.search(r"- Usuário: (.+)", section).group(1).strip()
    return {
        "host": re.search(r"- Host: (.+)", section).group(1).strip(),
        "port": int(re.search(r"- Porta: (.+)", section).group(1).strip()),
        "user": user,
        "password": re.search(r"- Senha: (.+)", section).group(1).strip(),
        "remote_root": f"/home/{user}/domains/fleti.com.br/public_html",
    }


def run_post_deploy(*, migrate: bool = False, maintenance: bool = True) -> int:
    if not CREDS.exists():
        print(f"Missing {CREDS}", file=sys.stderr)
        return 1

    creds = parse_ssh()
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    client.connect(
        creds["host"],
        port=creds["port"],
        username=creds["user"],
        password=creds["password"],
        timeout=30,
    )

    remote_root = creds["remote_root"]
    lines = ["set -e", f"cd {remote_root}"]

    if maintenance:
        lines.append("php artisan down --retry=60 2>/dev/null || true")

    lines.extend([
        "if ! grep -q '^SOFTWARE_VERSION=' .env 2>/dev/null; then",
        "  echo 'SOFTWARE_VERSION=3.2' >> .env",
        "fi",
        "grep -q '^CORS_ALLOWED_ORIGINS=' .env || echo 'CORS_ALLOWED_ORIGINS=https://fleti.com.br,https://www.fleti.com.br' >> .env",
    ])

    if migrate:
        lines.append("php artisan migrate --force")

    lines.extend([
        "php artisan optimize:clear",
        "php artisan config:cache",
        "php artisan route:cache",
        "mkdir -p Modules/AiModule/Resources/views",
        "php artisan view:cache",
        "php artisan queue:restart 2>/dev/null || true",
    ])

    if maintenance:
        lines.append("php artisan up")

    lines.append("php artisan --version")
    lines.append("grep '^SOFTWARE_VERSION=' .env")

    script = "\n".join(lines) + "\n"
    stdin, stdout, stderr = client.exec_command("bash -s")
    stdin.write(script)
    stdin.channel.shutdown_write()
    out = stdout.read().decode()
    err = stderr.read().decode()
    code = stdout.channel.recv_exit_status()
    client.close()

    if out:
        print(out.strip())
    if err:
        print(err.strip(), file=sys.stderr)
    return code


def main() -> int:
    parser = argparse.ArgumentParser(description="Run SSH post-deploy on production")
    parser.add_argument("--migrate", action="store_true", help="Run php artisan migrate --force")
    parser.add_argument("--no-maintenance", action="store_true", help="Skip artisan down/up")
    args = parser.parse_args()
    return run_post_deploy(migrate=args.migrate, maintenance=not args.no_maintenance)


if __name__ == "__main__":
    raise SystemExit(main())
