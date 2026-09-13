<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'listing_fee_enabled' => ['required', 'boolean'],
            'listing_fee' => ['required', 'integer', 'min:0'],
            'boost_fee_enabled' => ['required', 'boolean'],
            'boost_fee' => ['required', 'integer', 'min:0'],
            'boost_duration_hours' => ['required', 'integer', 'min:1', 'max:720'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'listing_fee_enabled.required' => 'Status biaya listing wajib ditentukan.',
            'listing_fee.required' => 'Nominal biaya listing wajib diisi.',
            'listing_fee.min' => 'Nominal biaya listing minimal 0.',
            'boost_fee_enabled.required' => 'Status biaya boost wajib ditentukan.',
            'boost_fee.required' => 'Nominal biaya boost wajib diisi.',
            'boost_fee.min' => 'Nominal biaya boost minimal 0.',
            'boost_duration_hours.required' => 'Durasi boost (jam) wajib diisi.',
            'boost_duration_hours.min' => 'Durasi boost minimal 1 jam.',
            'boost_duration_hours.max' => 'Durasi boost maksimal 720 jam (30 hari).',
            'reason.required' => 'Alasan perubahan wajib diisi untuk audit.',
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $isProduction = app()->isProduction() || config('app.env') === 'production';

                if (! $isProduction) {
                    return;
                }

                if ($this->boolean('listing_fee_enabled') || $this->boolean('boost_fee_enabled')) {
                    $clientId = config('services.doku.client_id');
                    $secretKey = config('services.doku.secret_key');
                    $baseUrl = (string) config('services.doku.base_url');
                    $appUrl = (string) config('app.url');

                    if (! is_string($clientId) || $clientId === '' || ! is_string($secretKey) || $secretKey === ''
                        || str_contains($baseUrl, 'sandbox') || ! str_starts_with($appUrl, 'https://')) {
                        $msg = 'Mode berbayar tidak dapat diaktifkan di production sebelum konfigurasi live DOKU (kredensial, endpoint live non-sandbox, dan HTTPS) siap.';

                        if ($this->boolean('listing_fee_enabled')) {
                            $validator->errors()->add('listing_fee_enabled', $msg);
                        }

                        if ($this->boolean('boost_fee_enabled')) {
                            $validator->errors()->add('boost_fee_enabled', $msg);
                        }
                    }
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'listing_fee_enabled' => $this->boolean('listing_fee_enabled'),
            'boost_fee_enabled' => $this->boolean('boost_fee_enabled'),
            'reason' => $this->string('reason')->trim()->toString(),
        ]);
    }
}
