<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use App\Models\Employee;
use App\Services\Auth\AllocateLoginUsername;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AllocateLoginUsernameTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_username_is_first_name_last_name_and_four_digits(): void
    {
        $employee = new Employee([
            'first_names' => 'Andrés Felipe',
            'last_name_paternal' => 'Pérez',
            'last_name_maternal' => 'Gómez',
        ]);

        $username = app(AllocateLoginUsername::class)->forEmployee($employee);

        $this->assertMatchesRegularExpression('/^andres\.perez\.\d{4}$/', $username);
    }
}
