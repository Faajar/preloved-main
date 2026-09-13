<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('whatsapp_number')) {
            $this->merge(['whatsapp_number' => null]);

            return;
        }

        $number = trim($this->string('whatsapp_number')->toString());

        if (str_starts_with($number, '+62')) {
            $number = substr($number, 1);
        } elseif (str_starts_with($number, '0')) {
            $number = '62'.substr($number, 1);
        }

        $this->merge(['whatsapp_number' => $number]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'whatsapp_number' => [
                'nullable',
                'string',
                'regex:/\A628[1-9][0-9]{7,10}\z/',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'whatsapp_number.regex' => 'Format nomor WhatsApp tidak valid. Gunakan format nomor Indonesia (contoh: 081234567890).',
        ];
    }
}
