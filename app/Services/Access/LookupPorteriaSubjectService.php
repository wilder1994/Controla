<?php

declare(strict_types=1);

namespace App\Services\Access;

use App\Models\MemberType;
use App\Models\Structure;
use App\Models\StructureMember;
use App\Models\Vehicle;
use App\Models\Visitor;
use App\Models\VisitorPreAuthorization;
use App\Support\Privacy\MinorPersonalData;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Collection;

final class LookupPorteriaSubjectService
{
    public function __construct(
        private readonly TenantContext $tenant,
        private readonly BlocklistGuard $blocklist,
    ) {}

    /**
     * @return array{q: string, members: list<array<string, mixed>>, visitors: list<array<string, mixed>>, vehicles: list<array<string, mixed>>, authorizations: list<array<string, mixed>>}
     */
    public function search(string $q): array
    {
        $q = trim($q);
        $clientId = (int) $this->tenant->clientId();
        $user = auth()->user();

        if ($q === '' || $clientId < 1) {
            return ['q' => $q, 'members' => [], 'visitors' => [], 'vehicles' => [], 'authorizations' => []];
        }

        $members = StructureMember::query()
            ->with(['structure.installation', 'memberType'])
            ->where('client_id', $clientId)
            ->where('is_active', true)
            ->where(function ($query) use ($q): void {
                $query->where('first_name', 'like', "%{$q}%")
                    ->orWhere('last_name', 'like', "%{$q}%")
                    ->orWhere('document_number', 'like', "%{$q}%");
            })
            ->limit(8)
            ->get()
            ->map(function (StructureMember $member) use ($user): array {
                $block = $this->blocklist->checkPerson(documentNumber: $member->document_number);

                return [
                    'id' => $member->id,
                    'kind' => 'member',
                    'name' => $member->full_name,
                    'document' => MinorPersonalData::documentForDisplay($user, $member->birth_date, $member->document_type, $member->document_number),
                    'node' => $member->structure?->name,
                    'installation' => $member->structure?->installation?->name,
                    'blocked' => $block !== null,
                    'block_reason' => $block?->reason,
                ];
            })
            ->all();

        $visitors = Visitor::query()
            ->where('client_id', $clientId)
            ->where(function ($query) use ($q): void {
                $query->where('first_name', 'like', "%{$q}%")
                    ->orWhere('last_name', 'like', "%{$q}%")
                    ->orWhere('document_number', 'like', "%{$q}%");
            })
            ->limit(8)
            ->get()
            ->map(function (Visitor $visitor): array {
                $block = $this->blocklist->checkPerson(visitor: $visitor);

                return [
                    'id' => $visitor->id,
                    'kind' => 'visitor',
                    'name' => $visitor->full_name,
                    'document' => $visitor->displayedDocument(),
                    'blocked' => $block !== null,
                    'block_reason' => $block?->reason,
                ];
            })
            ->all();

        $plate = strtoupper($q);
        $vehicles = Vehicle::query()
            ->with(['structure', 'visitor'])
            ->where('client_id', $clientId)
            ->where('plate', 'like', "%{$plate}%")
            ->limit(8)
            ->get()
            ->map(function (Vehicle $vehicle): array {
                $block = $this->blocklist->checkVehicle(vehicle: $vehicle);
                $census = $vehicle->structure_id !== null;

                return [
                    'id' => $vehicle->id,
                    'kind' => 'vehicle',
                    'plate' => $vehicle->plate,
                    'label' => trim($vehicle->brand.' '.$vehicle->model),
                    'census' => $census,
                    'owner' => $census ? ($vehicle->structure?->name) : ($vehicle->visitor?->full_name),
                    'blocked' => $block !== null,
                    'block_reason' => $block?->reason,
                ];
            })
            ->all();

        $authorizations = VisitorPreAuthorization::query()
            ->with(['structure', 'member'])
            ->where('client_id', $clientId)
            ->whereDate('valid_for_date', today())
            ->where(function ($query) use ($q): void {
                $query->where('visitor_name', 'like', "%{$q}%")
                    ->orWhere('visitor_document', 'like', "%{$q}%");
            })
            ->limit(8)
            ->get()
            ->map(fn (VisitorPreAuthorization $row): array => [
                'id' => $row->id,
                'kind' => 'authorization',
                'name' => $row->visitor_name,
                'document' => $row->visitor_document,
                'host' => $row->member?->full_name,
                'node' => $row->structure?->name,
            ])
            ->all();

        return compact('q', 'members', 'visitors', 'vehicles', 'authorizations');
    }

    /** @return Collection<int, Structure> */
    public function nodes(): Collection
    {
        $clientId = (int) $this->tenant->clientId();

        return Structure::query()
            ->with('installation')
            ->where('client_id', $clientId)
            ->orderBy('name')
            ->get();
    }

    /** @return Collection<int, MemberType> */
    public function memberTypes(): Collection
    {
        return MemberType::query()
            ->where('client_id', (int) $this->tenant->clientId())
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}
