<?php

declare(strict_types=1);

namespace Tests\Feature\Public;

use Tests\TestCase;

final class FieldAppPwaTest extends TestCase
{
    public function test_campo_pwa_serves_first_login_username_fields(): void
    {
        $this->get('/campo')
            ->assertOk()
            ->assertSee('Primer ingreso', false)
            ->assertSee('new-username', false)
            ->assertSee('Nuevo usuario', false);
    }
}
