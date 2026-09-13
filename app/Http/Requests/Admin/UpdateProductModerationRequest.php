<?php

namespace App\Http\Requests\Admin;

use App\ProductStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductModerationRequest extends FormRequest
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
            'status' => ['required', Rule::in([ProductStatus::Available->value, ProductStatus::Hidden->value])],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Status moderasi produk wajib dipilih.',
            'status.in' => 'Status moderasi hanya boleh Available atau Hidden.',
            'reason.required' => 'Alasan tindakan moderasi wajib diisi untuk catatan audit.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'reason' => $this->string('reason')->trim()->toString(),
        ]);
    }
}
