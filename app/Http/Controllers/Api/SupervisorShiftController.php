<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Supervision\Data\OpenSupervisorShiftInput;
use App\Enums\SupervisorChecklistKind;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CloseSupervisorShiftRequest;
use App\Http\Requests\Api\OpenSupervisorShiftRequest;
use App\Models\Client;
use App\Models\SupervisorChecklistItem;
use App\Models\SupervisorFleetVehicle;
use App\Models\SupervisorShiftTemplate;
use App\Models\SupervisorZone;
use App\Models\User;
use App\Services\Company\CloseSupervisorShiftService;
use App\Services\Company\ManageSupervisorShiftService;
use App\Services\Company\OpenSupervisorShiftService;
use App\Services\Company\RecordSupervisorFieldLogService;
use App\Services\Company\RecordSupervisorProReviewService;
use App\Services\Company\SeedSupervisorIntakeDefaultsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class SupervisorShiftController extends Controller
{
    public function __construct(
        private readonly ManageSupervisorShiftService $shiftService,
        private readonly OpenSupervisorShiftService $openShift,
        private readonly CloseSupervisorShiftService $closeShift,
        private readonly RecordSupervisorProReviewService $reviewService,
        private readonly RecordSupervisorFieldLogService $logService,
        private readonly SeedSupervisorIntakeDefaultsService $intakeDefaults,
    ) {}

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'nullable|string|max:100',
        ]);

        $user = User::query()->where('email', $request->string('email'))->with('securityCompany')->first();

        if (! $user || ! Hash::check((string) $request->input('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Credenciales inválidas.'],
            ]);
        }

        if (! $user->hasRole('supervisor') || $user->security_company_id === null) {
            abort(403, 'Esta cuenta no es de supervisor.');
        }

        $company = $user->securityCompany;
        if ($company === null || ! $company->hasSupervisionPackage()) {
            abort(403, 'La empresa no tiene Supervisión.');
        }

        $token = $user->createToken($request->string('device_name')->toString() ?: 'supervision-pro')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'company_id' => $user->security_company_id,
            ],
        ]);
    }

    public function current(Request $request): JsonResponse
    {
        $shift = $this->shiftService->currentFor($request->user());
        if ($shift !== null) {
            $shift->load(['fleetVehicle', 'zone', 'shiftTemplate']);
        }

        return response()->json([
            'shift' => $shift,
            'activity' => $shift !== null ? $this->logService->activityFor($shift) : null,
        ]);
    }

    public function sites(Request $request): JsonResponse
    {
        $sites = Client::query()
            ->where('security_company_id', $request->user()->security_company_id)
            ->where('has_supervision', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'address', 'has_access']);

        return response()->json(['sites' => $sites]);
    }

    public function intake(Request $request): JsonResponse
    {
        $companyId = (int) $request->user()->security_company_id;
        $this->intakeDefaults->execute($companyId);

        $vehicles = SupervisorFleetVehicle::query()
            ->where('security_company_id', $companyId)
            ->orderBy('plate')
            ->get();

        $zones = SupervisorZone::query()
            ->where('security_company_id', $companyId)
            ->active()
            ->get(['id', 'name']);

        $templates = SupervisorShiftTemplate::query()
            ->where('security_company_id', $companyId)
            ->active()
            ->get();

        return response()->json([
            'zones' => $zones->map(fn (SupervisorZone $zone) => [
                'id' => $zone->id,
                'name' => $zone->name,
            ])->values()->all(),
            'shift_templates' => $templates->map(fn (SupervisorShiftTemplate $template) => [
                'id' => $template->id,
                'name' => $template->name,
                'starts_at' => $template->starts_at,
                'ends_at' => $template->ends_at,
                'schedule' => $template->scheduleLabel(),
            ])->values()->all(),
            'ppe' => $this->checklistList($companyId, SupervisorChecklistKind::Ppe),
            'vehicle_check' => $this->checklistList($companyId, SupervisorChecklistKind::Vehicle),
            'vehicles' => $vehicles->map(fn (SupervisorFleetVehicle $vehicle) => [
                'id' => $vehicle->id,
                'plate' => $vehicle->plate,
                'label' => $vehicle->displayName(),
                'brand' => $vehicle->brand,
                'line' => $vehicle->line,
                'model' => $vehicle->model,
                'soat_expires_at' => $vehicle->soat_expires_at?->toDateString(),
                'technical_review_expires_at' => $vehicle->technical_review_expires_at?->toDateString(),
                'last_km' => $vehicle->last_km,
            ])->values()->all(),
            'first_vehicle' => $vehicles->isEmpty(),
        ]);
    }

    public function open(OpenSupervisorShiftRequest $request): JsonResponse
    {
        $vehicle = $request->input('vehicle', []);
        $shift = $this->openShift->execute(
            $request->user(),
            new OpenSupervisorShiftInput(
                shiftTemplateId: (int) $request->validated('shift_template_id'),
                zoneId: (int) $request->validated('zone_id'),
                kmStart: (int) $request->validated('km_start'),
                ppeChecklist: $request->validated('ppe_checklist'),
                vehicleChecklist: $request->validated('vehicle_checklist'),
                odometerPhoto: $request->file('odometer_photo'),
                selfiePhoto: $request->file('selfie_photo'),
                vehicleId: $request->validated('vehicle_id') !== null ? (int) $request->validated('vehicle_id') : null,
                plate: $vehicle['plate'] ?? null,
                brand: $vehicle['brand'] ?? null,
                line: $vehicle['line'] ?? null,
                model: $vehicle['model'] ?? null,
                color: $vehicle['color'] ?? null,
                type: $vehicle['type'] ?? null,
                soatExpiresAt: $vehicle['soat_expires_at'] ?? null,
                technicalReviewExpiresAt: $vehicle['technical_review_expires_at'] ?? null,
            ),
        );

        return response()->json(['shift' => $shift], 201);
    }

    public function ping(Request $request): JsonResponse
    {
        $data = $request->validate([
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'accuracy' => ['nullable', 'numeric'],
        ]);

        $shift = $this->shiftService->currentFor($request->user());
        abort_if($shift === null, 422, 'No hay turno abierto.');

        $point = $this->shiftService->ping(
            $shift,
            (float) $data['latitude'],
            (float) $data['longitude'],
            isset($data['accuracy']) ? (float) $data['accuracy'] : null,
        );

        return response()->json(['location' => $point]);
    }

    public function close(CloseSupervisorShiftRequest $request): JsonResponse
    {
        $shift = $this->shiftService->currentFor($request->user());
        abort_if($shift === null, 422, 'No hay turno abierto.');

        $closed = $this->closeShift->execute(
            $shift,
            (int) $request->validated('km_end'),
            $request->file('odometer_photo'),
            $request->file('selfie_photo'),
        );

        return response()->json(['shift' => $closed]);
    }

    public function review(Request $request): JsonResponse
    {
        $data = $request->validate([
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ]);

        $shift = $this->shiftService->currentFor($request->user());
        abort_if($shift === null, 422, 'No hay turno abierto.');

        $client = Client::query()->findOrFail($data['client_id']);
        $review = $this->reviewService->execute(
            $shift,
            $client,
            (string) ($data['notes'] ?? ''),
            isset($data['latitude']) ? (float) $data['latitude'] : null,
            isset($data['longitude']) ? (float) $data['longitude'] : null,
        );

        return response()->json(['review' => $review], 201);
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    private function checklistList(int $companyId, SupervisorChecklistKind $kind): array
    {
        $rows = [];
        foreach (SupervisorChecklistItem::keyedLabels($companyId, $kind) as $key => $label) {
            $rows[] = ['key' => $key, 'label' => $label];
        }

        return $rows;
    }
}
