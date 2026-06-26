# PHASE 018 Report — Deploy

**Data:** 2026-06-26  
**Branch:** `feature/fleti-enterprise-v4`  
**Status:** Concluída + deploy produção executado

## Objetivo

Documentar e executar o processo de deploy do ecossistema Fleti v4 para produção (`fleti.com.br`).

## Entregas

### Documentação

- [x] `DEPLOYMENT_GUIDE.md` — checklist, manual, rollback, troubleshooting

### Scripts

- [x] `scripts/deploy_ftp_common.py` — helpers FTP compartilhados
- [x] `scripts/deploy_production.py` — deploy por diff git + migrate
- [x] `scripts/ssh_post_deploy.py` — maintenance mode, migrate, cache, queue

### Deploy produção (2026-06-26)

| Etapa | Resultado |
|-------|-----------|
| FTP upload | 58 arquivos (FASE 013–017) |
| `php artisan down/up` | OK |
| `php artisan migrate --force` | Nothing to migrate (já aplicado) |
| `config/route/view cache` | OK |
| `queue:restart` | OK |
| `SOFTWARE_VERSION` | 3.2 |
| Laravel | 12.61.0 |

### Features em produção após deploy

| Fase | Feature |
|------|---------|
| 013 | Rate limit API, rotas debug gated, CORS configurável |
| 014 | Gateway Mercado Pago PIX |
| 015 | Gateway EFI PIX |
| 016 | Multi-stop delivery (flag OFF por padrão) |
| Hotfix | Dark mode admin + versão 3.2 |

## Pós-deploy manual (recomendado)

- [ ] Ativar credenciais PIX no Admin (se usar)
- [ ] Ativar multi-stop em Parcel Settings (se desejado)
- [ ] Testar wallet, corrida, delivery, zonas
- [ ] Build Flutter quando for release nas lojas

## Comandos usados

```bash
python3 scripts/deploy_production.py --dry-run
python3 scripts/deploy_production.py
```

## Próximo passo

**FASE 019** — Observabilidade (`OBSERVABILITY_GUIDE.md`)
