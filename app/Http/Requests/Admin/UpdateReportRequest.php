<?php

namespace App\Http\Requests\Admin;

use App\Models\Report;
use App\ReportStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateReportRequest extends FormRequest
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
            'status' => ['required', Rule::enum(ReportStatus::class)],
            'hide_product' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('status')) {
                    return;
                }

                $report = $this->route('report');
                $nextStatus = ReportStatus::tryFrom($this->string('status')->toString());

                if (! $report instanceof Report || $nextStatus === null || ! $report->status->canTransitionTo($nextStatus)) {
                    $validator->errors()->add('status', 'Perubahan status laporan tidak diizinkan.');
                }

                if ($this->boolean('hide_product') && ($report?->status !== ReportStatus::Reviewed || $nextStatus !== ReportStatus::Resolved)) {
                    $validator->errors()->add('hide_product', 'Produk hanya dapat disembunyikan ketika laporan yang ditinjau diselesaikan.');
                }
            },
        ];
    }
}
