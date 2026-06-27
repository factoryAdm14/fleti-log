@extends('financemanagement::admin.layout')

@section('title', translate('Finance_Settings'))

@section('finance_content')
    @include('financemanagement::admin.partials._page_header', [
        'title' => 'Configurações',
        'subtitle' => 'Modos de operação, saques, segurança e planos',
    ])

    <form action="{{ route('admin.finance.settings.update') }}" method="POST" class="row g-3">
        @csrf

        <div class="col-lg-6">
            <div class="fin-form-card">
                <h5>Modos de operação</h5>
                @foreach([
                    'commission_mode_enabled' => 'Ativar modo comissão',
                    'subscription_mode_enabled' => 'Ativar modo assinatura',
                    'hybrid_mode_enabled' => 'Ativar modo híbrido',
                ] as $field => $label)
                    <label class="fin-toggle-row">
                        <span>{{ $label }}</span>
                        <input type="hidden" name="{{ $field }}" value="0">
                        <input type="checkbox" name="{{ $field }}" value="1" class="form-check-input m-0"
                            {{ old($field, $settings->$field) ? 'checked' : '' }}>
                    </label>
                @endforeach
                <div class="mt-3">
                    <label class="form-label">Modo ativo</label>
                    <select name="active_mode" class="form-select form-select-sm">
                        @foreach(['commission' => 'Comissão', 'subscription' => 'Assinatura', 'hybrid' => 'Híbrido'] as $val => $lbl)
                            <option value="{{ $val }}" @selected(old('active_mode', $settings->active_mode) === $val)>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mt-3">
                    <label class="form-label">Comissão padrão (%)</label>
                    <input type="number" step="0.01" min="0" max="100" name="default_commission_percent"
                           class="form-control form-control-sm" value="{{ old('default_commission_percent', $settings->default_commission_percent) }}">
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="fin-form-card">
                <h5>Saques e pagamentos</h5>
                <div class="mb-3">
                    <label class="form-label">Valor mínimo para saque (R$)</label>
                    <input type="number" step="0.01" min="0" name="min_withdraw_amount" class="form-control form-control-sm"
                           value="{{ old('min_withdraw_amount', $settings->min_withdraw_amount) }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Prazo liberação saldo (dias)</label>
                    <input type="number" min="0" max="365" name="balance_release_days" class="form-control form-control-sm"
                           value="{{ old('balance_release_days', $settings->balance_release_days) }}">
                </div>
                @foreach([
                    'manual_withdraw_approval' => 'Aprovação manual de saque',
                    'pix_payment_enabled' => 'PIX habilitado',
                    'card_payment_enabled' => 'Cartão habilitado',
                    'auto_pix_payout_enabled' => 'PIX automático para motorista',
                ] as $field => $label)
                    <label class="fin-toggle-row">
                        <span>{{ $label }}</span>
                        <input type="hidden" name="{{ $field }}" value="0">
                        <input type="checkbox" name="{{ $field }}" value="1" class="form-check-input m-0"
                            {{ old($field, $settings->$field) ? 'checked' : '' }}>
                    </label>
                @endforeach
                <div class="mt-2 mb-3">
                    <label class="form-label">Gateway principal</label>
                    <select name="primary_gateway" class="form-select form-select-sm">
                        <option value="mercadopago" @selected(old('primary_gateway', $settings->primary_gateway) === 'mercadopago')>Mercado Pago</option>
                        <option value="efi" @selected(old('primary_gateway', $settings->primary_gateway) === 'efi')>Banco EFI</option>
                    </select>
                </div>
                <p class="text-muted mb-0" style="font-size:.75rem">
                    PIX automático requer EFI PIX com certificado e escopo <code>gn.pix.send</code>.
                </p>
            </div>
        </div>

        <div class="col-12">
            <div class="fin-form-card">
                <h5>Segurança financeira</h5>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="fin-toggle-row h-100 mb-0">
                            <span>Regras anti-fraude de saque</span>
                            <input type="hidden" name="withdraw_security_enabled" value="0">
                            <input type="checkbox" name="withdraw_security_enabled" value="1" class="form-check-input m-0"
                                {{ old('withdraw_security_enabled', $settings->withdraw_security_enabled ?? true) ? 'checked' : '' }}>
                        </label>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Limite por saque (R$)</label>
                        <input type="number" step="0.01" min="0" name="max_withdraw_amount" class="form-control form-control-sm"
                               value="{{ old('max_withdraw_amount', $settings->max_withdraw_amount ?? 0) }}">
                        <small class="text-muted">0 = sem limite</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Limite diário de valor (R$)</label>
                        <input type="number" step="0.01" min="0" name="max_withdraw_amount_per_day" class="form-control form-control-sm"
                               value="{{ old('max_withdraw_amount_per_day', $settings->max_withdraw_amount_per_day ?? 0) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Máx. solicitações por dia</label>
                        <input type="number" min="0" max="100" name="max_withdraw_requests_per_day" class="form-control form-control-sm"
                               value="{{ old('max_withdraw_requests_per_day', $settings->max_withdraw_requests_per_day ?? 3) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="fin-toggle-row h-100 mb-0">
                            <span>Exigir assinatura em webhooks</span>
                            <input type="hidden" name="webhook_signature_required" value="0">
                            <input type="checkbox" name="webhook_signature_required" value="1" class="form-check-input m-0"
                                {{ old('webhook_signature_required', $settings->webhook_signature_required ?? true) ? 'checked' : '' }}>
                        </label>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tolerância valor pagamento (%)</label>
                        <input type="number" step="0.01" min="0" max="10" name="payment_amount_tolerance_percent" class="form-control form-control-sm"
                               value="{{ old('payment_amount_tolerance_percent', $settings->payment_amount_tolerance_percent ?? 1) }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="fin-form-card">
                <h5>Planos do motorista</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Regra ao vencer plano</label>
                        <select name="plan_expiry_rule" class="form-select form-select-sm">
                            <option value="revert_commission" @selected(old('plan_expiry_rule', $settings->plan_expiry_rule) === 'revert_commission')>Voltar para comissão</option>
                            <option value="block_rides" @selected(old('plan_expiry_rule', $settings->plan_expiry_rule) === 'block_rides')>Bloquear corridas</option>
                            <option value="grace_period" @selected(old('plan_expiry_rule', $settings->plan_expiry_rule) === 'grace_period')>Período de tolerância</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Dias de tolerância</label>
                        <input type="number" min="0" max="90" name="plan_grace_period_days" class="form-control form-control-sm"
                               value="{{ old('plan_grace_period_days', $settings->plan_grace_period_days) }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <button type="submit" class="fin-btn fin-btn--primary">{{ translate('save') }}</button>
        </div>
    </form>
@endsection
