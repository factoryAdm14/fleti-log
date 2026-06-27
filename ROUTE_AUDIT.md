# ROUTE AUDIT — Fleti Log v3.2 (atualizado FASE 003)

**Data:** 2026-06-26  
**Método:** `php artisan route:list --json` cruzado com `app_constants.dart` (User + Driver)

---

## Resumo da validação cruzada

| Métrica | User App | Driver App |
|---------|----------|------------|
| Endpoints no app | 93 | 103 |
| Match exato backend | 82 | 91 |
| Match com parâmetro `{id}` | 10 | 11 |
| **Gap real** | 1 | 1 → **corrigido** |
| Taxa de cobertura | **99%** | **100%** (após fix) |

**Total rotas API backend:** 214 (únicas normalizadas)  
**Total rotas Laravel:** 848 (inclui web, admin, oauth)

---

## Gaps encontrados e ação

| App | Constante | URI no app | Backend | Ação |
|-----|-----------|------------|---------|------|
| User | `getRecentAddressList` | `api/customer/recent-address` | **Não existe** | Constante órfã (não usada no código) — documentar para FASE futura |
| Driver | `vehicleModelList` | `api/driver/vehicle/models/list` | `api/driver/vehicle/model/list` | **Corrigido** em FASE 003 |

---

## Rotas críticas — Wallet (confirmadas)

| Método | URI | App | Status |
|--------|-----|-----|--------|
| GET | `api/customer/wallet/bonus-list` | `getAddFundPromotionalList` | OK |
| GET | `api/customer/wallet/add-fund-digitally` | `digitalAddFund` | OK |
| POST | `api/customer/wallet/transfer-drivemond-to-mart` | `transferMoneyToMart` | OK |
| POST | `api/customer/wallet/transfer-drivemond-from-mart` | — (webhook mart) | OK |
| GET | `api/driver/transaction/wallet-list` | `walletListUri` | OK |
| GET | `api/driver/pay-digitally` | `digitalPayment` | OK |

---

## Rotas com parâmetro dinâmico (OK — app concatena ID)

Padrão normal: app define prefixo, backend exige `{id}`.

Exemplos User:
- `api/customer/ride/details` → `api/customer/ride/details/{id}`
- `api/customer/safety-alert/resend` → `.../resend/{id}`

Exemplos Driver:
- `api/driver/ride/details` → `.../details/{id}`
- `api/driver/vehicle/update` → `.../update/{id}`

---

## Rotas públicas sensíveis (sem auth:api)

| URI | Risco |
|-----|-------|
| `api/customer/wallet/add-fund-digitally` | Validar user_id server-side |
| `api/customer/wallet/transfer-drivemond-from-mart` | Webhook mart — validar origem |
| `api/customer/ride/digital-payment` | Pagamento corrida |
| `api/driver/pay-digitally` | Pagamento motorista |
| `api/customer/auth/external-login` | Login mart externo |

**Recomendação FASE 013:** auditoria de segurança nestes endpoints.

---

## Rotas duplicadas / anomalias

| Item | Detalhe |
|------|---------|
| Nome legado wallet | `transfer-drivemond-to-mart` — mantido por compatibilidade |
| Driver usa rota customer | `searchLocationUri` → `api/customer/config/place-api-autocomplete` (compartilhada) |
| TripManagement middleware | `auth:api` duplicado em subgrupo driver ride |

---

## Rotas órfãs (backend sem uso no app)

Não auditado exaustivamente nesta fase. Estimativa: ~30 rotas admin-only e rotas de blog/AI sem correspondente mobile.

---

## Arquivo de cruzamento

Detalhes em `ROUTE_CROSSCHECK.txt`.

---

## Próxima etapa

**FASE 004 — Auditoria de banco** com export MySQL produção (`u965007418_fleti_serv`).
