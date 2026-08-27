<?php

declare(strict_types=1);

namespace App\Services\Company;

use App\Enums\SupervisorFieldModule;
use App\Enums\SupervisorRecommendationPriority;
use App\Enums\SupervisorRecommendationStatus;
use App\Models\Client;
use App\Models\SupervisorFieldLog;
use App\Models\SupervisorRecommendation;
use App\Models\SupervisorShift;
use App\Models\SupervisorShiftReview;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RecordSupervisorFieldLogService
{
    public function __construct(
        private readonly AssertSupervisorFieldPayload $assertPayload,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function execute(
        SupervisorShift $shift,
        SupervisorFieldModule $module,
        array $payload,
        ?int $clientId,
        ?int $reviewId,
        ?string $notes,
        ?float $lat,
        ?float $lng,
    ): SupervisorFieldLog {
        if (! $shift->isOpen()) {
            throw ValidationException::withMessages([
                'shift' => 'El turno está cerrado.',
            ]);
        }

        $review = $this->resolveReview($shift, $module, $reviewId);
        $client = $review?->client ?? $this->resolveClient($shift, $module, $clientId);
        $validated = $this->assertPayload->execute($module, $payload);

        return DB::transaction(function () use ($shift, $module, $validated, $client, $review, $notes, $lat, $lng) {
            $recommendationId = null;

            if ($module === SupervisorFieldModule::Recommendations) {
                if ($client === null) {
                    throw ValidationException::withMessages([
                        'client_id' => 'La recomendación requiere un cliente.',
                    ]);
                }
                $recommendation = SupervisorRecommendation::query()->create([
                    'security_company_id' => $shift->security_company_id,
                    'client_id' => $client->id,
                    'opened_by_user_id' => $shift->user_id,
                    'opened_shift_id' => $shift->id,
                    'status' => SupervisorRecommendationStatus::Open,
                    'priority' => SupervisorRecommendationPriority::from((string) $validated->payload['priority']),
                    'due_date' => $validated->payload['due_date'] ?? null,
                    'title' => $validated->payload['title'],
                    'body' => $validated->payload['body'],
                ]);
                $recommendationId = $recommendation->id;
            }

            return SupervisorFieldLog::query()->create([
                'supervisor_shift_id' => $shift->id,
                'supervisor_shift_review_id' => $review?->id,
                'security_company_id' => $shift->security_company_id,
                'user_id' => $shift->user_id,
                'client_id' => $client?->id,
                'supervisor_recommendation_id' => $recommendationId,
                'module' => $module,
                'outcome' => $validated->outcome,
                'payload' => $validated->payload,
                'notes' => $notes !== null && $notes !== '' ? $notes : null,
                'latitude' => $lat,
                'longitude' => $lng,
                'recorded_at' => now(),
            ]);
        });
    }

    /**
     * @return array{reviews: int, logs: array<string, int>}
     */
    public function activityFor(SupervisorShift $shift): array
    {
        $counts = SupervisorFieldLog::query()
            ->where('supervisor_shift_id', $shift->id)
            ->selectRaw('module, count(*) as total')
            ->groupBy('module')
            ->pluck('total', 'module');

        $logs = [];
        foreach (SupervisorFieldModule::cases() as $module) {
            $logs[$module->value] = (int) ($counts[$module->value] ?? 0);
        }

        return [
            'reviews' => $shift->reviews()->count(),
            'logs' => $logs,
        ];
    }

    private function resolveClient(
        SupervisorShift $shift,
        SupervisorFieldModule $module,
        ?int $clientId,
    ): ?Client {
        if ($clientId === null) {
            if ($module->requiresClient()) {
                throw ValidationException::withMessages([
                    'client_id' => 'Este módulo requiere un sitio con Supervisión.',
                ]);
            }

            return null;
        }

        $client = Client::query()->find($clientId);
        if ($client === null) {
            throw ValidationException::withMessages([
                'client_id' => 'Sitio no encontrado.',
            ]);
        }

        if ((int) $client->security_company_id !== (int) $shift->security_company_id) {
            throw ValidationException::withMessages([
                'client_id' => 'El cliente no pertenece a esta empresa.',
            ]);
        }

        if (! $client->has_supervision) {
            throw ValidationException::withMessages([
                'client_id' => 'Este sitio no tiene Supervisión asignada.',
            ]);
        }

        return $client;
    }

    private function resolveReview(
        SupervisorShift $shift,
        SupervisorFieldModule $module,
        ?int $reviewId,
    ): ?SupervisorShiftReview {
        if ($module->hangsOffReview()) {
            if ($reviewId === null) {
                throw ValidationException::withMessages([
                    'supervisor_shift_review_id' => 'Guarde la revista de este puesto antes de registrar el módulo.',
                ]);
            }
        }

        if ($reviewId === null) {
            return null;
        }

        $review = SupervisorShiftReview::query()
            ->where('id', $reviewId)
            ->where('supervisor_shift_id', $shift->id)
            ->with('client')
            ->first();

        if ($review === null) {
            throw ValidationException::withMessages([
                'supervisor_shift_review_id' => 'La revista no pertenece a este turno.',
            ]);
        }

        return $review;
    }
}
