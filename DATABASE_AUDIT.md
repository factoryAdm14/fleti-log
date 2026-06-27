# DATABASE AUDIT — Fleti Log v3.2 (FASE 004)

**Data:** 2026-06-26  
**Referência schema:** `installation/backup/database_v3.2.sql`  
**Produção:** `u965007418_fleti_serv` @ Hostinger (`fleti.com.br`)

---

## 1. Resumo executivo

| Métrica | Valor |
|---------|-------|
| Tabelas no dump v3.2 | **126** |
| Migrations no dump v3.2 | **214** |
| Arquivos migration no código | **200** |
| Migrations no código ausentes no dump | **0** |
| Migrations legadas só no dump | **14** (renomeações históricas) |
| Foreign keys declaradas no dump | **4** |
| Conexão produção remota | **Não testada** — requer Remote MySQL no hPanel |

O schema v3.2 está **alinhado com o código atual**. Nenhuma migration pendente foi encontrada comparando dump vs código. A conexão direta ao MySQL de produção não foi possível nesta máquina (acesso remoto Hostinger exige hostname do painel + IP na whitelist).

---

## 2. Ambiente de produção

| Parâmetro | Valor |
|-----------|-------|
| Database | `u965007418_fleti_serv` |
| User | `u965007418_fleti_user` |
| Host (no servidor) | `localhost` |
| Host (remoto) | Ver hPanel → **Databases → Remote MySQL** |
| Porta | `3306` |

### Como habilitar auditoria live (Hostinger)

1. hPanel → **Websites** → `fleti.com.br` → **Databases** → **Remote MySQL**
2. Adicionar IP público da máquina de dev (ou "Any Host" temporariamente)
3. Copiar **MySQL hostname** exibido no topo da página
4. Atualizar `DB_HOST` no `.env` do Laravel
5. Executar:

```bash
php artisan migrate:status
php artisan db:show
```

---

## 3. Tabelas por domínio funcional

### Usuários e motoristas (11+ tabelas)

| Tabela | Propósito |
|--------|-----------|
| `users` | Customer + driver + admin (`user_type`) |
| `user_accounts` | **Wallet** (`wallet_balance`, saldos) |
| `driver_details` | Dados motorista |
| `driver_identity_verifications` | Verificação facial |
| `driver_time_logs` | Tempo online |
| `user_address` | Endereços salvos |
| `user_last_locations` | Última localização |
| `user_additional_infos` | Dados extras JSON |
| `otp_verifications` | OTP auth |
| `roles` / `role_user` | Permissões admin |

### Corridas e entregas (11+ tabelas)

| Tabela | Propósito |
|--------|-----------|
| `trip_requests` | Corridas, parcel, delivery (49 colunas) |
| `trip_status` | Histórico status |
| `trip_request_coordinates` | Coordenadas |
| `trip_request_fees` | Taxas |
| `trip_routes` | Rotas |
| `trip_fares` | Tarifas por zona |
| `fare_biddings` / `fare_bidding_logs` | Lances |
| `recent_addresses` | Endereços recentes (sem rota API listagem) |
| `temp_trip_notifications` | Notificações temp |
| `safety_alerts` | Alertas segurança |

### Parcel (11 tabelas)

`parcel_information`, `parcel_categories`, `parcel_weights`, `parcel_fares`, `parcel_refunds`, `parcel_refund_proofs`, `parcel_refund_reasons`, `parcel_cancellation_reasons`, `parcel_user_infomations`, `parcel_fares_parcel_weights`

> Nota: migration legada `create_parcels_table` renomeada — tabela atual é `parcel_information`.

### Zonas (6 tabelas)

| Tabela | Propósito |
|--------|-----------|
| `zones` | Polígono (`coordinates` tipo POLYGON), `readable_id`, extra fare |
| `zone_wise_default_trip_fares` | Tarifas por zona |
| `zone_coupon_setups` / `zone_discount_setups` | Promoções por zona |
| `surge_pricing_zones` | Surge pricing |
| `vehicle_category_zone` | Categorias por zona |

### Wallet e pagamentos

| Tabela | Propósito |
|--------|-----------|
| `user_accounts.wallet_balance` | Saldo principal |
| `wallet_bonuses` | Promoções adicionar saldo |
| `transactions` | Histórico transações |
| `payment_requests` | Pagamentos digitais |
| `withdraw_requests` | Saques motorista |
| `withdraw_methods` | Métodos de saque |
| `loyalty_points_histories` | Pontos fidelidade |

### Configuração e integrações

| Tabela | Propósito |
|--------|-----------|
| `business_settings` | Config chave-valor |
| `external_configurations` | Integração 6amMart (`key`, `value`) |
| `notification_settings` | Push config |
| `firebase_push_notifications` | Templates FCM |

---

## 4. Migrations — estado

### Código vs dump v3.2

| Status | Quantidade |
|--------|------------|
| Migrations executadas (dump) | 214 |
| Arquivos migration no repo | 200 |
| **Pendentes no código** | **0** |
| Legadas só no dump | 14 |

As 14 migrations legadas são renomeações de tabelas antigas (`areas`, `bonus_setups`, `parcels` → nomes atuais). **Não executar `migrate` novamente** em produção sem `migrate:status`.

### Migrations por módulo (arquivos)

| Módulo | Arquivos |
|--------|----------|
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
| `database/migrations/` | 9 |

---

## 5. Índices e performance

### Índices em tabelas críticas (dump v3.2)

| Tabela | Índices |
|--------|---------|
| `users` | PRIMARY, email, phone, ref_code |
| `user_accounts` | PRIMARY apenas |
| `trip_requests` | PRIMARY apenas |
| `transactions` | PRIMARY apenas |
| `zones` | PRIMARY, name |
| `payment_requests` | PRIMARY apenas |

### Recomendações FASE 011 (não aplicar ainda)

```sql
-- Sugestões para análise em produção com EXPLAIN
ALTER TABLE trip_requests ADD INDEX idx_customer_id (customer_id);
ALTER TABLE trip_requests ADD INDEX idx_driver_id (driver_id);
ALTER TABLE trip_requests ADD INDEX idx_zone_id (zone_id);
ALTER TABLE trip_requests ADD INDEX idx_current_status (current_status);
ALTER TABLE transactions ADD INDEX idx_user_id_created (user_id, created_at);
ALTER TABLE user_accounts ADD INDEX idx_user_id (user_id);
```

> Criar apenas após `EXPLAIN` em queries lentas reais. Toda alteração via migration reversível.

---

## 6. Foreign keys

Apenas **4 FKs** no dump v3.2:

| Tabela | Coluna | Referência |
|--------|--------|------------|
| `blogs` | `blog_author_id` | `blog_authors.id` |
| `blog_drafts` | `blog_author_id` | `blog_authors.id` |
| `trip_navigations` | `trip_request_id` | `trip_requests.id` |
| `user_additional_infos` | `user_id` | `users.id` |

Relacionamentos wallet/trip/user são mantidos via **Eloquent** (`belongsTo`) sem FK no banco — padrão do sistema original.

---

## 7. Campos nullable — riscos

| Tabela | Campo | Nullable | Risco |
|--------|-------|----------|-------|
| `user_accounts` | `user_id` | YES | Médio — conta órfã possível |
| `trip_requests` | `customer_id`, `driver_id`, `zone_id` | YES | Baixo — estados intermediários |
| `transactions` | `user_id` | YES | Médio — transação sem dono |
| `users` | `phone`, `email` | YES | Baixo — login por um dos dois |
| `zones` | `coordinates` | YES | **Alto** — zona sem polígono |
| `user_accounts` | `wallet_balance` | NO | OK — default 0.00 |

---

## 8. Tabelas ausentes (roadmap)

| Tabela | Fase | Status |
|--------|------|--------|
| `trip_stops` | FASE 016 Multi Stop | Não existe |
| `wallets` dedicada | — | Não necessária (`user_accounts`) |
| `deliveries` | — | Via `trip_requests.type` |

---

## 9. Entidades Eloquent vs tabelas

| Conceito | Entity | Tabela |
|----------|--------|--------|
| User/Driver | `UserManagement/Entities/User.php` | `users` |
| Wallet | `UserManagement/Entities/UserAccount.php` | `user_accounts` |
| Trip | `TripManagement/Entities/TripRequest.php` | `trip_requests` |
| Zone | `ZoneManagement/Entities/Zone.php` | `zones` |
| Parcel | `ParcelManagement/Entities/ParcelInformation.php` | `parcel_information` |
| Payment | `Gateways/Entities/PaymentRequest.php` | `payment_requests` |
| Wallet Bonus | `UserManagement/Entities/WalletBonus.php` | `wallet_bonuses` |
| Withdraw | `UserManagement/Entities/WithdrawRequest.php` | `withdraw_requests` |

---

## 10. Regras Master Plan (confirmadas)

- [x] Nenhuma tabela excluída nesta fase
- [x] Nenhuma migration destrutiva criada
- [x] Schema v3.2 documentado
- [ ] Export produção pendente (Remote MySQL)
- [ ] `migrate:status` em produção pendente

---

## 11. Arquivos de backup FASE 004

- `backup/phase-004/tables_v3.2.txt` — lista completa das 126 tabelas

---

## Próxima etapa

**FASE 005 — Auditoria de fluxo** (validação funcional com banco real quando Remote MySQL estiver habilitado).
