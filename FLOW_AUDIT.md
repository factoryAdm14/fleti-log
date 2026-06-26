# FLOW AUDIT — Fleti Log v3.2 (FASE 005)

**Data:** 2026-06-26  
**Produção testada:** `https://fleti.com.br` (smoke tests API)  
**Branch:** `feature/fleti-enterprise-v4`

---

## Legenda

| Status | Significado |
|--------|-------------|
| OK | Fluxo completo no código + API alinhada |
| WARN | Funciona com ressalvas ou risco |
| OFF | Desativado em produção (config admin) |
| BLOCK | Bug que impede o fluxo |
| N/A | Não aplicável por design |

---

## Resumo executivo produção (2026-06-26)

| Config produção | Valor | Impacto |
|-----------------|-------|---------|
| `wallet_add_fund_status` | **false** | Botão **+ Adicionar Saldo oculto** no app usuário |
| `payment_gateways` | **[]** | Pagamento digital e add-fund **não funcionam** |
| `wallet_minimum_deposit_limit` | 10 | OK (quando ativado) |
| `maintenance_mode` | off | Apps operacionais |
| `business_name` | Fleti log ltda | OK |
| `currency_symbol` | R$ | OK |

> **Ação operacional:** Admin → Business Setup → Customer → ativar **Customer Wallet → Add Fund** e configurar gateways em Payment Methods.

---

## 1. Autenticação

### 1.1 Cadastro usuário

```
sign_up_screen → AuthController.register()
  → POST /api/customer/auth/registration
  → AuthController@register → CustomerService::create()
  → users, user_accounts, otp_verifications
```

| Etapa | Status |
|-------|--------|
| Registro senha | OK |
| OTP send/verify | OK |
| Social login | OK |
| Firebase OTP | OK |
| Registro pós-OTP | OK |

### 1.2 Login usuário

```
sign_in_screen → AuthController.login()
  → POST /api/customer/auth/login
  → AuthService::checkClientRoute() → Passport token
```

| Etapa | Status | Nota |
|-------|--------|------|
| Login senha | OK | baseUrl corrigido FASE 002 |
| OTP login | **BLOCK** | Bug `otpLogin` quando user não existe |
| Logout | OK | `POST /api/user/logout` |
| FCM update | OK | `PUT /api/customer/update/fcm-token` |
| Login mart | WARN | Deep link typo em `sign_in_screen.dart` |

### 1.3 Cadastro / login motorista

```
sign_in_screen → POST /api/driver/auth/login
registration → POST /api/driver/auth/registration
biometric → POST /api/driver/verify-or-set-password-for-biometric
```

| Etapa | Status |
|-------|--------|
| Login | OK |
| Registro + docs | OK |
| OTP | OK |
| Face verification | OK |
| Online status | OK |

### Bug crítico auth

```753:760:fleti-admin-new-install-3.2/Modules/AuthManagement/Http/Controllers/Api/AuthController.php
        if (!$user) {
            //If customer not exists
            $firstLevel = $user->user_type == CUSTOMER ? ...
```

Quando usuário não existe, `$user` é null → **fatal error**. Corrigir na FASE 006.

---

## 2. Wallet

### 2.1 Wallet usuário — Adicionar Saldo

```
wallet_screen
  → wallet_money_amount_widget (+) [SE walletAddFundStatus]
  → add_fund_dialog (valor + gateway)
  → digital_add_fund_screen (WebView)
  → GET /api/customer/wallet/add-fund-digitally
  → WalletController@addFundDigitally
  → Payment::generate_link() → payment_requests
  → Gateway callback → CustomerWalletUpdate
  → user_accounts.wallet_balance++, transactions
```

| Etapa | Código | Produção |
|-------|--------|----------|
| Botão + existe | OK (`add_fund_dialog.dart`) | **OFF** — flag false |
| API bonus-list | OK | Não testado auth |
| API add-fund-digitally | OK | Sem gateway ativo |
| Flag `walletAddFundStatus` | OK | **false** em produção |
| Label i18n | `add_fund` (EN) | Sem pt-BR bundled |

**Master Plan:** botão Adicionar Saldo **existe no código** — oculto por config admin.

### 2.2 Wallet usuário — outras funções

| Fluxo | Cadeia | Status |
|-------|--------|--------|
| Histórico | `transaction/list` → `TransactionController` | OK |
| Bônus promo | `wallet/bonus-list` → `WalletController@bonusList` | OK |
| Transfer mart | `wallet/transfer-drivemond-to-mart` → `WalletTransferController` | OK (FASE 002) |
| Loyalty | `loyalty-points/*` | OK |

### 2.3 Wallet motorista

| Fluxo | Cadeia | Status |
|-------|--------|--------|
| Histórico wallet | `driver/transaction/wallet-list` | OK |
| Saque | `withdraw/request` → `withdraw_requests`, `user_accounts` | OK |
| Métodos saque | `withdraw-method-info/*` | OK |
| Pagar admin digital | `pay-digitally` → `DriverDigitalPay` | WARN — sem auth + sem gateway |
| Adicionar saldo | N/A | Por design (só usuário) |

---

## 3. Corrida (Ride)

### 3.1 Fluxo completo

```
[USER] map_screen
  → POST get-estimated-fare (type: ride_request)
  → POST ride/create → trip_requests + trip_status + coordinates
  → [DRIVER] push notification → trip-action (accepted)
  → match-otp → update-status (picked_up → ongoing → completed)
  → GET final-fare → payment (cash|wallet|digital)
  → review/store
```

| Etapa | User | Driver | Backend | Status |
|-------|------|--------|---------|--------|
| Estimar tarifa | OK | — | `getEstimatedFare` | OK |
| Criar corrida | OK | — | `createRideRequest` | OK |
| Aceitar | — | OK | `requestAction` | OK |
| Ignorar notificação | — | **BLOCK** | Endpoint errado no app | BUG |
| Atualizar status | OK | OK | `rideStatusUpdate` | OK |
| Bidding | OK | OK | `bidding-list`, `trip-action` | OK |
| Pagamento cash/wallet | OK | — | `PaymentController@payment` | OK |
| Pagamento digital | OK | — | `digital-payment` | OFF — sem gateway |
| Safety alert | OK | — | `safety-alert/*` | OK |

### Bug driver — ignorar notificação

```55:58:fleti-Driver-app-release-3.2/lib/features/ride/domain/repositories/ride_repository.dart
  Future<Response> ignoreMessage(String tripId) async {
    return await apiClient.postData(AppConstants.tripAcceptOrReject,{
      "trip_request_id": tripId
```

Deveria usar `AppConstants.ignoreNotification` (`/api/driver/ride/ignore-trip-notification`).

---

## 4. Parcel (inclui Delivery)

**Não existe tipo `delivery` separado.** Delivery = `type: parcel` em `trip_requests`.

```
[USER] ParcelController (categorias, peso, remetente/destinatário)
  → getEstimatedFare(parcel: true)
  → ride/create (type: parcel) → parcel_information + parcel_user_infomations
[ DRIVER ] mesmo fluxo accept/status + match-otp + delivery_proof_images
```

| Etapa | Status |
|-------|--------|
| Categorias | OK (requer auth + zoneId header) |
| Criar parcel | OK |
| Lists ongoing/unpaid | OK |
| Reembolso | OK — `parcel/refund/create` |
| Devolução | OK — `received-returning-parcel` |
| OTP retorno driver | OK — `returned-parcel`, `resend-otp` |
| Multi-stop 20 pontos | N/A — FASE 016 |

---

## 5. Cancelamento

```
TripController/RideController.tripStatusUpdate('cancelled', reason)
  → config/cancellation-reason-list (ride ou parcel)
  → PUT customer/ride/update-status/{id}
  → TripRequestService::updateRideStatus
```

| Tipo | Status |
|------|--------|
| Corrida | OK — produção retorna 200 em reason-list |
| Parcel | OK |
| Driver cancel | OK |

---

## 6. Cupom

```
coupon_controller → POST /api/customer/applied-coupon
  → CustomerController@applyCoupon → coupon_setups pivot
finalFareCalculation → getFinalCouponDiscount → trip_requests.coupon_amount
```

| Etapa | Status |
|-------|--------|
| Listar cupons | OK |
| Aplicar (app usa applied-coupon) | OK |
| Aplicar (coupon/apply endpoint) | WARN — controller com métodos quebrados, app não usa |
| Desconto no final fare | OK |

---

## 7. Pagamento

| Tipo | Rota | Gateway | Produção |
|------|------|---------|----------|
| Cash corrida | `ride/payment` | — | OK |
| Wallet corrida | `ride/payment` | `walletTransaction` | OK |
| Digital corrida | `ride/digital-payment` | `generate_link` | **OFF** — gateways vazios |
| Digital wallet | `wallet/add-fund-digitally` | idem | **OFF** |
| Driver pay admin | `driver/pay-digitally` | idem | **OFF** |
| PIX nativo | — | — | N/A — FASE 014/015 |
| Callbacks web | `/payment/*` | 15 controllers | OK no código |

### Riscos pagamento

- Endpoints digitais **sem auth:api** (user_id/trip_id na query)
- `generate_link()` incompleto para mercadopago, paymob, paytabs, pvit
- Validators aceitam gateways sem implementação

---

## 8. Notificações

| Canal | Fluxo | Status |
|-------|-------|--------|
| FCM push | `notification-list`, Firebase | WARN — projeto `ammart-8885e` legado |
| Pusher/Reverb | `pusher_helper.dart`, config `web_socket_url` | WARN — protected member warnings |
| SMS OTP | `AuthService::sendOtpToClient` | Depende gateway SMS admin |
| In-app | `app_notifications` | OK |

---

## 9. Zonas

| Etapa | Fluxo | Status |
|-------|-------|--------|
| Detectar zona app | `config/get-zone-id` + header zoneId | OK |
| Admin CRUD | ZoneManagement web + Google Maps JS | WARN — validação polígono FASE 006 |
| Driver zone list | `driver/zone/list` | OK |
| Tarifa por zona | `zone_wise_default_trip_fares` | OK |

---

## 10. Localização

| Etapa | API | Status |
|-------|-----|--------|
| Store live | `POST /api/user/store-live-location` | OK |
| Get live | `GET /api/user/get-live-location` | OK |
| Track corrida | `customer/ride/track-location` | OK |
| Recent addresses | `GET /api/customer/recent-address` | **BLOCK** — rota não existe (constante órfã) |

---

## 11. Admin dashboard

| Área | Rota web | Status |
|------|----------|--------|
| Dashboard | AdminModule | OK (código) |
| Usuários / Motoristas | UserManagement | OK |
| Corridas / Parcel | TripManagement | OK |
| Wallet admin | `admin/customer/wallet/*` | OK |
| Customer wallet toggle | `business-setup/customer` | **OFF** em produção |
| Payment gateways | BusinessManagement | **Vazio** em produção |
| Relatórios | TransactionManagement | OK |
| Zonas | ZoneManagement | OK |

---

## 12. Matriz de status por fluxo (Master Plan)

| Fluxo | Código | Produção | Prioridade fix |
|-------|--------|----------|----------------|
| Cadastro usuário | OK | Não testado login | — |
| Login usuário | WARN | API live | otpLogin bug |
| Cadastro motorista | OK | Não testado | — |
| Login motorista | OK | API live | — |
| Wallet usuário | OK | OK | — |
| Wallet motorista | OK | OK | — |
| **Adicionar saldo** | OK | **OFF** | Ativar no admin + gateway |
| Corrida | WARN | Parcial | ignoreMessage driver |
| Parcel | OK | Não testado | — |
| Delivery | OK (=parcel) | — | — |
| Cancelamento | OK | reason-list 200 | — |
| Cupom | OK | Não testado | — |
| Pagamento | WARN | **OFF** | Configurar gateways |
| PIX | N/A | N/A | FASE 014 |
| Notificações | WARN | FCM legado | — |
| Zonas | WARN | Não testado | FASE 006 |
| Localização | OK | — | — |
| Admin | OK | Config incompleta | Habilitar wallet+pagamentos |

---

## 13. Bugs confirmados (não corrigidos nesta fase)

| # | Severidade | Componente | Descrição |
|---|------------|------------|-----------|
| 1 | ALTA | Laravel | `AuthController::otpLogin` — null dereference |
| 2 | MÉDIA | Driver app | `ignoreMessage` usa endpoint errado |
| 3 | MÉDIA | Laravel | Endpoints digitais sem autenticação |
| 4 | MÉDIA | Laravel | `generate_link()` incompleto para alguns gateways |
| 5 | BAIXA | User app | `getRecentAddressList` — rota inexistente (não usada) |
| 6 | OPS | Produção | `wallet_add_fund_status=false`, `payment_gateways=[]` |

---

## 14. Próxima etapa

**FASE 006 — Google Maps e Zonas** + correção bugs auth/driver em branch separada com feature flag.

**Ação imediata recomendada (sem código):**
1. Admin → Customer Settings → ativar Add Fund
2. Admin → Payment Methods → configurar Mercado Pago ou outro gateway
3. Habilitar Remote MySQL para testes integrados
