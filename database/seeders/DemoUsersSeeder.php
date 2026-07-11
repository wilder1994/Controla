<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Client;
use App\Models\ClientUserAssignment;
use App\Models\SecurityCompany;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Usuarios demo del sistema — credenciales documentadas en README.md.
 */
final class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPlatformUsers();
        $this->seedCompanyAdmin();
        $this->seedClientAdmin();
        $this->linkLegacyUsersToPilotClient();
    }

    private function seedPlatformUsers(): void
    {
        $company = SecurityCompany::query()->where('tax_id', '900123456-1')->first();

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

        $guardia = User::query()->updateOrCreate(
            ['email' => 'guardia@control-acceso.test'],
            [
                'name' => 'Guardia Portero',
                'password' => 'Guardia123!',
                'email_verified_at' => now(),
                'is_active' => true,
                'security_company_id' => $company?->id,
            ]
        );
        $guardia->syncRoles(['guardia']);

        if ($company !== null) {
            $supervisor = User::query()->updateOrCreate(
                ['email' => 'supervisor@sj-seguridad.test'],
                [
                    'name' => 'Supervisor SJ',
                    'password' => 'Supervisor123!',
                    'email_verified_at' => now(),
                    'is_active' => true,
                    'security_company_id' => $company->id,
                ]
            );
            $supervisor->syncRoles(['supervisor']);
        }

        $anfitrion = User::query()->updateOrCreate(
            ['email' => 'anfitrion@control-acceso.test'],
            [
                'name' => 'Residente Ejemplo',
                'password' => 'Anfitrion123!',
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $anfitrion->syncRoles(['resident']);
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

    private function seedClientAdmin(): void
    {
        $palmas = Client::query()->where('slug', 'palmas-del-ingenio')->first();

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
                'primary_client_id' => $palmas->id,
            ]
        );
        $clientAdmin->syncRoles(['client-admin']);
        $this->assignClient($clientAdmin, $palmas, true);
    }

    private function linkLegacyUsersToPilotClient(): void
    {
        $palmas = Client::query()->where('slug', 'palmas-del-ingenio')->first();

        if ($palmas === null) {
            return;
        }

        $guardia = User::query()->where('email', 'guardia@control-acceso.test')->first();
        if ($guardia) {
            $guardia->update(['primary_client_id' => $palmas->id]);
            $guardia->syncRoles(['guardia']);
            $this->assignClient($guardia, $palmas, true);
        }

        $legacyAdmin = User::query()->where('email', 'admin@control-acceso.test')->first();
        if ($legacyAdmin) {
            $legacyAdmin->update(['primary_client_id' => $palmas->id]);
            $this->assignClient($legacyAdmin, $palmas, true);
        }
    }

    private function assignClient(User $user, Client $client, bool $primary = false): void
    {
        ClientUserAssignment::query()->firstOrCreate(
            ['user_id' => $user->id, 'client_id' => $client->id],
            ['is_primary' => $primary, 'assigned_at' => now()]
        );

        if ($primary) {
            $user->update(['primary_client_id' => $client->id]);
        }
    }
}
