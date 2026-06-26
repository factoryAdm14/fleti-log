# BACKEND PERFORMANCE — Fleti Enterprise v4.0 (FASE 011)

Auditoria e otimizações do Laravel admin/API em `fleti-admin-new-install-3.2`.

## Resumo executivo

| Área | Status atual | Ação |
|------|--------------|------|
| Índices MySQL | Faltavam em tabelas quentes | **Migration aplicada** |
| N+1 em listas de corrida | `TripRequestResource` com `exists()` | **Corrigido** |
| Eager loading trip list | Relações extras | **Estendido** |
| Zone API | `get()` sem limite | **Limite default 25, max 100** |
| Fleet map | N+1 safety alerts | **Corrigido** |
| Cache | `file` (default) | **Recomendado Redis em produção** |
| Queue | `sync` (default) | **Recomendado Redis + worker** |
| Config/Route/View cache | Parcial em prod | **Manter pós-deploy** |
| Logs | `LOG_LEVEL=debug` no example | **Usar `warning` em prod** |

---

## Correções implementadas (código)

### 1. Migration `2026_06_26_120000_add_fleti_performance_indexes.php`

Índices adicionados:

| Tabela | Índice | Colunas |
|--------|--------|---------|
| `trip_requests` | `trip_requests_customer_created_idx` | `customer_id`, `created_at` |
| `trip_requests` | `trip_requests_driver_status_created_idx` | `driver_id`, `current_status`, `created_at` |
| `trip_requests` | `trip_requests_zone_id_idx` | `zone_id` |
| `trip_requests` | `trip_requests_current_status_idx` | `current_status` |
| `transactions` | `transactions_user_created_idx` | `user_id`, `created_at` |
| `transactions` | `transactions_account_idx` | `account` |
| `users` | `users_type_active_idx` | `user_type`, `is_active` |

```bash
php artisan migrate --force
```

### 2. `TripRequestResource.php`

- `fee()->exists()` / `time()->exists()` → `relationLoaded()` (elimina 2 queries por item)
- `discount`, `coupon`, safety alerts, `proofImage`, `tripNavigation` → `whenLoaded()`
- `businessConfig` de localização em tempo real → 1 chamada por item

### 3. Eager loading em listas de corrida

Relações adicionadas em customer `rideList` e driver `rideList`:

`discount`, `coupon`, `driverSafetyAlert`, `customerSafetyAlert`, `proofImage`, `tripNavigation`

### 4. `ZoneController::list` (driver API)

- Default: `limit=25`, `offset=1`
- Máximo: `100` (evita dump completo da tabela)

### 5. `FleetMapViewController::fleetMapDriverDetails`

- `->with('driverSafetyAlertPending')` na query de `otherTrips`

---

## Itens auditados — pendências (documentação / ops)

### Queue & jobs

- Jobs: `SendPushNotificationJob`, `ProcessPushNotifications`, etc.
- Com `QUEUE_CONNECTION=sync`, push roda **inline** na request
- **Produção recomendada:**

```env
QUEUE_CONNECTION=redis
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
LOG_LEVEL=warning
```

```bash
php artisan queue:work redis --queue=high,default --tries=3
```

Horizon **não** está no projeto — usar Supervisor/systemd.

### Cache Laravel

Produção (já aplicado via SSH):

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize:clear  # apenas após deploy de código
```

`newBusinessConfig()` / `CACHE_BUSINESS_SETTINGS` já cacheia settings — preferir sobre `businessConfig()` em hot paths.

### N+1 / paginação — backlog P1

| Item | Arquivo | Risco |
|------|---------|-------|
| `allRideList()` sem paginação | `Driver/TripRequestController` | Alto para motoristas ativos |
| Admin carrega todos users para filtro | `TripController` | Alto com muitos usuários |
| `customerTrips` / `driverTrips` no index admin | `CustomerController`, `DriverController` | Médio |
| Analytics por zona (loop) | `TripRequestService` | Médio (admin only) |
| Export wallet sem limite | `CustomerWalletController` | Médio |

**Não alterados** nesta fase para evitar regressão em apps mobile/admin.

### Imagens

- Upload via GD em `fileUploader()` (`app/Lib/Helpers.php`)
- CPU-bound; considerar fila para resize em alto volume
- Validar tamanho máximo nos FormRequests existentes

### Scheduler

`app/Console/Kernel.php` — cron a cada minuto para corridas agendadas/cancelamento:

```bash
* * * * * cd /path && php artisan schedule:run >> /dev/null 2>&1
```

---

## Checklist produção Hostinger

- [ ] `php artisan migrate --force` (índices FASE 011)
- [ ] `php artisan config:cache && php artisan route:cache`
- [ ] `LOG_LEVEL=warning` no `.env`
- [ ] Redis disponível no plano (ou `QUEUE_CONNECTION=database` + worker)
- [ ] Cron `schedule:run`
- [ ] Worker `queue:work` (se Redis/database queue)
- [ ] OPcache habilitado no PHP 8.3

---

## Métricas sugeridas (pós-deploy)

1. Tempo de resposta `GET /api/customer/ride/list`
2. Tempo de resposta `GET /api/driver/ride/list`
3. Queries por request (Debugbar/Telescope em staging)
4. `EXPLAIN` em filtros `trip_requests` por `customer_id` / `driver_id`

---

*FASE 011 — Fleti Enterprise v4.0*
