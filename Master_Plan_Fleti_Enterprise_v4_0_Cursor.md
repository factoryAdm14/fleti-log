# MASTER PLAN FLETI ENTERPRISE v4.0
## Guia Técnico Oficial para Cursor — Laravel + Flutter + Admin + Apps

**Projeto:** Fleti Log v3.2 → Evolução Enterprise v4.0  
**Objetivo:** Modernizar, auditar, corrigir falhas e evoluir o sistema Fleti sem remover funcionalidades existentes e sem quebrar fluxos atuais.

---

# 1. PRINCÍPIO PRINCIPAL

Este documento deve ser seguido pelo Cursor como regra obrigatória.

O sistema Fleti deve ser evoluído com segurança, mantendo compatibilidade total com:

- Painel Administrativo Laravel
- App Usuário Flutter
- App Motorista Flutter
- Wallet existente
- Botão Adicionar Saldo do Usuário
- Botão Adicionar Saldo do Motorista
- Corridas existentes
- Parcel existente
- Delivery existente
- Pagamentos existentes
- Rotas existentes
- Banco de dados existente
- APIs existentes
- Fluxos de negócio atuais

A evolução deve acontecer por fases, sempre com auditoria, backup, rollback e validação.

---

# 2. REGRAS OBRIGATÓRIAS PARA O CURSOR

## 2.1 Nunca fazer

- Nunca remover funções existentes.
- Nunca excluir controllers existentes.
- Nunca excluir models existentes.
- Nunca excluir tabelas existentes.
- Nunca alterar APIs públicas existentes sem compatibilidade retroativa.
- Nunca remover o botão **Adicionar Saldo** do App Usuário.
- Nunca remover o botão **Adicionar Saldo** do App Motorista.
- Nunca quebrar o fluxo atual de corrida.
- Nunca quebrar o fluxo atual de parcel.
- Nunca quebrar o fluxo atual de delivery.
- Nunca quebrar o fluxo atual de wallet.
- Nunca substituir gateway de pagamento existente.
- Nunca alterar lógica crítica sem feature flag.
- Nunca fazer muitas alterações em uma única etapa.
- Nunca modernizar visual misturando alteração de regra de negócio.

## 2.2 Sempre fazer

Antes de qualquer alteração, listar:

- Arquivos que serão alterados
- Motivo da alteração
- Risco da alteração
- Estratégia de rollback
- Testes necessários

Depois de cada alteração, gerar relatório com:

- Arquivos modificados
- Rotas afetadas
- Migrations criadas
- APIs novas
- APIs alteradas
- Testes executados
- Riscos encontrados
- Rollback
- Próxima etapa

---

# 3. PROBLEMAS CONHECIDOS JÁ IDENTIFICADOS

## 3.1 Dependências Laravel incompletas

Problema encontrado:

```bash
Failed opening required ... Illuminate/Collections/functions.php
```

Possível causa:

- Pasta `vendor` incompleta
- Dependências Laravel quebradas
- Composer não executado corretamente

Correção segura:

```bash
cd fleti-admin-new-install-3.2
rm -rf vendor
composer install
php artisan optimize:clear
php artisan route:list
```

Nunca alterar lógica antes do `php artisan route:list` funcionar.

---

## 3.2 Erro grave no Flutter App Usuário

Arquivo provável:

```text
fleti-User-app-release-3.2/lib/util/app_constants.dart
```

Problema:

```dart
static const String transferMoneyFromfleti userToMart
```

A variável possui espaço no nome, quebrando o build.

Correção sugerida:

```dart
static const String transferMoneyFromFletiUserToMart =
    '/api/customer/wallet/transfer-fleti-user-to-mart';
```

Também verificar uso em:

```text
features/wallet/domain/repositories/wallet_repository.dart
```

---

## 3.3 Base URL sem protocolo HTTPS

Problema possível nos apps:

```dart
static const String baseUrl = 'fleti.com.br';
```

Melhor padrão:

```dart
static const String baseUrl = 'https://fleti.com.br';
```

Verificar antes se o backend espera domínio sem protocolo. Caso espere sem protocolo, criar constante separada:

```dart
static const String domain = 'fleti.com.br';
static const String baseUrl = 'https://fleti.com.br';
```

---

## 3.4 Risco no editor de zonas do Google Maps

Arquivos prováveis:

```text
Modules/ZoneManagement/Resources/views/admin/zone/index.blade.php
Modules/ZoneManagement/Resources/views/admin/zone/edit.blade.php
```

Problemas a verificar:

- Google Maps fixado em versão antiga.
- Falta de validação forte para mínimo de 3 pontos.
- Possível erro em `lastPolygon.setMap(null)` quando não há polígono.
- Coordenadas salvas como texto bruto.
- Falta de validação GeoJSON.
- Mapa iniciando em país/cidade incorreta quando geolocalização falha.
- Dificuldade em criar, marcar e salvar zonas.

Correção segura:

- Não remover tela atual.
- Criar validações adicionais.
- Preservar fluxo atual.
- Melhorar somente JS, validação e feedback visual.
- Adicionar proteção antes de chamar métodos em objetos nulos.
- Adicionar logs em caso de erro.

---

## 3.5 Referências antigas

Verificar e padronizar referências antigas como:

- DriveMond
- 6amMart
- Textos antigos de outro sistema
- Rotas antigas não usadas
- Variáveis antigas

Atenção: substituir apenas texto visual/configuração. Não alterar namespace, classe ou função sem confirmar dependência.

---

# 4. ORDEM GLOBAL DE EXECUÇÃO

O Cursor deve seguir esta ordem:

1. Auditoria e mapa do sistema
2. Correção de dependências e build
3. Validação de rotas Laravel
4. Validação de build Flutter Usuário
5. Validação de build Flutter Motorista
6. Correção de bugs críticos
7. Auditoria de APIs
8. Auditoria de banco
9. Auditoria de Google Maps e zonas
10. Auditoria de fluxo
11. Modernização visual isolada
12. Performance
13. Segurança
14. PIX Mercado Pago
15. PIX EFI
16. Multi Stop Delivery
17. Testes completos
18. Deploy controlado
19. Monitoramento
20. Roadmap futuro

---

# 5. FASE 000 — PREPARAÇÃO E BACKUP

## Objetivo

Criar ambiente seguro antes de qualquer alteração.

## Tarefas

```bash
git status
git checkout -b feature/fleti-enterprise-v4
```

Criar backup de:

- `.env`
- `composer.json`
- `composer.lock`
- `package.json`
- `pubspec.yaml`
- Banco de dados
- Arquivos alterados

## Checklist

- [ ] Branch criada
- [ ] Backup criado
- [ ] Banco exportado
- [ ] `.env` salvo
- [ ] Sistema atual documentado
- [ ] Nenhum arquivo alterado ainda

---

# 6. FASE 001 — AUDITORIA GERAL

## Objetivo

Mapear todo o sistema antes de alterar.

## Gerar arquivo

```text
SYSTEM_MAP.md
```

## Mapear Laravel

- routes/web.php
- routes/api.php
- Modules/*
- Controllers
- Models
- Services
- Repositories
- Middleware
- Providers
- Jobs
- Events
- Listeners
- Policies
- Gates
- Configs
- Views Blade
- Assets
- Migrations
- Seeders

## Mapear Flutter Usuário

- lib/util/app_constants.dart
- Rotas
- Pages
- Controllers
- Repository
- Services
- Widgets
- Theme
- Assets
- Pubspec

## Mapear Flutter Motorista

- Constantes
- Rotas
- Pages
- Controllers
- Services
- Widgets
- Theme
- Assets

## Resultado obrigatório

Gerar relatório:

```text
AUDIT_REPORT.md
```

---

# 7. FASE 002 — CORREÇÃO DE DEPENDÊNCIAS

## Laravel

Executar:

```bash
composer install
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan route:list
```

## Flutter Usuário

Executar:

```bash
flutter clean
flutter pub get
flutter analyze
```

## Flutter Motorista

Executar:

```bash
flutter clean
flutter pub get
flutter analyze
```

## Critério de conclusão

- Laravel executa `php artisan route:list`
- App Usuário passa em `flutter analyze` ou lista erros reais
- App Motorista passa em `flutter analyze` ou lista erros reais

---

# 8. FASE 003 — AUDITORIA DE ROTAS

## Objetivo

Encontrar rotas quebradas, duplicadas ou sem proteção.

## Verificar

- Rotas duplicadas
- Rotas órfãs
- Rotas sem middleware
- Rotas públicas sensíveis
- Rotas sem rate limit
- Rotas usadas no app que não existem no backend
- Rotas backend que não são usadas nos apps
- Rotas de wallet
- Rotas de corrida
- Rotas de delivery
- Rotas de parcel
- Rotas de zonas
- Rotas de mapas
- Rotas de pagamento

## Gerar

```text
ROUTE_AUDIT.md
```

---

# 9. FASE 004 — AUDITORIA DO BANCO

## Objetivo

Verificar estrutura sem excluir tabelas.

## Verificar

- Tabelas existentes
- Migrations antigas
- Índices ausentes
- Campos sem FK
- Campos duplicados
- Campos nullable perigosos
- Relacionamentos
- Tabelas de wallet
- Tabelas de trip
- Tabelas de parcel
- Tabelas de delivery
- Tabelas de zone
- Tabelas de payment
- Tabelas de notification
- Logs

## Regras

- Nunca excluir tabela.
- Criar apenas novas migrations.
- Toda migration deve ter rollback.
- Nunca alterar campo crítico sem migration reversível.

## Gerar

```text
DATABASE_AUDIT.md
```

---

# 10. FASE 005 — AUDITORIA DO FLUXO

## Fluxos obrigatórios

Verificar sem alterar:

- Cadastro usuário
- Login usuário
- Cadastro motorista
- Login motorista
- Wallet usuário
- Wallet motorista
- Adicionar saldo
- Corrida
- Parcel
- Delivery
- Cancelamento
- Cupom
- Pagamento
- PIX
- Notificações
- Zonas
- Localização
- Admin dashboard
- Relatórios

## Gerar

```text
FLOW_AUDIT.md
```

---

# 11. FASE 006 — GOOGLE MAPS E ZONAS

## Objetivo

Corrigir e modernizar mapas sem alterar lógica principal.

## Verificar

- Google Maps JavaScript
- API Key
- Places API
- Directions API
- Distance Matrix
- Drawing Manager
- Polygon
- Polyline
- Markers
- Clusters
- GeoJSON
- Geocoding
- Reverse Geocoding
- Salvar zona
- Editar zona
- Remover zona
- Validar mínimo 3 pontos

## Melhorias seguras

- Proteger objetos nulos.
- Melhorar feedback de erro.
- Validar pontos antes de salvar.
- Não salvar zona vazia.
- Exibir coordenadas selecionadas.
- Permitir limpar desenho.
- Permitir refazer desenho.
- Preservar controller atual.

## Gerar

```text
MAPS_ZONE_AUDIT.md
```

---

# 12. FASE 007 — DESIGN SYSTEM FLETI

## Objetivo

Criar padrão visual moderno sem alterar lógica.

## Criar componentes Flutter

```text
ModernCard
ModernButton
ModernTextField
ModernContainer
ModernBottomSheet
ModernDialog
ModernBadge
ModernChip
ModernStatus
ModernLoading
ModernDashboardCard
```

## Criar padrão Admin

```text
ModernPanelCard
ModernAdminButton
ModernTable
ModernFilter
ModernStatusBadge
ModernMetricCard
ModernChartCard
```

## Regras visuais

- Layout mais limpo
- Menos linhas
- Menos sombra
- Bordas suaves
- Containers mais slim
- Melhor espaçamento
- Botões mais modernos
- Tipografia mais legível
- Responsividade preservada
- Não alterar lógica
- Não alterar nomes de rotas
- Não alterar controllers
- Não alterar services

## Gerar

```text
DESIGN_SYSTEM_FLETI.md
```

---

# 13. FASE 008 — MODERNIZAÇÃO DO PAINEL ADMIN

## Objetivo

Modernizar painel administrativo sem alterar funcionalidades.

## Permitido

- CSS
- Blade component
- Classes visuais
- Cards
- Botões
- Tabelas
- Formulários
- Dashboard
- Gráficos
- Menus
- Espaçamentos
- Responsividade

## Proibido

- Alterar regra de negócio
- Alterar controller sem necessidade
- Alterar service
- Alterar model
- Alterar rotas existentes
- Remover botão
- Remover campo
- Remover tela

## Áreas

- Dashboard
- Usuários
- Motoristas
- Corridas
- Delivery
- Parcel
- Wallet
- Zonas
- Pagamentos
- Relatórios
- Configurações
- Logs

## Gerar

```text
ADMIN_MODERNIZATION.md
```

---

# 14. FASE 009 — MODERNIZAÇÃO DO APP USUÁRIO

## Objetivo

Modernizar layout sem alterar fluxo.

## Áreas

- Home
- Mapa
- Busca
- Corrida
- Delivery
- Parcel
- Wallet
- Adicionar Saldo
- Perfil
- Histórico
- Cupons
- Notificações

## Cuidados

- Verificar overflow
- Verificar SafeArea
- Verificar telas pequenas
- Verificar Android/iOS
- Não remover botão Adicionar Saldo
- Não alterar fluxo de pagamento
- Não alterar endpoints

## Gerar

```text
USER_APP_MODERNIZATION.md
```

---

# 15. FASE 010 — MODERNIZAÇÃO DO APP MOTORISTA

## Objetivo

Modernizar layout sem alterar fluxo.

## Áreas

- Home
- Mapa
- Aceitar corrida
- Delivery
- Parcel
- Ganhos
- Wallet
- Adicionar Saldo
- Perfil
- Histórico
- Navegação

## Cuidados

- Não remover botão Adicionar Saldo
- Não alterar lógica de aceite
- Não alterar cálculo de ganho
- Não alterar status da corrida
- Não alterar endpoints

## Gerar

```text
DRIVER_APP_MODERNIZATION.md
```

---

# 16. FASE 011 — PERFORMANCE BACKEND

## Verificar

- Queries N+1
- Eager loading
- Índices MySQL
- Cache Redis
- Paginação
- Jobs
- Queues
- Logs pesados
- Imagens
- Config cache
- Route cache
- View cache

## Gerar

```text
BACKEND_PERFORMANCE.md
```

---

# 17. FASE 012 — PERFORMANCE FLUTTER

## Verificar

- Rebuilds desnecessários
- ListView pesada
- FutureBuilder aninhado
- StreamBuilder aninhado
- Imagens grandes
- Assets sem compressão
- Widgets duplicados
- Consumo de bateria
- Consumo de internet
- Localização em background

## Gerar

```text
FLUTTER_PERFORMANCE.md
```

---

# 18. FASE 013 — SEGURANÇA

## Verificar

- LGPD
- CORS
- CSRF
- XSS
- SQL Injection
- Rate Limit
- Auth
- Admin permissions
- Logs sensíveis
- Webhooks
- Tokens
- Firebase
- Uploads
- Validação de arquivos
- 2FA Admin

## Gerar

```text
SECURITY_AUDIT.md
```

---

# 19. FASE 014 — PIX MERCADO PAGO

## Objetivo

Adicionar PIX Mercado Pago sem remover pagamentos existentes.

## Criar gateway novo

```text
mercadopago_pix
```

## Recursos

- QR Code
- PIX Copia e Cola
- Webhook
- Status pending
- Status paid
- Status expired
- Status failed
- Idempotência
- Logs
- Auditoria
- Sandbox/produção

## Regras

- Não substituir gateway existente.
- Ativar via Painel Admin.
- Feature flag obrigatória.
- Rollback obrigatório.

## Gerar

```text
PIX_MERCADO_PAGO.md
```

---

# 20. FASE 015 — PIX EFI

## Objetivo

Adicionar PIX Banco EFI separado.

## Criar gateway novo

```text
efi_pix
```

## Recursos

- Certificado
- Client ID
- Client Secret
- Ambiente sandbox
- Ambiente produção
- QR Code
- PIX Copia e Cola
- Webhook
- Logs
- Auditoria
- Estorno futuro

## Regras

- Não substituir Mercado Pago.
- Não substituir gateway atual.
- Feature flag obrigatória.
- Configuração pelo Admin.

## Gerar

```text
PIX_EFI.md
```

---

# 21. FASE 016 — DELIVERY MULTI STOP

## Objetivo

Permitir até 20 pontos para mesmo entregador, sem quebrar delivery atual.

## Criar tabela nova

```text
trip_stops
```

## Campos sugeridos

```text
id
trip_id
stop_order
type: pickup|dropoff
address
latitude
longitude
status
arrived_at
completed_at
proof_photo
signature
qr_code
notes
created_at
updated_at
```

## Recursos

- Até 20 paradas
- Múltiplas coletas
- Múltiplas entregas
- Status por parada
- Foto de entrega
- Assinatura
- QR Code
- Otimização de rota
- Timeline
- Prova de entrega

## Regras

- Não alterar delivery atual.
- Multi Stop deve ser opcional.
- Feature flag obrigatória.
- Se desativado, sistema funciona igual ao atual.

## Gerar

```text
MULTI_STOP_DELIVERY.md
```

---

# 22. FASE 017 — TESTES

## Laravel

- Unit tests
- Feature tests
- API tests
- Payment tests
- Wallet tests
- Zone tests
- Multi Stop tests

## Flutter

- Widget tests
- Navigation tests
- Layout tests
- Overflow tests
- API integration tests

## Gerar

```text
TESTING_GUIDE.md
```

---

# 23. FASE 018 — DEPLOY

## Checklist

- Backup banco
- Backup arquivos
- Composer install
- Migrations
- Cache clear
- Queue restart
- Horizon restart se existir
- Supervisor restart se existir
- Flutter build
- Teste rotas
- Teste login
- Teste wallet
- Teste corrida
- Teste delivery
- Teste zonas
- Teste pagamentos

## Comandos Laravel

```bash
php artisan down
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
php artisan up
```

## Gerar

```text
DEPLOYMENT_GUIDE.md
```

---

# 24. FASE 019 — OBSERVABILIDADE

## Criar logs para

- Login
- Corrida
- Delivery
- Wallet
- PIX
- Zonas
- Webhooks
- Erros de API
- Erros de mapa
- Erros de build
- Falhas de pagamento

## Gerar

```text
OBSERVABILITY_GUIDE.md
```

---

# 25. FASE 020 — ROADMAP FUTURO

## Recursos futuros

- Reconhecimento facial para motorista
- OCR de documentos
- IA para despacho inteligente
- Roteirização automática
- Previsão de demanda
- Heatmap inteligente
- BI financeiro
- Telemetria
- OpenTelemetry
- Docker
- Microsserviços
- Elasticsearch
- Chat suporte
- WhatsApp integrado

## Gerar

```text
ROADMAP_2026_2028.md
```

---

# 26. PROMPT MESTRE PARA USAR NO CURSOR

Copie e cole este prompt no Cursor antes de iniciar:

```text
Você é um Engenheiro Sênior Laravel + Flutter trabalhando no sistema Fleti Enterprise v4.0.

Objetivo:
Auditar, corrigir bugs, melhorar rotas, modernizar layout, melhorar dimensões, containers, linhas, botões e fluxos do sistema Fleti sem remover ou quebrar funcionalidades existentes.

Regras obrigatórias:
1. Nunca remover funcionalidades existentes.
2. Nunca excluir tabelas existentes.
3. Nunca alterar APIs existentes sem compatibilidade retroativa.
4. Nunca remover o botão Adicionar Saldo do App Usuário.
5. Nunca remover o botão Adicionar Saldo do App Motorista.
6. Nunca quebrar Wallet.
7. Nunca quebrar Corridas.
8. Nunca quebrar Delivery.
9. Nunca quebrar Parcel.
10. Nunca quebrar pagamentos existentes.
11. Criar apenas novas migrations.
12. Toda nova funcionalidade deve ser opcional via feature flag.
13. Toda alteração deve possuir rollback.
14. Antes de modificar, listar arquivos afetados, motivo, risco e rollback.
15. Executar apenas uma fase por vez.
16. Não misturar modernização visual com regra de negócio.
17. Não alterar lógica crítica sem autorização.
18. Ao final de cada fase gerar relatório técnico.

Primeira tarefa:
Ler este Master Plan completo e iniciar somente pela FASE 000 — Preparação e Backup.

Depois gerar:
- SYSTEM_MAP.md
- AUDIT_REPORT.md
- ROUTE_AUDIT.md
- DATABASE_AUDIT.md
- FLOW_AUDIT.md

Não implementar PIX, Multi Stop ou modernização visual antes da auditoria completa.
```

---

# 27. PROMPT PARA CADA FASE

Use este modelo no Cursor:

```text
Execute somente a FASE [NOME DA FASE] do Master Plan Fleti Enterprise v4.0.

Antes de alterar qualquer arquivo, liste:
- arquivos que serão alterados
- motivo
- risco
- rollback

Durante a fase:
- preservar funções existentes
- preservar rotas existentes
- preservar banco existente
- criar apenas alterações reversíveis

Depois da fase, gerar relatório:
- arquivos modificados
- migrations criadas
- APIs criadas
- APIs alteradas
- testes executados
- riscos encontrados
- rollback
- próxima fase recomendada
```

---

# 28. CHECKLIST GLOBAL DE SEGURANÇA

Antes de aprovar qualquer fase:

- [ ] Sistema compila
- [ ] Laravel sobe
- [ ] `php artisan route:list` funciona
- [ ] App Usuário compila
- [ ] App Motorista compila
- [ ] Login funciona
- [ ] Wallet funciona
- [ ] Adicionar Saldo Usuário existe
- [ ] Adicionar Saldo Motorista existe
- [ ] Corrida funciona
- [ ] Delivery funciona
- [ ] Parcel funciona
- [ ] Zona funciona
- [ ] Pagamento atual funciona
- [ ] Não houve remoção de função
- [ ] Rollback existe
- [ ] Relatório foi gerado

---

# 29. PADRÃO DE RELATÓRIO FINAL DE CADA FASE

```markdown
# RELATÓRIO DA FASE

## Fase executada

## Objetivo

## Arquivos alterados

## Arquivos criados

## Migrations criadas

## Rotas alteradas

## Rotas criadas

## APIs impactadas

## Testes executados

## Resultado dos testes

## Bugs encontrados

## Correções aplicadas

## Riscos

## Rollback

## Próxima etapa recomendada
```

---

# 30. CONCLUSÃO

Este Master Plan deve ser usado como guia principal para evolução segura do Fleti.

O Cursor deve começar pela auditoria, depois corrigir bugs críticos, validar rotas e somente depois avançar para modernização visual, PIX, EFI e Multi Stop.

A prioridade é preservar a estabilidade do sistema atual.
