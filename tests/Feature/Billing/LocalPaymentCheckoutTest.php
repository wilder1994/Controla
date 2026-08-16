<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Models\CommercialPayment;
use App\Models\SecurityCompany;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LocalPaymentCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_admin_can_complete_simulated_online_payment(): void
    {
        $this->seedWithPilot();

        $admin = User::query()->where('email', 'admin@control-acceso.test')->firstOrFail();
        $company = SecurityCompany::query()->where('tax_id', '900123456-1')->firstOrFail();
        $companyUser = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();

        $this->actingAs($admin)->post(
            route('admin.documents.expedientes.acceptance', $company),
            [
                'representative_name' => 'Rep Legal',
                'representative_role' => 'Gerente',
                'representative_document_type' => 'CC',
                'representative_document_number' => '1098765432',
                ...$this->acceptAllCorpusDocs($company->package_sku),
            ],
        )->assertRedirect();

        $checkoutResponse = $this->actingAs($companyUser)->post(route('company.billing.checkout'));
        $checkoutResponse->assertRedirect();

        $payment = CommercialPayment::query()
            ->where('security_company_id', $company->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $this->assertSame('local', $payment->gateway_driver);
        $this->assertSame('gateway', $payment->method->value);

        $approve = $this->actingAs($companyUser)->post(route('billing.checkout.approve', $payment));
        $approve->assertRedirect(route('company.billing.index'));
        $approve->assertSessionHas('success');

        $payment->refresh();
        $this->assertSame('completed', $payment->status->value);

        $this->assertDatabaseHas('platform_documents', [
            'security_company_id' => $company->id,
            'type' => 'invoice',
            'is_demo' => true,
        ]);
    }

    public function test_reject_simulated_payment_does_not_create_invoice(): void
    {
        $this->seedWithPilot();

        $admin = User::query()->where('email', 'admin@control-acceso.test')->firstOrFail();
        $company = SecurityCompany::query()->where('tax_id', '900123456-1')->firstOrFail();
        $companyUser = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();

        $this->actingAs($admin)->post(
            route('admin.documents.expedientes.acceptance', $company),
            [
                'representative_name' => 'Rep Legal',
                'representative_role' => 'Gerente',
                'representative_document_type' => 'CC',
                'representative_document_number' => '1098765432',
                ...$this->acceptAllCorpusDocs($company->package_sku),
            ],
        );

        $this->actingAs($companyUser)->post(route('company.billing.checkout'));

        $payment = CommercialPayment::query()
            ->where('security_company_id', $company->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $reject = $this->actingAs($companyUser)->post(route('billing.checkout.reject', $payment));
        $reject->assertRedirect(route('company.billing.index'));
        $reject->assertSessionHas('warning');

        $payment->refresh();
        $this->assertSame('failed', $payment->status->value);

        $this->assertFalse(
            \App\Models\PlatformDocument::query()
                ->where('security_company_id', $company->id)
                ->where('type', 'invoice')
                ->exists()
        );
    }

    public function test_cannot_checkout_without_acceptance(): void
    {
        $this->seedWithPilot();

        $companyUser = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();

        $response = $this->actingAs($companyUser)->post(route('company.billing.checkout'));

        $response->assertRedirect(route('company.billing.index'));
        $response->assertSessionHas('warning');
    }
}
