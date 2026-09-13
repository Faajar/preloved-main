<?php

namespace App\Http\Requests\Seller;

use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreProductImageRequest extends FormRequest
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
            'images' => ['required', 'array', 'min:1', 'max:5'],
            'images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $product = $this->route('product');
                $uploadedCount = count($this->file('images', []));

                if ($product instanceof Product && $product->images()->count() + $uploadedCount > 5) {
                    $validator->errors()->add('images', 'Total foto barang tidak boleh lebih dari lima.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'images.*.mimes' => 'Foto harus berformat JPG, JPEG, PNG, atau WebP.',
            'images.*.max' => 'Ukuran setiap foto maksimal 5 MB.',
        ];
    }
}
