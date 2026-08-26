<?php

declare(strict_types=1);

namespace App\Services\Platform;

use App\Enums\EvidenceEventType;
use App\Models\Client;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class PurgeClientTenantDataService
{
    public function __construct(
        private readonly RecordLifecycleEvidenceService $evidenceService,
    ) {}

    public function execute(Client $client): Client
    {
        if ($client->tenant_data_purged_at !== null) {
            return $client;
        }

        return DB::transaction(function () use ($client) {
            $clientId = (int) $client->id;

            $this->purgeStructures($clientId);
            $this->purgeTenantTables($clientId);

            User::query()
                ->where('primary_client_id', $clientId)
                ->update(['primary_client_id' => null]);

            $now = CarbonImmutable::now();

            $client->update([
                'name' => "Conjunto purgado #{$clientId}",
                'address' => null,
                'logo_path' => null,
                'access_url' => null,
                'login_suffix' => "purged-{$clientId}",
                'tenant_data_purged_at' => $now,
            ]);

            $this->evidenceService->record(
                EvidenceEventType::TenantPurged,
                'Acta de purga operativa (censo tenant)',
                [
                    'client_id' => $clientId,
                    'purged_at' => $now->toIso8601String(),
                ],
                $client->security_company_id,
                $clientId,
                $now,
            );

            return $client->fresh();
        });
    }

    private function purgeStructures(int $clientId): void
    {
        if (! Schema::hasTable('structures')) {
            return;
        }

        $maxPasses = 50;

        for ($i = 0; $i < $maxPasses; $i++) {
            $deleted = DB::table('structures')
                ->where('client_id', $clientId)
                ->whereRaw(
                    'id NOT IN (SELECT parent_id FROM (SELECT parent_id FROM structures WHERE client_id = ? AND parent_id IS NOT NULL) AS structure_parents)',
                    [$clientId]
                )
                ->delete();

            if ($deleted === 0) {
                break;
            }
        }
    }

    private function purgeTenantTables(int $clientId): void
    {
        foreach (config('retention.purge_tables', []) as $table) {
            if ($table === 'structures' || ! Schema::hasTable($table) || ! Schema::hasColumn($table, 'client_id')) {
                continue;
            }

            DB::table($table)->where('client_id', $clientId)->delete();
        }
    }
}
