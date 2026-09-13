<?php

namespace Database\Seeders;

use App\Models\User;
use App\UserRole;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            SettingSeeder::class,
        ]);

        $isProduction = app()->isProduction() || config('app.env') === 'production';

        if (! $isProduction) {
            User::query()->firstOrCreate([
                'email' => 'test@example.com',
            ], [
                'name' => 'Test User',
                'password' => 'password',
                'role' => UserRole::Buyer,
            ]);
        }
    }
}
