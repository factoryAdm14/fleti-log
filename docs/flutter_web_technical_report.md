# Relatório Técnico — Flutter Web Cliente e Motorista Fleti

**Data:** 2026-06-26  
**Escopo:** Dois apps Flutter Web (`apps/client_web_flutter`, `apps/driver_web_flutter`) consumindo o backend Laravel existente em `https://fleti.com.br`.

---

## 1. Stack atual mapeada

| Camada | Tecnologia |
|--------|------------|
| Backend | Laravel 12 (`fleti-admin-new-install-3.2`) |
| API | REST em `/api/customer/*`, `/api/driver/*`, `/api/user/*` |
| Autenticação | Laravel Passport — Bearer token (`auth:api`) |
| Banco | MySQL (via Laravel) |
| Tempo real | Laravel Reverb / Pusher (`dart_pusher_channels` nos apps mobile) |
| Push | Firebase Cloud Messaging (mobile; web usará WS + polling) |
| Mapas | Google Maps (chave via `/api/*/configuration`) |
| Pagamentos | Gateways web (`/payment/*`) — EFI PIX, MercadoPago PIX, Stripe, etc. |

---

## 2. Arquivos existentes — impacto

### Não serão alterados (regra do roteiro)
- `fleti-admin-new-install-3.2/` — painel admin e API (apenas consumo)
- `fleti-User-app-release-3.2/` — app mobile cliente
- `fleti-Driver-app-release-3.2/` — app mobile motorista

### Novos (criados neste projeto)
```
docs/
  flutter_web_api_map.md
  flutter_web_technical_report.md
apps/
  client_web_flutter/
  driver_web_flutter/
shared_flutter/
  lib/core/
  lib/services/
  lib/models/
  lib/widgets/
  lib/theme/
```

---

## 3. Riscos e mitigações

| Risco | Severidade | Mitigação |
|-------|------------|-----------|
| **CORS** — domínios web novos bloqueados | Alta | Adicionar `cliente.fleti.com.br` e `motorista.fleti.com.br` em `CORS_ALLOWED_ORIGINS` no `.env` de produção |
| **Google Maps** — domínio não autorizado | Alta | Registrar subdomínios no Google Cloud Console; usar chave do `configuration` API |
| **FCM** — não disponível no browser | Média | WebSocket (Pusher/Reverb) + polling controlado como fallback |
| **`/api/v1/` vs `/api/`** — config retorna `base_url` com v1, rotas reais sem v1 | Média | Apps web usam paths idênticos aos mobile (`/api/customer/...`) |
| **Pagamento digital** — redirect/WebView no mobile | Alta | Web abre checkout em nova aba ou iframe; callbacks HTTPS obrigatórios |
| **Upload de documentos (motorista)** | Média | `multipart/form-data` via `http` package; validar tamanho/extensão no cliente |
| **Geolocalização** — permissão do navegador | Média | `geolocator_web`; fallback para seleção manual no mapa |
| **Planos mensal/anual** | Alta | **Não há API de assinatura** no backend atual; tela placeholder + integração futura documentada |
| **Expor chaves** | Alta | Apenas `websocket_key` e `map_api_key` públicas (já expostas no mobile); nunca secret keys de pagamento |

---

## 4. Dependências externas (pub)

| Pacote | Uso |
|--------|-----|
| `http` | Cliente REST |
| `go_router` | Rotas web + deep links |
| `shared_preferences` | Token/sessão (web) |
| `provider` | Estado global leve |
| `geolocator` | GPS no browser |
| `google_maps_flutter` | Mapas (requer config web) |
| `dart_pusher_channels` | WebSocket (fase posterior) |

---

## 5. Plano de implementação por fases

| Fase | Entrega | Status |
|------|---------|--------|
| 1 | Diagnóstico + `flutter_web_api_map.md` | Concluído |
| 2 | Scaffold apps + `shared_flutter` | Concluído |
| 4 | Acompanhamento + histórico (cliente) | Concluído (polling) |
| 5 | Online/offline + chamadas (motorista) | Concluído (polling) |
| 6 | Pagamentos Pix/cartão | Concluído (checkout em nova aba) |
| 7 | WebSocket Reverb/Pusher | Concluído (+ fallback polling) |
| 8 | Carteira/saque motorista | Concluído |
| 9 | Build + deploy (`/client`, `/driver`) | Concluído (27/06/2026) |

---

## 6. Deploy

**Produção (27/06/2026):**

| App | URL |
|-----|-----|
| Cliente | https://fleti.com.br/client/ |
| Motorista | https://fleti.com.br/driver/ |

Arquivos em `public_html/client/` e `public_html/driver/` (FTP via `scripts/deploy_web_apps.py`).

**Subdomínios (opcional, futuro):**

```
cliente.fleti.com.br   → alias para /client/
motorista.fleti.com.br → alias para /driver/
```

Comandos:
```bash
python3 scripts/deploy_web_apps.py              # build + deploy
python3 scripts/deploy_web_apps.py --build-only  # só build
python3 scripts/deploy_web_apps.py --deploy-only # só FTP (após build)
```

---

## 7. Configurações de produção necessárias (pós-build)

1. `CORS_ALLOWED_ORIGINS` — incluir subdomínios web
2. Google Maps — autorizar domínios
3. Reverb/Pusher — `broadcasting/auth` com Bearer token
4. HTTPS obrigatório
5. Callbacks de pagamento apontando para URLs web
