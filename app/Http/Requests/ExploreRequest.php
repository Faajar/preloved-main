<?php

namespace App\Http\Requests;

use App\ProductCondition;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ExploreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'category' => [
                'nullable',
                'string',
                'max:255',
                Rule::exists('categories', 'slug')->where('is_active', true),
            ],
            'min_price' => ['nullable', 'integer', 'min:0'],
            'max_price' => ['nullable', 'integer', 'min:0'],
            'condition' => ['nullable', Rule::enum(ProductCondition::class)],
            'location' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', Rule::in(['newest', 'price_low', 'price_high'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category.exists' => 'Kategori yang dipilih tidak tersedia.',
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->filled(['min_price', 'max_price']) && $this->integer('max_price') < $this->integer('min_price')) {
                    $validator->errors()->add('max_price', 'Harga maksimum harus sama dengan atau lebih besar dari harga minimum.');
                }
            },
        ];
    }
}
