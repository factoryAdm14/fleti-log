# PHASE 019 Report — Observabilidade

**Data:** 2026-06-26  
**Branch:** `feature/fleti-enterprise-v4`  
**Status:** Concluída

## Objetivo

Estruturar logs operacionais para login, corridas, delivery, wallet, PIX, zonas, webhooks, erros de API/mapa e falhas de pagamento.

## Entregas

### Core

- [x] `App\Lib\FletiObservability` — logger estruturado com 11 domínios
- [x] Canal `fleti` em `config/logging.php` → `storage/logs/fleti-ops-*.log`
- [x] `FletiApiObservabilityMiddleware` — HTTP 4xx/5xx em `/api/*`
- [x] Exception reporter para APIs não tratadas

### Integrações

| Área | Arquivo | Eventos |
|------|---------|---------|
| Login | `AuthController` | success, invalid_credentials, temp_blocked, client_not_found |
| Corrida/Delivery | `TripRequestController` | create_failed |
| Wallet | `WalletController` | add_fund_*, gateway_inactive |
| Pagamento | `PaymentRecordController` | callback_*, invalid_trip |
| PIX MP | `MercadoPagoPixService` | audit → fleti + webhook |
| PIX EFI | `EfiPixService` | audit → fleti + webhook |
| Mapa | `ConfigController` | geocode_failed |
| Zona | `ConfigController` | not_found |

### Testes

- [x] `FletiObservabilityTest` — canal, redaction de campos sensíveis
- [x] Suite PHPUnit: **19 testes** passando

### Documentação

- [x] `OBSERVABILITY_GUIDE.md`

## Variáveis `.env`

```env
FLETI_LOG_LEVEL=info
FLETI_LOG_DAYS=30
LOG_LEVEL=warning
```

## Deploy

Requer deploy dos arquivos PHP alterados + `php artisan config:cache`.

## Próximo passo

**FASE 020** — Roadmap futuro (`ROADMAP_2026_2028.md`)
