# Checklist — O que falta implantar (Módulo Financeiro Fleti)

**Base:** `https://fleti.com.br`  
**Última atualização:** 2026-06-27  
**Contexto:** backend `FinanceManagement` já está em produção (migrations + seed). Itens abaixo são pendentes de configuração, integração ou deploy complementar.

---

## Legenda

| Símbolo | Significado |
|---------|-------------|
| ⬜ | Pendente |
| 🔄 | Parcial / precisa validar |
| ✅ | Concluído |
| ➖ | Não previsto (legado mantido de propósito) |

---

## 1. Backend (Laravel / API / servidor)

### 1.1 Infra e autoload

| # | Item | Comando / ação | OK |
|---|------|----------------|-----|
| B1 | `composer dump-autoload` em produção (hook de plano) | `cd ~/domains/fleti.com.br/public_html && /opt/alt/php82/usr/bin/php /usr/local/bin/composer dump-autoload -o` | ✅ |
| B2 | Confirmar hook `DriverSubscriptionPaymentUpdate` no autoload | `grep DriverSubscriptionPaymentUpdate vendor/composer/autoload_files.php` | ✅ |
| B3 | PHP web em **8.2** (evitar segfault do 8.3) | `.htaccess` com `AddHandler application/x-httpd-alt-php82 .php` | 🔄 |
| B4 | PHP CLI sempre via 8.2 | `/opt/alt/php82/usr/bin/php artisan ...` | 🔄 |

### 1.2 Banco e dados

| # | Item | Verificação | OK |
|---|------|-------------|-----|
| B5 | 12 migrations financeiras aplicadas | `php artisan migrate:status` → `2026_06_27_100000` … `140000` | ✅ |
| B6 | `finance_settings` com registro padrão | 1 linha na tabela | ✅ |
| B7 | 4 planos em `driver_plans` | Mensal, Trimestral, Semestral, Anual | ✅ |
| B8 | Tabelas vazias prontas para uso | `driver_wallets`, `payment_splits`, `wallet_transactions`, `finance_audit_logs` | ✅ |

### 1.3 API nova (disponível, falta uso real)

| # | Endpoint | Função | OK |
|---|----------|--------|-----|
| B9 | `GET /api/driver/finance/wallet` | Saldo carteira nova | 🔄 |
| B10 | `GET /api/driver/finance/wallet/transactions` | Histórico | 🔄 |
| B11 | `POST /api/driver/finance/withdraw/request` | Saque nova camada | 🔄 |
| B12 | `GET /api/driver/finance/withdraw/pending` | Saques pendentes | 🔄 |
| B13 | `GET /api/driver/finance/withdraw/settled` | Saques liquidados | 🔄 |
| B14 | `GET /api/driver/finance/plans` | Listar planos | 🔄 |
| B15 | `POST /api/driver/finance/plans/{id}/checkout` | Checkout PIX/cartão | ⬜ |
| B16 | `GET /api/driver/finance/subscription` | Plano ativo | 🔄 |
| B17 | `GET /api/driver/finance/subscription/pending` | Checkout pendente | 🔄 |
| B18 | `GET /api/finance/payment-gateways` | Gateways ativos | 🔄 |
| B19 | `POST /api/finance/webhooks/pix/mercadopago_pix` | Webhook PIX MP | ⬜ |
| B20 | `POST /api/finance/webhooks/pix/efi_pix` | Webhook PIX EFI | ⬜ |

### 1.4 Fluxos automáticos (validar em produção)

| # | Fluxo | Esperado | OK |
|---|-------|----------|-----|
| B21 | Corrida PIX/cartão paga → split | Registro em `payment_splits` + crédito em `driver_wallets` | ⬜ |
| B22 | Motorista com plano ativo → comissão 0% | `platform_fee = 0` no split | ⬜ |
| B23 | Corrida cash → split sem crédito sacável | Split registrado, saldo não creditado | ⬜ |
| B24 | Webhook confirma pagamento de plano | Assinatura `active` em `driver_subscriptions` | ⬜ |
| B25 | Saque aprovado + EFI payout automático | PIX enviado (se habilitado) | ⬜ |
| B26 | Verificação de valor no pagamento | Tolerância em `finance_settings` | ⬜ |
| B27 | Webhook duplicado auditado | Log em `finance_audit_logs`, sem duplo crédito | ⬜ |

### 1.5 Não implantado por desenho (legado mantido)

| # | Item | Status |
|---|------|--------|
| B28 | Ledger `transactions` / `user_accounts` | ➖ Mantido |
| B29 | API `/api/driver/withdraw/*` (legada) | ➖ Mantida |
| B30 | PIX payout automático **Mercado Pago** | ➖ Não implementado (só EFI) |

### 1.6 Outras alterações do repo (fora do deploy financeiro)

Estas mudanças locais **não** foram enviadas no pacote de 114 arquivos do financeiro:

| # | Módulo | Pendente de deploy? |
|---|--------|---------------------|
| B31 | `AdminModule` (chatting, dashboard) | ⬜ Se ainda não deployado em release anterior |
| B32 | `BusinessManagement` (chatting cliente) | ⬜ |
| B33 | `ChattingManagement` | ⬜ |
| B34 | `ZoneManagement` | ⬜ |
| B35 | `TransactionManagement` (relatórios) | ⬜ |
| B36 | `UserManagement` (services) | ⬜ |

---

## 2. Admin (painel web)

### 2.1 Acesso e permissões

| # | Item | Onde | OK |
|---|------|------|-----|
| A1 | Login admin funcionando | `/admin/auth/login` | ✅ |
| A2 | Menu **Financeiro** visível no sidebar | Após login | 🔄 |
| A3 | Módulo `finance_management` nos funcionários | `view`, `update`, `log` | 🔄 |
| A4 | Super-admin consegue acessar tudo | Teste com conta admin | 🔄 |

### 2.2 Telas financeiras

| # | URL | Função | OK |
|---|-----|--------|-----|
| A5 | `/admin/finance/dashboard` | Métricas e filtros de período | 🔄 |
| A6 | `/admin/finance/settings` | Modo, comissão, gateways, segurança | 🔄 |
| A7 | `/admin/finance/withdraws` | Aprovar / recusar / liquidar saques | ⬜ |
| A8 | `/admin/finance/plans` | CRUD planos motorista | 🔄 |
| A9 | `/admin/finance/subscriptions` | Assinaturas + ativação manual | 🔄 |
| A10 | `/admin/finance/audit` | Logs de auditoria | ⬜ |

### 2.3 Configuração obrigatória pós-deploy

| # | Configuração | Valor sugerido / nota | OK |
|---|--------------|----------------------|-----|
| A11 | Modo ativo | `hybrid` / `commission` / `subscription` | ✅ hybrid |
| A12 | % comissão padrão | Ex.: 15% | ✅ 15% |
| A13 | Valor mínimo de saque | Ex.: R$ 50 | ✅ R$ 50 |
| A14 | PIX habilitado | Sim | ✅ |
| A15 | Cartão habilitado | Conforme gateway | ✅ |
| A16 | Gateway principal | Mercado Pago ou EFI | ✅ mercadopago |
| A17 | Limites de segurança de saque | Valor máx., qtd/dia | ✅ |
| A18 | Assinatura de webhook obrigatória | `webhook_secret` nos gateways | 🔄 desligado p/ rollout |
| A19 | Tolerância de valor de pagamento | Ex.: 1% | ✅ 1% |
| A20 | PIX automático para saque (EFI) | Certificado `.p12` + toggle | ⬜ |

### 2.4 Gateways e webhooks (painéis externos)

| # | Gateway | URL webhook | OK |
|---|---------|-------------|-----|
| A21 | Mercado Pago PIX | `https://fleti.com.br/api/finance/webhooks/pix/mercadopago_pix` | 🔄 rota OK (422); gateway **inativo** no admin |
| A22 | EFI PIX | `https://fleti.com.br/api/finance/webhooks/pix/efi_pix` | 🔄 rota OK (422); gateway **inativo** no admin |
| A23 | Mercado Pago cartão (legado) | Webhook existente no painel MP | 🔄 |

---

## 3. Apps (motorista e cliente)

### 3.1 App motorista nativo (`fleti-Driver-app-release-3.2`)

| # | Item | API atual | API nova | OK |
|---|------|-----------|----------|-----|
| C1 | Tela de carteira | Legada (`user_accounts`) | `GET /api/driver/finance/wallet` | ⬜ |
| C2 | Histórico de transações | Legada | `GET /api/driver/finance/wallet/transactions` | ⬜ |
| C3 | Solicitar saque | `/api/driver/withdraw/request` | `/api/driver/finance/withdraw/request` | ⬜ |
| C4 | Saques pendentes/liquidados | `/api/driver/withdraw/pending-request` | `/api/driver/finance/withdraw/pending` | ⬜ |
| C5 | Listar planos | — | `GET /api/driver/finance/plans` | ⬜ |
| C6 | Comprar plano (PIX/cartão) | — | `POST /api/driver/finance/plans/{id}/checkout` | ⬜ |
| C7 | Ver assinatura ativa | — | `GET /api/driver/finance/subscription` | ⬜ |
| C8 | Build e publicação (APK/AAB) | — | Nova versão nas lojas | ⬜ |

**Arquivos principais a alterar:**
- `lib/util/app_constants.dart`
- Features de wallet / withdraw / subscription

### 3.2 App motorista web (`apps/driver_web_flutter` → `fleti.com.br/driver/`)

| # | Item | Situação | OK |
|---|------|----------|-----|
| C9 | `shared_flutter/lib/services/driver_wallet_service.dart` | Usa API legada | ⬜ |
| C10 | `wallet_screen.dart` | Saldo legado | ⬜ |
| C11 | `withdraw_dialog.dart` | Saque legado | ⬜ |
| C12 | Tela de planos / assinatura | Não existe | ⬜ |
| C13 | Build web e deploy em `/driver/` | Não atualizado pós-financeiro | ⬜ |

### 3.3 App cliente (`fleti-User-app-release-3.2` + `client_web_flutter`)

| # | Item | OK |
|---|------|-----|
| C14 | Módulo financeiro para cliente | ➖ Não previsto no escopo |
| C15 | Wallet cliente (recarga) | ➖ Continua legado |

### 3.4 Resumo apps

```
Hoje em produção:
  Motorista → API legada /api/driver/withdraw/*
  Cliente   → sem mudança financeira

Meta:
  Motorista → API nova /api/driver/finance/*
  Cliente   → sem alteração (por enquanto)
```

---

## 4. Validação final (smoke test)

Após concluir backend + admin + apps, rodar:

- Checklist detalhado: `docs/finance_smoke_test_checklist.md`
- Testes automatizados (local/CI): `./vendor/bin/phpunit --testsuite Finance`

| # | Critério de aceite | OK |
|---|-------------------|-----|
| V1 | Admin login 200 | ✅ |
| V2 | API config 200 | ✅ |
| V3 | Corrida PIX → split + carteira motorista | ⬜ |
| V4 | Saque motorista → admin aprova → liquida | ⬜ |
| V5 | Compra de plano → webhook → assinatura ativa | ⬜ |
| V6 | Dashboard financeiro com métricas reais | ⬜ |
| V7 | Auditoria registra ações críticas | ⬜ |
| V8 | App motorista usa nova API (ou convivência documentada) | ⬜ |

---

## 5. Ordem recomendada de execução

```
1. Backend B1–B2 (composer dump-autoload + hook)
2. Admin A3, A6, A11–A22 (config + permissões + webhooks)
3. Backend B21–B27 (validar fluxos com smoke test)
4. Apps C1–C13 (integrar API nova + rebuild)
5. Validação V3–V8
```

---

## 6. Comandos rápidos (produção)

```bash
cd ~/domains/fleti.com.br/public_html
PHP=/opt/alt/php82/usr/bin/php

# Autoload (pendente crítico)
$PHP /usr/local/bin/composer dump-autoload -o

# Verificar hook
grep DriverSubscriptionPaymentUpdate vendor/composer/autoload_files.php

# Status migrations financeiras
$PHP artisan migrate:status | grep 2026_06_27

# Contagens
$PHP artisan tinker --execute="echo 'settings='.\Modules\FinanceManagement\Entities\FinanceSetting::count().' plans='.\Modules\FinanceManagement\Entities\DriverPlan::count();"
```

---

## Referências

- Deploy: `docs/finance_production_recovery.md`
- Análise completa: `docs/finance_module_analysis.md` (seções 1–22)
- Smoke test: `docs/finance_smoke_test_checklist.md`
- Script deploy: `scripts/deploy_finance_remote.py`
