<?php

declare(strict_types=1);

namespace App\Services\Company;

use App\Domain\Supervision\Data\ValidatedFieldPayload;
use App\Enums\SupervisorFieldModule;
use App\Enums\SupervisorFieldOutcome;
use App\Enums\SupervisorPostDocumentKind;
use App\Enums\SupervisorRecommendationPriority;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class AssertSupervisorFieldPayload
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function execute(SupervisorFieldModule $module, array $payload): ValidatedFieldPayload
    {
        return match ($module) {
            SupervisorFieldModule::Inventory => $this->inventory($payload),
            SupervisorFieldModule::Documents => $this->documents($payload),
            SupervisorFieldModule::Folders => $this->folders($payload),
            SupervisorFieldModule::Weapons => $this->weapons($payload),
            SupervisorFieldModule::Recommendations => $this->recommendations($payload),
            SupervisorFieldModule::Alarms => $this->alarms($payload),
            SupervisorFieldModule::Supports => $this->supports($payload),
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function inventory(array $payload): ValidatedFieldPayload
    {
        $data = $this->validate($payload, [
            'condition' => ['required', 'string', Rule::in(['good', 'novelty', 'managed'])],
        ]);

        $outcome = $data['condition'] === 'novelty'
            ? SupervisorFieldOutcome::Attention
            : SupervisorFieldOutcome::Ok;

        return new ValidatedFieldPayload($data, $outcome);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function documents(array $payload): ValidatedFieldPayload
    {
        $data = $this->validate($payload, [
            'kind' => ['required', Rule::enum(SupervisorPostDocumentKind::class)],
            'status' => ['required', 'string', Rule::in(['pending', 'delivered'])],
            'quantity' => ['required', 'integer', 'min:1', 'max:9999'],
        ]);
        $data['quantity'] = (int) $data['quantity'];

        $outcome = $data['status'] === 'pending'
            ? SupervisorFieldOutcome::Attention
            : SupervisorFieldOutcome::Ok;

        return new ValidatedFieldPayload($data, $outcome);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function folders(array $payload): ValidatedFieldPayload
    {
        $data = $this->validate($payload, [
            'status' => ['required', 'string', Rule::in(['complete', 'missing'])],
            'missing_items' => ['nullable', 'string', 'max:500'],
        ]);

        if ($data['status'] === 'complete') {
            $data['missing_items'] = null;
        }

        $outcome = $data['status'] === 'missing'
            ? SupervisorFieldOutcome::Attention
            : SupervisorFieldOutcome::Ok;

        return new ValidatedFieldPayload($data, $outcome);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function weapons(array $payload): ValidatedFieldPayload
    {
        $payload['ammo_ok'] = $payload['ammo_ok'] ?? false;
        $payload['novelty'] = $payload['novelty'] ?? false;

        $data = $this->validate($payload, [
            'serial' => ['required', 'string', 'max:80'],
            'ammo_ok' => ['required', 'boolean'],
            'novelty' => ['required', 'boolean'],
        ]);
        $data['ammo_ok'] = (bool) $data['ammo_ok'];
        $data['novelty'] = (bool) $data['novelty'];

        $outcome = ($data['novelty'] || ! $data['ammo_ok'])
            ? SupervisorFieldOutcome::Attention
            : SupervisorFieldOutcome::Ok;

        return new ValidatedFieldPayload($data, $outcome);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function recommendations(array $payload): ValidatedFieldPayload
    {
        $data = $this->validate($payload, [
            'title' => ['required', 'string', 'min:3', 'max:120'],
            'body' => ['required', 'string', 'min:3', 'max:2000'],
            'priority' => ['required', Rule::enum(SupervisorRecommendationPriority::class)],
            'due_date' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        $priority = SupervisorRecommendationPriority::from($data['priority']);
        $outcome = match ($priority) {
            SupervisorRecommendationPriority::Urgent,
            SupervisorRecommendationPriority::High => SupervisorFieldOutcome::Critical,
            default => SupervisorFieldOutcome::Attention,
        };

        return new ValidatedFieldPayload($data, $outcome);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function alarms(array $payload): ValidatedFieldPayload
    {
        $data = $this->validate($payload, [
            'result' => ['required', 'string', Rule::in(['ok', 'fail'])],
        ]);

        $outcome = $data['result'] === 'fail'
            ? SupervisorFieldOutcome::Critical
            : SupervisorFieldOutcome::Ok;

        return new ValidatedFieldPayload($data, $outcome);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function supports(array $payload): ValidatedFieldPayload
    {
        $data = $this->validate($payload, [
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        return new ValidatedFieldPayload($data, SupervisorFieldOutcome::Attention);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    private function validate(array $payload, array $rules): array
    {
        $validator = Validator::make($payload, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}
