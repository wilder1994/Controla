<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SingleSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_second_web_login_asks_before_replacing(): void
    {
        $user = User::factory()->create();

        $user->forceFill(['single_session_token' => 'ocupada'])->save();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('session_takeover');

        $firstToken = $user->fresh()->single_session_token;

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'replace_session' => '1',
        ])->assertRedirect();

        $this->assertNotSame($firstToken, $user->fresh()->single_session_token);
    }

    public function test_second_supervisor_login_returns_409_until_replaced(): void
    {
        $this->seedWithPilot();
        $user = $this->companySupervisor();

        $first = $this->postJson('/api/supervision/login', [
            'login' => $user->username,
            'password' => self::COMPANY_SUPERVISOR_PASSWORD,
        ]);
        $first->assertOk();
        $this->assertNotEmpty($first->json('token'));

        $this->postJson('/api/supervision/login', [
            'login' => $user->username,
            'password' => self::COMPANY_SUPERVISOR_PASSWORD,
        ])->assertStatus(409)->assertJsonPath('code', 'session_active');

        $second = $this->postJson('/api/supervision/login', [
            'login' => $user->username,
            'password' => self::COMPANY_SUPERVISOR_PASSWORD,
            'replace_session' => true,
        ]);
        $second->assertOk();
        $this->assertNotSame($first->json('token'), $second->json('token'));
    }
}
