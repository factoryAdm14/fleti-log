@extends('financemanagement::admin.layout')

@section('title', $plan ? 'Editar plano' : 'Novo plano')

@section('finance_content')
    <div class="fin-page-head">
        <div>
            <h2>{{ $plan ? 'Editar plano' : 'Novo plano' }}</h2>
            <p>Configure preço, duração e benefícios</p>
        </div>
        <div class="fin-actions">
            <a href="{{ route('admin.finance.plans.index') }}" class="fin-btn fin-btn--ghost fin-btn--sm">Voltar</a>
        </div>
    </div>

    <div class="fin-panel">
        <div class="fin-panel-body">
            <form method="POST" action="{{ $plan ? route('admin.finance.plans.update', $plan->id) : route('admin.finance.plans.store') }}">
                @csrf
                @if($plan) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Nome</label>
                        <input type="text" name="name" class="form-control form-control-sm" required
                               value="{{ old('name', $plan?->name) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Preço (R$)</label>
                        <input type="number" step="0.01" min="0" name="price" class="form-control form-control-sm" required
                               value="{{ old('price', $plan?->price) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Duração (dias)</label>
                        <input type="number" min="1" name="duration_days" class="form-control form-control-sm" required
                               value="{{ old('duration_days', $plan?->duration_days ?? 30) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Comissão no plano (%)</label>
                        <input type="number" step="0.01" min="0" max="100" name="commission_percentage" class="form-control form-control-sm"
                               value="{{ old('commission_percentage', $plan?->commission_percentage ?? 0) }}">
                        <div class="form-text">Use 0% para plano livre sem comissão.</div>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <label class="fin-toggle-row w-100 mb-0">
                            <span>Plano ativo</span>
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input m-0"
                                {{ old('is_active', $plan?->is_active ?? true) ? 'checked' : '' }}>
                        </label>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Descrição</label>
                        <textarea name="description" class="form-control form-control-sm" rows="2">{{ old('description', $plan?->description) }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Benefícios (um por linha)</label>
                        <textarea name="benefits" class="form-control form-control-sm" rows="4" placeholder="zero_commission&#10;priority_support">{{ old('benefits', $plan ? implode("\n", $plan->benefits ?? []) : '') }}</textarea>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="fin-btn fin-btn--primary">Salvar plano</button>
                </div>
            </form>
        </div>
    </div>
@endsection
