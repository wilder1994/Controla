<?php

declare(strict_types=1);

namespace App\Services\Company;

use App\Enums\InstallationKind;
use App\Enums\ObservatoryReportKind;
use App\Models\Employee;
use App\Models\Installation;
use App\Models\SupervisorPost;
use App\Models\User;
use App\Support\Supervision\FieldModuleCatalog;

final class BuildSupervisorOfflinePackService
{
    public function __construct(
        private readonly LookupSupervisorVisitService $lookup,
        private readonly FieldModuleCatalog $catalog,
    ) {}

    /**
     * @return array{sites: list<array<string, mixed>>, posts: list<array<string, mixed>>, guards: list<array<string, mixed>>, modules: list<array<string, mixed>>}
     */
    public function execute(User $user): array
    {
        $sites = $this->lookup->sites($user);
        $siteIds = array_map(fn (array $site) => (int) $site['id'], $sites);

        $posts = SupervisorPost::query()
            ->withoutGlobalScopes()
            ->whereIn('client_id', $siteIds !== [] ? $siteIds : [0])
            ->where('is_active', true)
            ->whereHas('installation', fn ($query) => $query->where('is_active', true))
            ->with('installation:id,name')
            ->orderBy('name')
            ->get()
            ->map(fn (SupervisorPost $post) => [
                'id' => $post->id,
                'client_id' => (int) $post->client_id,
                'name' => $post->name,
                'installation_id' => $post->installation_id,
                'installation_name' => $post->installation?->name,
                'label' => trim(($post->installation?->name ?? '').' · '.$post->name, ' ·'),
            ])
            ->values()
            ->all();

        $guards = Employee::query()
            ->where('security_company_id', $user->security_company_id)
            ->where('is_active', true)
            ->whereNull('ceased_at')
            ->whereHas('jobTitle', fn ($query) => $query->where('name', 'like', '%vigilante%'))
            ->orderBy('document_number')
            ->get()
            ->map(fn (Employee $employee) => [
                'id' => $employee->id,
                'document_number' => $employee->document_number,
                'name' => $employee->fullName(),
            ])
            ->values()
            ->all();

        $colegios = Installation::query()
            ->withoutGlobalScopes()
            ->where('is_active', true)
            ->where('kind', InstallationKind::Colegio->value)
            ->whereHas('client', function ($q) use ($user): void {
                $q->where('security_company_id', $user->security_company_id)
                    ->where('is_active', true);
            })
            ->with('client:id,name')
            ->orderBy('name')
            ->get(['id', 'client_id', 'name', 'dane_code', 'city', 'latitude', 'longitude']);

        return [
            'sites' => $sites,
            'posts' => $posts,
            'guards' => $guards,
            'modules' => $this->catalog->modules((int) $user->security_company_id),
            'observatory_kinds' => ObservatoryReportKind::options(),
            'observatory_sites' => $colegios->map(static fn (Installation $site): array => [
                'id' => (int) $site->id,
                'client_id' => (int) $site->client_id,
                'name' => $site->name,
                'client' => $site->client?->name,
                'dane_code' => $site->dane_code,
                'city' => $site->city,
                'lat' => $site->latitude !== null ? (float) $site->latitude : null,
                'lng' => $site->longitude !== null ? (float) $site->longitude : null,
            ])->values()->all(),
        ];
    }
}
