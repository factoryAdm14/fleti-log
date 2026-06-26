# DATABASE AUDIT — Fleti Log v3.2

**Fase:** 004 (auditoria documental na FASE 001)  
**Data:** 2026-06-26  
**Referência:** `installation/backup/database_v3.2.sql`

---

## 1. Ambiente de produção

| Parâmetro | Valor |
|-----------|-------|
| Engine | MySQL |
| Database | `u965007418_fleti_serv` |
| Host | Hostinger (remoto) |
| Usuário | `u965007418_fleti_user` |

> Export do banco de produção pendente (FASE 000 checklist). Schema abaixo baseado no backup v3.2 do instalador.

---

## 2. Migrations por módulo

| Módulo | Migrations |
|--------|----------:|
| UserManagement | 46 |
| TripManagement | 35 |
| BusinessManagement | 29 |
| PromotionManagement | 15 |
| FareManagement | 14 |
| ChattingManagement | 9 |
| Gateways | 9 |
| VehicleManagement | 9 |
| TransactionManagement | 7 |
| BlogManagement | 6 |
| ParcelManagement | 4 |
| ZoneManagement | 4 |
| AdminModule | 2 |
| ReviewModule | 1 |
| AiModule | 1 |
| AuthManagement | 0 |
| **Total módulos** | **191** |
| `database/migrations/` | 9 |
| **Total** | **200** |

---

## 3. Tabelas principais (database_v3.2.sql)

### Usuários e motoristas

| Tabela | Propósito |
|--------|-----------|
| `users` | Usuários (customer + driver via `user_type`) |
| `user_accounts` | **Wallet** (`wallet_balance`, loyalty) |
| `driver_details` | Dados específicos motorista |
| `driver_identity_verifications` | Verificação facial |
| `driver_time_logs` | Tempo online |

### Corridas e entregas

| Tabela | Propósito |
|--------|-----------|
| `trip_requests` | Corridas, parcel, delivery |
| `trip_status` | Status da corrida |
| `trip_request_coordinates` | Coordenadas |
| `trip_request_fees` | Taxas |
| `trip_routes` | Rotas |
| `fare_biddings` | Lances |
| `recent_addresses` | Endereços recentes |

### Parcel

| Tabela | Propósito |
|--------|-----------|
| `parcel_informations` | Dados do parcel |
| `parcel_categories` | Categorias |
| `parcel_weights` | Pesos |
| `parcel_refunds` | Reembolsos |

### Zonas

| Tabela | Propósito |
|--------|-----------|
| `zones` | Polígonos (`coordinates` texto/JSON) |

### Pagamentos e transações

| Tabela | Propósito |
|--------|-----------|
| `payment_requests` | Requisições de pagamento |
| `transactions` | Histórico transações |
| `wallet_bonuses` | Bônus adicionar saldo |
| `withdraw_requests` | Saques motorista |
| `withdraw_methods` | Métodos de saque |

### Configuração

| Tabela | Propósito |
|--------|-----------|
| `business_settings` | Config geral |
| `external_configurations` | Integração 6amMart |
| `notification_settings` | Push |
| `firebase_push_notifications` | Templates FCM |

---

## 4. Relacionamentos críticos

```
users (1) ── (1) user_accounts [wallet_balance]
users (1) ── (1) driver_details [se user_type=driver]
users (1) ── (*) trip_requests [customer_id / driver_id]
trip_requests (1) ── (1) parcel_informations [se tipo parcel]
trip_requests (*) ── (1) zones [zone_id]
transactions (*) ── (1) users
payment_requests (*) ── (1) users / trip_requests
```

---

## 5. Campos sensíveis

| Tabela | Campo | Nota |
|--------|-------|------|
| `user_accounts` | `wallet_balance` | Crítico — transações atômicas |
| `users` | `password` | Hash bcrypt |
| `external_configurations` | mart URLs/keys | Integração externa |
| `business_settings` | payment keys | Criptografar em produção |

---

## 6. Índices e performance (a verificar em produção)

Checklist FASE 011:

- [ ] `trip_requests.customer_id`, `driver_id`, `zone_id`
- [ ] `transactions.user_id`, `created_at`
- [ ] `users.phone`, `email`
- [ ] `zones.readable_id`

---

## 7. Regras Master Plan

- **Nunca excluir tabelas**
- Novas features (PIX MP, PIX EFI, Multi Stop) → **novas migrations apenas**
- Migration `trip_stops` (FASE 016) — tabela nova, opcional

---

## 8. Gaps identificados

| Item | Status |
|------|--------|
| Tabela `wallets` dedicada | Não existe — wallet em `user_accounts` |
| Tabela `deliveries` | Não existe — delivery via `trip_requests` |
| Tabela `trip_stops` (multi-stop) | Não existe — roadmap FASE 016 |
| Export produção vs v3.2 SQL | **Não comparado** — executar na FASE 002 |

---

## 9. Rollback de migrations

Todas as migrations em `Modules/*/Database/Migrations/` possuem método `down()`.  
**Não executar `migrate:fresh`** em produção.

---

## Próxima etapa

1. Exportar `mysqldump` do banco `u965007418_fleti_serv`
2. Comparar tabelas produção vs `database_v3.2.sql`
3. Listar migrations pendentes com `php artisan migrate:status`
