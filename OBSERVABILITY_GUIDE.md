# OBSERVABILITY GUIDE — Fleti Enterprise v4.0 (FASE 019)

Guia de observabilidade operacional para o ecossistema **Fleti** (Laravel + Flutter).

**Branch:** `feature/fleti-enterprise-v4`  
**Data:** 2026-06-26

---

## 1. Visão geral

| Camada | Mecanismo | Local |
|--------|-----------|-------|
| Ops estruturado | `FletiObservability` + canal `fleti` | `storage/logs/fleti-ops-*.log` |
| Laravel geral | Monolog `single`/`daily` | `storage/logs/laravel.log` |
| PIX (DB) | `mercadopago_pix_logs`, `efi_pix_logs` | MySQL |
| Admin activity | `activity_logs` | MySQL (entidades admin) |
| API HTTP 4xx/5xx | `FletiApiObservabilityMiddleware` | `fleti-ops` |
| Exceções API | Exception reporter | `fleti-ops` |

---

## 2. Domínios de log

| Domínio | Constante | Eventos principais |
|---------|-----------|-------------------|
| Login | `login` | `success`, `invalid_credentials`, `temp_blocked`, `client_not_found` |
| Corrida | `ride` | `create_failed` |
| Delivery | `delivery` | `create_failed` (parcel) |
| Wallet | `wallet` | `add_fund_link_created`, `gateway_inactive`, `add_fund_validation_failed` |
| PIX | `pix` | `create_payment`, `create_cob`, `paid`, `webhook`, `webhook_orphan` |
| Zona | `zone` | `not_found` |
| Webhook | `webhook` | `mercadopago_pix_*`, `efi_pix_*` |
| API | `api` | `http_error`, `unhandled_exception` |
| Mapa | `map` | `geocode_failed` |
| Pagamento | `payment` | `callback_failed`, `callback_cancelled`, `gateway_inactive`, `invalid_trip` |
| Build | `build` | Reservado para CI/CD (Flutter build failures) |

---

## 3. Formato do log

Cada entrada no canal `fleti` inclui:

```json
{
  "domain": "login",
  "event": "success",
  "timestamp": "2026-06-26T12:00:00+00:00",
  "request_id": "...",
  "ip": "1.2.3.4",
  "path": "api/customer/auth/login",
  "method": "POST",
  "user_id": "uuid",
  "user_type": "customer"
}
```

Campos sensíveis (`password`, `token`, `access_token`, `qr_code`, etc.) são **redacted** automaticamente.

---

## 4. Configuração

### `.env` (produção)

```env
LOG_CHANNEL=stack
LOG_LEVEL=warning
FLETI_LOG_LEVEL=info
FLETI_LOG_DAYS=30
```

| Variável | Recomendação produção |
|----------|----------------------|
| `LOG_LEVEL` | `warning` — reduz ruído no `laravel.log` |
| `FLETI_LOG_LEVEL` | `info` — eventos operacionais |
| `FLETI_LOG_DAYS` | `30` — retenção rotação diária |

### Arquivo de config

`config/logging.php` — canal `fleti`:

```php
'fleti' => [
    'driver' => 'daily',
    'path' => storage_path('logs/fleti-ops.log'),
    'level' => env('FLETI_LOG_LEVEL', 'info'),
    'days' => 30,
],
```

---

## 5. Onde consultar logs

### SSH (Hostinger)

```bash
ssh -p 65002 u965007418@147.79.88.36
cd ~/domains/fleti.com.br/public_html

# Ops Fleti (últimas 100 linhas)
tail -100 storage/logs/fleti-ops-$(date +%Y-%m-%d).log

# Erros Laravel
tail -100 storage/logs/laravel.log

# Filtrar por domínio
grep '\[fleti:login\]' storage/logs/fleti-ops-*.log | tail -20
grep '\[fleti:pix\]' storage/logs/fleti-ops-*.log | tail -20
grep '\[fleti:payment\]' storage/logs/fleti-ops-*.log | tail -20
```

### PIX (banco de dados)

```sql
SELECT event, payment_request_id, created_at
FROM mercadopago_pix_logs
ORDER BY created_at DESC LIMIT 20;

SELECT event, payment_request_id, created_at
FROM efi_pix_logs
ORDER BY created_at DESC LIMIT 20;
```

### Admin — Activity Log

Painel admin → logs de entidades (zonas, usuários, veículos) via `activity_logs`.

---

## 6. Uso no código

```php
use App\Lib\FletiObservability;

// Login
FletiObservability::login('success', ['user_id' => $user->id, 'user_type' => $user->user_type]);

// Corrida / delivery
FletiObservability::ride('status_changed', ['trip_id' => $id, 'status' => $status]);
FletiObservability::delivery('stop_completed', ['trip_id' => $id, 'stop_id' => $stopId]);

// Wallet
FletiObservability::wallet('debit', ['user_id' => $id, 'amount' => $amount]);

// PIX / webhook
FletiObservability::pix('paid', ['payment_request_id' => $id]);
FletiObservability::webhook('gateway_event', ['gateway' => 'efi_pix']);

// Erros
FletiObservability::mapError('distance_api_failed', ['http_status' => 500]);
FletiObservability::paymentFailure('callback_failed', ['trip_request_id' => $id]);
FletiObservability::exception(FletiObservability::DOMAIN_API, 'handler', $e);
```

---

## 7. Middleware API

`FletiApiObservabilityMiddleware` registra automaticamente respostas **HTTP ≥ 400** em rotas `/api/*`:

- `warning` para 4xx
- `error` para 5xx

Registrado no grupo `api` em `bootstrap/app.php`.

---

## 8. Integrações futuras (backlog)

| Ferramenta | Uso |
|------------|-----|
| **Datadog** | Agente no servidor ou forward de `fleti-ops.log` |
| **Sentry** | Exceções não tratadas + release tracking Flutter |
| **OpenTelemetry** | Traces distribuídos (FASE 020 roadmap) |
| **Slack** | Canal `slack` em `logging.php` para `critical` |
| **Elasticsearch** | Indexação de `fleti-ops` para busca |

### Exemplo Slack (opcional)

```env
LOG_SLACK_WEBHOOK_URL=https://hooks.slack.com/services/...
```

Alterar `config/logging.php` stack para incluir `slack` em nível `critical`.

---

## 9. Flutter — erros de build e runtime

### Build (CI/local)

Registrar falhas com domínio `build`:

```bash
flutter build apk --release 2>&1 | tee build.log
# Em caso de falha, anexar build.log ao ticket
```

### Runtime (app)

- Firebase Crashlytics (já integrado via Firebase nos apps)
- Logs locais: `debugPrint` apenas em dev; produção via Crashlytics

**Backlog:** wrapper `FletiAppLogger` nos apps Flutter alinhado aos domínios backend.

---

## 10. Alertas recomendados

| Condição | Ação |
|----------|------|
| `unhandled_exception` > 10/min | Investigar `laravel.log` + deploy recente |
| `pix webhook_orphan` repetido | Verificar referência external_reference / txid |
| `login temp_blocked` spike | Possível brute force — revisar rate limit |
| `map geocode_failed` | Chave Google Maps ou quota |
| `payment callback_failed` | Gateway ou redirect URL |
| `zone not_found` alto | Cobertura de zonas ou coordenadas inválidas |

---

## 11. LGPD / privacidade

- Logs **não** armazenam senhas, tokens ou QR PIX completos
- `user_id` e `ip` são registrados para auditoria de segurança
- Retenção: 30 dias (`FLETI_LOG_DAYS`) — ajustar conforme política
- Em produção usar `LOG_LEVEL=warning` para evitar PII em debug

---

## 12. Comandos rápidos

```bash
# Testes
cd fleti-admin-new-install-3.2 && php artisan test --filter=FletiObservability

# Verificar canal após deploy
ssh ... "ls -la storage/logs/fleti-ops-*"

# Limpar logs antigos (cuidado)
php artisan log:clear  # se disponível, ou rm manual de arquivos > 30 dias
```

---

## 13. Referências

- `app/Lib/FletiObservability.php` — implementação central
- `app/Http/Middleware/FletiApiObservabilityMiddleware.php`
- `PIX_MERCADO_PAGO.md` / `PIX_EFI.md` — tabelas de audit PIX
- `SECURITY_AUDIT.md` — logging e PII
- `DEPLOYMENT_GUIDE.md` — deploy com cache
