# USER APP MODERNIZATION — Fleti Enterprise v4.0 (FASE 009)

Modernização visual do app usuário **sem alteração de fluxos, endpoints ou lógica**.

## Abordagem

1. **Tema global** — `applyFletiModernTheme()` em light/dark theme
2. **Decorações compartilhadas** — `FletiModernDecorations`
3. **Shell de tela** — `BodyWidget` (Home, Wallet, Perfil, Histórico, etc.)
4. **Widgets-chave** — busca, carteira, transações, categorias
5. **Sombras** — `styles.dart` com elevação mais suave

## Arquivos alterados

| Arquivo | Mudança |
|---------|---------|
| `lib/theme/fleti_theme_modern.dart` | **Novo** — Card, Button, Input, Dialog, Sheet, AppBar |
| `lib/theme/fleti_modern_decorations.dart` | **Novo** — card, bodyPanel, pill, sheet |
| `lib/theme/light_theme.dart` | Aplica tema moderno |
| `lib/theme/dark_theme.dart` | Aplica tema moderno |
| `lib/util/styles.dart` | Sombras flat |
| `lib/common_widgets/body_widget.dart` | Painel superior com borda suave |
| `lib/features/home/widgets/home_search_widget.dart` | Campo de busca moderno |
| `lib/features/wallet/widget/wallet_money_amount_widget.dart` | Card saldo flat |
| `lib/features/wallet/widget/transaction_card_widget.dart` | Cards de transação |
| `lib/common_widgets/category_widget.dart` | Ícones de categoria com borda |

## Áreas cobertas

| Área | Como |
|------|------|
| Home | BodyWidget + busca + categorias + tema |
| Mapa | BodyWidget / tema em telas de mapa |
| Busca | `HomeSearchWidget` + `inputDecorationTheme` |
| Corrida / Delivery / Parcel | BodyWidget + cards via tema |
| Wallet | Saldo + transações + FAB preservado |
| Adicionar Saldo | Botão `+` em `WalletMoneyAmountWidget` **mantido** |
| Perfil / Histórico / Cupons / Notificações | BodyWidget + tema global |

## Garantias (Master Plan)

- Botão **Adicionar Saldo** não removido (`walletAddFundStatus` + `AddFundDialog`)
- Fluxo de pagamento inalterado
- Endpoints inalterados
- SafeArea preservado (`WalletScreen`, etc.)
- Responsividade via `Dimensions` existentes

## Componentes FASE 007 disponíveis

`lib/common_widgets/modern/` — `ModernCard`, `ModernButton`, etc. para adoção incremental.

## Build

```bash
cd fleti-User-app-release-3.2
flutter analyze
flutter build apk   # ou ios
```

---

*FASE 009 — Fleti Enterprise v4.0*
