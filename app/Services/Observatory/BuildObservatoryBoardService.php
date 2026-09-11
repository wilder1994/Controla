<?php

declare(strict_types=1);

namespace App\Services\Observatory;

use App\Enums\ObservatoryEventStatus;
use App\Models\Installation;
use App\Models\ObservatoryEvent;
use Illuminate\Database\Eloquent\Builder;

final class BuildObservatoryBoardService
{
    /**
     * @param  list<int>|null  $installationIds
     * @return array{total: int, nuevo: int, en_atencion: int, cerrado: int, top: list<array{name: string, client: ?string, count: int}>}
     */
    public function execute(?int $companyId, ?int $clientId, ?array $installationIds, ?string $from, ?string $to): array
    {
        $query = $this->scoped($companyId, $clientId, $installationIds, $from, $to);

        $counts = (clone $query)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $ranked = (clone $query)
            ->selectRaw('installation_id, COUNT(*) as aggregate')
            ->groupBy('installation_id')
            ->orderByDesc('aggregate')
            ->limit(5)
            ->get();

        $sites = Installation::query()
            ->withoutGlobalScopes()
            ->with('client:id,name')
            ->whereIn('id', $ranked->pluck('installation_id')->all())
            ->get()
            ->keyBy('id');

        $top = $ranked->map(static function (ObservatoryEvent $row) use ($sites): array {
            $site = $sites->get((int) $row->installation_id);

            return [
                'name' => $site?->name ?? '—',
                'client' => $site?->client?->name,
                'count' => (int) $row->aggregate,
            ];
        })->all();

        return [
            'total' => (int) $counts->sum(),
            'nuevo' => (int) ($counts[ObservatoryEventStatus::Nuevo->value] ?? 0),
            'en_atencion' => (int) ($counts[ObservatoryEventStatus::EnAtencion->value] ?? 0),
            'cerrado' => (int) ($counts[ObservatoryEventStatus::Cerrado->value] ?? 0),
            'top' => $top,
        ];
    }

    /**
     * @param  list<int>|null  $installationIds
     */
    public function scoped(?int $companyId, ?int $clientId, ?array $installationIds, ?string $from, ?string $to): Builder
    {
        return ObservatoryEvent::query()
            ->when($clientId !== null, fn (Builder $q) => $q->where('client_id', $clientId))
            ->when($companyId !== null, fn (Builder $q) => $q->whereHas(
                'client',
                fn (Builder $c) => $c->where('security_company_id', $companyId),
            ))
            ->when($installationIds !== null, fn (Builder $q) => $q->whereIn('installation_id', $installationIds))
            ->when($from !== null && $from !== '', fn (Builder $q) => $q->whereDate('opened_at', '>=', $from))
            ->when($to !== null && $to !== '', fn (Builder $q) => $q->whereDate('opened_at', '<=', $to));
    }
}
