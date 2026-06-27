# RELATÓRIO DA FASE 005 — Auditoria de Fluxo

## Fase executada

**FASE 005 — Auditoria de fluxo**

## Objetivo

Verificar fluxos críticos (auth, wallet, corrida, parcel, pagamento, etc.) sem alterar regras de negócio, mapeando cadeia UI → API → Controller → DB e validando produção.

## Arquivos alterados

| Arquivo | Motivo |
|---------|--------|
| `FLOW_AUDIT.md` | Atualizado com auditoria completa FASE 005 |

## Arquivos criados

- `PHASE_005_REPORT.md` — este relatório

## Migrations criadas

Nenhuma.

## Metodologia

1. Rastreamento de código nos 3 componentes (Laravel + 2 apps Flutter)
2. Smoke tests HTTP em `https://fleti.com.br` (endpoints públicos)
3. Cruzamento com relatórios FASE 002–004

## Testes executados — produção

| Endpoint | HTTP | Resultado |
|----------|------|-----------|
| `GET /api/customer/configuration` | 200 | API live |
| `GET /api/driver/configuration` | 200 | API live |
| `GET /api/customer/config/cancellation-reason-list` | 200 | OK |
| `GET /api/customer/parcel/category` | 401 | Esperado sem auth |

### Config produção extraída

```json
{
  "wallet_add_fund_status": false,
  "wallet_minimum_deposit_limit": 10,
  "payment_gateways": [],
  "business_name": "Fleti log ltda",
  "currency_symbol": "R$",
  "maintenance_mode": "off"
}
```

## Resultado por fluxo

| Fluxo | Código | Produção |
|-------|--------|----------|
| Auth login/registro | OK | API responde |
| Wallet listagem | OK | — |
| **Adicionar saldo** | OK (botão no código) | **DESATIVADO** (flag admin) |
| Corrida end-to-end | OK | Não testado com corrida real |
| Parcel/delivery | OK | Tipo `parcel` apenas |
| Cancelamento | OK | reason-list 200 |
| Cupom | OK | — |
| Pagamento digital | OK no código | **Sem gateways** configurados |
| PIX | Não implementado | — |
| Notificações | WARN (FCM legado) | — |
| Zonas | WARN | FASE 006 |
| Admin | OK | Config wallet/payment vazia |

## Bugs encontrados

| Bug | Severidade | Componente |
|-----|------------|--------------|
| `otpLogin` null user dereference | Alta | `AuthController.php:755` |
| Driver `ignoreMessage` endpoint errado | Média | `ride_repository.dart:56` |
| Digital payment sem auth | Média | Laravel routes |
| `generate_link()` incompleto | Média | `Gateways/Traits/Payment.php` |
| Add fund OFF em produção | Operacional | Admin config |
| Payment gateways vazios | Operacional | Admin config |

## Correções aplicadas

Nenhuma alteração de código nesta fase (auditoria apenas, conforme Master Plan).

## Riscos

| Risco | Mitigação |
|-------|-----------|
| Usuários não conseguem adicionar saldo | Ativar no admin + gateway |
| Pagamento digital inoperante | Configurar Mercado Pago/Stripe |
| OTP login para novo usuário | Corrigir bug FASE 006 |
| Driver ignore notificação envia trip-action | Corrigir endpoint FASE 006 |

## Rollback

N/A — fase somente documentação.

## Checklist FASE 005

- [x] Cadastro/login usuário mapeado
- [x] Cadastro/login motorista mapeado
- [x] Wallet usuário + motorista mapeado
- [x] Botão Adicionar Saldo confirmado no código
- [x] Corrida mapeada end-to-end
- [x] Parcel/delivery mapeado
- [x] Cancelamento, cupom, pagamento mapeados
- [x] PIX documentado como ausente
- [x] Notificações, zonas, localização mapeados
- [x] Admin dashboard mapeado
- [x] Smoke test produção executado
- [ ] Teste corrida real com motorista
- [ ] Teste add-fund após ativar config

## Próxima etapa recomendada

**FASE 006 — Google Maps e Zonas**

Paralelamente (ação operacional urgente):
1. Admin → ativar **Customer Wallet → Add Fund Status**
2. Admin → configurar **Payment Gateway** (ex: Mercado Pago)
3. Corrigir bugs `otpLogin` e `ignoreMessage` em PR separado
