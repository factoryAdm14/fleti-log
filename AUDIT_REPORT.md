# AUDIT REPORT — Fleti Log v3.2 → Enterprise v4.0

**Fase:** 001 — Auditoria Geral  
**Data:** 2026-06-26  
**Branch:** `feature/fleti-enterprise-v4`

---

## Resumo executivo

O pacote v3.2 contém um monólito Laravel modular (16 módulos, ~336 rotas API em módulos) e dois apps Flutter. O **App Usuário não compila** por erro de sintaxe em `app_constants.dart`. O **backend não possui `vendor/`** e requer `composer install` (PHP/Composer não disponíveis nesta máquina de auditoria). Há branding legado extensivo (DriveMond, 6amMart, HexaRide) que precisa padronização visual sem quebrar integrações.

---

## Bugs críticos (bloqueiam build/operação)

| # | Severidade | Componente | Arquivo | Problema |
|---|------------|------------|---------|----------|
| 1 | **CRÍTICO** | User App | `lib/util/app_constants.dart:79` | Identificador inválido `transferMoneyFromfleti userToMart` (espaço no nome) |
| 2 | **CRÍTICO** | User App | `lib/features/wallet/domain/repositories/wallet_repository.dart:32` | Referência ao identificador quebrado |
| 3 | **CRÍTICO** | Laravel | `vendor/` ausente | `composer install` necessário antes de qualquer teste |
| 4 | **ALTO** | User + Driver | `lib/util/app_constants.dart` | `baseUrl = 'fleti.com.br'` sem `https://` — `Uri.parse()` gera URI inválida para HTTP |
| 5 | **ALTO** | User App | Endpoint wallet | App aponta para `/transfer-fleti user-to-mart` mas backend expõe `/transfer-drivemond-to-mart` |
| 6 | **ALTO** | Ambos apps | Firebase configs | Projeto ainda `ammart-8885e` (legado 6amMart) |

---

## Bugs médios

| # | Componente | Problema |
|---|------------|----------|
| 7 | User App | `sign_in_screen.dart:276` — deep link com `password=}` (typo) |
| 8 | Driver App | Google Maps API key hardcoded em `app_constants.dart:8` |
| 9 | Ambos | `pubspec.yaml` name `ride_sharing_user_app` (legado) |
| 10 | Driver | Bundle ID `com.sixamtech.hexariderider` |
| 11 | Laravel | `.env.example` com `APP_NAME=DriveMond`, `DB_DATABASE=drivemond` |
| 12 | Admin zonas | JS do mapa — risco de `setMap(null)` em polígono nulo (verificar FASE 006) |

---

## Estado dos builds

| Componente | Comando | Resultado |
|------------|---------|-----------|
| Laravel | `composer install` + `php artisan route:list` | **Não testado** — PHP/Composer ausentes localmente |
| User App | `flutter analyze` | **5 erros**, 22 issues — **não compila** |
| Driver App | `flutter analyze` | **0 erros**, 17 warnings — compila |

---

## Branding legado (contagem)

| Termo | Ocorrências | Arquivos |
|-------|-------------|----------|
| DriveMond | ~136 | 29 (lang, install, wallet) |
| 6amMart | ~60 | 7 |
| HexaRide / sixamtech | Apps Android/iOS | manifests, Firebase |

**Regra Master Plan:** substituir apenas textos visuais/config; não renomear namespaces/classes sem confirmar dependências.

---

## Funcionalidades preservadas (confirmadas no código)

- [x] Wallet usuário — `WalletController`, add-fund-digitally, bonus-list
- [x] Wallet motorista — withdraw, pay-digitally
- [x] Botão Adicionar Saldo — `add_fund_dialog.dart`, `walletAddFundStatus` flag
- [x] Corridas — `TripManagement` (69 rotas API)
- [x] Parcel — `ParcelManagement`
- [x] Delivery — via `TripRequest` (sem entity Delivery separada)
- [x] Gateways pagamento — 14+ provedores em `Modules/Gateways`
- [x] Zonas — `ZoneManagement` com editor de mapa

---

## Riscos

| Risco | Impacto | Mitigação |
|-------|---------|-----------|
| Corrigir endpoint wallet sem alinhar backend | Transferência mart quebrada | Manter rota backend `transfer-drivemond-to-mart`; corrigir apenas constante Dart |
| Alterar baseUrl sem testar imagens/storage | URLs de assets quebradas | Separar `apiBaseUrl` (https) de `assetBaseUrl` se necessário |
| composer install em produção | Downtime | `php artisan down`, backup, rollback vendor |
| Renomear rotas DriveMond→Fleti | Apps antigos param | Manter rotas; alias opcional com feature flag |

---

## Rollback

- Branch `main` intacta no GitHub
- Backup FASE 000 em `backup/phase-000/`
- Zip original em `/Users/flavio/Desktop/fleti log-v3.2.zip`
- Nenhum arquivo de produção alterado nesta fase

---

## Próxima etapa recomendada

**FASE 002 — Correção de dependências e build**

1. Instalar PHP 8.2 + Composer no ambiente de dev
2. `composer install` no Laravel → validar `php artisan route:list`
3. Corrigir `app_constants.dart:79` e `wallet_repository.dart:32` (User App)
4. Avaliar correção de `baseUrl` com `https://fleti.com.br`
5. `flutter analyze` em ambos apps
