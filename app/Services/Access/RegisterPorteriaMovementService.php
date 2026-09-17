<?php

declare(strict_types=1);

namespace App\Services\Access;

use App\Domain\Structure\Data\CreateMemberData;
use App\Models\AccessLog;
use App\Models\Location;
use App\Models\MemberType;
use App\Models\Structure;
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
    public function move(User $actor, array $input): AccessLog
    {
        $kind = (string) ($input['kind'] ?? '');
        $id = (int) ($input['id'] ?? 0);
        $action = (string) ($input['action'] ?? '');

        if (! in_array($kind, ['member', 'visitor', 'vehicle'], true) || $id < 1) {
            throw ValidationException::withMessages(['kind' => 'Selecciona una ficha.']);
        }
        if (! in_array($action, ['enter', 'exit'], true)) {
            throw ValidationException::withMessages(['action' => 'Indica si ingresa o sale.']);
        }

        if ($action === 'exit') {
            return $this->exit($kind, $id);
        }

        return $this->enterExisting($actor, $kind, $id, $input);
    }

    /**
     * Alta de ficha (foto opcional en el maestro). No crea movimiento.
     *
     * @param  array<string, mixed>  $input
     * @return array{kind: string, id: int}
     */
    public function register(array $input, ?UploadedFile $photo = null): array
    {
        $clientId = (int) $this->tenant->clientId();
        $kind = (string) ($input['subject_kind'] ?? 'visitor');

        if ($kind === 'member') {
            $member = $this->resolveMember($clientId, $input);
            $this->applyPhoto($member, $photo, 'porteria/personas');

            return ['kind' => 'member', 'id' => (int) $member->id];
        }

        if ($kind === 'vehicle') {
            $visitor = null;
            if (empty($input['structure_id'])) {
                $visitor = $this->resolveVisitor($clientId, $input);
                $this->applyPhoto($visitor, null, 'porteria/personas');
            }
            $vehicle = $this->resolveVehicle($clientId, $input, $visitor, null);
            $this->applyPhoto($vehicle, $photo, 'porteria/vehiculos');

            return ['kind' => 'vehicle', 'id' => (int) $vehicle->id];
        }

        $visitor = $this->resolveVisitor($clientId, $input);
        $this->applyPhoto($visitor, $photo, 'porteria/personas');

        if (! empty($input['plate'])) {
            $vehicle = $this->resolveVehicle($clientId, $input, $visitor, null);
            $this->applyPhoto($vehicle, null, 'porteria/vehiculos');
        }

        return ['kind' => 'visitor', 'id' => (int) $visitor->id];
    }

    /**
     * Compatibilidad: alta + ingreso (POST antiguo).
     *
     * @param  array<string, mixed>  $input
     */
    public function enter(User $actor, array $input, ?UploadedFile $personPhoto = null, ?UploadedFile $vehiclePhoto = null): AccessLog
    {
        if (! empty($input['member_id'])) {
            $this->applyPhoto(
                StructureMember::query()->where('client_id', (int) $this->tenant->clientId())->whereKey($input['member_id'])->firstOrFail(),
                $personPhoto,
                'porteria/personas',
            );

            return $this->move($actor, [
                'kind' => 'member',
                'id' => (int) $input['member_id'],
                'action' => 'enter',
            ] + $input);
        }

        if (! empty($input['visitor_id'])) {
            $this->applyPhoto(
                Visitor::query()->where('client_id', (int) $this->tenant->clientId())->whereKey($input['visitor_id'])->firstOrFail(),
                $personPhoto,
                'porteria/personas',
            );

            return $this->move($actor, [
                'kind' => 'visitor',
                'id' => (int) $input['visitor_id'],
                'action' => 'enter',
            ] + $input);
        }

        $kind = (string) ($input['subject_kind'] ?? 'visitor');
        $created = $this->register($input, $kind === 'vehicle' ? $vehiclePhoto : $personPhoto);

        return $this->move($actor, [
            'kind' => $created['kind'],
            'id' => $created['id'],
            'action' => 'enter',
        ] + $input);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function enterExisting(User $actor, string $kind, int $id, array $input): AccessLog
    {
        $clientId = (int) $this->tenant->clientId();
        $door = $this->requireDoor();
        $column = $this->column($kind);

        $active = AccessLog::query()->where('status', 'active')->where($column, $id)->first();
        if ($active !== null) {
            throw ValidationException::withMessages(['action' => 'Ya está adentro. Usa Sale.']);
        }

        $member = null;
        $visitor = null;
        $vehicle = null;
        $census = false;

        if ($kind === 'member') {
            $member = StructureMember::query()->where('client_id', $clientId)->whereKey($id)->firstOrFail();
            $this->assertPersonNotBlocked($member->document_number);
            $census = true;
        } elseif ($kind === 'visitor') {
            $visitor = Visitor::query()->where('client_id', $clientId)->whereKey($id)->firstOrFail();
            $this->assertVisitorNotBlocked($visitor);
        } else {
            $vehicle = Vehicle::query()->where('client_id', $clientId)->whereKey($id)->firstOrFail();
            $this->assertVehicleNotBlocked($vehicle);
            $census = $vehicle->structure_id !== null;
            if ($vehicle->visitor_id) {
                $visitor = $vehicle->visitor;
            }
        }

        $dest = $this->destination($clientId, $input, $census);

        $accessType = match (true) {
            $kind === 'vehicle' && $census => 'member_vehicle',
            $kind === 'vehicle' => 'visitor_vehicle',
            $kind === 'member' => 'member',
            default => 'visitor',
        };

        $log = AccessLog::query()->create([
            'client_id' => $clientId,
            'visitor_id' => $visitor?->id,
            'structure_member_id' => $member?->id,
            'vehicle_id' => $vehicle?->id,
            'host_id' => $actor->id,
            'location_id' => $door->id,
            'destination_structure_id' => $dest['structure_id'],
            'destination_text' => $dest['text'],
            'authorized_member_id' => $dest['authorized_member_id'],
            'authorized_by' => $actor->id,
            'access_type' => $accessType,
            'entry_time' => now(),
            'status' => 'active',
            'purpose' => $input['purpose'] ?? null,
            'notes' => $input['notes'] ?? null,
        ]);

        $this->audit->record($log, 'access.entry', null, [
            'access_type' => $accessType,
            'location_id' => $door->id,
        ]);

        return $log;
    }

    private function exit(string $kind, int $id): AccessLog
    {
        $log = AccessLog::query()
            ->where('status', 'active')
            ->where($this->column($kind), $id)
            ->latest('entry_time')
            ->first();

        if ($log === null) {
            throw ValidationException::withMessages(['action' => 'No hay ingreso activo.']);
        }

        $log->update([
            'exit_time' => now(),
            'status' => 'completed',
        ]);

        $this->audit->record($log, 'access.exit', ['status' => 'active'], ['status' => 'completed']);

        return $log->fresh();
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{structure_id: ?int, text: ?string, authorized_member_id: ?int}
     */
    private function destination(int $clientId, array $input, bool $census): array
    {
        $structureId = (int) ($input['destination_structure_id'] ?? 0);
        $text = trim((string) ($input['destination_text'] ?? ''));
        $authorizedId = (int) ($input['authorized_member_id'] ?? 0);

        if ($census) {
            return ['structure_id' => null, 'text' => null, 'authorized_member_id' => null];
        }

        if ($structureId > 0) {
            $ok = Structure::query()->where('client_id', $clientId)->whereKey($structureId)->exists();
            if (! $ok) {
                throw ValidationException::withMessages(['destination_structure_id' => 'Nodo inválido.']);
            }
        }

        if ($authorizedId > 0) {
            $ok = StructureMember::query()->where('client_id', $clientId)->whereKey($authorizedId)->exists();
            if (! $ok) {
                throw ValidationException::withMessages(['authorized_member_id' => 'Autorizador inválido.']);
            }
        }

        if ($structureId < 1 && $text === '') {
            throw ValidationException::withMessages([
                'destination_structure_id' => 'Indica nodo o destino (texto).',
            ]);
        }

        return [
            'structure_id' => $structureId > 0 ? $structureId : null,
            'text' => $text !== '' ? $text : null,
            'authorized_member_id' => $authorizedId > 0 ? $authorizedId : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function resolveMember(int $clientId, array $input): StructureMember
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

        $structureId = $member?->structure_id ?? ((int) ($input['structure_id'] ?? 0) ?: null);

        return Vehicle::query()->create([
            'client_id' => $clientId,
            'plate' => $plate,
            'brand' => $input['vehicle_brand'] ?? null,
            'color' => $input['vehicle_color'] ?? null,
            'type' => $input['vehicle_type'] ?? 'auto',
            'visitor_id' => $visitor?->id,
            'structure_id' => $structureId,
            'is_visitor_vehicle' => $structureId === null,
        ]);
    }

    private function applyPhoto(StructureMember|Visitor|Vehicle $model, ?UploadedFile $file, string $dir): void
    {
        if ($file === null) {
            return;
        }

        $path = $file->store($dir, 'public');
        $model->update(['photo_path' => $path]);
    }

    private function requireDoor(): Location
    {
        $door = $this->doors->operatingOrFirst(request());
        if ($door === null) {
            throw ValidationException::withMessages(['location_id' => 'No hay puerta activa para operar.']);
        }

        return $door;
    }

    private function column(string $kind): string
    {
        return match ($kind) {
            'member' => 'structure_member_id',
            'visitor' => 'visitor_id',
            default => 'vehicle_id',
        };
    }

    private function assertPersonNotBlocked(?string $documentNumber): void
    {
        $block = $this->blocklist->checkPerson(documentNumber: $documentNumber);
        if ($block !== null) {
            throw ValidationException::withMessages(['q' => 'Ingreso bloqueado: '.$block->reason]);
        }
    }

    private function assertVisitorNotBlocked(Visitor $visitor): void
    {
        $block = $this->blocklist->checkPerson(visitor: $visitor);
        if ($block !== null) {
            throw ValidationException::withMessages(['q' => 'Ingreso bloqueado: '.$block->reason]);
        }
    }

    private function assertVehicleNotBlocked(Vehicle $vehicle): void
    {
        $block = $this->blocklist->checkVehicle(vehicle: $vehicle);
        if ($block !== null) {
            throw ValidationException::withMessages(['plate' => 'Vehículo bloqueado: '.$block->reason]);
        }
    }
}
