# PIX Mercado Pago — Fleti Enterprise v4.0

Gateway independente `mercadopago_pix` para pagamentos via PIX no Brasil, sem substituir o gateway `mercadopago` (cartão).

## Ativação (Admin)

1. Admin → **3rd Party** → **Payment Methods**
2. Localize **Mercadopago Pix**
3. Configure:
   - **Access Token** — token de produção ou sandbox do Mercado Pago
   - **Public Key** — chave pública (opcional, reservada para evoluções)
   - **Webhook Secret** — segredo para validar assinatura `x-signature` (recomendado em produção)
4. Modo: **Test** (sandbox) ou **Live** (produção)
5. Status: **Active**
6. Título e imagem do gateway (exibidos no app)

### Rollback

Desative o gateway (**Inactive**) no painel. O gateway `mercadopago` (cartão) permanece inalterado.

## Requisitos

- Moeda do sistema: **BRL** (`business_config` → `currency_code`)
- Conta Mercado Pago com PIX habilitado
- URL de webhook acessível publicamente

## Endpoints

| Rota | Método | Descrição |
|------|--------|-----------|
| `/payment/mercadopago-pix/pay?payment_id={uuid}` | GET | Tela QR Code + Copia e Cola |
| `/payment/mercadopago-pix/status?payment_id={uuid}` | GET | Polling de status (JSON) |
| `/payment/mercadopago-pix/webhook` | POST | Webhook Mercado Pago (IPN) |

### Webhook no Mercado Pago

Configure no painel MP:

```
https://fleti.com.br/payment/mercadopago-pix/webhook
```

Eventos: `payment` / `payment.updated`

## Fluxo

1. App seleciona `mercadopago_pix` → abre WebView
2. Backend cria cobrança PIX via API MP (`payment_method_id: pix`)
3. Usuário paga via QR ou Copia e Cola
4. Confirmação por **webhook** e **polling** (5s)
5. Status `paid` → hook do sistema (`tripRequestUpdate` / `customerWalletUpdate`)
6. Redirect para `/payment-success`

## Status

| Status interno | Origem MP |
|----------------|-----------|
| `pending` | pending, in_process |
| `paid` | approved |
| `failed` | rejected, cancelled |
| `expired` | `date_of_expiration` ultrapassada |

## Idempotência

- `external_reference` = UUID do `payment_requests`
- Header `X-Idempotency-Key: fleti-pix-{payment_id}` na criação
- Reabrir a mesma URL reutiliza cobrança pendente existente

## Auditoria

Tabela `mercadopago_pix_logs`:

- `create_payment` — resposta da API MP
- `webhook` — notificações recebidas
- `paid` — confirmação final
- `webhook_orphan` — webhook sem `payment_request` correspondente

## Arquivos principais

- `Modules/Gateways/Services/MercadoPagoPixService.php`
- `Modules/Gateways/Http/Controllers/MercadoPagoPixController.php`
- `Modules/Gateways/Resources/views/payment/mercadopago-pix.blade.php`
- Migration: `2026_06_26_120000_create_mercadopago_pix_logs_table.php`
- Migration: `2026_06_26_120001_insert_mercadopago_pix_gateway_setting.php`

## Deploy

```bash
php artisan migrate
php artisan route:cache
php artisan view:cache
```

## Teste sandbox

1. Use credenciais de teste do Mercado Pago
2. Ative gateway em modo **Test**
3. Inicie pagamento digital de corrida ou recarga de wallet
4. Verifique QR Code e logs em `mercadopago_pix_logs`
