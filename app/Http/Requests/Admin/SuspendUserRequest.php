<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SuspendUserRequest extends FormRequest
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
            'type' => ['required', Rule::in(['temporary', 'permanent'])],
            'days' => [Rule::requiredIf($this->input('type') === 'temporary'), 'nullable', 'integer', 'min:1', 'max:365'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Tipe penangguhan (sementara atau permanen) wajib dipilih.',
            'days.required' => 'Jumlah hari penangguhan sementara wajib diisi.',
            'days.min' => 'Durasi penangguhan minimal 1 hari.',
            'days.max' => 'Durasi penangguhan maksimal 365 hari.',
            'reason.required' => 'Alasan penangguhan akun wajib diisi.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'reason' => $this->string('reason')->trim()->toString(),
        ]);
    }
}
