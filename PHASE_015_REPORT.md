# PHASE 015 Report — PIX EFI

**Data:** 2026-06-26  
**Branch:** `feature/fleti-enterprise-v4`  
**Status:** Concluída (deploy pendente)

## Objetivo

Adicionar gateway `efi_pix` com certificado, Client ID/Secret, sandbox/produção, QR Code, Copia e Cola, webhook, logs e ativação via Admin — sem substituir Mercado Pago ou gateways existentes.

## Entregas

### Backend

- [x] `EfiPixService` — OAuth mTLS, cobrança PIX, QR, webhook
- [x] `EfiPixController` — pay, status, webhook
- [x] View `efi-pix.blade.php`
- [x] Upload certificado `.p12` no Admin
- [x] Migration `efi_pix_logs` + insert gateway `settings`
- [x] Rotas, `generate_link`, validações API
- [x] `PIX_EFI.md`

### Flutter

- [x] Traduções `efi_pix` (en/ar)

## Configuração produção

1. `php artisan migrate`
2. Admin → Efi Pix → credenciais + certificado + chave PIX
3. Webhook EFI: `https://fleti.com.br/payment/efi-pix/webhook`
4. Moeda BRL

## Próximo passo

**FASE 016** — Delivery Multi Stop
