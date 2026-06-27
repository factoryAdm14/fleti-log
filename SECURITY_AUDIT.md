# SECURITY AUDIT — Fleti Enterprise v4.0 (FASE 013)

Auditoria de segurança do ecossistema **Fleti** (Laravel admin/API, apps Flutter User/Driver, produção `fleti.com.br`).

**Data:** 2026-06-26  
**Branch:** `feature/fleti-enterprise-v4`

---

## Resumo executivo

| Severidade | Qtd | Status |
|------------|-----|--------|
| Crítica | 4 | 2 corrigidas nesta fase, 2 em backlog |
| Alta | 12 | 3 corrigidas, 9 documentadas |
| Média | 15+ | Documentadas |
| Baixa | Várias | Documentadas |

---

## Correções aplicadas (FASE 013)

| Item | Arquivo | Mudança |
|------|---------|---------|
| API rate limit | `bootstrap/app.php` | `throttle:1000,1` → `throttle:api` (60/min por IP/user) |
| `store-configurations` aberto | `ConfigurationController.php` | Validação + tokens Mart obrigatórios |
| Rate limit store-config | `BusinessManagement/Routes/api.php` | `throttle:20,1` |
| Rotas debug públicas | `routes/web.php` | Só em `local` ou `APP_DEBUG=true` |
| CORS permissivo | `config/cors.php` | `CORS_ALLOWED_ORIGINS` via `.env` |
| Chaves OAuth no git | `.gitignore` | `storage/oauth-*.key` |

---

## 1. LGPD (Lei Geral de Proteção de Dados)

| Requisito | Status | Notas |
|-----------|--------|-------|
| Política de privacidade | Parcial | `/privacy` no landing — conteúdo editável no admin |
| Consentimento / finalidade | Documentar | Dados de corrida, localização, pagamento, documentos motorista |
| Direito de exclusão | Existe | API `permanentDelete` / soft-delete users |
| Minimização de dados | Parcial | Localização em tempo real — revisar retenção |
| DPO / canal de contato | Verificar | Página contact-us disponível |
| Logs com PII | Risco | `LOG_LEVEL=debug` em dev — usar `warning` em produção |
| Transferência internacional | Verificar | Hostinger, Firebase, gateways pagamento |

**Ação recomendada:** Revisar textos legais no admin e registrar base legal por tipo de dado (contrato, legítimo interesse).

---

## 2. CORS

| Antes | Depois |
|-------|--------|
| `allowed_origins: ['*']` | Configurável via `CORS_ALLOWED_ORIGINS` |

**Produção sugerida:**

```env
CORS_ALLOWED_ORIGINS=https://fleti.com.br,https://www.fleti.com.br
```

Apps mobile nativos não são afetados por CORS.

---

## 3. CSRF

| Área | Status |
|------|--------|
| Formulários web admin | `@csrf` presente |
| API Passport | Sem CSRF (esperado) |
| `external-login-from-mart` | **Excluído do CSRF** — risco se tokens vazarem |
| Callbacks pagamento | CSRF desabilitado — OK com assinatura |

**Backlog:** Revisar `VerifyCsrfToken` exceptions; Mart login via token assinado one-time.

---

## 4. XSS

| Vetor | Severidade | Local |
|-------|------------|-------|
| CMS landing `{!! !!}` | Alta | `resources/views/landing-page/*` |
| Blog HTML | Alta | `blog/details.blade.php` |
| Termos/privacidade | Alta | DB → Blade sem escape |
| Toastr messages | Baixa | Framework |

**Mitigação:** Apenas admins de confiança editam CMS; considerar HTMLPurifier em fase futura.

---

## 5. SQL Injection

| Local | Risco | Status |
|-------|-------|--------|
| Eloquent parametrizado | Baixo | Padrão do projeto |
| `DB::raw` com coordenadas | Médio | `TripRequestController`, `TripRequestCoordinate` |
| `groupColumn` em repositories | Baixo | Whitelist recomendado |

**Backlog:** Bindings em queries espaciais.

---

## 6. Rate limiting

| Endpoint | Limite |
|----------|--------|
| API geral | **60 req/min** (após fix) |
| `store-configurations` | **20 req/min** |
| OTP / login | Timers de negócio — adicionar throttle por IP (backlog) |

---

## 7. Autenticação & autorização

| Componente | Tecnologia |
|------------|------------|
| API mobile | Laravel **Passport** (`auth:api`) |
| Admin web | Session + `admin` middleware |
| Permissões | Gates + `$this->authorize()` (parcial) |

### Lacunas

- **Dashboard / Fleet Map:** sem `authorize()` no controller (só `@can` na view)
- **2FA admin:** **não existe** (apenas captcha/reCAPTCHA)
- **OTP fixo `0000`:** quando `APP_MODE != live` — garantir `live` em produção
- **Trip OTP** exposto em `TripRequestResource` — necessário para fluxo app

### Mart / integração externa

- `POST /api/store-configurations` — **agora exige** `mart_token`, `mart_base_url`, `drivemond_token` válidos
- `external-login-from-mart` — login super-admin se tokens corretos — **rotacionar tokens**

---

## 8. Webhooks & pagamentos

| Gateway | Verificação assinatura |
|---------|------------------------|
| SSLCommerz, Razorpay, Paytabs, Paymob, PayPal, Paystack, Flutterwave | OK |
| **SenangPay** | **Crítico — sem hash** |
| **Pvit** | **Crítico — só status HTTP** |
| bKash | Médio |

**Ação:** Desabilitar gateways não usados no admin; implementar verificação oficial antes de ativar SenangPay/Pvit.

---

## 9. Uploads & arquivos

- `fileUploader()` — validação por extensão + re-encode GD (JPEG/PNG)
- GIF/WebP copiados sem re-encode — risco polyglot
- FormRequests com `mimes` nos perfis — OK
- **Servidor:** `storage/app/public` não deve executar PHP

---

## 10. Tokens & secrets

| Item | Risco |
|------|-------|
| Google Maps API keys nos apps | Restringir por package/bundle no Google Cloud |
| Passport keys em `storage/` | Adicionado ao `.gitignore` |
| `.env` produção | Nunca no git; `APP_DEBUG=false` |
| Tokens em `SharedPreferences` (Flutter) | Padrão mobile — usar Keychain/Keystore (backlog) |

---

## 11. Firebase

- FCM configurado via admin + `firebase-messaging-sw.js`
- Validar regras Firebase Console (fora do escopo código)

---

## 12. Logs sensíveis

```env
LOG_LEVEL=warning   # produção
APP_DEBUG=false     # produção
```

Evitar logar payloads de pagamento, senhas, tokens OTP.

---

## 13. Apps Flutter

| Check | Status |
|-------|--------|
| HTTPS baseUrl | `https://fleti.com.br` |
| Tokens em memória GetX/SharedPreferences | Padrão v3.2 |
| Cert pinning | Não implementado (backlog) |
| API keys hardcoded | Google Maps — restringir no console |

---

## Checklist produção Fleti

- [ ] `APP_DEBUG=false` e `APP_MODE=live`
- [ ] `LOG_LEVEL=warning`
- [ ] `CORS_ALLOWED_ORIGINS` restrito
- [ ] Rotas `/sms-test`, `/test`, `/update-data-test` inacessíveis (confirmado com debug off)
- [ ] Gateways não usados desativados
- [ ] Tokens Mart rotacionados
- [ ] Restrição API keys Google (Android/iOS/HTTP referrer)
- [ ] Backup `.env` fora do webroot
- [ ] Cron + queue workers seguros

---

## Roadmap segurança (pós FASE 013)

| Prioridade | Item |
|------------|------|
| P0 | Assinatura SenangPay + Pvit |
| P1 | 2FA TOTP para admin |
| P1 | `authorize()` em Dashboard/FleetMap |
| P1 | HTMLPurifier no CMS |
| P2 | Cert pinning apps |
| P2 | Throttle em `send-otp` / login |

---

*FASE 013 — Fleti Enterprise v4.0*
