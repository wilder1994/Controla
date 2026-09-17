<?php

declare(strict_types=1);

namespace App\Services\Access;

use App\Domain\Structure\Data\CreateMemberData;
use App\Models\AccessLog;
use App\Models\MemberType;
use App\Models\StructureMember;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Visitor;
use App\Services\Structure\CreateMemberService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

final class RegisterPorteriaMovementService
{
    public function __construct(
        private readonly TenantContext $tenant,
        private readonly BlocklistGuard $blocklist,
        private readonly PorteriaDoorService $doors,
        private readonly CreateMemberService $createMember,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function enter(User $actor, array $input, ?UploadedFile $personPhoto = null, ?UploadedFile $vehiclePhoto = null): AccessLog
    {
        $clientId = (int) $this->tenant->clientId();
        $door = $this->doors->current(request());
        if ($door === null) {
            throw ValidationException::withMessages(['location_id' => 'Selecciona la puerta que vas a operar.']);
        }

        $kind = (string) ($input['subject_kind'] ?? 'visitor');
        $withVehicle = (bool) ($input['with_vehicle'] ?? false);

        $member = null;
        $visitor = null;
        $vehicle = null;

        if ($kind === 'member') {
            $member = $this->resolveMember($actor, $clientId, $input);
            $block = $this->blocklist->checkPerson(documentNumber: $member->document_number);
            if ($block !== null) {
                throw ValidationException::withMessages(['q' => 'Ingreso bloqueado: '.$block->reason]);
            }
        } else {
            $visitor = $this->resolveVisitor($clientId, $input);
            $block = $this->blocklist->checkPerson(visitor: $visitor);
            if ($block !== null) {
                throw ValidationException::withMessages(['q' => 'Ingreso bloqueado: '.$block->reason]);
            }
        }

        if ($withVehicle) {
            $vehicle = $this->resolveVehicle($clientId, $input, $visitor, $member);
            $vBlock = $this->blocklist->checkVehicle(vehicle: $vehicle);
            if ($vBlock !== null) {
                throw ValidationException::withMessages(['plate' => 'Vehículo bloqueado: '.$vBlock->reason]);
            }
        }

        $accessType = $kind === 'member'
            ? ($withVehicle ? 'member_vehicle' : 'member')
            : ($withVehicle ? 'visitor_vehicle' : 'visitor');

        $personPhotoPath = $this->storePhoto($personPhoto, 'porteria/personas');
        $vehiclePhotoPath = $this->storePhoto($vehiclePhoto, 'porteria/vehiculos');

        $log = AccessLog::query()->create([
            'client_id' => $clientId,
            'visitor_id' => $visitor?->id,
            'structure_member_id' => $member?->id,
            'vehicle_id' => $vehicle?->id,
            'host_id' => $actor->id,
            'location_id' => $door->id,
            'authorized_by' => $actor->id,
            'access_type' => $accessType,
            'entry_time' => now(),
            'status' => 'active',
            'purpose' => $input['purpose'] ?? null,
            'notes' => $input['notes'] ?? null,
            'photo_path' => $personPhotoPath,
            'vehicle_photo_path' => $vehiclePhotoPath,
        ]);

        $this->audit->record($log, 'access.entry', null, [
            'access_type' => $accessType,
            'location_id' => $door->id,
        ]);

        return $log;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function resolveMember(User $actor, int $clientId, array $input): StructureMember
    {
        if (! empty($input['member_id'])) {
            return StructureMember::query()
                ->where('client_id', $clientId)
                ->whereKey($input['member_id'])
                ->firstOrFail();
        }

        $structureId = (int) ($input['structure_id'] ?? 0);
        $first = trim((string) ($input['first_name'] ?? ''));
        $last = trim((string) ($input['last_name'] ?? ''));
        $doc = trim((string) ($input['document_number'] ?? ''));
        if ($structureId < 1 || $first === '' || $last === '' || $doc === '') {
            throw ValidationException::withMessages([
                'structure_id' => 'Para crear una persona del censo indica nodo, nombre y documento.',
            ]);
        }

        $existing = StructureMember::query()
            ->where('client_id', $clientId)
            ->where('document_number', $doc)
            ->first();
        if ($existing !== null) {
            return $existing;
        }

        $typeId = (int) ($input['member_type_id'] ?? 0);
        if ($typeId < 1) {
            $typeId = (int) MemberType::query()->where('client_id', $clientId)->where('is_active', true)->orderBy('id')->value('id');
        }
        if ($typeId < 1) {
            throw ValidationException::withMessages(['member_type_id' => 'No hay tipos de persona. Créalos en el panel del cliente.']);
        }

        return $this->createMember->execute(new CreateMemberData(
            clientId: $clientId,
            structureId: $structureId,
            memberTypeId: $typeId,
            firstName: $first,
            lastName: $last,
            documentType: (string) ($input['document_type'] ?? 'CC'),
            documentNumber: $doc,
            birthDate: (string) ($input['birth_date'] ?? now()->subYears(25)->toDateString()),
        ));
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function resolveVisitor(int $clientId, array $input): Visitor
    {
        if (! empty($input['visitor_id'])) {
            return Visitor::query()->where('client_id', $clientId)->whereKey($input['visitor_id'])->firstOrFail();
        }

        $first = trim((string) ($input['first_name'] ?? ''));
        $last = trim((string) ($input['last_name'] ?? ''));
        $doc = trim((string) ($input['document_number'] ?? ''));
        $docType = (string) ($input['document_type'] ?? 'CC');
        if ($first === '' || $last === '' || $doc === '') {
            throw ValidationException::withMessages([
                'first_name' => 'Para un visitante nuevo indica nombre, apellido y documento.',
            ]);
        }

        $existing = Visitor::withTrashed()
            ->where('client_id', $clientId)
            ->where('document_type', $docType)
            ->where('document_number', $doc)
            ->first();
        if ($existing !== null) {
            if ($existing->trashed()) {
                $existing->restore();
            }
            $existing->update(['first_name' => $first, 'last_name' => $last]);

            return $existing;
        }

        return Visitor::query()->create([
            'client_id' => $clientId,
            'document_type' => $docType,
            'document_number' => $doc,
            'first_name' => $first,
            'last_name' => $last,
            'visitor_type' => 'persona',
        ]);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function resolveVehicle(int $clientId, array $input, ?Visitor $visitor, ?StructureMember $member): Vehicle
    {
        if (! empty($input['vehicle_id'])) {
            return Vehicle::query()->where('client_id', $clientId)->whereKey($input['vehicle_id'])->firstOrFail();
        }

        $plate = strtoupper(trim((string) ($input['plate'] ?? '')));
        if ($plate === '') {
            throw ValidationException::withMessages(['plate' => 'Indica la placa o elige un vehículo.']);
        }

        $existing = Vehicle::query()->where('client_id', $clientId)->where('plate', $plate)->first();
        if ($existing !== null) {
            return $existing;
        }

        return Vehicle::query()->create([
            'client_id' => $clientId,
            'plate' => $plate,
            'brand' => $input['vehicle_brand'] ?? null,
            'color' => $input['vehicle_color'] ?? null,
            'type' => $input['vehicle_type'] ?? 'auto',
            'visitor_id' => $visitor?->id,
            'structure_id' => $member?->structure_id,
            'is_visitor_vehicle' => $member === null,
        ]);
    }

    private function storePhoto(?UploadedFile $file, string $dir): ?string
    {
        if ($file === null) {
            return null;
        }

        return $file->store($dir, 'public');
    }
}
