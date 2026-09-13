<?php

namespace App\Http\Requests\Seller;

use App\Models\Category;
use App\Models\Product;
use App\ProductCondition;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        $product = $this->route('product');

        return $product instanceof Product && ($this->user()?->can('update', $product) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category_id' => [
                'required',
                'integer',
                Rule::exists(Category::class, 'id')->where('is_active', true),
            ],
            'price' => ['required', 'integer', 'min:1'],
            'condition' => ['required', Rule::enum(ProductCondition::class)],
            'description' => ['required', 'string', 'max:5000'],
            'location' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category_id.exists' => 'Kategori yang dipilih tidak tersedia.',
        ];
    }
}
