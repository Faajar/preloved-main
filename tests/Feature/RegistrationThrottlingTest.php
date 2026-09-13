<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationThrottlingTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_is_throttled_after_exceeding_rate_limit(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('register'), [
                'name' => 'User '.$i,
                'email' => 'test'.$i.'@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);
        }

        // 6th attempt should receive 429 Too Many Requests
        $response = $this->post(route('register'), [
            'name' => 'Blocked User',
            'email' => 'blocked@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(429);
    }
}
