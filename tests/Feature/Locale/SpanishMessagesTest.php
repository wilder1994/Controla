<?php

declare(strict_types=1);

namespace Tests\Feature\Locale;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\Rules\Password;
use Tests\TestCase;

class SpanishMessagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_validation_is_spanish(): void
    {
        $this->from('/login')->post('/login', [])->assertSessionHasErrors(['email', 'password']);

        $email = session('errors')->first('email');
        $password = session('errors')->first('password');

        $this->assertStringContainsString('obligatorio', $email);
        $this->assertStringContainsString('usuario o correo', $email);
        $this->assertStringContainsString('contraseña', $password);
        $this->assertStringNotContainsString('required', strtolower($email.' '.$password));
        $this->assertStringNotContainsString('The ', $email);
    }

    public function test_failed_login_is_spanish(): void
    {
        $this->from('/login')->post('/login', [
            'email' => 'nadie@example.test',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertSame('Usuario o contraseña no coinciden.', session('errors')->first('email'));
    }

    public function test_common_rules_are_spanish(): void
    {
        $validator = validator(
            ['username' => 'Bad User', 'email' => 'no-es-correo', 'password' => '123'],
            [
                'username' => ['regex:/^[a-z]+\.[a-z]+\.\d{4}$/'],
                'email' => ['email'],
                'password' => [Password::defaults()],
            ],
        );

        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('formato', $validator->errors()->first('username'));
        $this->assertStringContainsString('correo', $validator->errors()->first('email'));
        $this->assertStringContainsString('contraseña', strtolower($validator->errors()->first('password')));
        $this->assertStringNotContainsString('The ', implode(' ', $validator->errors()->all()));
    }
}
