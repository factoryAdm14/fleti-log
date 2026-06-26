@extends('Gateways::payment.layouts.master')

@push('script')
    <style>
        :root {
            --fleti-primary: #f37021;
            --fleti-bg: #f5f7fb;
            --fleti-card: #ffffff;
            --fleti-text: #1f2937;
            --fleti-muted: #6b7280;
            --fleti-success: #16a34a;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--fleti-bg);
            color: var(--fleti-text);
        }
        .pix-wrap { max-width: 420px; margin: 0 auto; padding: 24px 16px 40px; }
        .pix-card {
            background: var(--fleti-card);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        }
        .pix-title { font-size: 1.25rem; font-weight: 700; margin: 0 0 8px; text-align: center; }
        .pix-amount {
            text-align: center;
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--fleti-primary);
            margin-bottom: 20px;
        }
        .pix-qr { display: flex; justify-content: center; margin: 16px 0; }
        .pix-qr img { width: 220px; height: 220px; border-radius: 12px; border: 1px solid #e5e7eb; }
        .pix-status { text-align: center; font-size: 0.95rem; color: var(--fleti-muted); margin-bottom: 16px; }
        .pix-status.pending { color: #d97706; }
        .pix-status.paid { color: var(--fleti-success); }
        .pix-status.failed, .pix-status.expired { color: #dc2626; }
        .pix-copy-label { font-size: 0.85rem; color: var(--fleti-muted); margin-bottom: 8px; }
        .pix-copy-box { display: flex; gap: 8px; align-items: stretch; }
        .pix-copy-box textarea {
            flex: 1; min-height: 72px; border: 1px solid #d1d5db; border-radius: 10px;
            padding: 10px 12px; font-size: 0.8rem; resize: none; word-break: break-all;
        }
        .pix-copy-btn {
            background: var(--fleti-primary); color: #fff; border: none; border-radius: 10px;
            padding: 0 16px; font-weight: 600; cursor: pointer; white-space: nowrap;
        }
        .pix-hint { margin-top: 16px; font-size: 0.8rem; color: var(--fleti-muted); text-align: center; line-height: 1.4; }
    </style>
@endpush

@section('content')
    <div class="pix-wrap">
        <div class="pix-card">
            <h1 class="pix-title">PIX EFI</h1>
            <div class="pix-amount">
                R$ {{ number_format((float) $payment->payment_amount, 2, ',', '.') }}
            </div>

            <div id="pix-status" class="pix-status pending">Aguardando pagamento...</div>

            @if(!empty($pix['qr_code_base64']))
                <div class="pix-qr">
                    <img src="data:image/png;base64,{{ $pix['qr_code_base64'] }}" alt="QR Code PIX">
                </div>
            @endif

            @if(!empty($pix['qr_code']))
                <div class="pix-copy-label">PIX Copia e Cola</div>
                <div class="pix-copy-box">
                    <textarea id="pix-code" readonly>{{ $pix['qr_code'] }}</textarea>
                    <button type="button" class="pix-copy-btn" onclick="copyPixCode()">Copiar</button>
                </div>
            @endif

            <p class="pix-hint">
                Escaneie o QR Code ou cole o código no app do seu banco.
                A confirmação é automática.
            </p>
        </div>
    </div>

    <script>
        'use strict';
        const paymentId = @json($payment->id);
        const statusEl = document.getElementById('pix-status');
        let pollTimer = null;

        function copyPixCode() {
            const textarea = document.getElementById('pix-code');
            textarea.select();
            navigator.clipboard.writeText(textarea.value).then(() => {
                const btn = document.querySelector('.pix-copy-btn');
                const original = btn.textContent;
                btn.textContent = 'Copiado!';
                setTimeout(() => btn.textContent = original, 2000);
            });
        }

        function updateStatus(status) {
            statusEl.className = 'pix-status ' + status;
            const labels = {
                pending: 'Aguardando pagamento...',
                paid: 'Pagamento confirmado!',
                expired: 'PIX expirado. Gere um novo pagamento.',
                failed: 'Pagamento não concluído.',
            };
            statusEl.textContent = labels[status] || labels.pending;
        }

        function pollStatus() {
            fetch(@json(route('efi_pix.status')) + '?payment_id=' + paymentId, {
                headers: { 'Accept': 'application/json' }
            })
                .then(r => r.json())
                .then(data => {
                    updateStatus(data.status || 'pending');
                    if (data.redirect) {
                        clearInterval(pollTimer);
                        window.location.href = data.redirect;
                    }
                    if (data.status === 'expired' || data.status === 'failed') {
                        clearInterval(pollTimer);
                    }
                })
                .catch(() => {});
        }

        updateStatus(@json($pix['status'] ?? 'pending'));
        pollTimer = setInterval(pollStatus, 5000);
        pollStatus();
    </script>
@endsection
