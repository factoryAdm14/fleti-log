<div class="modal fade fin-modal" id="detailModal{{ $item->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Saque #{{ Str::limit($item->id, 8) }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div class="fs-4 fw-bold fin-money--info">{{ set_currency_symbol($item->amount) }}</div>
                    @include('financemanagement::admin.withdraws._status_badge', ['status' => $item->status])
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div class="text-muted" style="font-size:.75rem">Motorista</div>
                        <div class="fw-semibold">
                            {{ $item->user ? $item->user->first_name . ' ' . $item->user->last_name : '—' }}
                        </div>
                        <div class="text-muted" style="font-size:.8125rem">{{ $item->user?->phone }} · {{ $item->user?->email }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted" style="font-size:.75rem">Datas</div>
                        <div style="font-size:.8125rem">Solicitado: {{ $item->created_at?->format('d/m/Y H:i') }}</div>
                        @if($item->paid_at)
                            <div style="font-size:.8125rem" class="fin-money--positive">Pago: {{ \Carbon\Carbon::parse($item->paid_at)->format('d/m/Y H:i') }}</div>
                        @endif
                    </div>
                </div>

                <div class="fin-panel mb-3">
                    <div class="fin-panel-head">Dados de pagamento</div>
                    <div class="fin-panel-body py-2">
                        <div class="mb-1"><span class="text-muted">Método:</span> {{ $item->method?->method_name ?? '—' }}</div>
                        @foreach($item->method_fields ?? [] as $key => $value)
                            <div class="mb-1">
                                <span class="text-muted">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span> {{ $value }}
                            </div>
                        @endforeach
                    </div>
                </div>

                @if($item->driver_note)
                    <div class="mb-3">
                        <div class="text-muted" style="font-size:.75rem;margin-bottom:.25rem">Observação do motorista</div>
                        <div class="fin-action-box" style="font-size:.8125rem">{{ $item->driver_note }}</div>
                    </div>
                @endif

                @if($item->approval_note)
                    <div class="mb-3">
                        <div class="text-muted" style="font-size:.75rem;margin-bottom:.25rem">Observação de aprovação</div>
                        <div class="fin-action-box" style="font-size:.8125rem">{{ $item->approval_note }}</div>
                    </div>
                @endif

                @if($item->denied_note)
                    <div class="mb-3">
                        <div class="text-muted" style="font-size:.75rem;margin-bottom:.25rem">Motivo da recusa</div>
                        <div class="fin-action-box" style="font-size:.8125rem">{{ $item->denied_note }}</div>
                    </div>
                @endif

                @if($item->receipt_url)
                    <div class="mb-3">
                        <div class="text-muted" style="font-size:.75rem;margin-bottom:.25rem">Comprovante</div>
                        @if(str_starts_with($item->receipt_url, 'pix-'))
                            <code style="font-size:.8125rem">{{ $item->receipt_url }}</code>
                        @else
                            <a href="{{ asset('storage/' . $item->receipt_url) }}" target="_blank" class="fin-btn fin-btn--outline fin-btn--sm">
                                Ver comprovante
                            </a>
                        @endif
                    </div>
                @endif

                @if($item->pix_payout_status || $item->pix_end_to_end_id)
                    <div class="mb-3">
                        <div class="text-muted" style="font-size:.75rem;margin-bottom:.25rem">PIX automático</div>
                        <div style="font-size:.8125rem">
                            Gateway: {{ $item->pix_payout_gateway ?? '—' }}<br>
                            Status: {{ $item->pix_payout_status ?? '—' }}<br>
                            @if($item->pix_end_to_end_id)
                                E2E: <code>{{ $item->pix_end_to_end_id }}</code>
                            @endif
                        </div>
                    </div>
                @endif

                @if($item->status === PENDING)
                    <form method="POST" action="{{ route('admin.finance.withdraws.action', $item->id) }}" class="fin-action-box">
                        @csrf
                        <input type="hidden" name="status" value="{{ APPROVED }}">
                        <label class="form-label fw-semibold fin-money--positive">Aprovar saque</label>
                        <textarea name="approval_note" class="form-control form-control-sm mb-2" rows="2" placeholder="Observação (opcional)"></textarea>
                        <button type="submit" class="fin-btn fin-btn--success fin-btn--sm">Aprovar</button>
                    </form>

                    <form method="POST" action="{{ route('admin.finance.withdraws.action', $item->id) }}" class="fin-action-box">
                        @csrf
                        <input type="hidden" name="status" value="{{ DENIED }}">
                        <label class="form-label fw-semibold fin-money--danger">Recusar saque</label>
                        <textarea name="denied_note" class="form-control form-control-sm mb-2" rows="2" placeholder="Motivo da recusa" required></textarea>
                        <button type="submit" class="fin-btn fin-btn--danger fin-btn--sm">Recusar</button>
                    </form>
                @endif

                @if($item->status === APPROVED)
                    @if($item->pix_payout_status === 'failed')
                        <form method="POST" action="{{ route('admin.finance.withdraws.retry-pix', $item->id) }}" class="fin-action-box">
                            @csrf
                            <label class="form-label fw-semibold fin-money--warning">Reenviar PIX automático</label>
                            <p class="text-muted mb-2" style="font-size:.8125rem">A última tentativa falhou. Tente novamente se o gateway EFI estiver configurado.</p>
                            <button type="submit" class="fin-btn fin-btn--warning fin-btn--sm">Reenviar PIX</button>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('admin.finance.withdraws.action', $item->id) }}" enctype="multipart/form-data" class="fin-action-box">
                        @csrf
                        <input type="hidden" name="status" value="{{ SETTLED }}">
                        <label class="form-label fw-semibold fin-money--info">Marcar como pago</label>
                        <p class="text-muted mb-2" style="font-size:.8125rem">Confirme o PIX manual e anexe o comprovante, se disponível.</p>
                        <input type="file" name="receipt" class="form-control form-control-sm mb-2" accept=".jpg,.jpeg,.png,.pdf">
                        <button type="submit" class="fin-btn fin-btn--primary fin-btn--sm">Marcar como pago</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
