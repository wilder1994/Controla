<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\SecurityCompany;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CreateCompanyTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_company_from_admin_panel(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@control-acceso.test')->firstOrFail();

        $response = $this->actingAs($admin)->post(route('admin.companies.store'), [
            'legal_name' => 'Nueva Seguridad S.A.S.',
            'trade_name' => 'Nueva Seguridad',
            'tax_id' => '901777666-3',
            'party_type' => 'legal_entity',
            'email' => 'contacto@nueva-seguridad.test',
            'phone' => '+57 300 999 0000',
            'address' => 'Calle 1 # 2-3',
            'city' => 'Medellín',
            'department' => 'Antioquia',
            'latitude' => 4.71,
            'longitude' => -74.07,
            'package_sku' => 'pack_1_manual',
            'billing_cycle' => 'monthly',
        ]);

        $company = SecurityCompany::query()->where('tax_id', '901777666-3')->firstOrFail();
        $response->assertRedirect(route('admin.companies.show', $company));
        $this->assertSame('Nueva Seguridad', $company->trade_name);
        $this->assertNotNull($company->package_sku);
    }
}
