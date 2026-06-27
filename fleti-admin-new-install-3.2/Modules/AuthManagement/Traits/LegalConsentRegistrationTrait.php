<?php

namespace Modules\AuthManagement\Traits;

use App\Services\LegalConsentService;
use Illuminate\Http\Request;
use Modules\UserManagement\Entities\User;

trait LegalConsentRegistrationTrait
{
    protected function legalConsentRules(): array
    {
        if (!legalConsentRequired()) {
            return [];
        }

        return [
            'terms_accepted' => 'required|accepted',
            'privacy_accepted' => 'required|accepted',
            'location_consent_accepted' => 'required|accepted',
            'marketing_consent_accepted' => 'sometimes|boolean',
        ];
    }

    protected function legalConsentMessages(): array
    {
        return [
            'terms_accepted.required' => translate('You must accept the terms of use'),
            'terms_accepted.accepted' => translate('You must accept the terms of use'),
            'privacy_accepted.required' => translate('You must accept the privacy policy'),
            'privacy_accepted.accepted' => translate('You must accept the privacy policy'),
            'location_consent_accepted.required' => translate('You must authorize location usage'),
            'location_consent_accepted.accepted' => translate('You must authorize location usage'),
        ];
    }

    protected function stripLegalConsentFields(array $data): array
    {
        return app(LegalConsentService::class)->stripFromRegistrationData($data);
    }

    protected function recordLegalConsent(User $user, Request $request): void
    {
        app(LegalConsentService::class)->record($user, $request);
    }
}
