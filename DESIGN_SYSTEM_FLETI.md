# DESIGN SYSTEM FLETI — Enterprise v4.0 (FASE 007)

Documentação do padrão visual moderno da Fleti. **Somente camada visual** — sem alteração de rotas, controllers, services ou regras de negócio.

## Princípios

| Regra | Aplicação |
|-------|-----------|
| Layout mais limpo | Menos bordas pesadas, cards flat |
| Menos sombra | `elevation: 0`, `box-shadow: none` |
| Bordas suaves | Radius 5–15px (Flutter) / 0.375–0.875rem (Admin) |
| Containers slim | Padding moderado, hierarquia clara |
| Tipografia legível | SF Pro Text (apps), pesos 400–700 |
| Responsividade | Breakpoints preservados nos apps e admin |

## Paleta (alinhada ao v3.2)

| Token | User App | Driver App | Admin |
|-------|----------|------------|-------|
| Primary | `#14B19E` | `#00A08D` | `--bs-primary: #14b19e` |
| Error | `#FF6767` | `#FF6767` | `--bs-danger: #ff6d6d` |
| Success | — | — | `#30b877` |
| Surface | `#F3F3F3` | `#F3F3F3` | `#ffffff` |
| Title | — | — | `#293231` |

---

## Flutter — User & Driver Apps

### Estrutura

```
lib/theme/fleti_design_tokens.dart
lib/common_widgets/modern/
  modern.dart              # barrel export
  modern_card.dart
  modern_button.dart
  modern_text_field.dart
  modern_container.dart
  modern_bottom_sheet.dart
  modern_dialog.dart
  modern_badge.dart
  modern_chip.dart
  modern_status.dart
  modern_loading.dart
  modern_dashboard_card.dart
```

### Import

```dart
import 'package:ride_sharing_user_app/common_widgets/modern/modern.dart';
```

### Componentes

| Componente | Uso |
|------------|-----|
| `ModernCard` | Container com borda suave e padding |
| `ModernButton` | `filled` / `outlined` / `text` + loading |
| `ModernTextField` | Input com borda e focus primary |
| `ModernContainer` | Wrapper genérico slim |
| `ModernBottomSheet.show()` | Sheet com handle e título opcional |
| `ModernDialog.confirm()` | Diálogo de confirmação |
| `ModernBadge` | Label compacto colorido |
| `ModernChip` | Seleção / filtro toggle |
| `ModernStatus` | Badge semântico (success/warning/error/info) |
| `ModernLoading` | Spinner central ou overlay |
| `ModernDashboardCard` | Métrica com ícone + valor |

### Exemplo

```dart
ModernDashboardCard(
  title: 'Corridas hoje',
  value: '128',
  subtitle: '+12% vs ontem',
  icon: Icons.local_taxi,
  onTap: () {},
)

ModernButton(
  label: 'Continuar',
  onPressed: () {},
)
```

### Tokens (`FletiDesignTokens`)

- Radius: `radiusSm` 5, `radiusMd` 10, `radiusLg` 15, `radiusXl` 20
- Spacing: `spaceXs` 5 … `spaceXl` 25
- Helpers: `surface()`, `border()`, `primary()`, `onSurfaceMuted()`

---

## Admin Laravel

### Estrutura

```
public/assets/admin-module/css/fleti-design-system.css
Modules/AdminModule/Resources/views/partials/design-system/
  modern-panel-card.blade.php
  modern-admin-button.blade.php
  modern-table.blade.php
  modern-filter.blade.php
  modern-status-badge.blade.php
  modern-metric-card.blade.php
  modern-chart-card.blade.php
```

CSS carregado em `master.blade.php` após `custom.css`.

### Classes CSS

| Classe | Descrição |
|--------|-----------|
| `.fleti-panel-card` | Card flat com título |
| `.fleti-btn` | Botão base (`--primary`, `--outline`, `--ghost`) |
| `.fleti-table` | Tabela sem sombra, bordas leves |
| `.fleti-filter` | Barra de filtros responsiva |
| `.fleti-status-badge` | Badge semântico |
| `.fleti-metric-card` | Card de métrica com ícone |
| `.fleti-chart-card` | Container para gráficos |

### Exemplo Blade

```blade
@include('adminmodule::partials.design-system.modern-metric-card', [
    'label' => 'Total de corridas',
    'value' => '1.284',
    'icon' => 'bi bi-car-front',
])

@include('adminmodule::partials.design-system.modern-status-badge', [
    'label' => 'Ativo',
    'type' => 'success',
])
```

---

## Migração gradual (FASE 008+)

Os componentes estão prontos para uso. A modernização visual das telas existentes ocorre na **FASE 008** (Admin) e fases seguintes dos apps, substituindo widgets legados tela a tela sem quebrar fluxos.

## Arquivos não alterados

- Controllers, Services, Models, Routes
- `style.css` base do admin
- Lógica dos widgets legados (`ButtonWidget`, etc.)

---

*Gerado na FASE 007 — Fleti Enterprise v4.0*
