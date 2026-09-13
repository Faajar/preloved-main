<?php

namespace App\Http\Requests\Seller;

use App\Models\Category;
use App\ProductCondition;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->filled('whatsapp_number')) {
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

    public function authorize(): bool
    {
        return $this->user()?->canSell() ?? false;
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
            'whatsapp_number' => [
                Rule::requiredIf($this->user()?->whatsapp_number === null),
                'nullable',
                'string',
                'regex:/\A628[1-9][0-9]{7,10}\z/',
            ],
            'images' => ['required', 'array', 'min:1', 'max:5'],
            'images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category_id.exists' => 'Kategori yang dipilih tidak tersedia.',
            'whatsapp_number.regex' => 'Nomor WhatsApp harus berupa nomor Indonesia yang valid.',
            'images.required' => 'Tambahkan minimal satu foto barang.',
            'images.max' => 'Maksimal lima foto untuk setiap barang.',
            'images.*.mimes' => 'Foto harus berformat JPG, JPEG, PNG, atau WebP.',
            'images.*.max' => 'Ukuran setiap foto maksimal 5 MB.',
        ];
    }
}
