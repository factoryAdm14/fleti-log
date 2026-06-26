# DEPLOYMENT GUIDE — Fleti Enterprise v4.0 (FASE 018)

Guia de deploy para **fleti.com.br** (Laravel admin/API + apps Flutter).

**Branch:** `feature/fleti-enterprise-v4`  
**Produção:** Hostinger — `public_html` = raiz Laravel  
**Data:** 2026-06-26

---

## 1. Pré-requisitos

| Item | Detalhe |
|------|---------|
| Credenciais | `DEPLOYMENT_CREDENTIALS.local.md` (gitignored, apenas local) |
| Python 3.9+ | Scripts em `scripts/` |
| `paramiko` | `python3 -m pip install --user paramiko` |
| Git | Branch atualizada e testes passando |
| Backup | Banco + arquivos antes de cada deploy |

### Verificar antes do deploy

```bash
cd fleti-admin-new-install-3.2 && php artisan test
cd ../fleti-User-app-release-3.2 && flutter test
```

---

## 2. Checklist de deploy

### Pré-deploy

- [ ] Backup MySQL (`u965007418_fleti_serv`)
- [ ] Backup `public_html` (FTP ou painel Hostinger)
- [ ] Revisar migrations pendentes: `php artisan migrate:status`
- [ ] Confirmar `.env` produção: `APP_DEBUG=false`, `APP_URL=https://fleti.com.br`
- [ ] `SOFTWARE_VERSION=3.2` no `.env`
- [ ] `CORS_ALLOWED_ORIGINS=https://fleti.com.br,https://www.fleti.com.br`

### Deploy backend (Laravel)

- [ ] Upload arquivos alterados (FTP ou script)
- [ ] `composer install --no-dev --optimize-autoloader` (se `composer.json` mudou)
- [ ] `php artisan down` (modo manutenção)
- [ ] `php artisan migrate --force`
- [ ] `php artisan optimize:clear`
- [ ] `php artisan config:cache`
- [ ] `php artisan route:cache`
- [ ] `php artisan view:cache`
- [ ] `php artisan queue:restart` (se usar filas)
- [ ] `php artisan up`

### Pós-deploy — testes manuais

- [ ] Login admin: `https://fleti.com.br/admin/auth/login`
- [ ] Versão 3.2 no rodapé e login
- [ ] Dark mode (engrenagem → Dark Mode)
- [ ] API config: `GET /api/customer/configuration`
- [ ] Rotas debug retornam 404 (`/sender`, `/sms-test`)
- [ ] Wallet (recarga / saldo)
- [ ] Corrida ride (criar / aceitar)
- [ ] Delivery parcel
- [ ] Zonas no mapa admin
- [ ] Pagamentos (gateways ativos no admin)

### Deploy apps Flutter (quando houver release)

- [ ] `baseUrl = https://fleti.com.br` em `app_constants.dart`
- [ ] Build APK/AAB/IPA
- [ ] Publicar nas lojas ou distribuição interna

---

## 3. Scripts automatizados

Credenciais lidas de `DEPLOYMENT_CREDENTIALS.local.md` (nunca commitar senhas).

### Deploy completo (desde FASE 007+)

Envia todos os arquivos alterados no admin desde um commit base e executa migrate + cache:

```bash
# Listar arquivos sem enviar
python3 scripts/deploy_production.py --dry-run

# Deploy + migrate + cache
python3 scripts/deploy_production.py --since 3c5bda5
```

### Hotfix pontual (lista fixa de arquivos)

```bash
python3 scripts/deploy_admin_hotfix.py
python3 scripts/ssh_post_deploy.py --migrate
```

### Apenas pós-deploy SSH

```bash
python3 scripts/ssh_post_deploy.py --migrate
python3 scripts/ssh_post_deploy.py              # só cache, sem migrate
```

---

## 4. Deploy manual

### SSH

```bash
ssh -p 65002 u965007418@147.79.88.36
cd ~/domains/fleti.com.br/public_html
```

### Sequência Laravel (produção)

```bash
php artisan down --retry=60
composer install --no-dev --optimize-autoloader   # se necessário
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
mkdir -p Modules/AiModule/Resources/views
php artisan view:cache
php artisan queue:restart
php artisan up
```

### FTP

- Host: IP `147.79.88.36` (ou `fleti.com.br`)
- Usuário: ver `DEPLOYMENT_CREDENTIALS.local.md`
- Raiz remota = `public_html` = raiz do Laravel (`app/`, `Modules/`, `public/`, etc.)

**Importante:** caminhos locais em `fleti-admin-new-install-3.2/` mapeiam 1:1 para a raiz FTP (ex.: `Modules/Gateways/...`).

---

## 5. Migrations por fase (referência)

| Fase | Migrations |
|------|------------|
| 013 | Índices performance `2026_06_26_120000_add_fleti_performance_indexes` |
| 014 | `mercadopago_pix_logs`, gateway setting |
| 015 | `efi_pix_logs`, gateway setting |
| 016 | `trip_stops`, `is_multi_stop`, settings multi-stop |

Verificar após deploy:

```bash
php artisan migrate:status | tail -20
```

---

## 6. Variáveis `.env` recomendadas (produção)

```env
APP_NAME=Fleti
APP_ENV=production
APP_DEBUG=false
APP_URL=https://fleti.com.br
SOFTWARE_VERSION=3.2

DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=u965007418_fleti_serv
DB_USERNAME=u965007418_fleti_user
DB_PASSWORD=***

CORS_ALLOWED_ORIGINS=https://fleti.com.br,https://www.fleti.com.br
LOG_LEVEL=warning
```

### Gateways PIX (ativar no Admin após deploy)

- **Mercado Pago PIX:** `mercadopago_pix` — access token, webhook secret
- **EFI PIX:** `efi_pix` — client_id, client_secret, certificado P12, chave PIX

---

## 7. Build Flutter

### User app

```bash
cd fleti-User-app-release-3.2
flutter pub get
flutter build apk --release
# ou
flutter build appbundle --release
```

### Driver app

```bash
cd fleti-Driver-app-release-3.2
flutter pub get
flutter build apk --release
```

Saída: `build/app/outputs/flutter-apk/app-release.apk`

---

## 8. Rollback

### Código

1. Restaurar backup FTP / git checkout da versão anterior
2. `php artisan optimize:clear && php artisan config:cache && php artisan route:cache`

### Banco

1. Restaurar dump MySQL do backup pré-deploy
2. Ou `php artisan migrate:rollback --step=N` (cuidado em produção)

### Feature flags (sem rollback de código)

| Feature | Desativar em |
|---------|--------------|
| Multi-stop | Admin → Parcel Settings → toggle OFF |
| PIX gateways | Admin → Payment Methods → desativar gateway |

---

## 9. Troubleshooting

| Problema | Solução |
|----------|---------|
| `view:cache` falha AiModule | `mkdir -p Modules/AiModule/Resources/views` |
| Versão 3.2 vazia | `SOFTWARE_VERSION=3.2` no `.env` + `config:cache` |
| Dark mode não aplica | Limpar cache browser; verificar `fleti-admin-modern.css` |
| 500 após deploy | `tail storage/logs/laravel.log`; `optimize:clear` |
| Migrate falha | Verificar permissões DB; rodar migration isolada |
| CORS bloqueado | Configurar `CORS_ALLOWED_ORIGINS` |
| FTP `550` em subpastas | Usar scripts Python (criam dirs incrementalmente) |

---

## 10. Comandos rápidos

```bash
# Testes locais
cd fleti-admin-new-install-3.2 && php artisan test

# Deploy automatizado
python3 scripts/deploy_production.py --dry-run
python3 scripts/deploy_production.py

# Verificar produção
curl -sI https://fleti.com.br/admin/auth/login
curl -s https://fleti.com.br/api/customer/configuration | head -c 200
```

---

## 11. Referências

- `TESTING_GUIDE.md` — testes automatizados e manuais
- `SECURITY_AUDIT.md` — itens de segurança pós-deploy
- `PIX_MERCADO_PAGO.md` / `PIX_EFI.md` — configuração PIX
- `MULTI_STOP_DELIVERY.md` — multi-stop parcel
- `DEPLOYMENT_CREDENTIALS.local.md` — credenciais (local only)
