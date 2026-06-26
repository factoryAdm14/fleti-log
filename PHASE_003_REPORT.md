# RELATÓRIO DA FASE 003 — Auditoria de Rotas

## Fase executada

**FASE 003 — Auditoria de rotas (validação cruzada app ↔ backend)**

## Objetivo

Confirmar que todos os endpoints usados pelos apps Flutter existem no Laravel e identificar gaps.

## Arquivos alterados

| Arquivo | Motivo | Risco |
|---------|--------|-------|
| `fleti-Driver-app-release-3.2/lib/util/app_constants.dart` | Corrigir typo `models/list` → `model/list` | Baixo |
| `ROUTE_AUDIT.md` | Atualizar com resultados do cruzamento | Nenhum |

## Arquivos criados

- `ROUTE_CROSSCHECK.txt` — resultado detalhado do script de cruzamento

## Migrations criadas

Nenhuma.

## Resultado do cruzamento

### Backend
- **848** rotas totais (`php artisan route:list`)
- **214** rotas API únicas normalizadas

### App Usuário (93 endpoints)
- **82** match exato
- **10** match com parâmetro `{id}` (padrão esperado)
- **1** gap: `getRecentAddressList` → rota não existe no backend
  - Constante **não é usada** em nenhum outro arquivo — código morto, sem impacto runtime

### App Motorista (103 endpoints)
- **91** match exato
- **11** match com parâmetro `{id}`
- **1** gap corrigido: `vehicleModelList`
  - Antes: `/api/driver/vehicle/models/list`
  - Depois: `/api/driver/vehicle/model/list`
  - Usado em `profile_repository.dart` — **bug real** que impedia listar modelos de veículo

## Rotas wallet — 100% alinhadas

Todas as 4 rotas wallet do backend confirmadas e mapeadas nos apps (FASE 002).

## Testes executados

| Teste | Resultado |
|-------|-----------|
| Export `route:list --json` | OK |
| Script cruzamento Python | OK |
| Verificação manual gaps | OK |
| Correção `vehicleModelList` | Aplicada |

## Bugs encontrados

| Bug | Severidade | Status |
|-----|------------|--------|
| `vehicleModelList` typo models/model | Alta | Corrigido |
| `getRecentAddressList` sem rota backend | Baixa | Documentado (não usado) |
| Rotas pagamento sem auth | Média | Documentado para FASE 013 |

## Riscos

- Produção `fleti.com.br` pode ter rotas ativas mesmo sem validação local com DB real
- Constante `getRecentAddressList` pode ser implementada no futuro — requer nova rota backend

## Rollback

```bash
git checkout -- fleti-Driver-app-release-3.2/lib/util/app_constants.dart
```

## Próxima etapa recomendada

**FASE 004 — Auditoria de banco**

1. Exportar `mysqldump` do Hostinger
2. Comparar schema produção vs `database_v3.2.sql`
3. `php artisan migrate:status` com `.env` de produção
