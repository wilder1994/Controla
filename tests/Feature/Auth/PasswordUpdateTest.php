<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
        $this->assertFalse($user->must_change_password);
    }

    public function test_forced_password_screen_renders_when_flag_is_set(): void
    {
        $user = User::factory()->create([
            'must_change_password' => true,
        ]);

        $this->actingAs($user)
            ->get(route('password.first'))
            ->assertOk()
            ->assertSee('Cambia tu contraseña');
    }

    public function test_forced_password_screen_redirects_home_when_flag_is_clear(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('password.first'))
            ->assertRedirect(route('home'));
    }

    public function test_home_redirects_to_forced_password_screen(): void
    {
        $user = User::factory()->create([
            'must_change_password' => true,
        ]);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertRedirect(route('password.first'));
    }

    public function test_profile_redirects_to_forced_password_screen(): void
    {
        $user = User::factory()->create([
            'must_change_password' => true,
        ]);

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertRedirect(route('password.first'));
    }

    public function test_forced_password_change_clears_flag_and_leaves_first_password_screen(): void
    {
        $user = User::factory()->create([
            'must_change_password' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->from(route('password.first'))
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('home'));

        $this->assertFalse($user->refresh()->must_change_password);
    }

    public function test_correct_password_must_be_provided_to_update_password(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'wrong-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('updatePassword', 'current_password')
            ->assertRedirect('/profile');
    }
}
