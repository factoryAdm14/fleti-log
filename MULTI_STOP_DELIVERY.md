# Multi Stop Delivery — Fleti Enterprise v4.0

Entrega parcel com múltiplas paradas (até 20), opcional via feature flag. O delivery simples permanece inalterado quando desativado.

## Feature flag

| Config | Admin | Padrão |
|--------|-------|--------|
| `enable_multi_stop_delivery` | Parcel Settings → Enable Multi Stop Delivery | `0` (off) |
| `multi_stop_max_stops` | `business_settings` | `20` |

Apps recebem via `/api/customer/config`:
- `enable_multi_stop_delivery`
- `multi_stop_max_stops`

## Tabela `trip_stops`

| Campo | Descrição |
|-------|-----------|
| `trip_request_id` | Viagem parcel |
| `stop_order` | Ordem (1..N) |
| `type` | `pickup` ou `dropoff` |
| `address`, `latitude`, `longitude` | Localização |
| `status` | pending, arrived, completed, failed, expired |
| `proof_photo`, `signature`, `qr_code` | Prova de entrega |
| `arrived_at`, `completed_at` | Timeline |

Flag `trip_requests.is_multi_stop = 1` quando paradas são criadas.

## Criar parcel multi-stop (Customer API)

`POST /api/customer/ride/create` — mesmo endpoint, campo extra:

```json
{
  "type": "parcel",
  "stops": "[{\"stop_order\":1,\"type\":\"pickup\",\"address\":\"...\",\"latitude\":-23.5,\"longitude\":-46.6},{\"stop_order\":2,\"type\":\"dropoff\",\"address\":\"...\",\"latitude\":-23.6,\"longitude\":-46.7}]",
  "...": "demais campos parcel obrigatórios"
}
```

Regras:
- Feature flag deve estar ativa
- Mínimo 2 paradas, máximo configurável (20)
- Pelo menos 1 pickup e 1 dropoff
- Sem `stops` → fluxo parcel normal (sem alteração)

## APIs Motorista

| Método | Rota | Ação |
|--------|------|------|
| GET | `/api/driver/ride/trip-stops/trip/{trip_id}` | Listar paradas |
| GET | `/api/driver/ride/trip-stops/trip/{trip_id}/timeline` | Timeline + próxima parada |
| PUT | `/api/driver/ride/trip-stops/{stop_id}/arrive` | Marcar chegada |
| POST | `/api/driver/ride/trip-stops/{stop_id}/complete` | Concluir parada (proof_photo, signature, qr_code) |

Conclusão da viagem (`update-status` → `completed`) exige **todas** as paradas concluídas quando `is_multi_stop = 1`.

## Rollback

Desative **Enable Multi Stop Delivery** no Admin. Pedidos sem `stops` continuam iguais.

## Arquivos principais

- `Modules/TripManagement/Entities/TripStop.php`
- `Modules/TripManagement/Service/TripStopService.php`
- `Modules/TripManagement/Lib/MultiStopHelper.php`
- `Modules/TripManagement/Http/Controllers/Api/Driver/TripStopController.php`
- Migration `2026_06_26_140000_create_trip_stops_table.php`

## Deploy

```bash
php artisan migrate
php artisan route:cache
```
