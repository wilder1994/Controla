<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Client;
use App\Models\ClientUserAssignment;
use App\Models\SecurityCompany;
use App\Models\User;
use App\Services\User\EnsureClientScopeCatalog;
use App\Services\User\EnsureCompanyAdminCatalog;
use Illuminate\Database\Seeder;

/**
 * Usuarios demo de empresa/conjunto/ops (requiere TenantSeeder previo).
 * No crea supervisor: el acceso de vigilancia sale de un empleado en Usuarios.
 */
final class PilotUsersSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedCompanyAdmin();
        $this->seedClientAdmin();
        $this->linkOperationalUsersToPilotClient();
    }

    private function seedCompanyAdmin(): void
    {
        $company = SecurityCompany::query()->where('tax_id', '900123456-1')->first();

        if ($company === null) {
            $this->command?->warn('PilotUsersSeeder: empresa piloto no encontrada; omitiendo admin empresa.');

            return;
        }

        $companyAdmin = User::query()->updateOrCreate(
            ['email' => 'empresa@sj-seguridad.test'],
            [
                'name' => 'Admin Empresa SJ',
                'password' => 'Empresa123!',
                'email_verified_at' => now(),
                'is_active' => true,
                'security_company_id' => $company->id,
            ]
        );
        $companyAdmin->syncRoles(['company-admin']);
        app(EnsureCompanyAdminCatalog::class)->execute($companyAdmin);
    }

    private function seedClientAdmin(): void
    {
        $palmas = Client::query()->where('slug', 'palmas-del-ingenio')->first();
        $company = SecurityCompany::query()->where('tax_id', '900123456-1')->first();

        if ($palmas === null) {
            $this->command?->warn('PilotUsersSeeder: cliente piloto no encontrado; omitiendo admin cliente.');

            return;
        }

        $clientAdmin = User::query()->updateOrCreate(
            ['email' => 'admin@palmasdelingenio.test'],
            [
                'name' => 'Admin Cliente Palmas',
                'password' => 'Cliente123!',
                'email_verified_at' => now(),
                'is_active' => true,
                'security_company_id' => $company?->id,
                'primary_client_id' => $palmas->id,
                'admin_origin' => 'external',
            ]
        );
        $clientAdmin->syncRoles(['client-admin']);
        $this->assignClient($clientAdmin, $palmas, true);
        app(EnsureClientScopeCatalog::class)->execute($clientAdmin);
    }

    private function linkOperationalUsersToPilotClient(): void
    {
        $palmas = Client::query()->where('slug', 'palmas-del-ingenio')->first();
        $company = SecurityCompany::query()->where('tax_id', '900123456-1')->first();

        if ($palmas === null || $company === null) {
            return;
        }

        $vigilante = User::query()->updateOrCreate(
            ['email' => 'guardia@control-acceso.test'],
            [
                'name' => 'Vigilante Portero',
                'job_title' => 'Portería',
                'password' => 'Guardia123!',
                'email_verified_at' => now(),
                'is_active' => true,
                'security_company_id' => $company->id,
                'primary_client_id' => $palmas->id,
            ]
        );
        $vigilante->syncRoles(['guardia']);
        $this->assignClient($vigilante, $palmas, true);

        $anfitrion = User::query()->updateOrCreate(
            ['email' => 'anfitrion@control-acceso.test'],
            [
                'name' => 'Residente Ejemplo',
                'password' => 'Anfitrion123!',
                'email_verified_at' => now(),
                'is_active' => true,
                'primary_client_id' => $palmas->id,
            ]
        );
        $anfitrion->syncRoles(['resident']);
        $this->assignClient($anfitrion, $palmas, true);
    }

    private function assignClient(User $user, Client $client, bool $primary = false): void
    {
        ClientUserAssignment::query()->updateOrCreate(
            ['user_id' => $user->id, 'client_id' => $client->id],
            ['is_primary' => $primary, 'assigned_at' => now()]
        );

        if ($primary) {
            $user->update(['primary_client_id' => $client->id]);
        }
    }
}
