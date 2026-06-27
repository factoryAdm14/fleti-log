# Mapa de API — Flutter Web Fleti

Base URL: `https://fleti.com.br`  
Headers padrão: `Authorization: Bearer {token}`, `X-Localization: en|pt`, `zoneId: {zone_id}`, `Accept: application/json`

Fonte: apps mobile `fleti-User-app-release-3.2`, `fleti-Driver-app-release-3.2` + rotas Laravel `fleti-admin-new-install-3.2`.

---

## Autenticação (Passport Bearer)

### Cliente — `/api/customer/auth/*`

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| POST | `/api/customer/auth/login` | Login `{phone_or_email, password}` |
| POST | `/api/customer/auth/registration` | Cadastro multipart |
| POST | `/api/customer/auth/send-otp` | Enviar OTP |
| POST | `/api/customer/auth/otp-verification` | Verificar OTP |
| POST | `/api/customer/auth/firebase-otp-verification` | OTP Firebase |
| POST | `/api/customer/auth/reset-password` | Redefinir senha |
| POST | `/api/customer/auth/registration-from-otp` | Completar cadastro OTP |
| POST | `/api/customer/auth/update-data` | Atualizar dados OTP |
| POST | `/api/customer/auth/check` | Verificar se usuário existe |

### Motorista — `/api/driver/auth/*`

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| POST | `/api/driver/auth/login` | Login |
| POST | `/api/driver/auth/registration` | Cadastro multipart + documentos |
| POST | `/api/driver/auth/send-otp` | OTP |
| POST | `/api/driver/auth/otp-verification` | Verificar OTP |
| POST | `/api/driver/auth/firebase-otp-verification` | Firebase OTP |
| POST | `/api/driver/auth/reset-password` | Redefinir senha |
| POST | `/api/driver/auth/registration-from-otp` | Cadastro via OTP |
| POST | `/api/driver/auth/update-data` | Atualizar dados |
| POST | `/api/driver/auth/check` | Verificar usuário |
| POST | `/api/driver/verify-or-set-password-for-biometric` | Senha biométrica |

### Compartilhado — `/api/user/*` (auth:api)

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| POST | `/api/user/logout` | Logout |
| POST | `/api/user/delete` | Excluir conta |
| POST | `/api/user/change-password` | Alterar senha |
| PUT | `/api/user/read-notification` | Marcar notificação lida |
| POST | `/api/user/store-live-location` | Enviar GPS |

---

## Configuração

| Método | Endpoint | Público | Descrição |
|--------|----------|---------|-----------|
| GET | `/api/customer/configuration` | Sim | Config completa cliente (WS, mapas, gateways) |
| GET | `/api/driver/configuration` | Sim | Config motorista |
| GET | `/api/configurations` | Sim | Config global |
| GET | `/api/customer/config/get-zone-id?lat=&lng=` | Sim | Resolver zona |
| GET | `/api/customer/config/geocode-api?lat=&lng=` | Sim | Geocoding reverso |
| GET | `/api/customer/config/place-api-autocomplete?search_text=` | Sim | Busca endereço |
| GET | `/api/customer/config/place-api-details?placeid=` | Sim | Detalhes do lugar |
| GET | `/api/customer/config/get-payment-methods` | Sim | Gateways de pagamento |
| GET | `/api/customer/config/cancellation-reason-list` | Sim | Motivos cancelamento |
| GET | `/api/driver/config/*` | Sim | Equivalentes motorista |
| GET | `/api/v1/payment-config` | Sim | Config gateways (módulo Gateways) |

---

## Corridas e entregas

### Cliente

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| POST | `/api/customer/ride/get-estimated-fare` | Estimativa de preço |
| POST | `/api/customer/ride/create` | Criar corrida/entrega |
| GET | `/api/customer/ride/details/{id}` | Detalhes |
| POST | `/api/customer/ride/update-status/{id}` | Cancelar/atualizar |
| POST | `/api/customer/ride/trip-action` | Aceitar/recusar lance |
| GET | `/api/customer/ride/bidding-list/{id}` | Lances de motoristas |
| GET | `/api/customer/ride/list` | Histórico |
| GET | `/api/customer/ride/ride-resume-status` | Corrida ativa |
| GET | `/api/customer/ride/final-fare` | Tarifa final |
| GET | `/api/customer/ride/payment` | Pagamento cash/wallet |
| GET | `/api/customer/ride/digital-payment` | Checkout digital (web) |
| GET | `/api/customer/drivers-near-me` | Motoristas próximos |
| POST | `/api/customer/config/get-routes` | Rota/ETA restante |
| GET | `/api/get-direction` | Polilinha |

### Motorista

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/api/driver/ride/pending-ride-list` | Chamadas pendentes |
| GET | `/api/driver/ride/details/{id}` | Detalhes |
| POST | `/api/driver/ride/trip-action` | Aceitar `{action: accepted}` / recusar |
| POST | `/api/driver/ride/update-status` | Etapas da corrida |
| PUT | `/api/driver/ride/update-to-out-for-pickup/{id}` | Saiu para coleta |
| POST | `/api/driver/ride/match-otp` | Validar OTP |
| POST | `/api/driver/ride/arrival-time` | Chegada |
| POST | `/api/driver/ride/coordinate-arrival` | Chegou ao destino |
| POST | `/api/driver/ride/bid` | Lance |
| GET | `/api/driver/ride/final-fare` | Tarifa final |
| GET | `/api/driver/ride/payment` | Confirmar pagamento |
| GET | `/api/driver/ride/list` | Histórico |
| GET | `/api/driver/ride/overview` | Resumo |
| POST | `/api/driver/update-online-status` | Online/offline |
| POST | `/api/driver/get-routes` | Rota ativa |

### Parcel (entrega)

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/api/customer/parcel/category` | Categorias |
| GET | `/api/customer/parcel/suggested-vehicle-category` | Veículo sugerido |
| POST | `/api/customer/parcel/refund/create` | Reembolso |

---

## Carteira, saques e transações

### Cliente

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/api/customer/info` | Perfil + saldo |
| GET | `/api/customer/transaction/list` | Transações |
| GET | `/api/customer/wallet/bonus-list` | Bônus recarga |
| GET | `/api/customer/wallet/add-fund-digitally` | Recarga digital |
| POST | `/api/customer/loyalty-points/convert` | Converter pontos |

### Motorista

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/api/driver/info` | Perfil |
| GET | `/api/driver/transaction/list` | Transações |
| GET | `/api/driver/transaction/wallet-list` | Carteira |
| GET | `/api/driver/transaction/payable-list` | A receber |
| GET | `/api/driver/withdraw/methods` | Métodos saque |
| GET | `/api/driver/withdraw-method-info/list` | Contas cadastradas |
| POST | `/api/driver/withdraw-method-info/create` | Cadastrar conta |
| POST | `/api/driver/withdraw/request` | Solicitar saque |
| GET | `/api/driver/withdraw/pending-request` | Saques pendentes |
| GET | `/api/driver/withdraw/settled-request` | Saques pagos |
| GET | `/api/driver/income-statement` | Extrato |
| GET | `/api/driver/pay-digitally` | Pagamento digital |
| GET | `/api/driver/activity/daily-income` | Ganhos do dia |

### Planos (assinatura)
**Não há endpoint de plano mensal/anual** no backend atual. Gamificação: `GET /api/driver/level`, `GET /api/customer/level`.

---

## Chat e suporte

| Método | Endpoint | App |
|--------|----------|-----|
| POST | `/api/customer/chat/create-channel` | Cliente |
| GET | `/api/customer/chat/conversation` | Cliente |
| POST | `/api/customer/chat/send-message` | Cliente |
| POST | `/api/customer/chat/create-channel-with-admin` | Cliente |
| POST | `/api/customer/chat/send-message-to-admin` | Cliente |
| POST | `/api/customer/chat/submit-service-request` | Cliente |
| POST | `/api/driver/chat/*` | Motorista (equivalente) |

---

## WebSocket (Pusher/Reverb)

Auth: `POST https://{websocket_url}/broadcasting/auth` + Bearer token

### Canais cliente
- `private-driver-trip-accepted.{tripId}`
- `private-driver-trip-started.{tripId}`
- `private-driver-trip-completed.{tripId}`
- `private-customer-ride-chat.{tripId}`

### Canais motorista
- `private-customer-trip-request.{driverId}`
- `private-customer-trip-cancelled.{tripId}.{driverId}`
- `private-driver-ride-chat.{tripId}`

Config via `GET /api/*/configuration`: `websocket_url`, `websocket_port`, `websocket_key`, `websocket_scheme`.

---

## Pagamentos web (redirect)

Gateways em `/payment/{gateway}/pay` — EFI PIX, MercadoPago PIX, Stripe, PayPal, etc.  
Lista: `Modules/Gateways/Library/Constant.php`

---

## Perfil e documentos (motorista)

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/api/driver/info` | Status da conta |
| POST | `/api/driver/update/profile` | Atualizar perfil multipart |
| POST | `/api/driver/vehicle/store` | Cadastrar veículo |
| GET | `/api/driver/vehicle/brand/list` | Marcas |
| GET | `/api/driver/vehicle/model/list` | Modelos |
| POST | `/api/driver/face-verification/verify` | Verificação facial |

---

## Rastreamento público

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/api/track/{trackingId}/data` | Localização em tempo real (polling) |
