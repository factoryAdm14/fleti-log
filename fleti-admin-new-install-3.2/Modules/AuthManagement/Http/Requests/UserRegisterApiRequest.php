<?php

namespace Modules\AuthManagement\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UserRegisterApiRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $driverRoute = str_contains($this->route()->getPrefix(), 'driver');
        $rules = [
            'first_name' => 'required',
            'last_name' => 'sometimes',
            'email' => [
                Rule::requiredIf(function () use ($driverRoute) {
                    return $driverRoute;
                }),
                'nullable',
                'email',
                Rule::unique('users', 'email'),
            ],
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:8|max:17',
            'password' => 'required|min:8',
            'profile_image' => 'image|mimes:' . str_replace(['.', ' '], '', IMAGE_ACCEPTED_EXTENSIONS) . '|max:' . convertBytesToKiloBytes(maxUploadSize('image')),
            'identification_type' => Rule::in(['nid', 'passport', 'driving_license']),
            'identification_number' => 'sometimes',
            'identity_images' => 'sometimes|array',
            'identity_images.*' => 'image|mimes:' . str_replace(['.', ' '], '', IMAGE_ACCEPTED_EXTENSIONS) . '|max:' . convertBytesToKiloBytes(maxUploadSize('image')),
            'other_documents' => 'sometimes|array',
            'other_documents.*' => 'mimes:'
                . str_replace(['.', ' '], '', IMAGE_ACCEPTED_EXTENSIONS)
                . ',pdf,doc,docx'
                . '|max:' . convertBytesToKiloBytes(maxUploadSize('file')),
            'fcm_token' => 'sometimes',
            'referral_code' => 'sometimes',
            'service' => [
                Rule::requiredIf(function () use ($driverRoute) {
                    return $driverRoute;
                })
            ]
        ];

        if ($driverRoute) {
            $rules['gender'] = 'required|in:' . implode(',', GENDERS);
        }

        return array_merge($rules, $this->legalConsentRules());
    }

    protected function legalConsentRules(): array
    {
        if (!legalConsentRequired()) {
            return [];
        }

        return [
            'terms_accepted' => 'required|accepted',
            'privacy_accepted' => 'required|accepted',
            'location_consent_accepted' => 'required|accepted',
            'marketing_consent_accepted' => 'sometimes',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    public function messages()
    {
        return array_merge([
            'profile_image.max' => translate(key: 'The Profile Image must be less than {maxSize}', replace: ['maxSize' => readableUploadMaxFileSize('image')]),
            'identity_images.*.max' => translate(key: 'Each Identity Image must be less than {maxSize}', replace: ['maxSize' => readableUploadMaxFileSize('image')]),
            'other_documents.*.max' => translate(key: 'Each document must be less than {maxSize}', replace: ['maxSize' => readableUploadMaxFileSize('image')]),
            'terms_accepted.required' => translate('You must accept the terms of use'),
            'terms_accepted.accepted' => translate('You must accept the terms of use'),
            'privacy_accepted.required' => translate('You must accept the privacy policy'),
            'privacy_accepted.accepted' => translate('You must accept the privacy policy'),
            'location_consent_accepted.required' => translate('You must authorize location usage'),
            'location_consent_accepted.accepted' => translate('You must authorize location usage'),
        ], []);
    }

    protected function prepareForValidation()
    {
        showValidationMessageForUploadMaxSize(files: $this->allFiles(), isAjax: $this->ajax(), doesExpectJson: $this->expectsJson());
    }

    protected function failedValidation(Validator $validator)
    {
        $error = $validator->errors()->toArray();
        $key = key($error);
        $message = $error[$key][0] ?? null;
        $fieldName = str_contains($key, '.') ? explode('.', $key)[0] : $key;

        throw new HttpResponseException(response()->json([
            'errors' => [['error_code' => $fieldName, 'message' => $message]],
        ], 403));
    }
}
