<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Mostrar contraseña', false);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('profile.edit', absolute: false));
    }

    public function test_users_with_forced_password_are_sent_to_first_password_screen(): void
    {
        $user = User::factory()->create([
            'must_change_password' => true,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('password.first', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_supervisor_web_home_is_app_only(): void
    {
        $this->seedWithPilot();
        $user = $this->companySupervisor();
        $user->update(['must_change_password' => false]);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertRedirect(route('supervisor.app-only'));

        $this->actingAs($user)
            ->get(route('company.dashboard'))
            ->assertRedirect(route('supervisor.app-only'));

        $this->actingAs($user)
            ->get(route('supervisor.app-only'))
            ->assertOk()
            ->assertSee('App de campo');
    }
}
