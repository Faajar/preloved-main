<?php

namespace Database\Factories;

use App\Models\ModerationAudit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ModerationAudit>
 */
class ModerationAuditFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'actor_id' => User::factory()->admin(),
            'action' => 'category.updated',
            'subject_type' => Category::class,
            'subject_id' => null,
            'before' => ['name' => 'Kategori Lama'],
            'after' => ['name' => 'Kategori Baru'],
            'reason' => fake()->sentence(),
        ];
    }
}
