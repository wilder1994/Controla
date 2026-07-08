<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ClientPlanTier;
use App\Models\AccessLog;
use App\Models\Building;
use App\Models\Client;
use App\Models\ClientUserAssignment;
use App\Models\Correspondence;
use App\Models\GuardLog;
use App\Models\HousingUnit;
use App\Models\Location;
use App\Models\PreAuthorization;
use App\Models\Resident;
use App\Models\SecurityCompany;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Visitor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $company = SecurityCompany::query()->firstOrCreate(
            ['tax_id' => '900123456-1'],
            [
                'legal_name' => 'SJ Seguridad Privada S.A.S.',
                'trade_name' => 'SJ Seguridad / BigSky',
                'email' => 'contacto@sj-seguridad.test',
                'phone' => '+57 300 000 0000',
                'is_active' => true,
            ]
        );

        $palmas = Client::query()->firstOrCreate(
            ['security_company_id' => $company->id, 'slug' => 'palmas-del-ingenio'],
            [
                'name' => 'Palmas del Ingenio',
                'login_suffix' => 'palmasdelingenio',
                'plan_tier' => ClientPlanTier::Deluxe,
                'max_structures' => ClientPlanTier::Deluxe->maxStructures(),
                'access_url' => 'https://controla.test',
                'is_active' => true,
            ]
        );

        $torres = Client::query()->firstOrCreate(
            ['security_company_id' => $company->id, 'slug' => 'torres-loma'],
            [
                'name' => 'Torres de la Loma',
                'login_suffix' => 'torresloma',
                'plan_tier' => ClientPlanTier::Economic,
                'max_structures' => ClientPlanTier::Economic->maxStructures(),
                'access_url' => 'https://controla.test',
                'is_active' => true,
            ]
        );

        $this->backfillOperationalData($palmas->id);

        $companyAdmin = User::query()->firstOrCreate(
            ['email' => 'empresa@sj-seguridad.test'],
            [
                'name' => 'Admin Empresa SJ',
                'password' => bcrypt('Empresa123!'),
                'email_verified_at' => now(),
                'is_active' => true,
                'security_company_id' => $company->id,
            ]
        );
        $companyAdmin->syncRoles(['company-admin']);

        $clientAdmin = User::query()->firstOrCreate(
            ['email' => 'admin@palmasdelingenio.test'],
            [
                'name' => 'Admin Cliente Palmas',
                'password' => bcrypt('Cliente123!'),
                'email_verified_at' => now(),
                'is_active' => true,
                'primary_client_id' => $palmas->id,
            ]
        );
        $clientAdmin->syncRoles(['client-admin']);
        $this->assignClient($clientAdmin, $palmas, true);

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

    private function backfillOperationalData(int $clientId): void
    {
        $tables = [
            Location::class,
            Building::class,
            HousingUnit::class,
            Resident::class,
            Visitor::class,
            Vehicle::class,
            AccessLog::class,
            PreAuthorization::class,
            Correspondence::class,
            GuardLog::class,
        ];

        foreach ($tables as $modelClass) {
            DB::table((new $modelClass)->getTable())
                ->whereNull('client_id')
                ->update(['client_id' => $clientId]);
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
