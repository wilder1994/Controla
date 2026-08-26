<?php

declare(strict_types=1);

namespace Tests\Feature\Company;

use App\Enums\SupervisionPackageSku;
use App\Models\User;
use App\Services\Tenant\AssignCompanySupervisionPackageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CompanySupervisionMapTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_admin_can_open_supervision_map(): void
    {
        $this->seedWithPilot();

        $user = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();
        app(AssignCompanySupervisionPackageService::class)->execute(
            $user->securityCompany,
            SupervisionPackageSku::Sit1,
        );

        $response = $this->actingAs($user)->get(route('company.supervision.index'));

        $response->assertOk();
        $response->assertSee('Supervisión');
        $response->assertSee('En vivo');
        $response->assertSee('Historial / replay');
        $response->assertSee('Resumen');
        $response->assertSee('Descargar PPTX');
        $response->assertSee('Ocho módulos');
    }

    public function test_company_admin_can_download_supervision_pptx(): void
    {
        $this->seedWithPilot();

        $user = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();

        $response = $this->actingAs($user)->get(route('company.supervision.report'));

        $response->assertOk();
        $response->assertHeader('content-disposition');
        $this->assertStringContainsString(
            'Informe_Supervision_',
            (string) $response->headers->get('content-disposition'),
        );
    }
}
