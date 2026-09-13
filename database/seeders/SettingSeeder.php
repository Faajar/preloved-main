<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        Setting::query()->firstOrCreate(
            ['id' => 1],
            [
                'listing_fee_enabled' => false,
                'boost_fee_enabled' => false,
                'listing_fee' => 3000,
                'boost_fee' => 3000,
                'boost_duration_hours' => null,
            ],
        );
    }
}
