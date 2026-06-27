#!/usr/bin/env python3
"""Deploy FinanceManagement module to fleti.com.br (FTP upload + SSH migrate/seed)."""

from __future__ import annotations

import argparse
import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
ADMIN = ROOT / "fleti-admin-new-install-3.2"
CREDS = ROOT / "DEPLOYMENT_CREDENTIALS.local.md"
SEEDER = r"Modules\FinanceManagement\Database\Seeders\FinanceManagementDatabaseSeeder"

# Paths required for the finance module (relative to fleti-admin-new-install-3.2).
EXTRA_PATHS = [
    "app/Library/DriverSubscriptionPaymentUpdate.php",
    "app/Library/TripRequestUpdate.php",
    "app/Lib/Constant.php",
    "app/Providers/AuthServiceProvider.php",
    "bootstrap/app.php",
    "composer.json",
    "modules_statuses.json",
    "Modules/AdminModule/Resources/views/partials/_sidebar.blade.php",
    "Modules/Gateways/Services/EfiPixService.php",
    "Modules/Gateways/Services/EfiPixPayoutService.php",
    "Modules/Gateways/Services/MercadoPagoPixService.php",
    "Modules/Gateways/Http/Controllers/EfiPixController.php",
    "Modules/Gateways/Http/Controllers/MercadoPagoPixController.php",
    "Modules/Gateways/Entities/PaymentRequest.php",
    "Modules/TripManagement/Http/Controllers/Api/PaymentController.php",
    "Modules/TripManagement/Repository/Eloquent/TripStopRepository.php",
    "Modules/TripManagement/Service/Interfaces/TripStopServiceInterface.php",
    "Modules/UserManagement/Entities/WithdrawRequest.php",
]


def collect_files() -> list[str]:
    files: set[str] = set(EXTRA_PATHS)
    module = ADMIN / "Modules/FinanceManagement"
    if module.is_dir():
        for path in module.rglob("*"):
            if path.is_file():
                files.add(str(path.relative_to(ADMIN)))
    return sorted(f for f in files if (ADMIN / f).is_file())


def upload_files(files: list[str]) -> None:
    sys.path.insert(0, str(Path(__file__).resolve().parent))
    from deploy_ftp_common import connect_ftp, upload_file

    ftp = connect_ftp()
    for rel in files:
        upload_file(ftp, ADMIN / rel, rel)
    ftp.quit()


def run_ssh(*, maintenance: bool, skip_seed: bool, skip_composer: bool) -> int:
    sys.path.insert(0, str(Path(__file__).resolve().parent))
    from ssh_post_deploy import parse_ssh

    import paramiko

    creds = parse_ssh()
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    client.connect(
        creds["host"],
        port=creds["port"],
        username=creds["user"],
        password=creds["password"],
        timeout=60,
    )

    remote = creds["remote_root"]
    php = "/opt/alt/php82/usr/bin/php"
    lines = ["set -euo pipefail", f"cd {remote}"]

    if maintenance:
        lines.append(f"{php} artisan down --retry=60 2>/dev/null || true")

    if not skip_composer:
        lines.extend([
            f"{php} /usr/local/bin/composer install --no-dev --optimize-autoloader --no-interaction 2>/dev/null || true",
            f"{php} /usr/local/bin/composer dump-autoload -o --no-interaction 2>/dev/null || true",
        ])

    lines.append(f"{php} artisan migrate --path=Modules/FinanceManagement/Database/Migrations --force")

    if not skip_seed:
        lines.append(f'{php} artisan db:seed --class="Modules\\\\FinanceManagement\\\\Database\\\\Seeders\\\\FinanceManagementDatabaseSeeder" --force')

    lines.extend([
        f"{php} artisan optimize:clear",
        "# NUNCA apagar bootstrap/cache/modules.php",
        "rm -f bootstrap/cache/config.php bootstrap/cache/routes-v7.php 2>/dev/null || true",
        f"{php} artisan config:cache",
        f"{php} artisan route:cache",
        "mkdir -p Modules/FinanceManagement/Resources/views",
        "mkdir -p Modules/AiModule/Resources/views",
        f"{php} artisan view:cache",
        f"{php} artisan queue:restart 2>/dev/null || true",
    ])

    if maintenance:
        lines.append(f"{php} artisan up")

    lines.extend([
        'echo "=== Finance deploy done ==="',
        f"{php} artisan migrate:status | grep -i finance || true",
    ])

    script = "\n".join(lines) + "\n"
    stdin, stdout, stderr = client.exec_command("bash -s", timeout=600)
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
    parser = argparse.ArgumentParser(description="Deploy FinanceManagement to production")
    parser.add_argument("--dry-run", action="store_true", help="List files only")
    parser.add_argument("--skip-upload", action="store_true", help="SSH steps only")
    parser.add_argument("--skip-seed", action="store_true")
    parser.add_argument("--skip-composer", action="store_true")
    parser.add_argument("--no-maintenance", action="store_true")
    args = parser.parse_args()

    if not CREDS.exists():
        print(f"Missing {CREDS}", file=sys.stderr)
        return 1

    files = collect_files()
    print(f"Finance deploy: {len(files)} files")

    if args.dry_run:
        for path in files:
            print(path)
        return 0

    if not args.skip_upload:
        upload_files(files)
        print("FTP upload complete.")

    return run_ssh(
        maintenance=not args.no_maintenance,
        skip_seed=args.skip_seed,
        skip_composer=args.skip_composer,
    )


if __name__ == "__main__":
    raise SystemExit(main())
