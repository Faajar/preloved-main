<?php

namespace App\Http\Requests\Admin;

use App\Models\Category;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique(Category::class, 'name'),
            ],
            'slug' => [
                'required',
                'string',
                'max:100',
                'alpha_dash:ascii',
                Rule::unique(Category::class, 'slug'),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'Nama kategori sudah digunakan.',
            'slug.unique' => 'Slug kategori sudah digunakan.',
            'slug.alpha_dash' => 'Slug hanya boleh berisi huruf kecil, angka, dan tanda hubung.',
            'reason.required' => 'Alasan perubahan wajib diisi untuk audit.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $name = $this->string('name')->trim()->toString();

        $this->merge([
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $this->filled('description') ? $this->string('description')->trim()->toString() : null,
            'reason' => $this->string('reason')->trim()->toString(),
        ]);
    }
}
