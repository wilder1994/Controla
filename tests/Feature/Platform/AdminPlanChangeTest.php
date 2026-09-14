<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Enums\CompanyPackageSku;
use App\Enums\SupervisionPackageSku;
use App\Models\SecurityCompany;
use App\Models\User;
use Carbon\CarbonImmutable;
use Tests\TestCase;

final class AdminPlanChangeTest extends TestCase
{
    public function test_super_admin_applies_access_and_supervision_immediately(): void
    {
        $this->seedWithPilot();
        $admin = User::query()->where('email', 'admin@control-acceso.test')->firstOrFail();
        $company = SecurityCompany::query()->where('tax_id', '900123456-1')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.companies.plan.apply', $company), [
                'package_sku' => CompanyPackageSku::Pack10Manual->value,
                'billing_cycle' => 'monthly',
                'manual_seats' => 10,
                'hardware_seats' => 0,
                'supervision_package_sku' => SupervisionPackageSku::Unlimited->value,
                'apply_when' => 'now',
            ])
            ->assertRedirect(route('admin.companies.show', $company));

        $company->refresh();
        $this->assertSame(CompanyPackageSku::Pack10Manual, $company->package_sku);
        $this->assertSame(SupervisionPackageSku::Unlimited, $company->supervision_package_sku);
        $this->assertNull($company->scheduled_change_at);
    }

    public function test_super_admin_schedules_plan_for_period_end(): void
    {
        $this->seedWithPilot();
        $admin = User::query()->where('email', 'admin@control-acceso.test')->firstOrFail();
        $company = SecurityCompany::query()->where('tax_id', '900123456-1')->firstOrFail();
        $ends = CarbonImmutable::parse($company->package_ends_at);

        $this->actingAs($admin)
            ->post(route('admin.companies.plan.apply', $company), [
                'package_sku' => CompanyPackageSku::Pack10Manual->value,
                'billing_cycle' => 'monthly',
                'manual_seats' => 10,
                'hardware_seats' => 0,
                'supervision_package_sku' => SupervisionPackageSku::Sit5->value,
                'apply_when' => 'period_end',
            ])
            ->assertRedirect(route('admin.companies.show', $company));

        $company->refresh();
        $this->assertSame(CompanyPackageSku::Pack50Manual, $company->package_sku);
        $this->assertSame(CompanyPackageSku::Pack10Manual->value, $company->scheduled_package_sku);
        $this->assertSame(SupervisionPackageSku::Sit5->value, $company->scheduled_supervision_package_sku);
        $this->assertTrue($company->scheduled_change_at->equalTo($ends));
    }

    public function test_super_admin_schedules_plan_on_a_date(): void
    {
        $this->seedWithPilot();
        $admin = User::query()->where('email', 'admin@control-acceso.test')->firstOrFail();
        $company = SecurityCompany::query()->where('tax_id', '900123456-1')->firstOrFail();
        $date = CarbonImmutable::now()->addDays(10)->toDateString();

        $this->actingAs($admin)
            ->post(route('admin.companies.plan.apply', $company), [
                'package_sku' => $company->package_sku->value,
                'billing_cycle' => $company->billing_cycle->value,
                'manual_seats' => $company->package_manual_seats,
                'hardware_seats' => $company->package_hardware_seats,
                'supervision_package_sku' => SupervisionPackageSku::Sit50->value,
                'apply_when' => 'on_date',
                'effective_on' => $date,
            ])
            ->assertRedirect(route('admin.companies.show', $company));

        $company->refresh();
        $this->assertSame(SupervisionPackageSku::Sit10, $company->supervision_package_sku);
        $this->assertSame(SupervisionPackageSku::Sit50->value, $company->scheduled_supervision_package_sku);
        $this->assertSame($date, $company->scheduled_change_at->toDateString());
    }
}
