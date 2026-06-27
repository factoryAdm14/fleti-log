#!/usr/bin/env python3
"""Create demo customer/driver accounts and simulate ride + parcel on production."""

from __future__ import annotations

import json
import re
import sys
import time
from pathlib import Path
from typing import Any

import paramiko
import requests

ROOT = Path(__file__).resolve().parents[1]
CREDS = ROOT / "DEPLOYMENT_CREDENTIALS.local.md"
SEED_PHP = ROOT / "scripts" / "seed_demo_data.php"
BASE = "https://fleti.com.br"


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


def run_seed_on_server() -> dict[str, Any]:
    creds = parse_ssh()
    remote_root = creds["remote_root"]
    remote_script = f"{remote_root}/scripts/seed_demo_data.php"

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
    sftp.put(str(SEED_PHP), remote_script)
    sftp.close()

    stdin, stdout, stderr = client.exec_command(f"cd {remote_root} && php scripts/seed_demo_data.php")
    out = stdout.read().decode()
    err = stderr.read().decode()
    client.exec_command(f"rm -f {remote_script}")
    client.close()

    if err.strip():
        print(err, file=sys.stderr)
    data = json.loads(out)
    if not data.get("ok"):
        raise RuntimeError(data.get("error") or out)
    return data


def api_login(role: str, phone: str, password: str) -> str:
    r = requests.post(
        f"{BASE}/api/{role}/auth/login",
        json={"phone_or_email": phone, "password": password},
        timeout=60,
    )
    body = r.json()
    if body.get("response_code") not in ("auth_login_200", "default_200"):
        raise RuntimeError(f"{role} login failed: {body}")
    token = body["data"]["token"]
    return token


def headers(token: str, zone_id: str) -> dict[str, str]:
    return {
        "Authorization": f"Bearer {token}",
        "zoneId": zone_id,
        "X-Localization": "pt",
        "Accept": "application/json",
    }


def driver_set_location(token: str, user_id: str, zone_id: str, lat: float, lng: float) -> None:
    requests.post(
        f"{BASE}/api/user/store-live-location",
        headers={"Authorization": f"Bearer {token}", "Accept": "application/json"},
        json={
            "user_id": user_id,
            "type": "driver",
            "latitude": lat,
            "longitude": lng,
            "zone_id": zone_id,
        },
        timeout=30,
    )


def estimate_fare(
    token: str,
    zone_id: str,
    pickup: dict,
    destination: dict,
    trip_type: str,
    parcel_category_id: str | None = None,
    parcel_weight: str | None = None,
) -> dict[str, Any]:
    payload: dict[str, Any] = {
        "pickup_coordinates": f"[{pickup['lat']},{pickup['lng']}]",
        "destination_coordinates": f"[{destination['lat']},{destination['lng']}]",
        "type": trip_type,
        "pickup_address": pickup["address"],
        "destination_address": destination["address"],
        "intermediate_coordinates": "",
        "scheduled_at": "",
    }
    if trip_type == "ride_request":
        payload["ride_request_type"] = "regular"
    else:
        payload["parcel_category_id"] = parcel_category_id
        payload["parcel_weight"] = parcel_weight

    r = requests.post(
        f"{BASE}/api/customer/ride/get-estimated-fare",
        headers=headers(token, zone_id),
        json=payload,
        timeout=90,
    )
    body = r.json()
    if body.get("response_code") != "default_200":
        raise RuntimeError(f"estimate fare failed ({trip_type}): {body}")
    return body["data"]


def _parse_duration_minutes(value: Any) -> str:
    if value is None:
        return "10"
    text = str(value)
    match = re.search(r"([\d.]+)", text)
    return str(int(float(match.group(1)))) if match else "10"


def create_trip(
    token: str,
    zone_id: str,
    pickup: dict,
    destination: dict,
    trip_type: str,
    estimate: dict,
    vehicle_category_id: str | None = None,
    parcel_category_id: str | None = None,
    customer_phone: str = "",
) -> str:
    fare_options = estimate if isinstance(estimate, list) else estimate.get("data", estimate)
    if trip_type == "ride_request":
        option = fare_options[0] if isinstance(fare_options, list) else fare_options
        vehicle_category_id = vehicle_category_id or option["vehicle_category_id"]
        estimated_fare = option["estimated_fare"]
        estimated_distance = option["estimated_distance"]
        estimated_time = _parse_duration_minutes(
            option.get("estimated_time") or option.get("estimated_duration")
        )
        encoded_polyline = option.get("encoded_polyline", "")
        surge_multiplier = option.get("surge_multiplier", 0)
    else:
        option = fare_options[0] if isinstance(fare_options, list) else fare_options
        estimated_fare = option["estimated_fare"]
        estimated_distance = option["estimated_distance"]
        estimated_time = _parse_duration_minutes(
            option.get("estimated_time") or option.get("estimated_duration")
        )
        encoded_polyline = option.get("encoded_polyline", "")
        surge_multiplier = option.get("surge_multiplier", 0)

    payload: dict[str, Any] = {
        "pickup_coordinates": f"[{pickup['lat']},{pickup['lng']}]",
        "destination_coordinates": f"[{destination['lat']},{destination['lng']}]",
        "customer_coordinates": f"[{pickup['lat']},{pickup['lng']}]",
        "customer_request_coordinates": f"[{pickup['lat']},{pickup['lng']}]",
        "estimated_distance": str(estimated_distance).replace(",", ""),
        "estimated_time": str(estimated_time).replace(",", ""),
        "estimated_fare": str(estimated_fare),
        "actual_fare": str(estimated_fare),
        "payment_method": "cash",
        "type": trip_type,
        "bid": False,
        "pickup_address": pickup["address"],
        "destination_address": destination["address"],
        "encoded_polyline": encoded_polyline,
        "zone_id": zone_id,
        "note": "",
        "pickup_note": "",
        "surge_multiplier": surge_multiplier,
        "intermediate_coordinates": "",
        "intermediate_addresses": "",
    }
    if trip_type == "ride_request":
        payload["vehicle_category_id"] = vehicle_category_id
        payload["ride_request_type"] = "regular"
    else:
        payload.update(
            {
                "parcel_category_id": parcel_category_id,
                "weight": "2",
                "payer": "sender",
                "return_fee": 5,
                "cancellation_fee": 3,
                "sender_name": "Cliente Demo",
                "sender_phone": customer_phone,
                "sender_address": pickup["address"],
                "receiver_name": "Maria Demo",
                "receiver_phone": "+5544999888777",
                "receiver_address": destination["address"],
            }
        )

    r = requests.post(
        f"{BASE}/api/customer/ride/create",
        headers=headers(token, zone_id),
        json=payload,
        timeout=90,
    )
    body = r.json()
    if body.get("response_code") not in ("default_200", "ride_request_create_200", "trip_request_store_200"):
        raise RuntimeError(f"create trip failed ({trip_type}): {body}")
    return body["data"]["id"]


def driver_accept_and_complete(
    driver_token: str,
    customer_token: str,
    zone_id: str,
    trip_id: str,
    trip_type: str,
) -> None:
    accept = requests.post(
        f"{BASE}/api/driver/ride/trip-action",
        headers=headers(driver_token, zone_id),
        json={"trip_request_id": trip_id, "action": "accepted"},
        timeout=60,
    )
    if accept.status_code == 403 and accept.json().get("response_code") == "driver_unavailable_403":
        requests.post(
            f"{BASE}/api/driver/update-online-status",
            headers={"Authorization": f"Bearer {driver_token}", "Accept": "application/json"},
            timeout=30,
        )
        accept = requests.post(
            f"{BASE}/api/driver/ride/trip-action",
            headers=headers(driver_token, zone_id),
            json={"trip_request_id": trip_id, "action": "accepted"},
            timeout=60,
        )
    accept.raise_for_status()

    if trip_type == "ride_request":
        # update-to-out-for-pickup applies only to scheduled rides in this API build.
        pass
    else:
        requests.put(
            f"{BASE}/api/driver/ride/update-to-out-for-pickup/{trip_id}",
            headers=headers(driver_token, zone_id),
            timeout=60,
        )

    for status in ("picked_up", "ongoing", "completed"):
        r = requests.put(
            f"{BASE}/api/driver/ride/update-status",
            headers=headers(driver_token, zone_id),
            json={"trip_request_id": trip_id, "status": status},
            timeout=90,
        )
        if r.status_code >= 400 and r.status_code != 500:
            body = r.json()
            raise RuntimeError(f"status {status} failed: {body}")
        time.sleep(0.5)

    requests.get(
        f"{BASE}/api/customer/ride/payment",
        headers=headers(customer_token, zone_id),
        params={"trip_request_id": trip_id, "payment_method": "cash"},
        timeout=60,
    ).raise_for_status()


def manual_trip_payload(
    accounts: dict,
    trip_type: str,
    pickup: dict,
    destination: dict,
) -> dict[str, Any]:
    return {
        "pickup_coordinates": f"[{pickup['lat']},{pickup['lng']}]",
        "destination_coordinates": f"[{destination['lat']},{destination['lng']}]",
        "customer_coordinates": f"[{pickup['lat']},{pickup['lng']}]",
        "customer_request_coordinates": f"[{pickup['lat']},{pickup['lng']}]",
        "estimated_distance": "3.5",
        "estimated_time": "12",
        "estimated_fare": "18.50",
        "actual_fare": "18.50",
        "payment_method": "cash",
        "type": trip_type,
        "bid": False,
        "pickup_address": pickup["address"],
        "destination_address": destination["address"],
        "encoded_polyline": "",
        "zone_id": accounts["zone_id"],
        "surge_multiplier": 0,
        "intermediate_coordinates": "",
        "intermediate_addresses": "",
        "vehicle_category_id": accounts.get("vehicle_category_id"),
        "ride_request_type": "regular",
        "parcel_category_id": accounts.get("parcel_category_id"),
        "weight": "2",
        "payer": "sender",
        "return_fee": 5,
        "cancellation_fee": 3,
        "sender_name": "Cliente Demo",
        "sender_phone": accounts["customer"]["phone"],
        "sender_address": pickup["address"],
        "receiver_name": "Maria Demo",
        "receiver_phone": "+5544999888777",
        "receiver_address": destination["address"],
    }


def simulate_trip(
    accounts: dict,
    trip_type: str,
    customer_token: str,
    driver_token: str,
) -> dict[str, Any]:
    pickup = accounts["pickup"]
    destination = accounts["destination"]
    zone_id = accounts["zone_id"]

    try:
        estimate = estimate_fare(
            customer_token,
            zone_id,
            pickup,
            destination,
            trip_type,
            parcel_category_id=accounts.get("parcel_category_id"),
            parcel_weight="2",
        )
        trip_id = create_trip(
            customer_token,
            zone_id,
            pickup,
            destination,
            trip_type,
            estimate,
            vehicle_category_id=accounts.get("vehicle_category_id"),
            parcel_category_id=accounts.get("parcel_category_id"),
            customer_phone=accounts["customer"]["phone"],
        )
        mode = "api_estimate"
    except Exception as exc:
        print(f"  estimate/create fallback for {trip_type}: {exc}", file=sys.stderr)
        payload = manual_trip_payload(accounts, trip_type, pickup, destination)
        r = requests.post(
            f"{BASE}/api/customer/ride/create",
            headers=headers(customer_token, zone_id),
            json=payload,
            timeout=90,
        )
        body = r.json()
        if body.get("response_code") not in ("default_200", "ride_request_create_200", "trip_request_store_200"):
            raise RuntimeError(f"manual create failed: {body}")
        trip_id = body["data"]["id"]
        mode = "manual_fare"

    driver_accept_and_complete(driver_token, customer_token, zone_id, trip_id, trip_type)
    return {"trip_id": trip_id, "type": trip_type, "mode": mode, "status": "completed"}


def main() -> int:
    import argparse

    parser = argparse.ArgumentParser()
    parser.add_argument("--simulate-only", action="store_true")
    args = parser.parse_args()

    if not CREDS.exists():
        print(f"Missing {CREDS}", file=sys.stderr)
        return 1

    if args.simulate_only and (ROOT / "scripts" / "demo_accounts.local.json").exists():
        seed = {"accounts": json.loads((ROOT / "scripts" / "demo_accounts.local.json").read_text())["accounts"]}
        print("Using cached accounts from demo_accounts.local.json")
    else:
        print("1/3 Seeding master data + demo accounts on production...")
        seed = run_seed_on_server()
    accounts = seed["accounts"]
    print(json.dumps(accounts, indent=2, ensure_ascii=False))

    print("\n2/3 Logging in...")
    customer_token = api_login("customer", accounts["customer"]["phone"], accounts["customer"]["password"])
    driver_token = api_login("driver", accounts["driver"]["phone"], accounts["driver"]["password"])
    driver_set_location(
        driver_token,
        accounts["driver"]["id"],
        accounts["zone_id"],
        accounts["pickup"]["lat"],
        accounts["pickup"]["lng"],
    )

    print("\n3/3 Simulating ride + parcel...")
    simulations = []
    simulations.append(simulate_trip(accounts, "ride_request", customer_token, driver_token))
    time.sleep(2)
    simulations.append(simulate_trip(accounts, "parcel", customer_token, driver_token))

    output = {
        "accounts": accounts,
        "simulations": simulations,
    }
    out_file = ROOT / "scripts" / "demo_accounts.local.json"
    out_file.write_text(json.dumps(output, indent=2, ensure_ascii=False), encoding="utf-8")

    print("\nDone.")
    print(f"Saved: {out_file}")
    print("\nLogin credentials:")
    print(f"  Cliente:  {accounts['customer']['phone']} / {accounts['customer']['password']}")
    print(f"  Motorista: {accounts['driver']['phone']} / {accounts['driver']['password']}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
