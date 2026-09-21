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
            ->orderBy('name')
            ->get();

        $employees = $posts->pluck('employees')->flatten()->unique('id')->values();
        $parafiscalByEmployee = $this->latestParafiscal($employees->pluck('id')->all());

        $historyPosts = SupervisorPost::query()
            ->withoutGlobalScopes()
            ->withTrashed()
            ->when($client !== null, fn ($q) => $q->where('client_id', $client->id))
            ->when($siteIds !== [], fn ($q) => $q->whereIn('installation_id', $siteIds))
            ->when($siteIds === [] && $client === null, fn ($q) => $q->whereRaw('1 = 0'))
            ->get(['id', 'created_at', 'updated_at', 'deleted_at', 'is_active']);

        $from = CarbonImmutable::now()->subMonths(11)->startOfMonth();
        $months = [];
        $cursor = $from;
        $endMonth = CarbonImmutable::now()->startOfMonth();
        while ($cursor->lte($endMonth)) {
            $cut = $cursor->endOfMonth();
            $months[$cursor->format('Y-m')] = $historyPosts->filter(
                fn (SupervisorPost $post) => $this->wasActiveAt($post, $cut)
            )->count();
            $cursor = $cursor->addMonth();
        }

        $todayStart = CarbonImmutable::now()->startOfDay();

        $feedBase = OperationalAlert::query()
            ->where('type', OperationalAlertType::ServiceChange)
            ->where('security_company_id', $companyId)
            ->where('body', 'not like', 'Revista en%')
            ->when($client !== null, fn ($q) => $q->where('client_id', $client->id))
            ->when($siteIds !== [], fn ($q) => $q->where(function ($inner) use ($siteIds) {
                $inner->whereIn('installation_id', $siteIds)->orWhereNull('installation_id');
            }));

        $reviewsBase = SupervisorShiftReview::query()
            ->when($client !== null, fn ($q) => $q->where('client_id', $client->id))
            ->when($siteIds !== [], fn ($q) => $q->whereHas('supervisorPost', fn ($p) => $p->whereIn('installation_id', $siteIds)))
            ->when($siteIds === [] && $client === null, fn ($q) => $q->whereRaw('1 = 0'));

        $novedadesToday = (clone $feedBase)->where('created_at', '>=', $todayStart)->count();
        $reviewsToday = (clone $reviewsBase)->where('recorded_at', '>=', $todayStart)->count();

        $feedQuery = (clone $feedBase)->latest('id')->limit(40);
        $reviewsQuery = (clone $reviewsBase)
            ->with(['shift.user', 'employee', 'supervisorPost', 'fieldLogs'])
            ->latest('recorded_at')
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

        $servicesToday = $posts->count();

        return [
            'show_installations_kpi' => $sites->count() > 1,
            'installations_count' => $sites->count(),
            'services_today' => $servicesToday,
            'novedades_today' => $novedadesToday,
            'reviews_today' => $reviewsToday,
            'posts_count' => $servicesToday,
            'staff_count' => $employees->count(),
            'markers' => $markers,
            'maps' => [
                'api_key' => (string) config('google-maps.api_key', ''),
                'center' => config('google-maps.default_center'),
            ],
            'services' => $posts->map(fn (SupervisorPost $post): array => [
                'name' => $post->name,
                'modality' => $post->modalityLabel(),
                'guards' => $post->employees->count(),
            ])->all(),
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
                'observations' => is_array($row->payload) ? ($row->payload['observations'] ?? null) : null,
                'at' => $row->created_at?->format('d/m H:i'),
            ])->all(),
            'reviews' => $reviewsQuery->get()->map(function (SupervisorShiftReview $review): array {
                $modules = $review->fieldLogs
                    ->map(fn ($log) => $log->module?->label())
                    ->filter()
                    ->unique()
                    ->values();
                $record = trim((string) $review->notes);
                if ($modules->isNotEmpty()) {
                    $record = trim($record.' · '.$modules->implode(', '));
                }
                if ($record === '') {
                    $record = $review->has_novelty ? 'Con novedad' : 'Sin novedad';
                }

                return [
                    'id' => $review->id,
                    'at' => $review->recorded_at?->timezone(config('app.timezone'))->format('d/m H:i'),
                    'supervisor' => $review->shift?->user?->name ?? '—',
                    'post' => $review->supervisorPost?->name ?? '—',
                    'guard' => $review->employee?->fullName() ?? '—',
                    'record' => $record,
                    'novelty' => (bool) $review->has_novelty,
                ];
            })->all(),
            'chart' => [
                'labels' => array_map(
                    fn (string $key) => CarbonImmutable::createFromFormat('Y-m', $key)->format('m/Y'),
                    array_keys($months),
                ),
                'values' => array_values($months),
            ],
        ];
    }

    private function wasActiveAt(SupervisorPost $post, CarbonImmutable $cut): bool
    {
        if ($post->created_at === null || $post->created_at->gt($cut)) {
            return false;
        }
        if ($post->deleted_at !== null && $post->deleted_at->lte($cut)) {
            return false;
        }
        if (! $post->is_active && $post->updated_at !== null && $post->updated_at->lte($cut)) {
            return false;
        }

        return true;
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
