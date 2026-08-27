<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\SupervisorFieldModule;
use App\Enums\SupervisorRecommendationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreSupervisorFieldLogRequest;
use App\Http\Requests\Api\UpdateSupervisorRecommendationRequest;
use App\Models\SupervisorRecommendation;
use App\Services\Company\AdvanceSupervisorRecommendationService;
use App\Services\Company\ManageSupervisorShiftService;
use App\Services\Company\RecordSupervisorFieldLogService;
use App\Support\Supervision\FieldModuleCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SupervisorFieldLogController extends Controller
{
    public function __construct(
        private readonly ManageSupervisorShiftService $shiftService,
        private readonly RecordSupervisorFieldLogService $logService,
        private readonly AdvanceSupervisorRecommendationService $advanceRecommendation,
        private readonly FieldModuleCatalog $catalog,
    ) {}

    public function catalog(): JsonResponse
    {
        return response()->json(['modules' => $this->catalog->modules()]);
    }

    public function store(StoreSupervisorFieldLogRequest $request): JsonResponse
    {
        $shift = $this->shiftService->currentFor($request->user());
        abort_if($shift === null, 422, 'No hay turno abierto.');

        $log = $this->logService->execute(
            $shift,
            SupervisorFieldModule::from((string) $request->validated('module')),
            $request->validated('payload'),
            $request->validated('client_id') !== null ? (int) $request->validated('client_id') : null,
            $request->validated('supervisor_shift_review_id') !== null
                ? (int) $request->validated('supervisor_shift_review_id')
                : null,
            $request->validated('notes'),
            $request->validated('latitude') !== null ? (float) $request->validated('latitude') : null,
            $request->validated('longitude') !== null ? (float) $request->validated('longitude') : null,
        );

        return response()->json([
            'log' => $log->load(['client:id,name', 'recommendation']),
            'activity' => $this->logService->activityFor($shift),
        ], 201);
    }

    public function recommendations(Request $request): JsonResponse
    {
        $status = $request->string('status')->toString();
        $clientId = $request->integer('client_id');

        $query = SupervisorRecommendation::query()
            ->where('security_company_id', $request->user()->security_company_id)
            ->with(['client:id,name'])
            ->orderByRaw("field(status, 'open', 'progress', 'closed')")
            ->orderByDesc('created_at');

        if ($status !== '' && SupervisorRecommendationStatus::tryFrom($status) !== null) {
            $query->where('status', $status);
        } else {
            $query->whereIn('status', [
                SupervisorRecommendationStatus::Open,
                SupervisorRecommendationStatus::Progress,
            ]);
        }

        if ($clientId > 0) {
            $query->where('client_id', $clientId);
        }

        return response()->json([
            'recommendations' => $query->limit(50)->get(),
        ]);
    }

    public function updateRecommendation(
        UpdateSupervisorRecommendationRequest $request,
        SupervisorRecommendation $recommendation,
    ): JsonResponse {
        $shift = $this->shiftService->currentFor($request->user());
        abort_if($shift === null, 422, 'No hay turno abierto.');

        abort_unless(
            (int) $recommendation->security_company_id === (int) $request->user()->security_company_id,
            404,
        );

        $updated = $this->advanceRecommendation->execute(
            $recommendation,
            $shift,
            SupervisorRecommendationStatus::from((string) $request->validated('status')),
            $request->validated('notes'),
            $request->validated('latitude') !== null ? (float) $request->validated('latitude') : null,
            $request->validated('longitude') !== null ? (float) $request->validated('longitude') : null,
        );

        return response()->json(['recommendation' => $updated]);
    }
}
