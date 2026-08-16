<?php

declare(strict_types=1);

namespace Tests\Unit\Platform;

use App\Enums\ClientLifecycle;
use App\Models\Client;
use App\Models\Location;
use App\Models\SecurityCompany;
use App\Services\Platform\ProcessDataRetentionPurgeService;
use App\Services\Platform\PurgeClientTenantDataService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DataRetentionPurgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_purges_tenant_data_after_retention_period(): void
    {
        $this->seedWithPilot();

        $client = Client::query()->where('slug', 'palmas-del-ingenio')->firstOrFail();
        $client->update([
            'lifecycle' => ClientLifecycle::Released,
            'released_at' => CarbonImmutable::now()->subDays(400),
            'is_active' => false,
        ]);

        $this->assertGreaterThan(0, Location::query()->where('client_id', $client->id)->count());

        $result = app(ProcessDataRetentionPurgeService::class)->execute();

        $this->assertSame(1, $result['clients_purged']);

        $client->refresh();
        $this->assertNotNull($client->tenant_data_purged_at);
        $this->assertSame(0, Location::query()->where('client_id', $client->id)->count());
        $this->assertStringContainsString('purgado', $client->name);
    }

    public function test_does_not_purge_active_clients(): void
    {
        $this->seedWithPilot();

        $client = Client::query()->where('slug', 'palmas-del-ingenio')->firstOrFail();
        $this->assertSame(ClientLifecycle::Active, $client->lifecycle);

        $purged = app(ProcessDataRetentionPurgeService::class)->execute();

        $this->assertSame(0, $purged['clients_purged']);
        $this->assertGreaterThan(0, Location::query()->where('client_id', $client->id)->count());
    }

    public function test_anonymizes_archived_company_after_commercial_retention(): void
    {
        $this->seedWithPilot();

        $company = SecurityCompany::query()->where('tax_id', '900123456-1')->firstOrFail();
        $company->update([
            'archived_at' => CarbonImmutable::now()->subYears(6),
            'is_active' => false,
        ]);

        $result = app(ProcessDataRetentionPurgeService::class)->execute();

        $this->assertSame(1, $result['companies_anonymized']);

        $company->refresh();
        $this->assertNotNull($company->commercial_anonymized_at);
        $this->assertStringContainsString('archivada', strtolower($company->trade_name));
    }
}
