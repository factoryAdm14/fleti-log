#!/usr/bin/env python3
from __future__ import annotations

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


def main() -> int:
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
    script = f"""set -e
cd {remote_root}
if ! grep -q '^SOFTWARE_VERSION=' .env 2>/dev/null; then
  echo 'SOFTWARE_VERSION=3.2' >> .env
  echo added SOFTWARE_VERSION
else
  sed -i 's/^SOFTWARE_VERSION=.*/SOFTWARE_VERSION=3.2/' .env
  echo updated SOFTWARE_VERSION
fi
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
mkdir -p Modules/AiModule/Resources/views
php artisan view:cache
grep '^SOFTWARE_VERSION=' .env
"""
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


if __name__ == "__main__":
    raise SystemExit(main())
