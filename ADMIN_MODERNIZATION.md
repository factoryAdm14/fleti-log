# ADMIN MODERNIZATION — Fleti Enterprise v4.0 (FASE 008)

Modernização visual do painel administrativo **sem alteração de funcionalidades**.

## Abordagem

Camada CSS global escopada em `body.fleti-admin-v4`, aplicada via `master.blade.php` a **todas** as áreas do admin:

| Área | Cobertura |
|------|-----------|
| Dashboard | Banner welcome + cards métricas + gráficos |
| Usuários / Motoristas | Cards, tabelas, tabs, busca |
| Corridas / Delivery / Parcel | Listagens, filtros, estatísticas |
| Wallet / Pagamentos | Tabelas e formulários |
| Zonas | Mapas com borda arredondada |
| Relatórios / Configurações / Logs | Layout, cards, paginação |

## Arquivos alterados

| Arquivo | Mudança |
|---------|---------|
| `public/assets/admin-module/css/fleti-admin-modern.css` | **Novo** — overrides visuais |
| `Modules/AdminModule/Resources/views/layouts/master.blade.php` | Classe `fleti-admin-v4` + link CSS |
| `Modules/AdminModule/Resources/views/dashboard.blade.php` | Banner `fleti-welcome-banner` |

## O que mudou visualmente

### Layout
- Fundo da área principal: `#f4f9f8`
- Container max-width 1440px
- Cards flat (sem sombra pesada), borda suave

### Componentes
- **Cards:** borda 1px, radius 0.875rem, padding reduzido
- **Botões:** radius 0.5rem, peso 600, sem box-shadow
- **Tabelas:** header `#f7fbfb`, hover sutil primary
- **Formulários:** inputs com focus ring primary
- **Nav tabs:** estilo pill moderno
- **Badges:** cores semânticas suaves (não sólidas)
- **Sidebar:** itens com radius, active com fundo primary 10%
- **Header:** borda inferior, sem sombra

### Dashboard
- Banner de boas-vindas em card dedicado
- Métricas e gráficos herdam estilo de card modernizado

## O que NÃO foi alterado

- Controllers, Services, Models, Routes
- Campos, botões ou telas removidos
- `style.css` base (intocado)
- Lógica JavaScript / ApexCharts / DataTables

## Stack CSS (ordem de carga)

```
style.css → custom.css → fleti-design-system.css → fleti-admin-modern.css → layouts/css.blade.php
```

## Componentes FASE 007 (disponíveis para uso incremental)

Partials em `partials/design-system/` e classes `.fleti-*` podem ser adotados tela a tela nas próximas iterações.

## Rollback

Remover do `master.blade.php`:
- classe `fleti-admin-v4` do `<body>`
- link `fleti-admin-modern.css`

---

*FASE 008 — Fleti Enterprise v4.0*
