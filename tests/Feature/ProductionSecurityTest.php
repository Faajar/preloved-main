<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_are_present_on_web_responses(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }

    public function test_database_seeder_does_not_create_dummy_user_in_production(): void
    {
        config()->set('app.env', 'production');

        (new DatabaseSeeder)->run();

        $this->assertDatabaseMissing('users', [
            'email' => 'test@example.com',
        ]);
    }

    public function test_admin_cannot_enable_fees_in_production_when_doku_is_not_production_ready(): void
    {
        config()->set('app.env', 'production');
        config()->set('services.doku.base_url', 'https://api-sandbox.doku.com');
        config()->set('app.url', 'http://localhost:8000');

        $admin = User::factory()->admin()->create();
        Setting::factory()->create(['id' => 1]);

        $response = $this->actingAs($admin)->put(route('admin.settings.update'), [
            'listing_fee_enabled' => 1,
            'listing_fee' => 3000,
            'boost_fee_enabled' => 1,
            'boost_fee' => 3000,
            'boost_duration_hours' => 24,
            'reason' => 'Mencoba aktifkan fee di production.',
        ]);

        $response->assertSessionHasErrors(['listing_fee_enabled', 'boost_fee_enabled']);
    }

    public function test_webhook_rejects_oversized_payload_with_400(): void
    {
        $largePayload = str_repeat('A', 70000); // 70 KB > 64 KB

        $response = $this->call(
            'POST',
            route('payments.doku.notifications'),
            [],
            [],
            [],
            [
                'HTTP_CLIENT_ID' => config('services.doku.client_id'),
                'HTTP_REQUEST_ID' => 'REQ-LARGE-001',
                'HTTP_REQUEST_TIMESTAMP' => now()->toIso8601String(),
                'HTTP_SIGNATURE' => 'dummy',
            ],
            $largePayload
        );

        $response->assertStatus(400);
    }

    public function test_webhook_rejects_malformed_json_cleanly_with_400(): void
    {
        $response = $this->call(
            'POST',
            route('payments.doku.notifications'),
            [],
            [],
            [],
            [
                'HTTP_CLIENT_ID' => config('services.doku.client_id'),
                'HTTP_REQUEST_ID' => 'REQ-INVALID-001',
                'HTTP_REQUEST_TIMESTAMP' => now()->toIso8601String(),
                'HTTP_SIGNATURE' => 'dummy',
            ],
            'invalid { json [ payload'
        );

        $response->assertStatus(400);
    }
}
