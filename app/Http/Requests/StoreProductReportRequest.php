<?php

namespace App\Http\Requests;

use App\Models\Product;
use App\ReportReason;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductReportRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('description')) {
            $description = trim($this->string('description')->toString());
            $this->merge(['description' => $description === '' ? null : $description]);
        }
    }

    public function authorize(): bool
    {
        $product = $this->route('product');

        return $product instanceof Product && ($this->user()?->can('report', $product) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', Rule::enum(ReportReason::class)],
            'description' => [
                'nullable',
                'string',
                'max:2000',
                Rule::requiredIf($this->input('reason') === ReportReason::Other->value),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'Pilih alasan laporan.',
            'reason.enum' => 'Alasan laporan tidak valid.',
            'description.required' => 'Jelaskan pelanggaran yang kamu temukan.',
            'description.max' => 'Deskripsi laporan maksimal 2.000 karakter.',
        ];
    }
}
