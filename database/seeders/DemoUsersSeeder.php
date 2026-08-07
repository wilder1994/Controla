<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Client;
use App\Models\ClientUserAssignment;
use App\Models\SecurityCompany;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Usuarios demo — credenciales en README.md.
 * Roles de producto: Vigilante (`guardia`), Supervisor de vigilancia (`supervisor`).
 */
final class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPlatformUsers();
        $this->seedCompanyAdmin();
        $this->seedCompanySupervisor();
        $this->seedClientAdmin();
        $this->linkOperationalUsersToPilotClient();
    }

    private function seedPlatformUsers(): void
    {
        $superAdmin = User::query()->updateOrCreate(
            ['email' => 'admin@control-acceso.test'],
            [
                'name' => 'Súper Administrador',
                'password' => 'Admin123!',
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $superAdmin->syncRoles(['super-admin']);
    }

    private function seedCompanyAdmin(): void
    {
        $company = SecurityCompany::query()->where('tax_id', '900123456-1')->first();

        if ($company === null) {
            $this->command?->warn('DemoUsersSeeder: empresa piloto no encontrada; omitiendo admin empresa.');

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
    }

    private function seedCompanySupervisor(): void
    {
        $company = SecurityCompany::query()->where('tax_id', '900123456-1')->first();

        if ($company === null) {
            return;
        }

        $supervisor = User::query()->updateOrCreate(
            ['email' => 'supervisor@sj-seguridad.test'],
            [
                'name' => 'Supervisor Zona Demo',
                'job_title' => 'Supervisor de vigilancia',
                'password' => 'Super123!',
                'email_verified_at' => now(),
                'is_active' => true,
                'security_company_id' => $company->id,
                'supervisor_code' => '123456',
            ]
        );
        $supervisor->syncRoles(['supervisor']);
    }

    private function seedClientAdmin(): void
    {
        $palmas = Client::query()->where('slug', 'palmas-del-ingenio')->first();
        $company = SecurityCompany::query()->where('tax_id', '900123456-1')->first();

        if ($palmas === null) {
            $this->command?->warn('DemoUsersSeeder: cliente piloto no encontrado; omitiendo admin cliente.');

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
            ]
        );
        $clientAdmin->syncRoles(['client-admin']);
        $this->assignClient($clientAdmin, $palmas, true);
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
