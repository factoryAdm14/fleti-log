# RELATÓRIO DA FASE 002 — Correção de Dependências e Build

## Fase executada

**FASE 002 — Correção de dependências e build**

## Objetivo

Restaurar builds do Laravel e Flutter, corrigir bugs críticos que impediam compilação.

## Arquivos alterados

| Arquivo | Motivo | Risco |
|---------|--------|-------|
| `fleti-User-app-release-3.2/lib/util/app_constants.dart` | Corrigir `baseUrl` e constante wallet | Baixo |
| `fleti-User-app-release-3.2/lib/features/wallet/domain/repositories/wallet_repository.dart` | Referência à constante corrigida | Baixo |
| `fleti-Driver-app-release-3.2/lib/util/app_constants.dart` | `baseUrl` com HTTPS | Baixo |
| `fleti-admin-new-install-3.2/app/Providers/RouteServiceProvider.php` | Ativar rotas pós-instalação (estado produção) | Médio — rollback em backup |
| `fleti-admin-new-install-3.2/modules_statuses.json` | Habilitar 16 módulos | Médio — reversível |
| `Modules/Gateways/Http/Controllers/SslCommerzPaymentController.php` | Guard null em config gateway | Baixo |
| `Modules/Gateways/Http/Controllers/BkashPaymentController.php` | Guard null em config gateway | Baixo |
| `Modules/Gateways/Http/Controllers/PvitController.php` | Guard null em config gateway | Baixo |
| `Modules/Gateways/Http/Controllers/PaypalPaymentController.php` | Guard null em config gateway | Baixo |

## Arquivos criados

- `backup/phase-000/RouteServiceProvider.install.php` — backup do provider de instalação
- `fleti-admin-new-install-3.2/.env` — cópia local de `.env.example` (gitignored)
- `fleti-admin-new-install-3.2/vendor/` — dependências Composer (gitignored)

## Migrations criadas

Nenhuma.

## Correções aplicadas

### Flutter User App

```dart
// Antes (erro de compilação)
static const String transferMoneyFromfleti userToMart = '...';

// Depois (alinhado ao backend)
static const String transferMoneyToMart = '/api/customer/wallet/transfer-drivemond-to-mart';
static const String baseUrl = 'https://fleti.com.br';
```

### Flutter Driver App

```dart
static const String baseUrl = 'https://fleti.com.br';
```

### Laravel

1. Instalado PHP 8.5.7 + Composer 2.10.1 (Homebrew)
2. `composer install` — 131 pacotes
3. Ativado `RouteServiceProvider.txt` → estado pós-instalação
4. Habilitados todos os módulos via `php artisan module:enable`
5. Corrigidos construtores de gateways que quebravam `route:list` sem config no banco

## Testes executados

| Teste | Antes | Depois |
|-------|-------|--------|
| `composer install` | Falhou (sem PHP) | OK |
| `php artisan route:list` | 66 rotas (modo install) | **848 rotas** |
| `flutter analyze` User | **5 erros** | **0 erros** (16 warnings) |
| `flutter analyze` Driver | 0 erros | 0 erros (17 warnings) |
| `flutter pub get` ambos | — | OK |

### Rotas wallet confirmadas

- `GET api/customer/wallet/bonus-list`
- `GET api/customer/wallet/add-fund-digitally`
- `POST api/customer/wallet/transfer-drivemond-to-mart`
- `POST api/customer/wallet/transfer-drivemond-from-mart`

## Bugs encontrados (não corrigidos nesta fase)

- PHP 8.5 deprecation warnings em `Helpers.php` e `database.php`
- Warnings Pusher `protected member` nos apps Flutter
- Firebase ainda aponta para projeto `ammart-8885e`
- Branding DriveMond/6amMart no backend
- `.env` local usa DB `drivemond` — produção precisa credenciais Hostinger

## Riscos

| Risco | Mitigação |
|-------|-----------|
| RouteServiceProvider alterado | Backup em `backup/phase-000/RouteServiceProvider.install.php` |
| Gateways sem config no DB | Guards adicionados — pagamentos exigem config no admin |
| baseUrl com https em assets | URLs de storage continuam válidas com `https://fleti.com.br/...` |

## Rollback

```bash
# Laravel rotas install
cp backup/phase-000/RouteServiceProvider.install.php fleti-admin-new-install-3.2/app/Providers/RouteServiceProvider.php

# Flutter
git checkout -- fleti-User-app-release-3.2/lib/util/app_constants.dart
git checkout -- fleti-User-app-release-3.2/lib/features/wallet/domain/repositories/wallet_repository.dart
git checkout -- fleti-Driver-app-release-3.2/lib/util/app_constants.dart
```

## Próxima etapa recomendada

**FASE 003 — Auditoria de rotas (validação cruzada)**

1. Exportar `php artisan route:list --json` e cruzar com todos os endpoints dos apps
2. Configurar `.env` com banco Hostinger para testes de integração
3. Testar login e wallet em staging (`fleti.com.br`)
