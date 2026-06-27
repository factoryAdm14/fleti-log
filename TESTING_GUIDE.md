# TESTING GUIDE — Fleti Enterprise v4.0 (FASE 017)

Guia de testes para o ecossistema **Fleti** (Laravel admin/API + apps Flutter User/Driver).

**Branch:** `feature/fleti-enterprise-v4`  
**Data:** 2026-06-26

---

## 1. Visão geral

| Camada | Framework | Local dos testes |
|--------|-----------|------------------|
| Backend Laravel | PHPUnit 11 | `fleti-admin-new-install-3.2/tests/` |
| App User | Flutter Test | `fleti-User-app-release-3.2/test/` |
| App Driver | Flutter Test | `fleti-Driver-app-release-3.2/test/` |

### Cobertura desta fase

| Área | Testes |
|------|--------|
| Multi-stop delivery | `MultiStopHelperTest` — parse, validação, ordenação |
| PIX Mercado Pago | `MercadoPagoPixServiceTest` — assinatura webhook HMAC |
| Versão 3.2 | `SoftwareVersionConfigTest`, `AdminAuthPagesTest` |
| Segurança (debug routes) | `DebugRoutesSecurityTest` |
| API config | `CustomerConfigurationApiTest` |
| Flutter tema/UI | `theme_controller_test`, `modern_card_widget_test`, tokens |

---

## 2. Laravel — como rodar

```bash
cd fleti-admin-new-install-3.2
php artisan test
# ou
./vendor/bin/phpunit
```

### Ambiente de teste

- `phpunit.xml` usa **SQLite em memória** (`DB_DATABASE=:memory:`) — não toca o banco de produção.
- `APP_DEBUG=false` nos testes para validar rotas debug bloqueadas.
- `tests/bootstrap.php` define `DOMAIN_POINTED_DIRECTORY=root`.

### Suites

```bash
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature
php artisan test --filter=MultiStopHelperTest
```

### Testes manuais recomendados (staging/produção)

| Fluxo | Passos |
|-------|--------|
| Login admin | `/admin/auth/login` — versão 3.2 visível |
| Dark mode admin | Engrenagem → Dark Mode |
| Config API | `GET /api/customer/configuration` |
| Multi-stop | Admin: ativar flag → criar parcel com `stops` JSON |
| PIX MP | Ativar gateway → gerar link → webhook |
| PIX EFI | Certificado P12 → cobrança → webhook |
| Wallet | Recarga / conversão pontos |
| Zonas | Criar zona → fare dentro/fora |

---

## 3. Flutter — como rodar

### User app

```bash
cd fleti-User-app-release-3.2
flutter test
```

### Driver app

```bash
cd fleti-Driver-app-release-3.2
flutter test
```

### Testes incluídos

- **Tokens de design** — escala de radius/spacing
- **ThemeController** — persistência dark/light em SharedPreferences
- **ModernCard** — render sem overflow em largura fixa
- **AppConstants** — versão 3.2 e baseUrl HTTPS

### Testes manuais nos apps

| Item | Onde |
|------|------|
| Dark mode | Configurações → toggle tema |
| Layout | Home, carteira, perfil em telas pequenas |
| API | Splash carrega config; login funciona |

---

## 4. CI sugerido (GitHub Actions)

```yaml
# Exemplo mínimo
jobs:
  laravel-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: sqlite3
      - run: composer install -d fleti-admin-new-install-3.2
      - run: php artisan test
        working-directory: fleti-admin-new-install-3.2

  flutter-user-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: subosito/flutter-action@v2
      - run: flutter test
        working-directory: fleti-User-app-release-3.2
```

---

## 5. Backlog de testes (próximas iterações)

- [ ] Feature tests com `RefreshDatabase` + factories (corrida, wallet, zona)
- [ ] HTTP fake para PIX create/webhook (Mercado Pago + EFI)
- [ ] Testes de integração Flutter com `integration_test`
- [ ] Golden tests para telas modernizadas
- [ ] Testes E2E multi-stop (driver arrive/complete por parada)
- [ ] Rate limit API (`throttle:api` 60/min)

---

## 6. Troubleshooting

| Problema | Solução |
|----------|---------|
| Testes Laravel conectam ao MySQL remoto | Verificar `phpunit.xml` — `DB_CONNECTION=sqlite` |
| `DOMAIN_POINTED_DIRECTORY` undefined | Usar `tests/bootstrap.php` |
| Rotas debug passam nos testes | `APP_DEBUG` deve ser `false` no phpunit |
| Flutter `widget_test` quebrado | Removido — usar testes em `fleti_*_test.dart` |
| Deprecation PDO no PHP 8.5 | Avisos apenas; não falham os testes |

---

## 7. Comandos rápidos

```bash
# Tudo Laravel
cd fleti-admin-new-install-3.2 && php artisan test

# Tudo Flutter
cd fleti-User-app-release-3.2 && flutter test
cd fleti-Driver-app-release-3.2 && flutter test
```
