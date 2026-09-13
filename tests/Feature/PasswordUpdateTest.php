<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_can_be_updated_from_profile(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);

        $response = $this->actingAs($user)
            ->from(route('profile.edit'))
            ->put(route('password.change'), [
                'current_password' => 'old-password',
                'password' => 'new-password-1234',
                'password_confirmation' => 'new-password-1234',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('status');

        $this->assertTrue(Hash::check('new-password-1234', $user->refresh()->password));
    }

    public function test_correct_password_must_be_provided_to_update_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);

        $response = $this->actingAs($user)
            ->from(route('profile.edit'))
            ->put(route('password.change'), [
                'current_password' => 'wrong-password',
                'password' => 'new-password-1234',
                'password_confirmation' => 'new-password-1234',
            ]);

        $response->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('old-password', $user->refresh()->password));
    }
}
