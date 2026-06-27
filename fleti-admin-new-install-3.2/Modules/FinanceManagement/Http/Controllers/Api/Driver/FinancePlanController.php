<?php

namespace Modules\FinanceManagement\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\FinanceManagement\Exceptions\FinanceWithdrawException;
use Modules\FinanceManagement\Http\Requests\FinancePlanCheckoutRequest;
use Modules\FinanceManagement\Http\Resources\DriverPlanResource;
use Modules\FinanceManagement\Http\Resources\DriverSubscriptionResource;
use Modules\FinanceManagement\Service\Interfaces\DriverPlanServiceInterface;
use Modules\FinanceManagement\Service\Interfaces\DriverSubscriptionServiceInterface;
use Modules\FinanceManagement\Service\Interfaces\FinanceSettingServiceInterface;
use Modules\Gateways\Library\Payer;
use Modules\Gateways\Library\Payment as PaymentInfo;
use Modules\Gateways\Library\Receiver;
use Modules\Gateways\Traits\Payment;

class FinancePlanController extends Controller
{
    use Payment;

    public function __construct(
        private readonly DriverPlanServiceInterface $driverPlanService,
        private readonly DriverSubscriptionServiceInterface $subscriptionService,
        private readonly FinanceSettingServiceInterface $financeSettingService,
    ) {
    }

    public function index(): JsonResponse
    {
        $settings = $this->financeSettingService->get();

        if (!$settings->subscription_mode_enabled && !$settings->hybrid_mode_enabled) {
            return response()->json(responseFormatter(DEFAULT_200, [
                'plans_enabled' => false,
                'plans' => [],
            ]));
        }

        $plans = $this->driverPlanService->listActive();

        return response()->json(responseFormatter(DEFAULT_200, [
            'plans_enabled' => true,
            'active_mode' => $settings->active_mode,
            'plans' => DriverPlanResource::collection($plans),
        ]));
    }

    public function subscription(): JsonResponse
    {
        $driverId = auth('api')->id();
        $subscription = $this->subscriptionService->getActiveSubscription($driverId);

        return response()->json(responseFormatter(DEFAULT_200, [
            'has_active_plan' => $subscription !== null,
            'subscription' => $subscription
                ? DriverSubscriptionResource::make($subscription)
                : null,
        ]));
    }

    public function pendingSubscription(): JsonResponse
    {
        $subscription = $this->subscriptionService->getPendingSubscription(auth('api')->id());

        return response()->json(responseFormatter(DEFAULT_200, [
            'pending_subscription' => $subscription
                ? DriverSubscriptionResource::make($subscription)
                : null,
        ]));
    }

    public function checkout(string $planId, FinancePlanCheckoutRequest $request): JsonResponse
    {
        try {
            $driver = auth('api')->user();
            $subscription = $this->subscriptionService->createPendingCheckout($driver->id, $planId);
            $plan = $subscription->plan;

            $businessLogo = dynamicStorage('storage/app/public/business') . '/' . (businessConfig('header_logo')?->value ?? '');
            $businessName = businessConfig('business_name')?->value;

            $payer = new Payer(
                name: trim(($driver->first_name ?? '') . ' ' . ($driver->last_name ?? '')),
                email: $driver->email ?? '',
                phone: $driver->phone ?? '',
                address: '',
            );

            $paymentInfo = new PaymentInfo(
                hook: 'driverSubscriptionPaymentUpdate',
                currencyCode: businessConfig('currency_code')?->value ?? 'BRL',
                paymentMethod: $request->payment_method,
                paymentPlatform: 'mono',
                payerId: $driver->id,
                receiverId: '100',
                additionalData: [
                    'business_name' => $businessName,
                    'business_logo' => $businessLogo,
                    'plan_id' => $plan->id,
                    'plan_name' => $plan->name,
                    'subscription_id' => $subscription->id,
                ],
                paymentAmount: (float) $plan->price,
                externalRedirectLink: null,
                attribute: 'driver_subscription',
                attributeId: $subscription->id,
            );

            $receiverInfo = new Receiver('receiver_name', 'example.png');
            $redirectLink = $this->generate_link($payer, $paymentInfo, $receiverInfo);

            if (!$redirectLink) {
                throw new FinanceWithdrawException(
                    'payment_link_failed',
                    'Não foi possível gerar o link de pagamento.',
                    500,
                );
            }

            parse_str(parse_url($redirectLink, PHP_URL_QUERY) ?: '', $query);
            if (!empty($query['payment_id'])) {
                $this->subscriptionService->attachPaymentId($subscription->id, $query['payment_id']);
            }

            return response()->json(responseFormatter(DEFAULT_200, [
                'redirect_url' => $redirectLink,
                'subscription_id' => $subscription->id,
                'payment_id' => $query['payment_id'] ?? null,
                'plan' => DriverPlanResource::make($plan),
            ]));
        } catch (FinanceWithdrawException $e) {
            return response()->json(responseFormatter([
                'response_code' => $e->responseCode,
                'message' => $e->getMessage(),
            ]), $e->httpStatus);
        }
    }
}
