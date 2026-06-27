<?php

namespace App\Services;

use App\Lib\FletiLegalPagesContent;
use Illuminate\Http\Request;
use Modules\UserManagement\Entities\User;
use Modules\UserManagement\Entities\UserLegalAcceptance;

class LegalConsentService
{
    public const CONSENT_FIELDS = [
        'terms_accepted',
        'privacy_accepted',
        'location_consent_accepted',
        'marketing_consent_accepted',
    ];

    public function stripFromRegistrationData(array $data): array
    {
        foreach (self::CONSENT_FIELDS as $field) {
            unset($data[$field]);
        }

        return $data;
    }

    public function record(User $user, Request $request): void
    {
        if (!legalConsentRequired()) {
            return;
        }

        $now = now();
        $termsVersion = FletiLegalPagesContent::VERSION;
        $privacyVersion = FletiLegalPagesContent::VERSION;
        $ip = $request->ip();
        $userAgent = $request->userAgent();

        $user->fill([
            'terms_accepted_at' => $this->isAccepted($request, 'terms_accepted') ? $now : null,
            'privacy_accepted_at' => $this->isAccepted($request, 'privacy_accepted') ? $now : null,
            'location_consent_at' => $this->isAccepted($request, 'location_consent_accepted') ? $now : null,
            'marketing_consent_at' => $this->isAccepted($request, 'marketing_consent_accepted') ? $now : null,
            'terms_version' => $termsVersion,
            'privacy_version' => $privacyVersion,
        ])->save();

        foreach ([
            'terms_of_use' => ['terms_accepted', $termsVersion],
            'privacy_policy' => ['privacy_accepted', $privacyVersion],
            'location_consent' => ['location_consent_accepted', $termsVersion],
            'marketing_consent' => ['marketing_consent_accepted', $termsVersion],
        ] as $documentType => [$field, $version]) {
            if (!$this->isAccepted($request, $field)) {
                continue;
            }

            UserLegalAcceptance::query()->create([
                'user_id' => $user->id,
                'document_type' => $documentType,
                'document_version' => $version,
                'accepted_at' => $now,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
            ]);
        }
    }

    private function isAccepted(Request $request, string $field): bool
    {
        if (!$request->has($field)) {
            return false;
        }

        $value = $request->input($field);

        return in_array($value, [true, 1, '1', 'true', 'on', 'yes'], true);
    }
}
