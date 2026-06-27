#!/usr/bin/env python3
"""Smoke tests for Fleti Flutter web apps (client + driver) and backing API."""

from __future__ import annotations

import argparse
import json
import subprocess
import sys
import urllib.error
import urllib.request
from dataclasses import dataclass

DEFAULT_BASE = "https://fleti.com.br"
DEMO_CUSTOMER = {"phone_or_email": "+5544999000001", "password": "Test1234!"}
DEMO_DRIVER = {"phone_or_email": "+5544999000002", "password": "Test1234!"}


@dataclass
class Result:
    name: str
    ok: bool
    detail: str = ""


def request(
    method: str,
    url: str,
    body: dict | None = None,
    token: str | None = None,
) -> tuple[int, dict]:
    headers = {"Accept": "application/json", "Content-Type": "application/json"}
    if token:
        headers["Authorization"] = f"Bearer {token}"
    data = json.dumps(body).encode() if body is not None else None
    req = urllib.request.Request(url, data=data, headers=headers, method=method)
    try:
        with urllib.request.urlopen(req, timeout=30) as resp:
            raw = resp.read().decode()
            code = resp.status
    except urllib.error.HTTPError as exc:
        raw = exc.read().decode()
        code = exc.code
    try:
        parsed = json.loads(raw) if raw else {}
    except json.JSONDecodeError:
        parsed = {"_raw": raw[:300]}
    return code, parsed


def login(base: str, role: str, creds: dict) -> str | None:
    code, payload = request("POST", f"{base}/api/{role}/auth/login", creds)
    if code != 200:
        return None
    data = payload.get("data")
    if isinstance(data, dict):
        return data.get("token") or data.get("access_token")
    return payload.get("token")


def run_tests(base: str) -> list[Result]:
    results: list[Result] = []

    def check(name: str, ok: bool, detail: str = "") -> None:
        results.append(Result(name, ok, detail))

    for app in ("client", "driver"):
        code, _ = request("GET", f"{base}/{app}/")
        check(f"App {app} HTML", code == 200, f"HTTP {code}")
        code, _ = request("GET", f"{base}/{app}/main.dart.js")
        check(f"App {app} bundle JS", code == 200, f"HTTP {code}")

    code, cfg = request("GET", f"{base}/api/customer/configuration")
    maint = (cfg.get("maintenance_mode") or {}).get("maintenance_status", 0)
    check("API config cliente", code == 200 and bool(cfg.get("business_name")), str(cfg.get("business_name", ""))[:40])
    check("Manutenção desligada", maint == 0, f"status={maint}")
    has_map = bool(cfg.get("map_api_key"))
    check("Chave Google Maps (cliente)", has_map, "configurada" if has_map else "ausente")

    code, dcfg = request("GET", f"{base}/api/driver/configuration")
    check("API config motorista", code == 200 and bool(dcfg.get("business_name")), str(dcfg.get("business_name", ""))[:40])
    check("Chave Google Maps (motorista)", bool(dcfg.get("map_api_key")), "ok" if dcfg.get("map_api_key") else "ausente")

    cors = subprocess.run(
        [
            "curl",
            "-s",
            "-o",
            "/dev/null",
            "-w",
            "%{http_code}",
            "-X",
            "OPTIONS",
            "-H",
            "Origin: https://fleti.com.br",
            "-H",
            "Access-Control-Request-Method: POST",
            f"{base}/api/customer/auth/login",
        ],
        capture_output=True,
        text=True,
        check=False,
    )
    check("CORS preflight login", cors.stdout.strip() in ("200", "204"), f"HTTP {cors.stdout.strip()}")

    token_c = login(base, "customer", DEMO_CUSTOMER)
    check("Login cliente demo", bool(token_c))

    token_d = login(base, "driver", DEMO_DRIVER)
    check("Login motorista demo", bool(token_d))

    if token_c:
        code, _ = request("GET", f"{base}/api/customer/info", token=token_c)
        check("Perfil cliente", code == 200, f"HTTP {code}")
        code, rides = request("GET", f"{base}/api/customer/ride/list?limit=5&offset=1", token=token_c)
        items = rides.get("data") if isinstance(rides.get("data"), list) else []
        check("Histórico cliente", code == 200, f"{len(items)} itens")

    if token_d:
        code, _ = request("GET", f"{base}/api/driver/info", token=token_d)
        check("Perfil motorista", code == 200, f"HTTP {code}")
        code, wallet = request("GET", f"{base}/api/driver/finance/wallet", token=token_d)
        wdata = wallet.get("data") if isinstance(wallet.get("data"), dict) else {}
        balance = wdata.get("withdrawable_balance", wdata.get("available_balance", "?"))
        check("Carteira financeira", code == 200, f"saldo={balance}")
        code, plans = request("GET", f"{base}/api/driver/finance/plans", token=token_d)
        pdata = plans.get("data") if isinstance(plans.get("data"), dict) else {}
        check("Planos motorista", code == 200, f"enabled={pdata.get('plans_enabled')}")
        code, _ = request("GET", f"{base}/api/driver/ride/pending-ride-list?limit=5&offset=1", token=token_d)
        check("Chamadas pendentes", code == 200, f"HTTP {code}")

    code, _ = request("GET", f"{base}/api/finance/payment-gateways")
    check("Gateways pagamento", code == 200, f"HTTP {code}")

    return results


def main() -> int:
    parser = argparse.ArgumentParser(description="Smoke test Fleti web apps")
    parser.add_argument("--base-url", default=DEFAULT_BASE)
    args = parser.parse_args()

    results = run_tests(args.base_url.rstrip("/"))
    passed = sum(1 for r in results if r.ok)
    failed = len(results) - passed

    for r in results:
        mark = "PASS" if r.ok else "FAIL"
        suffix = f" — {r.detail}" if r.detail else ""
        print(f"[{mark}] {r.name}{suffix}")

    print(f"\n=== {passed} OK, {failed} FALHAS de {len(results)} ===")
    return 0 if failed == 0 else 1


if __name__ == "__main__":
    raise SystemExit(main())
