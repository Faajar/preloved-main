<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_profile_settings(): void
    {
        $this->get(route('profile.edit'))->assertRedirect(route('login'));
        $this->put(route('profile.update'), ['name' => 'Nama Baru'])->assertRedirect(route('login'));
    }

    public function test_user_can_view_profile_page_and_see_current_details(): void
    {
        $user = User::factory()->create([
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'whatsapp_number' => '6281234567890',
        ]);

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertSee('budi@example.com')
            ->assertSee('+6281234567890');
    }

    public function test_user_can_update_name_and_indonesian_whatsapp_number(): void
    {
        $user = User::factory()->create([
            'name' => 'Nama Lama',
            'whatsapp_number' => null,
        ]);

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'name' => 'Nama Baru',
                'whatsapp_number' => '081298765432',
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('status');

        $user->refresh();
        $this->assertSame('Nama Baru', $user->name);
        $this->assertSame('6281298765432', $user->whatsapp_number);
    }

    public function test_whatsapp_number_can_be_updated_with_plus_62_prefix(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'name' => $user->name,
                'whatsapp_number' => '+628987654321',
            ])
            ->assertRedirect(route('profile.edit'));

        $this->assertSame('628987654321', $user->refresh()->whatsapp_number);
    }

    public function test_invalid_whatsapp_number_format_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'name' => $user->name,
                'whatsapp_number' => '12345',
            ])
            ->assertSessionHasErrors('whatsapp_number');
    }
}
