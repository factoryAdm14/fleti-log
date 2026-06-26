# SYSTEM MAP — Fleti Log v3.2

**Gerado em:** 2026-06-26  
**Branch:** `feature/fleti-enterprise-v4`  
**Domínio alvo:** `fleti.com.br`

---

## 1. Visão geral

| Componente | Pasta | Stack | Versão |
|------------|-------|-------|--------|
| Painel Admin + API | `fleti-admin-new-install-3.2/` | Laravel 12, PHP 8.2, nwidart/modules | 3.2 |
| App Usuário | `fleti-User-app-release-3.2/` | Flutter (GetX) | 3.2 |
| App Motorista | `fleti-Driver-app-release-3.2/` | Flutter (GetX) | 3.2 |

**Repositório:** `git@github.com:factoryAdm14/fleti-log.git`  
**Produção:** Hostinger (MySQL + FTP) — credenciais em arquivo local gitignored.

---

## 2. Backend Laravel — módulos (16)

```
Modules/
├── AdminModule          # Dashboard admin, permissões
├── AiModule               # Configurações IA
├── AuthManagement         # Login/registro API (customer + driver)
├── BlogManagement         # Blog público/admin
├── BusinessManagement     # Configurações de negócio, landing, externals
├── ChattingManagement     # Chat in-app
├── FareManagement         # Tarifas
├── Gateways               # Pagamentos digitais (Stripe, MP, etc.)
├── ParcelManagement       # Parcel (encomendas)
├── PromotionManagement    # Cupons, descontos, banners
├── ReviewModule           # Avaliações
├── TransactionManagement  # Transações, histórico wallet driver
├── TripManagement         # Corridas, parcel trips, bidding
├── UserManagement         # Users, drivers, wallet, withdraw
├── VehicleManagement      # Categorias, marcas, modelos
└── ZoneManagement         # Zonas geográficas (Google Maps)
```

### Rotas principais

| Arquivo | Papel |
|---------|-------|
| `routes/web.php` | Landing, blog, callbacks pagamento, tracking |
| `routes/api.php` | Sanctum `/user`, tracking polyline |
| `routes/install.php` | Instalador |
| `routes/update.php` | Atualizador |
| `Modules/*/Routes/api.php` | **API mobile** (maior parte) |
| `Modules/*/Routes/web.php` | **Painel admin** |

### Entidades centrais

| Conceito | Model/Entity |
|----------|--------------|
| Usuário / Motorista | `UserManagement/Entities/User.php` (+ `DriverDetail.php`) |
| Corrida | `TripManagement/Entities/TripRequest.php` |
| Wallet | `UserManagement/Entities/UserAccount.php` (`wallet_balance`) |
| Zona | `ZoneManagement/Entities/Zone.php` |
| Parcel | `ParcelManagement/Entities/ParcelInformation.php` |
| Pagamento | `Gateways/Entities/PaymentRequest.php` |

### Migrations

- **191** migrations em `Modules/*/Database/Migrations/`
- **9** migrations em `database/migrations/` (OAuth, jobs, websockets)
- Backup SQL: `installation/backup/database_v3.2.sql`

### Gateways de pagamento

Stripe, PayPal, Razorpay, Paystack, Paytm, Flutterwave, MercadoPago, bKash, SSLCommerz, SenangPay, LiqPay, Paymob, Paytabs, Pvit.

---

## 3. App Usuário Flutter

**Package:** `ride_sharing_user_app` (nome legado HexaRide)

### Features (`lib/features/`)

`address`, `auth`, `coupon`, `dashboard`, `home`, `location`, `maintainance_mode`, `map`, `message`, `my_level`, `my_offer`, `notification`, `onboard`, `parcel`, `payment`, `profile`, `realtime_location_trac`, `refer_and_earn`, `refund_request`, `ride`, `safety_setup`, `set_destination`, `settings`, `splash`, `support`, `trip`, `wallet`

### Arquivos críticos

| Arquivo | Função |
|---------|--------|
| `lib/util/app_constants.dart` | URLs API, baseUrl |
| `lib/data/api_client.dart` | Cliente HTTP |
| `lib/helper/di_container.dart` | Injeção de dependências |
| `lib/features/wallet/` | Adicionar saldo, transferência mart |
| `lib/features/ride/` | Corridas |
| `lib/features/parcel/` | Parcel |
| `lib/features/payment/` | Pagamento digital |

---

## 4. App Motorista Flutter

**Package:** `ride_sharing_user_app` (mesmo nome — legado)

### Features (`lib/features/`)

`auth`, `chat`, `dashboard`, `face_verification`, `help_and_support`, `home`, `html`, `leaderboard`, `location`, `maintainance_mode`, `map`, `notification`, `out_of_zone`, `profile`, `realtime_location_trac`, `refer_and_earn`, `review`, `ride`, `safety_setup`, `setting`, `splash`, `trip`, `wallet`

### Wallet motorista

- Saque (`withdraw`)
- Pagamento digital de taxas (`pay-digitally`)
- **Sem** endpoint de adicionar saldo (apenas app usuário)

---

## 5. Integrações externas

| Integração | Onde |
|------------|------|
| 6amMart / Mart wallet | Backend `WalletTransferController`, User app wallet/sign-in |
| Firebase (`ammart-8885e`) | Apps iOS/Android/Web |
| Google Maps | Admin zonas, apps mapa |
| Pusher/Reverb | WebSocket tempo real |
| DriveMond branding | Lang files, `.env.example`, install SQL |

---

## 6. Infraestrutura de deploy

| Recurso | Valor |
|---------|-------|
| Domínio | `fleti.com.br` |
| MySQL DB | `u965007418_fleti_serv` |
| MySQL User | `u965007418_fleti_user` |
| FTP User | `u965007418.fletiuser` |

> Senhas armazenadas apenas localmente (não versionadas).

---

## 7. Estrutura do repositório Git

```
fleti-log/
├── Master_Plan_Fleti_Enterprise_v4_0_Cursor.md
├── SYSTEM_MAP.md
├── AUDIT_REPORT.md
├── ROUTE_AUDIT.md
├── DATABASE_AUDIT.md
├── FLOW_AUDIT.md
├── backup/phase-000/          # Snapshots composer/pubspec
├── fleti-admin-new-install-3.2/
├── fleti-User-app-release-3.2/
└── fleti-Driver-app-release-3.2/
```
