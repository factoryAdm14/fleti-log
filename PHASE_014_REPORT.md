# PHASE 014 Report — PIX Mercado Pago

**Data:** 2026-06-26  
**Branch:** `feature/fleti-enterprise-v4`  
**Status:** Concluída (deploy pendente)

## Objetivo

Adicionar gateway `mercadopago_pix` com QR Code, Copia e Cola, webhook, status (pending/paid/expired/failed), idempotência, logs e ativação via Admin — sem remover o `mercadopago` existente.

## Entregas

### Backend (Laravel)

- [x] `MercadoPagoPixService` — API MP, idempotência, auditoria
- [x] `MercadoPagoPixController` — pay, status, webhook
- [x] View `mercadopago-pix.blade.php` — QR + Copia e Cola + polling
- [x] Rotas em `Modules/Gateways/Routes/web.php`
- [x] `generate_link` para `mercadopago_pix`
- [x] `PAYMENT_METHODS` + validações API/admin
- [x] Migration `mercadopago_pix_logs`
- [x] Migration insert gateway em `settings`
- [x] Documentação `PIX_MERCADO_PAGO.md`

### Flutter (User App)

- [x] Traduções `mercadopago_pix` em `en.json` / `ar.json`
- [x] Fluxo existente via WebView (`DigitalPaymentScreen`) — sem alteração estrutural

## Configuração produção

1. `php artisan migrate` no servidor
2. Admin → ativar **Mercadopago Pix** com Access Token live
3. Configurar webhook MP: `https://fleti.com.br/payment/mercadopago-pix/webhook`
4. Garantir `currency_code = BRL`

## Rollback

Desativar gateway no Admin (Inactive). Código do `mercadopago` cartão permanece intacto.

## Próximo passo

**FASE 015** — PIX EFI (`efi_pix`)
