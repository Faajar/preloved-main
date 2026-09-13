<?php

namespace App\Http\Controllers\Admin;

use App\Actions\RecordModerationAudit;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingRequest;
use App\Models\ModerationAudit;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class SettingController extends Controller
{
    public function edit(): View
    {
        $setting = Setting::query()->firstOrCreate(
            ['id' => 1],
            [
                'listing_fee_enabled' => false,
                'boost_fee_enabled' => false,
                'listing_fee' => 3000,
                'boost_fee' => 3000,
                'boost_duration_hours' => 24,
            ]
        );

        $audits = ModerationAudit::query()
            ->where('action', 'setting.updated')
            ->with('actor:id,name')
            ->latest()
            ->limit(20)
            ->get();

        return view('admin.settings.edit', compact('setting', 'audits'));
    }

    public function update(UpdateSettingRequest $request, RecordModerationAudit $recordAudit): RedirectResponse
    {
        DB::transaction(function () use ($request, $recordAudit): void {
            $setting = Setting::query()->lockForUpdate()->firstOrCreate(
                ['id' => 1],
                [
                    'listing_fee_enabled' => false,
                    'boost_fee_enabled' => false,
                    'listing_fee' => 3000,
                    'boost_fee' => 3000,
                    'boost_duration_hours' => 24,
                ]
            );

            $before = $setting->only([
                'listing_fee_enabled',
                'listing_fee',
                'boost_fee_enabled',
                'boost_fee',
                'boost_duration_hours',
            ]);

            $setting->update($request->safe()->only([
                'listing_fee_enabled',
                'listing_fee',
                'boost_fee_enabled',
                'boost_fee',
                'boost_duration_hours',
            ]));

            $after = $setting->only([
                'listing_fee_enabled',
                'listing_fee',
                'boost_fee_enabled',
                'boost_fee',
                'boost_duration_hours',
            ]);

            if ($before !== $after) {
                $recordAudit->handle(
                    $request->user(),
                    'setting.updated',
                    $setting,
                    before: $before,
                    after: $after,
                    reason: $request->string('reason')->toString(),
                );
            }
        });

        return redirect()->route('admin.settings.edit')->with('status', 'Pengaturan monetisasi dan boost berhasil diperbarui.');
    }
}
