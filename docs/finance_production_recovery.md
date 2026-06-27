# Recuperação — Deploy financeiro em produção

**Status:** upload concluído, migrations pendentes, site pode retornar HTTP 500 até correção manual.

## O que foi feito

1. **114 arquivos** enviados via FTP (`scripts/deploy_finance_remote.py`)
   - Módulo `Modules/FinanceManagement/` completo
   - Hooks, gateways, sidebar, bootstrap, etc.

2. **Migrations NÃO aplicadas** — `php artisan migrate` falha no servidor (exit 255 silencioso)

3. **Composer bloqueado** na Hostinger:
   ```
   DISEVAL - Use of eval is forbidden in composer2.phar
   ```

## Causa provável do HTTP 500

- **PHP 8.3 na Hostinger causa segfault** em `php artisan` e no HTTP kernel
- **Solução:** usar PHP **8.2** (CLI e web)

### Fix PHP 8.2 (web)

Adicionar no topo do `.htaccess` em `public_html`:

```apache
# PHP 8.2 (Hostinger)
<IfModule mime_module>
  AddHandler application/x-httpd-alt-php82 .php
</IfModule>
```

### Fix PHP 8.2 (CLI / artisan)

```bash
/opt/alt/php82/usr/bin/php artisan migrate --path=Modules/FinanceManagement/Database/Migrations --force
```

- Cache Laravel (`bootstrap/cache/`) ficou inconsistente durante o deploy
- **Não apagar** `bootstrap/cache/modules.php` — o app deixa de subir

## Recuperação imediata (Terminal Hostinger)

Acesse **hPanel → Avançado → Terminal SSH** (ou SSH na porta 65002):

```bash
cd ~/domains/fleti.com.br/public_html

# 1. Garantir modules.php (não apagar bootstrap/cache inteiro)
ls -la bootstrap/cache/modules.php

# 2. Remover apenas caches regeneráveis (manter modules.php)
rm -f bootstrap/cache/config.php bootstrap/cache/routes-v7.php

# 3. Tentar artisan
php artisan --version

# 4. Se artisan funcionar:
php artisan down --retry=60
php artisan migrate --force
php artisan db:seed --class="Modules\\FinanceManagement\\Database\\Seeders\\FinanceManagementDatabaseSeeder" --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan up
```

### Se artisan continuar falhando

**Opção A — Restaurar backup** (recomendado se houver backup de hoje antes do deploy):
- Restaurar `public_html` e banco via painel Hostinger
- Repetir deploy com checklist abaixo

**Opção B — Restaurar só cache de backup:**
- Copiar `bootstrap/cache/` de backup para produção
- Manter arquivos do módulo FinanceManagement no FTP

## Deploy correto (próxima tentativa)

```bash
# Local — corrigir ServiceProvider antes (imports de segurança já corrigidos)
python3 scripts/deploy_finance_remote.py

# Ou só SSH pós-upload:
python3 scripts/deploy_finance_remote.py --skip-upload
```

**Nunca executar:**
```bash
rm -f bootstrap/cache/*.php   # remove modules.php e derruba o app
composer install                # bloqueado na Hostinger (eval)
```

## Bugs corrigidos localmente (re-enviar antes do próximo deploy)

| Arquivo | Correção |
|---------|----------|
| `FinanceManagementServiceProvider.php` | `use` faltando para `FinanceWithdrawSecurityService`, `FinancePaymentVerificationService`, `FinanceAuditLogService` |
| `TripRequestUpdate.php` | Guards `class_exists()` para não quebrar sem módulo |

## Verificação pós-recuperação

```bash
curl -I https://fleti.com.br/admin/auth/login   # esperado: 200
curl -I https://fleti.com.br/api/customer/configuration  # esperado: 200
```

Checklist completo: `docs/finance_smoke_test_checklist.md`

## Contato Hostinger

Se `php artisan` seguir com exit 255 sem mensagem, abrir ticket mencionando:
- PHP 8.3 CLI segfault / exit 255 em `php artisan migrate`
- `composer install` bloqueado por `disable_functions` (eval)
