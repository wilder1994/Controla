<?php

declare(strict_types=1);

namespace Tests\Feature\Company;

use App\Models\SecurityCompany;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CompanyBillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_billing_page_shows_membership_and_history(): void
    {
        $this->seedWithPilot();

        $companyUser = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();

        $response = $this->actingAs($companyUser)->get(route('company.billing.index'));

        $response->assertOk();
        $response->assertSee('Membresía');
        $response->assertSee('Historial de pagos');
        $response->assertSee('Línea de tiempo');
        $response->assertSee('Facturas');
        $response->assertSee('Reactivar membresía');
    }

    public function test_company_can_cancel_and_undo_membership(): void
    {
        $this->seedWithPilot();

        $companyUser = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();
        $company = SecurityCompany::query()->where('tax_id', '900123456-1')->firstOrFail();

        $cancel = $this->actingAs($companyUser)->post(
            route('company.billing.membership.cancel'),
            ['reason' => 'Ya no necesitamos el servicio este mes'],
        );

        $cancel->assertRedirect(route('company.billing.index'));
        $company->refresh();
        $this->assertTrue($company->hasPendingCancellation());

        $undo = $this->actingAs($companyUser)->post(
            route('company.billing.membership.undo-cancel'),
        );

        $undo->assertRedirect(route('company.billing.index'));
        $undo->assertSessionHas('success');
        $company->refresh();
        $this->assertFalse($company->hasPendingCancellation());
    }
}
