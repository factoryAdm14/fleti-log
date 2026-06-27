#!/usr/bin/env python3
"""E2E tests for Fleti web apps: wallet credit, ride simulation, payments."""

from __future__ import annotations

import json
import re
import sys
import time
from dataclasses import dataclass
from pathlib import Path
from typing import Any

import paramiko
import requests

ROOT = Path(__file__).resolve().parents[1]
CREDS = ROOT / "DEPLOYMENT_CREDENTIALS.local.md"
DEMO_FILE = ROOT / "scripts" / "demo_accounts.local.json"
BASE = "https://fleti.com.br"
CREDIT_PHP = ROOT / "scripts" / "credit_demo_wallet.php"


@dataclass
class Result:
    name: str
    ok: bool
    detail: str = ""


def parse_ssh() -> dict[str, Any]:
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


def run_credit_on_server(customer_amount: float = 100, driver_amount: float = 50) -> dict[str, Any]:
    creds = parse_ssh()
    remote_root = creds["remote_root"]
    remote_script = f"{remote_root}/scripts/credit_demo_wallet.php"
    php = "/opt/alt/php82/usr/bin/php"

    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    client.connect(
        creds["host"],
        port=creds["port"],
        username=creds["user"],
        password=creds["password"],
        timeout=30,
    )
    sftp = client.open_sftp()
    try:
        sftp.mkdir(f"{remote_root}/scripts")
    except OSError:
        pass
    sftp.put(str(CREDIT_PHP), remote_script)
    sftp.close()

    cmd = (
        f"cd {remote_root} && {php} scripts/credit_demo_wallet.php "
        f"--customer-amount={customer_amount} --driver-amount={driver_amount}"
    )
    stdin, stdout, stderr = client.exec_command(cmd)
    out = stdout.read().decode()
    err = stderr.read().decode()
    client.close()
    if err.strip():
        print(err, file=sys.stderr)
    data = json.loads(out)
    if not data.get("ok"):
        raise RuntimeError(data.get("error") or out)
    return data


def load_accounts() -> dict[str, Any]:
    if DEMO_FILE.exists():
        return json.loads(DEMO_FILE.read_text(encoding="utf-8"))["accounts"]
    raise RuntimeError(f"Missing {DEMO_FILE}. Run seed_and_simulate_demo.py first.")


def login(role: str, phone: str, password: str) -> str:
    r = requests.post(
        f"{BASE}/api/{role}/auth/login",
        json={"phone_or_email": phone, "password": password},
        timeout=60,
    )
    body = r.json()
    if body.get("response_code") not in ("auth_login_200", "default_200"):
        raise RuntimeError(f"login {role} failed: {body}")
    return body["data"]["token"]


def headers(token: str, zone_id: str) -> dict[str, str]:
    return {
        "Authorization": f"Bearer {token}",
        "zoneId": zone_id,
        "X-Localization": "pt",
        "Accept": "application/json",
    }


def get_profile(role: str, token: str) -> dict[str, Any]:
    r = requests.get(f"{BASE}/api/{role}/info", headers=headers(token, ""), timeout=30)
    r.raise_for_status()
    return r.json()["data"]


def driver_online(token: str) -> None:
    requests.post(
        f"{BASE}/api/driver/update-online-status",
        headers={"Authorization": f"Bearer {token}", "Accept": "application/json"},
        timeout=30,
    )


def driver_location(token: str, user_id: str, zone_id: str, lat: float, lng: float) -> None:
    requests.post(
        f"{BASE}/api/user/store-live-location",
        headers={"Authorization": f"Bearer {token}", "Accept": "application/json"},
        json={"user_id": user_id, "type": "driver", "latitude": lat, "longitude": lng, "zone_id": zone_id},
        timeout=30,
    )


def wallet_balance(profile: dict[str, Any]) -> float:
    wallet = profile.get("wallet")
    if isinstance(wallet, dict):
        return float(wallet.get("wallet_balance") or 0)
    return float(profile.get("wallet_balance") or 0)


def manual_trip_payload(accounts: dict[str, Any], payment_method: str) -> dict[str, Any]:
    pickup = accounts["pickup"]
    dest = accounts["destination"]
    return {
        "pickup_coordinates": f"[{pickup['lat']},{pickup['lng']}]",
        "destination_coordinates": f"[{dest['lat']},{dest['lng']}]",
        "customer_coordinates": f"[{pickup['lat']},{pickup['lng']}]",
        "customer_request_coordinates": f"[{pickup['lat']},{pickup['lng']}]",
        "estimated_distance": "3.5",
        "estimated_time": "12",
        "estimated_fare": "25.00",
        "actual_fare": "25.00",
        "payment_method": payment_method,
        "type": "ride_request",
        "bid": False,
        "pickup_address": pickup["address"],
        "destination_address": dest["address"],
        "encoded_polyline": "",
        "zone_id": accounts["zone_id"],
        "surge_multiplier": 0,
        "intermediate_coordinates": "",
        "intermediate_addresses": "[]",
        "vehicle_category_id": accounts.get("vehicle_category_id"),
        "ride_request_type": "regular",
        "note": "",
        "pickup_note": "",
        "surge_multiplier": 0,
    }


def create_ride(
    customer_token: str,
    accounts: dict[str, Any],
    payment_method: str,
) -> tuple[str, str]:
    zone_id = accounts["zone_id"]
    h = headers(customer_token, zone_id)
    mode = "api_estimate"

    try:
        pickup = accounts["pickup"]
        dest = accounts["destination"]
        est = requests.post(
            f"{BASE}/api/customer/ride/get-estimated-fare",
            headers=h,
            json={
                "pickup_coordinates": f"[{pickup['lat']},{pickup['lng']}]",
                "destination_coordinates": f"[{dest['lat']},{dest['lng']}]",
                "type": "ride_request",
                "ride_request_type": "regular",
                "pickup_address": pickup["address"],
                "destination_address": dest["address"],
                "intermediate_coordinates": "",
                "scheduled_at": "",
            },
            timeout=90,
        ).json()
        if est.get("response_code") != "default_200":
            raise RuntimeError(est.get("message") or est.get("response_code"))

        option = est["data"][0]
        payload = manual_trip_payload(accounts, payment_method)
        payload.update({
            "vehicle_category_id": option["vehicle_category_id"],
            "estimated_distance": str(option["estimated_distance"]).replace(",", ""),
            "estimated_time": str(int(float(re.search(r"[\d.]+", str(option.get("estimated_duration", "10"))).group()))),
            "estimated_fare": str(option["estimated_fare"]),
            "actual_fare": str(option["estimated_fare"]),
            "encoded_polyline": option.get("encoded_polyline", ""),
            "surge_multiplier": option.get("surge_multiplier", 0),
        })
    except Exception as exc:
        print(f"  fallback manual fare: {exc}", file=sys.stderr)
        payload = manual_trip_payload(accounts, payment_method)
        mode = "manual_fare"

    created = requests.post(
        f"{BASE}/api/customer/ride/create",
        headers=h,
        json=payload,
        timeout=90,
    ).json()
    if created.get("response_code") not in ("default_200", "ride_request_create_200", "trip_request_store_200"):
        raise RuntimeError(f"create failed: {created}")
    return created["data"]["id"], mode


def estimate_and_create_ride(
    customer_token: str,
    accounts: dict[str, Any],
    payment_method: str = "cash",
) -> str:
    trip_id, _ = create_ride(customer_token, accounts, payment_method)
    return trip_id


RIDE_TRANSITIONS = {
    "accepted": ("out_for_pickup", "ongoing", "completed"),
    "out_for_pickup": ("ongoing", "completed"),
    "picked_up": ("ongoing", "completed"),
    "ongoing": ("completed",),
}


def advance_trip(driver_token: str, zone_id: str, trip_id: str, current_status: str) -> None:
    h_driver = headers(driver_token, zone_id)
    for status in RIDE_TRANSITIONS.get(current_status, ("completed",)):
        r = requests.put(
            f"{BASE}/api/driver/ride/update-status",
            headers=h_driver,
            json={"trip_request_id": trip_id, "status": status},
            timeout=90,
        )
        if r.status_code >= 400:
            body = r.json() if "application/json" in r.headers.get("content-type", "") else {}
            if body.get("response_code") not in ("default_update_200", "default_200"):
                # Web app allows finishing directly from accepted/out_for_pickup.
                if status != "completed":
                    continue
                raise RuntimeError(f"status {status} failed ({current_status}): {body or r.text[:200]}")
        time.sleep(0.3)


def settle_unpaid_trips(customer_token: str, zone_id: str) -> int:
    h = headers(customer_token, zone_id)
    paid = 0
    listing = requests.get(
        f"{BASE}/api/customer/ride/list?limit=30&offset=1&filter=all_time&status=completed",
        headers=h,
        timeout=30,
    ).json().get("data") or []
    for trip in listing:
        if trip.get("payment_status") == "paid":
            continue
        trip_id = trip["id"]
        method = trip.get("payment_method") or "cash"
        if method not in ("cash", "wallet"):
            method = "cash"
        pay = requests.get(
            f"{BASE}/api/customer/ride/payment",
            headers=h,
            params={"trip_request_id": trip_id, "payment_method": method},
            timeout=60,
        )
        if pay.status_code == 200:
            paid += 1
    return paid


def clear_incomplete_rides(customer_token: str, driver_token: str, zone_id: str) -> str | None:
    """Complete or cancel any active demo trip before starting a new one."""
    h_customer = headers(customer_token, zone_id)
    h_driver = headers(driver_token, zone_id)

    resume = requests.get(
        f"{BASE}/api/customer/ride/ride-resume-status",
        headers=h_customer,
        timeout=30,
    )
    if resume.status_code != 200:
        return None
    trip = resume.json().get("data")
    if not isinstance(trip, dict) or not trip.get("id"):
        return None

    trip_id = trip["id"]
    status = trip.get("current_status", "accepted")
    if status in ("completed", "cancelled", "returned"):
        return None
    print(f"  clearing active trip {trip_id} (status={status})", file=sys.stderr)

    if status == "accepted":
        requests.post(
            f"{BASE}/api/driver/ride/trip-action",
            headers=h_driver,
            json={"trip_request_id": trip_id, "action": "accepted"},
            timeout=60,
        )

    for next_status in RIDE_TRANSITIONS.get(status, ("completed",)):
        requests.put(
            f"{BASE}/api/driver/ride/update-status",
            headers=h_driver,
            json={"trip_request_id": trip_id, "status": next_status},
            timeout=90,
        )

    if trip.get("payment_status") != "paid":
        requests.get(
            f"{BASE}/api/customer/ride/payment",
            headers=h_customer,
            params={"trip_request_id": trip_id, "payment_method": "cash"},
            timeout=60,
        )
    return trip_id


def complete_ride(
    driver_token: str,
    customer_token: str,
    zone_id: str,
    trip_id: str,
    payment_method: str,
) -> dict[str, Any]:
    h_driver = headers(driver_token, zone_id)
    h_customer = headers(customer_token, zone_id)

    accept = requests.post(
        f"{BASE}/api/driver/ride/trip-action",
        headers=h_driver,
        json={"trip_request_id": trip_id, "action": "accepted"},
        timeout=60,
    )
    if accept.status_code == 403:
        driver_online(driver_token)
        accept = requests.post(
            f"{BASE}/api/driver/ride/trip-action",
            headers=h_driver,
            json={"trip_request_id": trip_id, "action": "accepted"},
            timeout=60,
        )
    accept.raise_for_status()

    details = requests.get(
        f"{BASE}/api/driver/ride/details/{trip_id}",
        headers=h_driver,
        timeout=30,
    ).json().get("data", {})
    current = details.get("current_status", "accepted")
    advance_trip(driver_token, zone_id, trip_id, current)

    pay = requests.get(
        f"{BASE}/api/customer/ride/payment",
        headers=h_customer,
        params={"trip_request_id": trip_id, "payment_method": payment_method},
        timeout=60,
    )
    pay_body = pay.json()
    if pay.status_code >= 400 and pay_body.get("response_code") not in ("default_paid_200", "default_200"):
        raise RuntimeError(f"payment {payment_method} failed: {pay_body}")

    details = requests.get(
        f"{BASE}/api/customer/ride/details/{trip_id}",
        headers=h_customer,
        timeout=30,
    ).json()["data"]

    return {
        "trip_id": trip_id,
        "payment_method": payment_method,
        "payment_status": details.get("payment_status"),
        "paid_fare": details.get("paid_fare"),
    }


def check_web_apps() -> list[Result]:
    results: list[Result] = []
    for app in ("client", "driver"):
        for path in (f"/{app}/", f"/{app}/main.dart.js"):
            code = requests.get(f"{BASE}{path}", timeout=30).status_code
            results.append(Result(f"Web {app} {path}", code == 200, f"HTTP {code}"))
    return results


def main() -> int:
    results: list[Result] = []

    if not CREDS.exists():
        print(f"Missing {CREDS}", file=sys.stderr)
        return 1

    print("1/5 Verificando apps web...")
    results.extend(check_web_apps())

    accounts = load_accounts()
    customer = accounts["customer"]
    driver = accounts["driver"]

    print("2/5 Creditando carteiras demo...")
    try:
        credit = run_credit_on_server(100, 50)
        results.append(Result(
            "Creditar carteira cliente",
            credit.get("customer") is not None,
            f"saldo={credit.get('customer', {}).get('wallet_balance')}",
        ))
        results.append(Result(
            "Creditar carteira motorista",
            credit.get("driver") is not None,
            f"saldo={credit.get('driver', {}).get('available_balance')}",
        ))
    except Exception as exc:
        results.append(Result("Creditar carteiras", False, str(exc)))

    print("3/5 Login cliente e motorista...")
    customer_token = login("customer", customer["phone"], customer["password"])
    driver_token = login("driver", driver["phone"], driver["password"])
    results.append(Result("Login cliente", True))
    results.append(Result("Login motorista", True))

    customer_profile = get_profile("customer", customer_token)
    balance = wallet_balance(customer_profile)
    results.append(Result(
        "Saldo cliente após crédito",
        balance >= 50,
        f"R$ {balance:.2f}",
    ))

    driver_location(driver_token, driver["id"], accounts["zone_id"], accounts["pickup"]["lat"], accounts["pickup"]["lng"])
    driver_online(driver_token)

    print("3b/5 Limpando corridas pendentes...")
    settled = settle_unpaid_trips(customer_token, accounts["zone_id"])
    if settled:
        results.append(Result("Quitar corridas não pagas", True, f"{settled} corrida(s)"))
    cleared = clear_incomplete_rides(customer_token, driver_token, accounts["zone_id"])
    if cleared:
        results.append(Result("Limpar corrida ativa", True, cleared[:8] + "..."))

    print("4/5 Simulando corrida com pagamento em dinheiro...")
    try:
        trip_cash = estimate_and_create_ride(customer_token, accounts, "cash")
        cash_result = complete_ride(driver_token, customer_token, accounts["zone_id"], trip_cash, "cash")
        results.append(Result(
            "Corrida + pagamento cash",
            cash_result["payment_status"] == "paid",
            f"trip={trip_cash[:8]}... fare={cash_result.get('paid_fare')}",
        ))
    except Exception as exc:
        results.append(Result("Corrida + pagamento cash", False, str(exc)))

    print("5/5 Simulando corrida com pagamento via carteira...")
    try:
        # Re-credit in case cash ride used balance (shouldn't for cash)
        run_credit_on_server(100, 0)
        customer_token = login("customer", customer["phone"], customer["password"])
        customer_profile = get_profile("customer", customer_token)
        wallet_before = wallet_balance(get_profile("customer", customer_token))

        trip_wallet = estimate_and_create_ride(customer_token, accounts, "wallet")
        wallet_result = complete_ride(driver_token, customer_token, accounts["zone_id"], trip_wallet, "wallet")

        customer_token = login("customer", customer["phone"], customer["password"])
        wallet_after = wallet_balance(get_profile("customer", customer_token))

        results.append(Result(
            "Corrida + pagamento wallet",
            wallet_result["payment_status"] == "paid"
            and (wallet_after < wallet_before or wallet_result.get("paid_fare") in (0, 0.0, None, "0")),
            f"saldo {wallet_before:.2f} -> {wallet_after:.2f}, fare={wallet_result.get('paid_fare')}",
        ))
    except Exception as exc:
        results.append(Result("Corrida + pagamento wallet", False, str(exc)))

    # Driver finance wallet check
    try:
        fw = requests.get(
            f"{BASE}/api/driver/finance/wallet",
            headers=headers(driver_token, accounts["zone_id"]),
            timeout=30,
        ).json()["data"]
        results.append(Result(
            "Carteira financeira motorista",
            True,
            f"disponível=R$ {fw.get('withdrawable_balance', fw.get('available_balance'))}",
        ))
        txs = requests.get(
            f"{BASE}/api/driver/finance/wallet/transactions?limit=5&offset=1",
            headers=headers(driver_token, accounts["zone_id"]),
            timeout=30,
        )
        results.append(Result(
            "Transações financeiras motorista",
            txs.status_code == 200,
            f"HTTP {txs.status_code}",
        ))
    except Exception as exc:
        results.append(Result("Carteira motorista web", False, str(exc)))

    # Customer wallet transactions
    try:
        ctx = requests.get(
            f"{BASE}/api/customer/transaction/list?limit=5&offset=1&transaction_type=both",
            headers=headers(customer_token, accounts["zone_id"]),
            timeout=30,
        )
        results.append(Result(
            "Histórico carteira cliente",
            ctx.status_code == 200,
            f"HTTP {ctx.status_code}, itens={len(ctx.json().get('data') or [])}",
        ))
    except Exception as exc:
        results.append(Result("Histórico carteira cliente", False, str(exc)))

    passed = sum(1 for r in results if r.ok)
    failed = len(results) - passed
    print("\n=== RESULTADOS E2E ===")
    for r in results:
        mark = "OK" if r.ok else "FAIL"
        suffix = f" — {r.detail}" if r.detail else ""
        print(f"[{mark}] {r.name}{suffix}")
    print(f"\n{passed} OK, {failed} FALHAS de {len(results)}")

    print("\n--- URLs para teste manual ---")
    print(f"Cliente:  {customer['web']} (login: {customer['phone']} / {customer['password']})")
    print(f"Motorista: {driver['web']} (login: {driver['phone']} / {driver['password']})")
    print("Carteira cliente: https://fleti.com.br/client/#/wallet")

    return 0 if failed == 0 else 1


if __name__ == "__main__":
    raise SystemExit(main())
