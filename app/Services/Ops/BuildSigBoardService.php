<?php

declare(strict_types=1);

namespace App\Services\Ops;

use App\Enums\DocumentFolder;
use App\Enums\OperationalAlertType;
use App\Models\Client;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\Installation;
use App\Models\OperationalAlert;
use App\Models\SupervisorPost;
use App\Models\SupervisorShiftReview;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;

final class BuildSigBoardService
{
    /**
     * @param  list<int>|null  $installationIds
     * @return array<string, mixed>
     */
    public function forClient(Client $client, ?array $installationIds = null): array
    {
        $sites = Installation::query()
            ->withoutGlobalScopes()
            ->where('client_id', $client->id)
            ->where('is_active', true)
            ->when($installationIds !== null, fn ($q) => $q->whereIn('id', $installationIds))
            ->orderBy('name')
            ->get();

        return $this->assemble($client->security_company_id, $client, $sites);
    }

    /**
     * @return array<string, mixed>
     */
    public function forCompany(int $companyId): array
    {
        $sites = Installation::query()
            ->withoutGlobalScopes()
            ->where('is_active', true)
            ->whereHas('client', fn ($q) => $q->where('security_company_id', $companyId)->where('is_active', true))
            ->with('client:id,name,latitude,longitude')
            ->orderBy('name')
            ->get();

        return $this->assemble($companyId, null, $sites);
    }

    public function forTenant(TenantContext $tenant): array
    {
        $client = Client::query()->findOrFail((int) $tenant->clientId());

        return $this->forClient($client, $tenant->installationIds());
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Installation>  $sites
     * @return array<string, mixed>
     */
    private function assemble(int $companyId, ?Client $client, $sites): array
    {
        $siteIds = $sites->pluck('id')->map(fn ($id) => (int) $id)->all();
        $posts = SupervisorPost::query()
            ->withoutGlobalScopes()
            ->where('is_active', true)
            ->when($client !== null, fn ($q) => $q->where('client_id', $client->id))
            ->when($siteIds !== [], fn ($q) => $q->whereIn('installation_id', $siteIds))
            ->when($siteIds === [] && $client === null, fn ($q) => $q->whereRaw('1 = 0'))
            ->with(['employees', 'installation', 'client'])
            ->get();

        $employees = $posts->pluck('employees')->flatten()->unique('id')->values();
        $parafiscalByEmployee = $this->latestParafiscal($employees->pluck('id')->all());

        $from = CarbonImmutable::now()->subMonths(11)->startOfMonth();
        $reviews = SupervisorShiftReview::query()
            ->when($client !== null, fn ($q) => $q->where('client_id', $client->id))
            ->when($siteIds !== [], fn ($q) => $q->whereHas('supervisorPost', fn ($p) => $p->whereIn('installation_id', $siteIds)))
            ->where('recorded_at', '>=', $from)
            ->get(['id', 'recorded_at']);

        $months = [];
        $cursor = $from;
        $end = CarbonImmutable::now()->startOfMonth();
        while ($cursor->lte($end)) {
            $key = $cursor->format('Y-m');
            $months[$key] = 0;
            $cursor = $cursor->addMonth();
        }
        foreach ($reviews as $row) {
            $key = CarbonImmutable::parse($row->recorded_at)->format('Y-m');
            if (isset($months[$key])) {
                $months[$key]++;
            }
        }

        $feedQuery = OperationalAlert::query()
            ->where('type', OperationalAlertType::ServiceChange)
            ->where('security_company_id', $companyId)
            ->when($client !== null, fn ($q) => $q->where('client_id', $client->id))
            ->when($siteIds !== [], fn ($q) => $q->where(function ($inner) use ($siteIds) {
                $inner->whereIn('installation_id', $siteIds)->orWhereNull('installation_id');
            }))
            ->latest('id')
            ->limit(40);

        $markers = [];
        foreach ($sites as $site) {
            if ($site->latitude === null || $site->longitude === null) {
                continue;
            }
            $markers[] = [
                'kind' => 'installation',
                'name' => $site->name,
                'client' => $site->client?->name ?? $client?->name,
                'lat' => (float) $site->latitude,
                'lng' => (float) $site->longitude,
            ];
        }
        if ($client !== null && $client->latitude && $client->longitude) {
            $markers[] = [
                'kind' => 'client',
                'name' => $client->name,
                'client' => $client->name,
                'lat' => (float) $client->latitude,
                'lng' => (float) $client->longitude,
            ];
        }

        return [
            'installations_count' => $sites->count(),
            'posts_count' => $posts->count(),
            'staff_count' => $employees->count(),
            'markers' => $markers,
            'maps' => [
                'api_key' => (string) config('google-maps.api_key', ''),
                'center' => config('google-maps.default_center'),
            ],
            'staff' => $employees->map(function (Employee $employee) use ($parafiscalByEmployee, $posts) {
                $post = $posts->first(fn (SupervisorPost $p) => $p->employees->contains('id', $employee->id));

                return [
                    'name' => $employee->fullName(),
                    'document' => $employee->document_number,
                    'post' => $post?->name,
                    'site' => $post?->installation?->name,
                    'eps' => $employee->eps_name ?: '—',
                    'pension' => $employee->afp_name ?: '—',
                    'caja' => $employee->compensation_fund ?: '—',
                    'parafiscal' => $parafiscalByEmployee[$employee->id] ?? 'Sin planilla',
                    'ok' => filled($employee->eps_name) && filled($employee->afp_name)
                        && filled($employee->compensation_fund)
                        && isset($parafiscalByEmployee[$employee->id]),
                ];
            })->all(),
            'feed' => $feedQuery->get()->map(fn (OperationalAlert $row) => [
                'id' => $row->id,
                'body' => $row->body,
                'at' => $row->created_at?->format('d/m H:i'),
            ])->all(),
            'chart' => [
                'labels' => array_map(
                    fn (string $key) => CarbonImmutable::createFromFormat('Y-m', $key)->format('m/Y'),
                    array_keys($months),
                ),
                'values' => array_values($months),
            ],
        ];
    }

    /**
     * @param  list<int>  $employeeIds
     * @return array<int, string>
     */
    private function latestParafiscal(array $employeeIds): array
    {
        if ($employeeIds === []) {
            return [];
        }

        $rows = EmployeeDocument::query()
            ->whereIn('employee_id', $employeeIds)
            ->where('folder', DocumentFolder::Parafiscales)
            ->whereNotNull('disk_path')
            ->orderByDesc('taken_on')
            ->orderByDesc('id')
            ->get(['employee_id', 'taken_on', 'display_name']);

        $out = [];
        foreach ($rows as $row) {
            $id = (int) $row->employee_id;
            if (isset($out[$id])) {
                continue;
            }
            $out[$id] = $row->taken_on
                ? $row->taken_on->format('m/Y')
                : ($row->display_name ?: 'Cargada');
        }

        return $out;
    }
}
