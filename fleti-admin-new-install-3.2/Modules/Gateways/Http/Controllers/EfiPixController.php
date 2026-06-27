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
use Modules\Gateways\Services\EfiPixService;
use Modules\FinanceManagement\Service\PaymentGatewayManager;
use Modules\Gateways\Traits\Processor;

class EfiPixController extends Controller
{
    use Processor;

    public function __construct(
        private readonly PaymentRequest $paymentRequest,
        private readonly EfiPixService $pixService,
        private readonly PaymentGatewayManager $gatewayManager,
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
        if (!$config || empty($config->client_id) || empty($config->pix_key) || !$this->pixService->certificatePath($config)) {
            return redirect()->route('gateway-inactive');
        }

        try {
            $pix = $this->pixService->createOrGetPixPayment($payment, $config);
        } catch (\Throwable $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 502);
        }

        return view('Gateways::payment.efi-pix', [
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
        $result = $this->gatewayManager->handlePixWebhook($request, 'efi_pix');

        if (!$result->accepted) {
            return response()->json(['ok' => false], 503);
        }

        return response()->json(['ok' => true]);
    }
}
