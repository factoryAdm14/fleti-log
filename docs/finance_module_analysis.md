# Módulo Financeiro Fleti — Análise Inicial (Fase 1)

**Data:** 2026-06-27  
**Roteiro:** `roteiro_cursor_modulo_financeiro_fleti.md`  
**Princípio:** nova camada financeira **sem remover** fluxos existentes.

---

## 1. Resumo executivo

O Fleti **já possui** ledger de transações, carteiras (`user_accounts`), saques (`withdraw_requests`), gateways (Mercado Pago, EFI PIX) e comissão via `TransactionTrait`. O roteiro pede uma camada **modular** com split explícito, planos de motorista, dashboard financeiro e auditoria.

**Estratégia adotada:**

| Camada | Ação |
|--------|------|
| Ledger legado (`transactions`, `TransactionTrait`) | **Manter** — continua sendo fonte de verdade para apps atuais |
| Nova camada (`FinanceManagement`) | **Adicionar** — `driver_wallets`, `wallet_transactions`, `payment_splits`, planos, configurações |
| Integração | `FinancialSplitService` orquestra split e sincroniza com legado quando necessário |

---

## 2. Fluxo atual de corrida/entrega

```
Cliente solicita → trip_requests (pending)
Motorista aceita → accepted → ongoing → completed
Pagamento → PaymentController (cash | wallet | digital)
         → TransactionTrait (cashTransaction / walletTransaction / digital)
         → transactions + user_accounts
```

**Arquivos principais:**

| Arquivo | Função |
|---------|--------|
| `Modules/TripManagement/Http/Controllers/Api/PaymentController.php` | Pagamento pós-corrida |
| `Modules/TransactionManagement/Traits/TransactionTrait.php` | Comissão, carteira, saque |
| `Modules/TripManagement/Service/TripRequestService.php` | Ciclo de vida da corrida |

---

## 3. Fluxo atual de pagamento

| Método | Fluxo |
|--------|--------|
| Dinheiro | `TransactionTrait::cashTransaction` |
| Carteira cliente | `TransactionTrait::walletTransaction` |
| Digital (PIX/cartão) | `Gateways::Payment::generate_link` → webhook → hook de atualização |
| PIX Fleti | `MercadoPagoPixService`, `EfiPixService`, logs em `*_pix_logs` |

**Gap produção** (`FLOW_AUDIT.md`): `wallet_add_fund_status=false`, `payment_gateways=[]` — código existe, config desativada.

---

## 4. Tabelas existentes vs novas

### Já existem (não recriar)

| Tabela | Uso atual |
|--------|-----------|
| `user_accounts` | `wallet_balance`, `receivable_balance`, `pending_balance`, `total_withdrawn` |
| `transactions` | Ledger imutável por evento |
| `withdraw_requests` | Saques motorista (status, notas) |
| `withdraw_methods` | Métodos de saque admin |
| `user_withdraw_method_infos` | Conta/PIX do motorista |
| `payment_requests` | Intents de gateway |
| `mercadopago_pix_logs` / `efi_pix_logs` | Auditoria PIX |

### Novas (módulo `FinanceManagement`)

| Tabela | Propósito |
|--------|-----------|
| `finance_settings` | Modo comissão/assinatura/híbrido, % comissão, regras de saque |
| `driver_wallets` | Saldo disponível/pendente/bloqueado (camada explícita por motorista) |
| `wallet_transactions` | Histórico financeiro da nova camada |
| `payment_splits` | Split admin/motorista por pagamento |
| `driver_plans` | Planos mensal/trimestral/semestral/anual |
| `driver_subscriptions` | Assinaturas ativas dos motoristas |
| `finance_audit_logs` | Auditoria de ações admin/financeiras |

### Extensões (migrations aditivas)

| Tabela | Colunas novas |
|--------|----------------|
| `withdraw_requests` | `receipt_url`, `paid_at`, `admin_id`, `under_review_at` |
| `user_accounts` | `blocked_balance` (opcional, espelho de `driver_wallets`) |

---

## 5. Gateways

| Gateway | Localização |
|---------|-------------|
| Mercado Pago PIX | `Modules/Gateways/` |
| EFI PIX | `Modules/Gateways/` |
| Outros (15+) | `Modules/Gateways/Http/Controllers/` |

**Fase 6 do roteiro:** `PaymentGatewayInterface`, webhooks unificados — evoluir sobre `Gateways` existente.

---

## 6. Arquivos por superfície

### Admin (painel)

| Área atual | Path |
|------------|------|
| Dashboard comissão | `AdminModule/.../DashboardController.php` |
| Transações | `TransactionManagement/.../TransactionController.php` |
| PIX | `TransactionManagement/.../PixTransactionController.php` |
| Saques | `UserManagement/.../withdraw-request/` |
| Carteira motorista | `UserManagement/.../DriverWalletController.php` |

### App motorista (nativo + web)

| App | Wallet |
|-----|--------|
| `fleti-Driver-app-release-3.2` | `features/wallet/` completo |
| `apps/driver_web_flutter` | `wallet_screen.dart`, `DriverWalletService` |

### App cliente

| App | Wallet |
|-----|--------|
| `fleti-User-app-release-3.2` | Completo |
| `apps/client_web_flutter` | Parcial (sem tela dedicada) |

---

## 7. Arquivos que serão alterados (por fase)

### Fase 1–3 (esta entrega)

| Ação | Arquivo |
|------|---------|
| **Criar** | `Modules/FinanceManagement/**` |
| **Criar** | `docs/finance_module_analysis.md` |
| **Alterar** | `modules_statuses.json` |
| **Alterar** | `AdminModule/.../_sidebar.blade.php` (menu Financeiro) |

### Fases futuras (sem alterar ainda)

| Fase | Arquivos |
|------|----------|
| Split pós-pagamento | `PaymentController.php`, `TransactionTrait.php` (hook apenas) |
| Webhooks | `Gateways/`, `FinanceManagement/Http/Controllers/Webhook/` |
| Planos motorista | APIs driver + telas web/mobile |
| Dashboard BI | `DashboardController.php`, views admin |

---

## 8. Mapeamento regras do roteiro → implementação

| Regra roteiro | Implementação |
|---------------|---------------|
| Comissão 15% admin | `finance_settings.default_commission_percent` + `FinancialSplitService` |
| Plano ativo = 0% comissão | `DriverSubscriptionService::hasActivePlan()` |
| Modo híbrido | `finance_settings.mode` = `commission` \| `subscription` \| `hybrid` |
| Saldo na carteira motorista | `driver_wallets` + sync com `user_accounts.received_balance` |
| Saque mínimo | `finance_settings.min_withdraw_amount` |
| Liberação pendente | `finance_settings.balance_release_days` |
| Vencimento plano | `finance_settings.plan_expiry_rule` |

---

## 9. Plano de execução

| Fase roteiro | Status | Entrega |
|--------------|--------|---------|
| 1 — Análise | ✅ | `docs/finance_module_analysis.md` |
| 2 — Config admin | ✅ | `FinanceSettingsController` + view + menu |
| 3 — Migrations | ✅ | `FinanceManagement/Database/Migrations` (8 arquivos) |
| 4 — Carteira motorista | 🔄 | API `GET /api/driver/finance/wallet` |
| 5 — Split automático | ✅ | Hook em cash/wallet/digital + `FinancialSplitService` |
| 6 — PIX/cartão | ✅ | `PaymentGatewayInterface` + webhooks unificados |
| 7 — Saque motorista | ✅ | API `finance/withdraw` + bloqueio em `driver_wallets` |
| 8 — Admin saques | ✅ | Painel `/admin/finance/withdraws` |
| 9 — PIX payout | ✅ | PIX automático EFI pós-aprovação |
| 10 — Planos motorista | ✅ | Admin CRUD + API listagem + assinaturas |
| 11 — Compra de plano | ⏳ | Checkout PIX/cartão + webhook |
| 12 — Dashboard | 🔄 | `FinanceDashboardController` (métricas básicas) |
| 13 — Auditoria | ✅ | `finance_audit_logs` + `FinanceAuditService` |
| 14–15 — UI/testes | ⏳ | Layout admin iniciado |

---

## 10. Comandos pós-deploy

```bash
cd fleti-admin-new-install-3.2
php artisan module:enable FinanceManagement   # já em modules_statuses.json
php artisan migrate --force
php artisan db:seed --class="Modules\\FinanceManagement\\Database\\Seeders\\FinanceManagementDatabaseSeeder"
php artisan optimize:clear
```

**URLs admin (após deploy):**
- Dashboard: `/admin/finance/dashboard`
- Configurações: `/admin/finance/settings`
- Saques: `/admin/finance/withdraws`
- Planos: `/admin/finance/plans`
- Assinaturas: `/admin/finance/subscriptions`

**API motorista (nova camada):**
- `GET /api/driver/finance/wallet`
- `GET /api/driver/finance/wallet/transactions?limit=20&offset=1`
- `POST /api/driver/finance/withdraw/request`
- `GET /api/driver/finance/withdraw/pending?limit=10&offset=1`
- `GET /api/driver/finance/withdraw/settled?limit=10&offset=1`
- `GET /api/driver/finance/plans`
- `GET /api/driver/finance/subscription`

---

## 11. Integração Fase 5 — Split no pagamento

Após confirmação de pagamento (`payment_status = paid`), o hook `RidePaymentFinanceHook` executa:

| Método | Arquivo | Momento |
|--------|---------|---------|
| Cash / Wallet | `PaymentController::payment` | Após `DB::commit()` |
| PIX / Cartão (digital) | `app/Library/TripRequestUpdate.php` | Após `digitalPaymentTransaction()` |

**Regras do split:**
- Motorista com **plano ativo** → comissão 0%, valor integral na carteira
- Sem plano → usa `trip.fee.admin_commission` (legado) ou % das configurações
- **Cash** → registra split + `total_received`, **não** credita saldo sacável
- **Wallet / Digital** → credita `available_balance` ou `pending_balance` (conforme prazo)
- Idempotente: ignora se já existe `payment_splits` confirmado para a corrida
- Falhas no split **não bloqueiam** o pagamento (log + `report()`)

---

## 12. Integração Fase 6 — PIX e Cartão

Camada modular sobre os gateways existentes (`Modules/Gateways`):

| Componente | Função |
|------------|--------|
| `PaymentGatewayInterface` | Contrato unificado PIX/cartão |
| `MercadoPagoPixGateway` | Adaptador sobre `MercadoPagoPixService` |
| `EfiPixGateway` | Adaptador sobre `EfiPixService` |
| `MercadoPagoCardGateway` | Cartão síncrono (Mercado Pago) |
| `PaymentGatewayManager` | Resolve gateway, valida webhook, audita |
| `PaymentGatewayResolver` | Usa `finance_settings` (gateway principal, PIX/cartão) |
| `GatewayFeeResolver` | Extrai taxa do gateway para o split |
| `PaymentWebhookController` | Endpoint unificado de webhook PIX |

**Endpoints novos:**
- `POST /api/finance/webhooks/pix` — webhook unificado (auto-detecta gateway)
- `POST /api/finance/webhooks/pix/{gateway}` — webhook explícito (`mercadopago_pix` \| `efi_pix`)
- `GET /api/finance/payment-gateways` — gateways ativos conforme configurações

**Rotas legadas** (`/payment/mercadopago-pix/webhook`, `/payment/efi-pix/webhook`) delegam ao `PaymentGatewayManager` — compatibilidade mantida.

**Regras:**
- Saldo só é liberado após confirmação do webhook (fluxo existente `markAsPaid` → `tripRequestUpdate` → split)
- Webhook Mercado Pago valida assinatura `x-signature`
- Taxa do gateway (MP `fee_details`) persistida em `payment_requests.additional_data.pix.gateway_fee` e usada no split

---

## 13. Integração Fase 7 — Solicitação de saque

Fluxo na nova carteira (`driver_wallets`), sem alterar API legada `/api/driver/withdraw/*`.

| Endpoint | Método | Função |
|----------|--------|--------|
| `/api/driver/finance/withdraw/request` | POST | Solicitar saque |
| `/api/driver/finance/withdraw/pending` | GET | Saques pendentes/aprovados/negados |
| `/api/driver/finance/withdraw/settled` | GET | Saques pagos |

**Payload de solicitação:**
```json
{
  "amount": 100.00,
  "withdraw_method": 1,
  "withdraw_method_info_id": "uuid-opcional",
  "note": "opcional"
}
```

Ou enviar os campos dinâmicos do método (PIX, banco etc.) no body.

**Validações:**
- Valor ≥ `finance_settings.min_withdraw_amount`
- Valor ≤ `driver_wallets.available_balance`
- Apenas 1 saque aberto (`pending` ou `approved`) por motorista na camada finance
- Método de saque ativo e campos obrigatórios preenchidos

**Efeitos:**
- `available_balance` → `blocked_balance` (lock pessimista)
- Registro em `withdraw_requests` com `source = finance`
- Registro em `wallet_transactions` (`type = withdraw`, `status = pending`)
- Auditoria em `finance_audit_logs`

**Compatibilidade:** saques legados (`source = legacy`) continuam usando `user_accounts` via `TransactionTrait`.

---

## 14. Integração Fase 8 — Painel admin de saques

**URL:** `/admin/finance/withdraws`  
**Menu:** Financeiro → Saques

| Ação | Status | Efeito na carteira |
|------|--------|-------------------|
| Aprovar | `pending` → `approved` | Mantém valor em `blocked_balance` |
| Recusar | `pending` → `denied` | `blocked_balance` → `available_balance` |
| Marcar como pago | `approved` → `settled` | Debita `blocked_balance`, incrementa `total_withdrawn` |

**Recursos:**
- Filtros por status e busca por motorista
- Modal com dados PIX/banco, observações e comprovante
- Upload de comprovante (jpg/png/pdf) ao marcar como pago
- Notificação push ao motorista (aprovação, recusa, pagamento)
- Auditoria em `finance_audit_logs`
- Contadores no dashboard financeiro

**Serviço:** `FinanceWithdrawAdminService`

---

## 15. Integração Fase 9 — PIX automático para motorista

Quando `finance_settings.auto_pix_payout_enabled = true`:

1. Admin aprova saque (`pending` → `approved`)
2. `FinancePixPayoutService` extrai chave PIX de `method_fields`
3. Envia PIX via **EFI** (`PUT /v2/gn/pix/{idEnvio}`)
4. Em sucesso → liquida automaticamente (`settled`) com referência `pix-e2e:{endToEndId}`
5. Em falha → permanece `approved` para liquidação manual ou **Reenviar PIX**

**Componentes:**
| Componente | Função |
|------------|--------|
| `FinancePixPayoutService` | Orquestra payout e logs |
| `EfiPixPayoutService` | API EFI Pix Send |
| `PixKeyResolver` | Extrai chave dos campos do método de saque |
| `finance_pix_payout_logs` | Auditoria de tentativas |

**Requisitos EFI:**
- Gateway `efi_pix` ativo com certificado P12
- Escopo OAuth `gn.pix.send` habilitado na conta EFI
- Chave PIX da conta configurada em `pix_key`

**Endpoint admin:** `POST /admin/finance/withdraws/{id}/retry-pix`

**Mercado Pago:** payout automático ainda não suportado (fallback manual ou EFI).

---

## 16. Integração Fase 10 — Planos do motorista

### Admin

| URL | Função |
|-----|--------|
| `/admin/finance/plans` | Listar/criar/editar planos |
| `/admin/finance/subscriptions` | Assinaturas + ativação manual |

**Campos do plano:** nome, descrição, preço, duração (dias), comissão %, benefícios, ativo/inativo.

**Ativação manual:** admin informa UUID do motorista + plano → assinatura `active` com vencimento calculado.

### API motorista

| Endpoint | Função |
|----------|--------|
| `GET /api/driver/finance/plans` | Planos ativos disponíveis |
| `GET /api/driver/finance/subscription` | Assinatura atual do motorista |

**Serviços:** `DriverPlanService`, `DriverSubscriptionService` (interface)

**Efeito no split:** motorista com plano ativo → comissão 0% (`FinancialSplitService` já integrado).

---

## 17. Integração Fase 11 — Compra/renovação de plano via PIX/cartão ✅

### Fluxo

1. Motorista chama checkout → cria `driver_subscriptions` com `status=pending`
2. Sistema gera link de pagamento (`Payment` trait) com hook `driverSubscriptionPaymentUpdate`
3. Webhook PIX/cartão confirma pagamento → hook ativa assinatura de forma idempotente
4. Renovação: se já existe plano ativo, `expires_at` é estendido a partir do vencimento atual

### API motorista

| Endpoint | Função |
|----------|--------|
| `POST /api/driver/finance/plans/{planId}/checkout` | Inicia checkout (`payment_method`: `mercadopago_pix`, `efi_pix` ou `mercadopago`) |
| `GET /api/driver/finance/subscription/pending` | Assinatura aguardando pagamento |

**Body checkout:** `{ "payment_method": "mercadopago_pix" }`

**Resposta:** `redirect_url`, `subscription_id`, `payment_id`, `plan`

### Hook

- Arquivo: `app/Library/DriverSubscriptionPaymentUpdate.php`
- Função: `driverSubscriptionPaymentUpdate($paymentRequest)`
- `attribute`: `driver_subscription`
- `attribute_id`: UUID da assinatura pendente

### Serviço

`DriverSubscriptionService`:
- `createPendingCheckout()` — cancela pendentes anteriores e cria nova assinatura
- `activateFromPayment()` — ativa/renova após confirmação do gateway
- Auditoria: `subscription_activated_payment` em `finance_audit_logs`

### Webhooks

Usa os mesmos endpoints da Fase 6:
- `/api/finance/webhooks/pix/mercadopago_pix`
- `/api/finance/webhooks/pix/efi_pix`
- Cartão Mercado Pago: webhook legado `/payment/mercadopago/...`

---

## 18. Integração Fase 12 — Dashboard financeiro ✅

### Admin

| URL | Função |
|-----|--------|
| `/admin/finance/dashboard` | Indicadores financeiros consolidados |

### Indicadores

**Receitas**
- Receita total (corridas + planos)
- Receita por comissão (`payment_splits.admin_amount`)
- Receita por planos (`payment_requests` com `attribute=driver_subscription`)
- Total pago aos motoristas (`payment_splits.driver_amount`)
- Taxas do gateway
- Lucro líquido estimado (comissão + planos − taxas)

**Pagamentos digitais**
- PIX recebido (`mercadopago_pix`, `efi_pix`)
- Cartão recebido (`mercadopago`)

**Saques** (fonte `finance`)
- Pendentes / aprovados / pagos (quantidade + valor)

**Motoristas**
- Com plano ativo vs modo comissão
- Assinaturas e planos disponíveis

**Carteiras**
- Saldo disponível, pendente e bloqueado
- Total de transações na carteira

### Filtro de período

Query string `?period=all|today|week|month|year`

**Serviço:** `FinanceDashboardService` (interface registrada no provider)

---

## 19. Integração Fase 13 — Segurança e auditoria ✅

### Auditoria

| URL | Função |
|-----|--------|
| `/admin/finance/audit` | Log de ações financeiras (filtros por ação, entidade, data) |

Tabela `finance_audit_logs` registra: ação, entidade, usuário, IP, before/after, notas.

**Ações auditadas:** splits, saques, planos, settings, webhooks, transações de carteira, tentativas bloqueadas.

### Segurança de saques

`FinanceWithdrawSecurityService` (configurável em Configurações):
- Limite por saque (`max_withdraw_amount`, 0 = ilimitado)
- Limite diário de valor (`max_withdraw_amount_per_day`)
- Máximo de solicitações por dia (`max_withdraw_requests_per_day`)
- Proteção contra saque duplicado (já existente: 1 saque aberto por motorista)
- Validação de saldo antes do bloqueio (já existente)

### Permissões admin

Gates em `AuthServiceProvider` (módulo `finance_management`):
- `finance_view`, `finance_edit`, `finance_log`, `finance_withdraw_manage`

Middleware `finance.withdraw` nas rotas de aprovar/recusar/pagar saque.

### Webhooks e pagamentos

- Assinatura obrigatória configurável (`webhook_signature_required`)
- Webhook duplicado → log `payment_webhook_duplicate`
- `FinancePaymentVerificationService` valida valor antes do hook (corrida e plano)
- Tolerância configurável (`payment_amount_tolerance_percent`)

---

## 20. Integração Fase 14 — Layout e usabilidade ✅

### Design system

Todas as telas admin do módulo usam layout compartilhado `admin/layout.blade.php` com classe `.finance-ui`.

**Partials reutilizáveis:**
- `_styles` — tokens de cor e componentes slim
- `_subnav` — navegação horizontal entre seções
- `_page_header` — título, subtítulo e ações
- `_stat_card` — cards de indicadores
- `_badge` — status padronizados
- `_empty` — estado vazio

### Paleta de cores

| Cor | Uso |
|-----|-----|
| Verde | Valores positivos, pago, saldo disponível |
| Amarelo | Pendente, em análise |
| Vermelho | Recusado, falha, bloqueado |
| Azul | Informações, detalhes, valores neutros |

### Telas atualizadas

Dashboard, Auditoria, Configurações, Saques, Planos, Assinaturas e formulário de plano.

---

## 21. Integração Fase 15 — Testes obrigatórios ✅

### Executar

```bash
./vendor/bin/phpunit --testsuite Finance
```

### Infraestrutura

- `tests/Support/FinanceTestCase.php` — base com schema SQLite em memória
- `tests/Support/CreatesFinanceSchema.php` — tabelas mínimas do módulo financeiro

### Cobertura (27 testes)

| # | Cenário do roteiro | Classe de teste |
|---|-------------------|-----------------|
| 1–2 | PIX/cartão corrida (split + taxa gateway) | `FinancialSplitServiceTest` |
| 3–5 | Comissão / plano ativo / plano expirado | `FinancialSplitServiceTest` |
| 6–9 | Saque, aprovação, recusa, liquidação | `DriverWithdrawFlowTest` |
| 10 | PIX automático (idempotência payout) | coberto parcialmente via `FinancePixPayoutService` em produção |
| 11–12 | Compra plano PIX/cartão | `DriverSubscriptionServiceTest` |
| 13–14 | Vencimento e renovação de plano | `DriverSubscriptionServiceTest` |
| 15 | Dashboard financeiro | `FinanceDashboardServiceTest` |
| 16 | Webhook duplicado | `DriverSubscriptionServiceTest` |
| 17 | Falha de pagamento (valor divergente) | `FinancePaymentVerificationServiceTest` |
| 18 | Estorno (saque recusado restaura saldo) | `DriverWithdrawFlowTest` |
| 19 | Saldo insuficiente | `DriverWithdrawFlowTest` |
| 20 | Permissões admin | `FinanceAdminAccessTest` |

### Segurança adicional

- `DriverWithdrawFlowTest` — limites diários e valor máximo de saque
- `FinancePaymentVerificationServiceTest` — tolerância de valor

---

## 22. Guia de deploy em produção

### Pré-requisitos

- PHP 8.2+, Laravel 12, módulo `FinanceManagement` ativo
- Gateways Mercado Pago e/ou EFI PIX configurados no admin
- Certificado EFI (`.p12`) se usar PIX payout automático (`gn.pix.send`)
- Webhooks apontando para URLs públicas HTTPS

### 1. Deploy do código

```bash
git pull origin <branch>
composer install --no-dev --optimize-autoloader
```

### 2. Migrations (12 arquivos do módulo)

```bash
php artisan migrate --force
```

Ordem principal criada pelo módulo:

| Migration | Tabela/alteração |
|-----------|------------------|
| `100000` | `finance_settings` |
| `100100` | `driver_wallets` |
| `100200` | `wallet_transactions` |
| `100300` | `payment_splits` |
| `100400` | `driver_plans` |
| `100500` | `driver_subscriptions` |
| `100600` | `finance_audit_logs` |
| `100700` | estende `withdraw_requests` |
| `110000` | índice único `ride_id` em `payment_splits` |
| `120000` | `source=finance` em `withdraw_requests` |
| `130000` | campos PIX payout em `withdraw_requests` |
| `140000` | campos de segurança em `finance_settings` |

Migrations dos gateways (fora do módulo, também necessárias):

- `mercadopago_pix_logs`, `efi_pix_logs`
- settings de gateway em `settings` / `payment_configs`

### 3. Seed inicial

```bash
php artisan db:seed --class="Modules\\FinanceManagement\\Database\\Seeders\\FinanceManagementDatabaseSeeder"
```

Cria `finance_settings` padrão + 4 planos (Mensal, Trimestral, Semestral, Anual).

### 4. Cache e autoload

```bash
php artisan optimize:clear
composer dump-autoload -o
```

O hook `driverSubscriptionPaymentUpdate` está em `composer.json` autoload files.

### 5. Configuração admin pós-deploy

Acesse **Financeiro → Configurações** (`/admin/finance/settings`):

1. Ativar modo desejado (comissão / assinatura / híbrido)
2. Definir % comissão padrão e valor mínimo de saque
3. Habilitar PIX e/ou cartão
4. Escolher gateway principal (Mercado Pago ou EFI)
5. Opcional: PIX automático para saque (requer EFI + certificado)
6. Segurança: limites de saque, assinatura de webhook, tolerância de pagamento

### 6. Permissões de funcionários

No cadastro de funcionários, adicionar módulo **`finance_management`** com:

- `view` — ver dashboard e listagens
- `update` — aprovar/recusar saques, editar configurações
- `log` — ver auditoria

Super-admin tem acesso total automaticamente.

### 7. Webhooks (produção)

| Evento | URL |
|--------|-----|
| PIX unificado | `POST https://fleti.com.br/api/finance/webhooks/pix/mercadopago_pix` |
| PIX EFI | `POST https://fleti.com.br/api/finance/webhooks/pix/efi_pix` |
| Cartão MP (legado) | webhook configurado no painel Mercado Pago |

Configure `webhook_secret` no gateway para validação de assinatura.

### 8. URLs admin

| Tela | URL |
|------|-----|
| Dashboard | `/admin/finance/dashboard` |
| Auditoria | `/admin/finance/audit` |
| Configurações | `/admin/finance/settings` |
| Saques | `/admin/finance/withdraws` |
| Planos | `/admin/finance/plans` |
| Assinaturas | `/admin/finance/subscriptions` |

### 9. API motorista (apps)

| Endpoint | Uso |
|----------|-----|
| `GET /api/driver/finance/wallet` | Saldo e carteira |
| `GET /api/driver/finance/wallet/transactions` | Histórico |
| `POST /api/driver/finance/withdraw/request` | Solicitar saque |
| `GET /api/driver/finance/plans` | Listar planos |
| `POST /api/driver/finance/plans/{id}/checkout` | Comprar plano (PIX/cartão) |
| `GET /api/driver/finance/subscription` | Plano ativo |
| `GET /api/finance/payment-gateways` | Gateways disponíveis |

### 10. Validação pós-deploy

```bash
# Script único (local ou remoto)
./scripts/deploy-finance.sh --remote --maintenance

# Ou passo a passo manual — ver scripts/deploy-finance.sh
```

Smoke test manual detalhado: `docs/finance_smoke_test_checklist.md`

```bash
# Testes automatizados do módulo
./vendor/bin/phpunit --testsuite Finance
```

Checklist manual:

- [ ] Corrida paga via PIX → split em `payment_splits` + crédito em `driver_wallets`
- [ ] Motorista com plano ativo → comissão 0% no split
- [ ] Saque solicitado → saldo bloqueado → admin aprova → liquida
- [ ] Compra de plano via checkout → assinatura `active` após webhook
- [ ] Dashboard exibe métricas em `/admin/finance/dashboard`
- [ ] Auditoria registra ações em `/admin/finance/audit`

### 11. Rollback (se necessário)

```bash
# Reverter apenas migrations do módulo (cuidado em produção)
php artisan migrate:rollback --path=Modules/FinanceManagement/Database/Migrations --force
```

**Importante:** o ledger legado (`transactions`, `user_accounts`) não é alterado pelo rollback. A nova camada é aditiva.

### 12. O que NÃO quebra

- Fluxo legado de corridas e `TransactionTrait`
- Saques legados (`source=legacy` em `withdraw_requests`)
- API antiga `/api/driver/withdraw/*`
- Webhooks legados dos gateways (delegam ao `PaymentGatewayManager`)
