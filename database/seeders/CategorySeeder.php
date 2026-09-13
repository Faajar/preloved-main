<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->categories() as $name) {
            Category::query()->firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'is_active' => true],
            );
        }
    }

    /**
     * @return list<string>
     */
    private function categories(): array
    {
        return [
            'Fashion',
            'Sepatu',
            'Elektronik',
            'Aksesoris',
            'Buku',
            'Hobi',
            'Rumah Tangga',
            'Lainnya',
        ];
    }
}
