# MAPS & ZONE AUDIT — Fleti Log v3.2 (FASE 006)

**Data:** 2026-06-26  
**Escopo:** Admin zonas + Google Maps (apps via proxy API)

---

## 1. Resumo

| Área | Status antes | Status após FASE 006 |
|------|--------------|----------------------|
| Editor zona (index) | WARN | OK — correções JS |
| Editor zona (edit) | WARN | OK — correções JS |
| Validação 3 pontos | Parcial (só UI vazia) | OK — JS + backend |
| Formato coordenadas | **BUG** | OK — formatação correta |
| Null guard polígono | **BUG** | OK — `safeClearPolygon` |
| Fallback geolocalização | Bangladesh fixo | OK — São Paulo + geo error |
| Google Maps API versão | v3.50 (pinned) | Mantido |
| PIX / Maps mobile | Via backend proxy | OK no código |

---

## 2. Arquivos do editor de zonas

| Arquivo | Função |
|---------|--------|
| `Modules/ZoneManagement/Resources/views/admin/zone/index.blade.php` | Criar zona |
| `Modules/ZoneManagement/Resources/views/admin/zone/edit.blade.php` | Editar zona |
| `public/assets/admin-module/js/zone-management/zone/map-zone-utils.js` | **Novo** — helpers compartilhados |
| `public/assets/admin-module/js/zone-management/zone/index.js` | Form keydown |
| `Modules/ZoneManagement/Http/Requests/ZoneStoreUpdateRequest.php` | Validação server |
| `Modules/ZoneManagement/Service/ZoneService.php` | `createPoint()` → Polygon MySQL |
| `Modules/ZoneManagement/Http/Controllers/Web/Admin/ZoneController.php` | CRUD web |

---

## 3. Google Maps — configuração

### Admin (Blade)

```html
maps.googleapis.com/maps/api/js?key={{ map_api_key }}&libraries=drawing,places&v=3.50
```

- **API Key:** `businessConfig(GOOGLE_MAP_API)->value['map_api_key']`
- **Libraries:** `drawing` (DrawingManager), `places` (SearchBox)
- **Versão:** `v=3.50` (fixada — boa prática)

### Apps mobile (proxy backend)

| API App | Endpoint backend | Controller |
|---------|------------------|------------|
| User geocode | `/api/customer/config/geocode-api` | `ConfigController@geocodeApi` |
| User places | `/api/customer/config/place-api-autocomplete` | `placeApiAutocomplete` |
| User routes | `/api/customer/config/get-routes` | distance/directions |
| Driver geocode | `/api/driver/config/geocode-api` | Driver `ConfigController` |
| Detectar zona | `/api/customer/config/get-zone-id` | zone point-in-polygon |

**Server key:** `map_api_key_server` em `business_settings` (GOOGLE_MAP_API)

### Driver app — risco conhecido

- `polylineMapKey` hardcoded em `app_constants.dart` — **não corrigido nesta fase** (fora escopo zonas admin)

---

## 4. Bugs encontrados e correções (FASE 006)

### 4.1 `lastPolygon.setMap(null)` sem null check

**Sintoma:** Erro JS ao clicar Reset antes de desenhar polígono.

**Correção:** `FletiZoneMap.safeClearPolygon()` em index e edit.

### 4.2 Coordenadas salvas em formato inválido

**Sintoma:** `$('#coordinates').val(event.overlay.getPath().getArray())` serializava array LatLng incorretamente para o parser PHP `createPoint()`.

**Formato esperado pelo backend:**

```
(lat,lng),(lat,lng),(lat,lng)
```

**Correção:** `FletiZoneMap.formatPathToCoordinates()` → `(lat,lng)` pairs joined by comma.

### 4.3 Validação mínimo 3 pontos ausente

**Antes:** Apenas verificava textarea vazio.

**Correção:**
- JS: `validateFormSubmit(e, 3)` no submit
- PHP: closure em `ZoneStoreUpdateRequest` conta pares `(x,y)`

### 4.4 `auto_grow()` indefinido em index

**Sintoma:** `auto_grow is not defined` após desenhar zona.

**Correção:** `FletiZoneMap.autoGrow()` no utilitário compartilhado.

### 4.5 Centro do mapa padrão incorreto

**Antes:** Dhaka, Bangladesh (`23.757989, 90.360587`).

**Depois:** São Paulo, BR (`-23.55052, -46.633308`) + geolocation com error handler.

### 4.6 Listener duplicado em edit

**Antes:** Dois `overlaycomplete` listeners (um inútil).

**Depois:** Listener único com validação de tipo POLYGON.

---

## 5. Fluxo salvar zona (preservado)

```
Admin desenha polígono (DrawingManager)
  → textarea #coordinates formatado
  → POST admin.zone.store / update
  → ZoneStoreUpdateRequest (name + coordinates + min 3 pts)
  → ZoneService::createPoint()
  → Polygon MySQL (coluna coordinates)
  → zones table
```

**Nenhum controller ou rota alterado.** Apenas JS, validação e request.

---

## 6. APIs Maps utilizadas

| API Google | Uso | Onde |
|------------|-----|------|
| Maps JavaScript API | Admin zone editor | index/edit blade |
| Drawing Library | Polygon draw | DrawingManager |
| Places API | SearchBox localização | pac-input |
| Geocoding API | Reverse geocode apps | ConfigController |
| Places Autocomplete | Busca endereço apps | ConfigController |
| Directions/Distance | Rotas apps | get-routes |
| Geometry (contains) | Point in zone | ZoneRepository |

---

## 7. Banco de dados — zonas

| Campo | Tipo | Nota |
|-------|------|------|
| `zones.coordinates` | POLYGON | Nullable — risco se vazio |
| `zones.name` | varchar | Unique |
| `zones.readable_id` | int | ID legível |
| `zones.is_active` | tinyint | Status |
| `zones.extra_fare_*` | vários | Tarifa extra por zona |

Spatial queries: `whereContains('coordinates', $point)` em `ZoneRepository`.

---

## 8. O que NÃO foi alterado (Master Plan)

- [x] Tela admin preservada
- [x] Controllers de negócio intactos
- [x] Rotas intactas
- [x] `ZoneService::createPoint()` intacto
- [x] Sem remoção de funcionalidades
- [x] Sem alteração de apps Flutter nesta fase

---

## 9. Checklist operacional admin

Para zonas funcionarem em produção:

1. **Admin → 3rd Party → Google Map API** — configurar `map_api_key` (JS) e `map_api_key_server` (backend/apps)
2. Habilitar APIs no Google Cloud Console:
   - Maps JavaScript API
   - Places API
   - Geocoding API
   - Directions API
   - Distance Matrix API
3. Restringir keys por domínio/IP
4. Desenhar zonas com mínimo 3 pontos
5. Ativar zonas (`is_active = 1`)

---

## 10. Próxima etapa

**FASE 007 — Design System Fleti** (visual isolado) ou correção bugs FASE 005 (`otpLogin`, `ignoreMessage`).
