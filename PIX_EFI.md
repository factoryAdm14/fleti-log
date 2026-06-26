# PIX EFI — Fleti Enterprise v4.0

Gateway independente `efi_pix` para pagamentos via PIX com Banco EFI (Efí/Gerencianet), sem substituir Mercado Pago ou outros gateways.

## Ativação (Admin)

1. Admin → **3rd Party** → **Payment Methods** → **Efi Pix**
2. Configure:
   - **Client ID** — credencial da aplicação EFI
   - **Client Secret** — segredo da aplicação
   - **Certificate (.p12)** — certificado mTLS da EFI
   - **Certificate Password** — senha do certificado
   - **Pix Key** — chave PIX cadastrada na conta EFI
3. Modo **Test** (homologação) ou **Live** (produção)
4. Status **Active**
5. Título e imagem do gateway

### Rollback

Desative o gateway no painel (**Inactive**). Nenhum outro gateway é alterado.

## Ambientes API

| Modo Admin | Base URL |
|------------|----------|
| Test | `https://pix-h.api.efipay.com.br` |
| Live | `https://pix.api.efipay.com.br` |

## Endpoints Fleti

| Rota | Método | Descrição |
|------|--------|-----------|
| `/payment/efi-pix/pay?payment_id={uuid}` | GET | QR Code + Copia e Cola |
| `/payment/efi-pix/status?payment_id={uuid}` | GET | Polling JSON |
| `/payment/efi-pix/webhook` | POST | Webhook EFI |

### Webhook na EFI

```
https://fleti.com.br/payment/efi-pix/webhook
```

## Fluxo

1. App seleciona `efi_pix` → WebView
2. OAuth2 + mTLS → cria cobrança `PUT /v2/cob/{txid}`
3. Obtém QR via `GET /v2/loc/{id}/qrcode`
4. Confirmação por webhook + polling (5s)
5. Status `CONCLUIDA` → hook do sistema → `/payment-success`

## Status

| Interno | EFI |
|---------|-----|
| `pending` | ATIVA |
| `paid` | CONCLUIDA |
| `expired` | REMOVIDA_* ou expiração |
| `failed` | erro / cobrança inválida |

## Idempotência

- `txid` derivado do UUID do `payment_requests` (máx. 35 chars)
- Reabrir URL reutiliza cobrança pendente existente

## Auditoria

Tabela `efi_pix_logs`: `create_cob`, `webhook`, `paid`, `webhook_orphan`

## Estorno

Não implementado nesta fase (previsto para evolução futura).

## Deploy

```bash
php artisan migrate
php artisan route:cache
php artisan view:cache
```

## Requisitos

- Moeda **BRL**
- Certificado `.p12` válido para o ambiente (sandbox ou produção)
- Chave PIX ativa na conta EFI
