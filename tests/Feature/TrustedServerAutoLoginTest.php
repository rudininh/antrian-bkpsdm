<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class TrustedServerAutoLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_trusted_server_host_auto_logs_in_the_configured_user(): void
    {
        Config::set('trusted_server.hosts', ['desktop-904qfme']);
        Config::set('trusted_server.user_email', 'admin@bkpsdm.test');

        $user = User::factory()->create([
            'name' => 'Admin Server',
            'email' => 'admin@bkpsdm.test',
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $response = $this->get('http://desktop-904qfme/profile');

        $response->assertOk();

        $this->assertAuthenticatedAs($user);
    }

    public function test_untrusted_host_still_requires_login(): void
    {
        Config::set('trusted_server.hosts', ['desktop-904qfme']);
        Config::set('trusted_server.user_email', 'admin@bkpsdm.test');

        User::factory()->create([
            'name' => 'Admin Server',
            'email' => 'admin@bkpsdm.test',
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $this->get('http://localhost/profile')
            ->assertRedirect(route('login', absolute: false));
    }
}
