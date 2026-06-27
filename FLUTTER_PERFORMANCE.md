# FLUTTER PERFORMANCE — Fleti Enterprise v4.0 (FASE 012)

Auditoria e otimizações nos apps **User** e **Driver** (`fleti-User-app-release-3.2`, `fleti-Driver-app-release-3.2`).

## Resumo

| Área | Antes | Depois |
|------|-------|--------|
| Home categorias | `ListView` aninhado + `shrinkWrap` | `ListView.builder` único |
| Imagens rede (User) | `CachedNetworkImage` sem limite decode | `memCacheWidth/Height` |
| Imagens rede (Driver) | `FadeInImage` (sem cache disco) | `CachedNetworkImage` + cache |
| GPS stream | Updates contínuos `high` | `medium` + `distanceFilter: 10m` |
| Listas horizontais | Sem `cacheExtent` | `FletiPerformanceConfig.listCacheExtent` |

---

## Arquivos novos

```
lib/util/fleti_performance_config.dart
lib/helper/fleti_performance_helper.dart
```

Constantes centrais: cache de lista, tamanho máximo de decode de imagem, filtro GPS.

---

## Correções implementadas

### 1. `CategoryView` (User — Home)

**Problema:** `ListView` pai com `ListView.builder` filho e `NeverScrollableScrollPhysics` — dupla árvore de scroll, layout custoso.

**Solução:** Um único `ListView.builder` com categorias + parcel + agendamento.

### 2. `ImageWidget`

**User:** `memCacheWidth` / `memCacheHeight` baseados no tamanho lógico × DPR (máx. 1200px).

**Driver:** migração para `cached_network_image` (^3.4.1) com mesma estratégia de memória.

### 3. Localização (bateria)

Streams ajustados em:

- User: `location_controller.dart`
- Driver: `location_controller.dart`, `map_screen.dart`

```dart
LocationSettings(
  accuracy: LocationAccuracy.medium,
  distanceFilter: 10, // metros
)
```

`getCurrentPosition` mantém `LocationAccuracy.high` para precisão pontual.

### 4. `RideCategoryWidget` + `MyActivityListViewWidget`

`cacheExtent: 320` em listas horizontais.

---

## Auditoria — itens documentados (backlog)

| Item | Apps | Notas |
|------|------|-------|
| `FutureBuilder` em auth/onboard | User | 7 telas — aceitável em fluxos curtos |
| `GetBuilder` amplo | Ambos | Rebuild de telas inteiras — migrar para `GetBuilder` granular em fases futuras |
| `shrinkWrap: true` em listas longas | User wallet, offers | OK com paginação; evitar em listas >50 itens sem builder |
| Assets PNG/JPG | Ambos | Revisar compressão manual (`assets/`) — não alterado nesta fase |
| Background location | Driver | Depende de permissões OS + `geolocator`; stream já otimizado |
| Mapa + polling API | Ambos | Intervalos definidos nos controllers — não alterados (fluxo) |

---

## Boas práticas Fleti (manutenção)

1. Preferir `ListView.builder` / `ListView.separated` para listas dinâmicas.
2. Usar `ImageWidget` para URLs — nunca `Image.network` direto.
3. Evitar `FutureBuilder` dentro de `FutureBuilder` em telas de lista.
4. Usar `const` em widgets estáticos quando possível.
5. Paginação: manter `PaginatedListWidget` (User wallet/histórico).
6. GPS: não usar `LocationAccuracy.best` em streams contínuos.

---

## Validação

```bash
cd fleti-User-app-release-3.2 && flutter analyze
cd fleti-Driver-app-release-3.2 && flutter pub get && flutter analyze
```

Testar manualmente:

- Home → categorias scroll horizontal
- Wallet → transações e imagens
- Mapa → marker acompanha com suavidade (Driver)
- Adicionar Saldo → botão e fluxo intactos

---

## Próxima fase

**FASE 013** — Segurança (`SECURITY_AUDIT.md`).

---

*FASE 012 — Fleti Enterprise v4.0*
