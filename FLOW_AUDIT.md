# FLOW AUDIT — Fleti Log v3.2

**Fase:** 005 (auditoria documental na FASE 001)  
**Data:** 2026-06-26

---

## Legenda

| Status | Significado |
|--------|-------------|
| OK | Código backend + app presentes, rotas alinhadas |
| WARN | Presente mas com bugs/risco |
| BLOCK | Bloqueado por erro de build |

---

## 1. Autenticação

### Cadastro usuário

| Etapa | Backend | User App | Status |
|-------|---------|----------|--------|
| Registro | `POST /api/customer/auth/registration` | `registration` | OK |
| OTP | `send-otp`, `otp-verification` | `sendOTP`, `otpVerification` | OK |
| Social | `social-login` | `socialLogin` | OK |
| Login mart | `external-login` | `externalLoginUri` | WARN — deep link typo |

### Login usuário

| Etapa | Backend | User App | Status |
|-------|---------|----------|--------|
| Login | `POST /api/customer/auth/login` | `loginUri` | WARN — baseUrl sem https |
| Logout | `POST /api/user/logout` | `logOutUri` | OK |
| Token FCM | `PUT /api/customer/update/fcm-token` | `fcmTokenUpdate` | OK |

### Cadastro / login motorista

| Etapa | Backend | Driver App | Status |
|-------|---------|------------|--------|
| Registro | `POST /api/driver/auth/registration` | `registration` | OK |
| Login | `POST /api/driver/auth/login` | `loginUri` | WARN — baseUrl |
| Biometria | `verify-or-set-password-for-biometric` | face_verification feature | OK |

---

## 2. Wallet

### Wallet usuário

| Etapa | Backend | User App | Status |
|-------|---------|----------|--------|
| Listar transações | `TransactionManagement` | `transactionListUri` | OK |
| Bônus promo | `wallet/bonus-list` | `getAddFundPromotionalList` | OK |
| **Adicionar saldo** | `wallet/add-fund-digitally` | `digitalAddFund`, `AddFundDialog` | WARN — baseUrl |
| Transferir mart | `wallet/transfer-drivemond-to-mart` | constante quebrada | **BLOCK** |
| Loyalty | `loyalty-points/*` | `loyaltyPointListUri` | OK |

**Botão Adicionar Saldo:** presente em `wallet_money_amount_widget.dart` — controlado por `walletAddFundStatus` do config.

### Wallet motorista

| Etapa | Backend | Driver App | Status |
|-------|---------|------------|--------|
| Histórico | `driver/transaction/wallet-list` | `walletListUri` | OK |
| Saque | `driver/withdraw/*` | `withdrawRequestUri` | OK |
| Pagar digital | `driver/pay-digitally` | `digitalPayment` | OK |
| Adicionar saldo | N/A | N/A | N/A (por design) |

---

## 3. Corrida (Ride)

| Etapa | Backend | Apps | Status |
|-------|---------|------|--------|
| Estimar tarifa | `ride/get-estimated-fare` | User `estimatedFare` | OK |
| Solicitar | `ride/create` | User `rideRequest` | OK |
| Aceitar/rejeitar | `driver/ride/trip-action` | Driver | OK |
| Atualizar status | `ride/update-status/{id}` | Ambos | OK |
| Pagamento | `ride/payment`, `digital-payment` | Ambos | WARN |
| Bidding | `bidding-list`, `ignore-bidding` | User | OK |
| Safety alert | `safety-alert/*` | User | OK |

---

## 4. Parcel

| Etapa | Backend | User App | Status |
|-------|---------|----------|--------|
| Categorias | `parcel/category` | `parcelCategoryUri` | OK |
| Criar (via ride) | `ride/create` (tipo parcel) | ride flow | OK |
| Lista ongoing | `ongoing-parcel-list` | `parcelOngoingList` | OK |
| Reembolso | `parcel/refund/create` | `parcelRefundCreate` | OK |
| Devolução | `received-returning-parcel` | `parcelReceived` | OK |

---

## 5. Delivery

Delivery não possui módulo separado — fluxo via `TripRequest` com tipo de serviço delivery.

| Etapa | Status |
|-------|--------|
| Solicitação | OK (TripManagement) |
| Multi-stop (até 20 pontos) | **Não implementado** — FASE 016 |

---

## 6. Cancelamento

| Tipo | Backend | App |
|------|---------|-----|
| Corrida | `config/cancellation-reason-list` | `rideCancellationReasonList` |
| Parcel | `config/parcel-cancellation-reason-list` | `parcelCancellationReasonList` |

Status: **OK**

---

## 7. Cupom

| Etapa | Backend | User App | Status |
|-------|---------|----------|--------|
| Listar | `coupon/list` | `couponList` | OK |
| Aplicar | `applied-coupon` | `customerAppliedCoupon` | OK |

---

## 8. Pagamento

| Tipo | Gateway | Rota | Status |
|------|---------|------|--------|
| Digital corrida | Gateways module | `ride/digital-payment` | OK |
| Digital wallet | Gateways module | `wallet/add-fund-digitally` | OK |
| PIX dedicado | — | — | **Não implementado** — FASE 014/015 |
| Callbacks | `/payment/*` | web views | OK |

---

## 9. Notificações

| Canal | Backend | Apps | Status |
|-------|---------|------|--------|
| FCM | `notification-list`, `read-notification` | Ambos | WARN — Firebase legado |
| Pusher/Reverb | WebSocket config | `pusher_helper.dart` | WARN — protected member warnings |

---

## 10. Zonas

| Etapa | Backend | Apps | Status |
|-------|---------|------|--------|
| Detectar zona | `config/get-zone-id` | User `getZone` | OK |
| Admin CRUD | ZoneManagement web | Admin panel | WARN — JS mapa |
| Driver zones | `driver/zone` | Driver | OK |

---

## 11. Localização

| Etapa | Rota | Status |
|-------|------|--------|
| Store live | `POST /api/user/store-live-location` | OK |
| Get live | `GET /api/user/get-live-location` | OK |
| Track corrida | `ride/track-location` | OK |

---

## 12. Admin dashboard

| Área | Módulo | Status |
|------|--------|--------|
| Dashboard | AdminModule | OK (não testado runtime) |
| Usuários | UserManagement web | OK |
| Motoristas | UserManagement web | OK |
| Corridas | TripManagement web | OK |
| Wallet admin | UserManagement `wallet/*` | OK |
| Relatórios | TransactionManagement | OK |
| Config | BusinessManagement | OK |

---

## 13. Fluxos bloqueados

1. **Build User App** — erro sintaxe wallet transfer
2. **Laravel runtime** — vendor ausente
3. **API calls** — baseUrl sem `https://` (se não houver override runtime)

---

## 14. Próxima etapa

FASE 002: desbloquear builds, depois teste manual dos fluxos OK/WARN em ambiente staging com `fleti.com.br`.
