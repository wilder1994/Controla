<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Enums\SupervisorChecklistKind;
use App\Models\SupervisorChecklistItem;
use App\Services\Company\SeedSupervisorIntakeDefaultsService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class OpenSupervisorShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('supervisor') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $companyId = (int) $this->user()?->security_company_id;
        if ($companyId > 0) {
            app(SeedSupervisorIntakeDefaultsService::class)->execute($companyId);
        }

        $this->merge([
            'ppe_checklist' => $this->boolMap($this->input('ppe_checklist')),
            'vehicle_checklist' => $this->boolMap($this->input('vehicle_checklist')),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $companyId = (int) $this->user()?->security_company_id;
        $ppe = array_keys(SupervisorChecklistItem::keyedLabels($companyId, SupervisorChecklistKind::Ppe));
        $vehicle = array_keys(SupervisorChecklistItem::keyedLabels($companyId, SupervisorChecklistKind::Vehicle));

        $rules = [
            'shift_template_id' => [
                'required',
                'integer',
                Rule::exists('supervisor_shift_templates', 'id')
                    ->where('security_company_id', $companyId)
                    ->where('is_active', true),
            ],
            'zone_id' => [
                'required',
                'integer',
                Rule::exists('supervisor_zones', 'id')
                    ->where('security_company_id', $companyId)
                    ->where('is_active', true),
            ],
            'km_start' => ['required', 'integer', 'min:0'],
            'vehicle_id' => ['nullable', 'integer', 'exists:supervisor_fleet_vehicles,id'],
            'vehicle.plate' => ['required_without:vehicle_id', 'nullable', 'string', 'max:12'],
            'vehicle.brand' => ['required_without:vehicle_id', 'nullable', 'string', 'max:80'],
            'vehicle.line' => ['nullable', 'string', 'max:80'],
            'vehicle.model' => ['nullable', 'string', 'max:40'],
            'vehicle.color' => ['nullable', 'string', 'max:40'],
            'vehicle.type' => ['nullable', 'string', 'max:40'],
            'vehicle.soat_expires_at' => ['nullable', 'date'],
            'vehicle.technical_review_expires_at' => ['nullable', 'date'],
            'odometer_photo' => ['required', 'image', 'max:5120'],
            'selfie_photo' => ['required', 'image', 'max:5120'],
            'ppe_checklist' => ['required', 'array'],
            'vehicle_checklist' => ['required', 'array'],
        ];

        foreach ($ppe as $key) {
            $rules['ppe_checklist.'.$key] = ['required', 'boolean', 'accepted'];
        }
        foreach ($vehicle as $key) {
            $rules['vehicle_checklist.'.$key] = ['required', 'boolean', 'accepted'];
        }

        return $rules;
    }

    /**
     * @return array<string, bool>
     */
    private function boolMap(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($value)) {
            return [];
        }

        $mapped = [];
        foreach ($value as $key => $item) {
            $mapped[(string) $key] = filter_var($item, FILTER_VALIDATE_BOOLEAN);
        }

        return $mapped;
    }
}
