<?php

namespace Modules\Gateways\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Gateways\Entities\PaymentRequest;
use Modules\Gateways\Services\MercadoPagoPixService;
use Modules\Gateways\Traits\Processor;

class MercadoPagoPixController extends Controller
{
    use Processor;

    public function __construct(
        private readonly PaymentRequest $paymentRequest,
        private readonly MercadoPagoPixService $pixService,
    ) {
    }

    public function index(Request $request): View|Application|JsonResponse|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'payment_id' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json(
                $this->responseFormatter(GATEWAYS_DEFAULT_400, null, $this->errorProcessor($validator)),
                400
            );
        }

        if (!$this->pixService->isGatewayActive()) {
            return redirect()->route('gateway-inactive');
        }

        $payment = $this->paymentRequest::query()
            ->where('id', $request['payment_id'])
            ->where('is_paid', 0)
            ->first();

        if (!$payment) {
            return response()->json($this->responseFormatter(GATEWAYS_DEFAULT_204), 200);
        }

        $config = $this->pixService->resolveConfig();
        if (!$config || empty($config->access_token)) {
            return redirect()->route('gateway-inactive');
        }

        try {
            $pix = $this->pixService->createOrGetPixPayment($payment, $config);
        } catch (\Throwable $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 502);
        }

        return view('Gateways::payment.mercadopago-pix', [
            'payment' => $payment->fresh(),
            'pix' => $pix,
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'payment_id' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'failed'], 400);
        }

        $payment = $this->paymentRequest::query()->where('id', $request['payment_id'])->first();
        if (!$payment) {
            return response()->json(['status' => 'failed'], 404);
        }

        if ($payment->is_paid) {
            return response()->json([
                'status' => 'paid',
                'redirect' => route('payment-success'),
            ]);
        }

        $config = $this->pixService->resolveConfig();
        if (!$config) {
            return response()->json(['status' => 'failed'], 503);
        }

        $pix = $this->pixService->refreshPaymentStatus($payment, $config);
        $payment->refresh();

        $response = ['status' => $pix['status']];
        if ($pix['status'] === 'paid' || $payment->is_paid) {
            $response['redirect'] = route('payment-success');
        }

        return response()->json($response);
    }

    public function webhook(Request $request): JsonResponse
    {
        $config = $this->pixService->resolveConfig();
        if (!$config) {
            return response()->json(['ok' => false], 503);
        }

        $dataId = (string) ($request->input('data.id') ?? $request->input('id') ?? '');
        $secret = $config->webhook_secret ?? null;

        if (!$this->pixService->verifyWebhookSignature(
            $request->header('x-signature'),
            $request->header('x-request-id'),
            $dataId,
            $secret
        )) {
            return response()->json(['ok' => false], 401);
        }

        $this->pixService->processWebhookPayload($request->all(), $config);

        return response()->json(['ok' => true]);
    }
}
