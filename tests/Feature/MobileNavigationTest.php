<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_login_and_register_buttons(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Masuk')
            ->assertSee('Daftar')
            ->assertDontSee('Keluar');
    }

    public function test_seller_sees_seller_links_and_logout_button(): void
    {
        $seller = User::factory()->seller()->create();

        $this->actingAs($seller)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Seller')
            ->assertSee('Barang')
            ->assertSee('Bayar')
            ->assertSee('Akun')
            ->assertSee('Keluar');
    }

    public function test_admin_sees_admin_nav_link(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Admin')
            ->assertSee('Akun')
            ->assertSee('Keluar');
    }

    public function test_user_can_logout_via_post(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('home'));

        $this->assertGuest();
    }
}
