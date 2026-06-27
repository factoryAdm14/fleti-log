# Smoke test — Módulo Financeiro Fleti

Checklist manual para validar o módulo `FinanceManagement` após deploy em produção ou staging.

**Base URL:** `https://fleti.com.br`  
**Admin:** `/admin/finance/*`  
**API motorista:** `/api/driver/finance/*` (Bearer token)

---

## 0. Pré-requisitos

- [ ] Deploy executado: `./scripts/deploy-finance.sh --remote --maintenance`
- [ ] Migrations do módulo aplicadas (`php artisan migrate:status` sem pendências em `FinanceManagement`)
- [ ] Seeder rodou (4 planos padrão em `/admin/finance/plans`)
- [ ] Gateways PIX/cartão configurados no admin
- [ ] Webhooks apontando para:
  - `POST /api/finance/webhooks/pix/mercadopago_pix`
  - `POST /api/finance/webhooks/pix/efi_pix`
- [ ] Motorista de teste com saldo ou corrida disponível
- [ ] Funcionário com permissão `finance_management` (view, update, log)

---

## 1. Admin — Configurações

**URL:** `/admin/finance/settings`

| # | Teste | Esperado | OK |
|---|-------|----------|-----|
| 1.1 | Abrir tela de configurações | Página carrega sem erro 500 | [ ] |
| 1.2 | Alterar modo (comissão / assinatura / híbrido) e salvar | Mensagem de sucesso; valor persiste após reload | [ ] |
| 1.3 | Definir % comissão padrão | Valor salvo corretamente | [ ] |
| 1.4 | Definir valor mínimo de saque | Valor salvo corretamente | [ ] |
| 1.5 | Habilitar limites de segurança (valor máx., qtd/dia) | Campos salvos; refletidos na API de saque | [ ] |
| 1.6 | Habilitar verificação de assinatura de webhook | Gateway rejeita webhook sem assinatura válida | [ ] |

---

## 2. Admin — Dashboard

**URL:** `/admin/finance/dashboard`

| # | Teste | Esperado | OK |
|---|-------|----------|-----|
| 2.1 | Abrir dashboard | Cards de métricas carregam | [ ] |
| 2.2 | Filtrar por período (`today`, `week`, `month`, `year`, `all`) | Números mudam conforme filtro | [ ] |
| 2.3 | Verificar totais após corrida paga | Receita / splits atualizados | [ ] |

---

## 3. Corrida paga — Split automático

**Fluxo:** Cliente paga corrida via PIX ou cartão → webhook confirma pagamento.

| # | Teste | Esperado | OK |
|---|-------|----------|-----|
| 3.1 | Finalizar corrida com pagamento PIX | Pagamento confirmado no app | [ ] |
| 3.2 | Verificar tabela `payment_splits` | Registro com `ride_id`, valores driver/platform/gateway | [ ] |
| 3.3 | Verificar `driver_wallets` do motorista | `balance` creditado; `total_received` incrementado | [ ] |
| 3.4 | Verificar `wallet_transactions` | Entrada `credit` com referência à corrida | [ ] |
| 3.5 | Corrida em dinheiro (cash) | Split registrado; saldo carteira **não** creditado (comportamento esperado) | [ ] |

**SQL rápido (opcional):**

```sql
SELECT * FROM payment_splits ORDER BY id DESC LIMIT 5;
SELECT * FROM driver_wallets WHERE driver_id = <ID>;
SELECT * FROM wallet_transactions WHERE driver_id = <ID> ORDER BY id DESC LIMIT 10;
```

---

## 4. Plano ativo — Comissão zero

| # | Teste | Esperado | OK |
|---|-------|----------|-----|
| 4.1 | Atribuir plano ativo ao motorista (admin ou checkout) | Assinatura `active` em `/admin/finance/subscriptions` | [ ] |
| 4.2 | Corrida paga com motorista em plano ativo | `payment_splits.platform_fee` = 0 (ou comissão reduzida conforme regra) | [ ] |
| 4.3 | Plano expirado | Comissão volta ao padrão | [ ] |

---

## 5. API motorista — Carteira

**Headers:** `Authorization: Bearer <token_motorista>`

| # | Endpoint | Esperado | OK |
|---|----------|----------|-----|
| 5.1 | `GET /api/driver/finance/wallet` | Retorna `balance`, `blocked_balance`, `total_received` | [ ] |
| 5.2 | `GET /api/driver/finance/wallet/transactions` | Lista transações paginadas | [ ] |
| 5.3 | `GET /api/finance/payment-gateways` | Lista gateways ativos (PIX/cartão) | [ ] |

---

## 6. Saque motorista

| # | Teste | Esperado | OK |
|---|-------|----------|-----|
| 6.1 | `POST /api/driver/finance/withdraw/request` com valor válido | Status `pending`; `blocked_balance` aumenta | [ ] |
| 6.2 | Solicitar valor acima do saldo | Erro 422 / mensagem clara | [ ] |
| 6.3 | Solicitar abaixo do mínimo configurado | Erro de validação | [ ] |
| 6.4 | Exceder limite diário (se habilitado) | Bloqueio com mensagem de segurança | [ ] |
| 6.5 | `GET /api/driver/finance/withdraw/pending` | Lista saque pendente | [ ] |

---

## 7. Admin — Saques

**URL:** `/admin/finance/withdraws`

| # | Teste | Esperado | OK |
|---|-------|----------|-----|
| 7.1 | Ver saque pendente na listagem | Aparece com badge `pending` | [ ] |
| 7.2 | Aprovar saque | Status `approved`; auditoria registrada | [ ] |
| 7.3 | Marcar como pago / liquidar | Status `settled`; `balance` debitado; `blocked_balance` liberado | [ ] |
| 7.4 | Recusar saque | Status `rejected`; saldo bloqueado devolvido | [ ] |
| 7.5 | PIX automático (se EFI habilitado) | Payout disparado; campos PIX preenchidos em `withdraw_requests` | [ ] |

---

## 8. Checkout de plano (PIX/cartão)

| # | Teste | Esperado | OK |
|---|-------|----------|-----|
| 8.1 | `GET /api/driver/finance/plans` | Lista planos ativos com preços | [ ] |
| 8.2 | `POST /api/driver/finance/plans/{id}/checkout` (PIX) | Retorna QR/copia-e-cola; assinatura `pending` | [ ] |
| 8.3 | `GET /api/driver/finance/subscription/pending` | Retorna checkout pendente | [ ] |
| 8.4 | Webhook confirma pagamento | Assinatura `active`; `expires_at` definido | [ ] |
| 8.5 | Renovação com plano ainda ativo | `expires_at` estendido a partir do vencimento atual | [ ] |
| 8.6 | `GET /api/driver/finance/subscription` | Plano ativo com datas corretas | [ ] |

---

## 9. Auditoria

**URL:** `/admin/finance/audit`

| # | Teste | Esperado | OK |
|---|-------|----------|-----|
| 9.1 | Aprovar/recusar saque | Log em `finance_audit_logs` | [ ] |
| 9.2 | Alterar configurações | Log com usuário e diff | [ ] |
| 9.3 | Webhook duplicado | Log de tentativa duplicada (sem duplo crédito) | [ ] |
| 9.4 | Funcionário sem permissão `log` | Acesso negado à tela de auditoria | [ ] |

---

## 10. Regressão — Legado intacto

| # | Teste | Esperado | OK |
|---|-------|----------|-----|
| 10.1 | `GET /api/driver/withdraw/pending` (API antiga) | Continua funcionando para saques `source=legacy` | [ ] |
| 10.2 | Ledger `transactions` / `user_accounts` | Sem alterações indevidas | [ ] |
| 10.3 | Corrida cash + `TransactionTrait` | Fluxo legado normal | [ ] |
| 10.4 | Webhook legado Mercado Pago | Ainda processa pagamentos antigos | [ ] |

---

## 11. Testes automatizados (opcional no servidor)

```bash
cd fleti-admin-new-install-3.2
./vendor/bin/phpunit --testsuite Finance
```

- [ ] 27 testes passando

---

## Resultado

| Campo | Valor |
|-------|-------|
| Data | |
| Ambiente | produção / staging |
| Executado por | |
| Versão / branch | |
| Bloqueadores encontrados | |
| Aprovado para tráfego real | sim / não |

---

## Rollback (se bloqueador crítico)

```bash
php artisan down --retry=60
php artisan migrate:rollback --path=Modules/FinanceManagement/Database/Migrations --force
php artisan optimize:clear
php artisan up
```

O ledger legado **não** é afetado pelo rollback do módulo.
