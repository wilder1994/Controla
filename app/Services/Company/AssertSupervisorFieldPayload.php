<?php

declare(strict_types=1);

namespace App\Services\Company;

use App\Domain\Supervision\Data\ValidatedFieldPayload;
use App\Enums\SupervisorFieldModule;
use App\Enums\SupervisorFieldOutcome;
use App\Enums\SupervisorRiskImpact;
use App\Enums\SupervisorRiskLikelihood;
use App\Enums\SupervisorWeaponPermitKind;
use App\Support\Supervision\RecommendationEvidencePhotos;
use App\Support\Supervision\RiskMatrix;
use App\Models\SupervisorControlBookType;
use App\Models\SupervisorDocumentType;
use App\Models\SupervisorRiskType;
use App\Models\SupervisorWeaponBrand;
use App\Models\SupervisorWeaponType;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class AssertSupervisorFieldPayload
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function execute(SupervisorFieldModule $module, array $payload, ?int $companyId = null): ValidatedFieldPayload
    {
        return match ($module) {
            SupervisorFieldModule::Inventory => $this->inventory($payload),
            SupervisorFieldModule::ControlBooks => $this->controlBooks($payload, $companyId),
            SupervisorFieldModule::Documents => $this->documents($payload, $companyId),
            SupervisorFieldModule::Folders => $this->folders($payload),
            SupervisorFieldModule::Weapons => $this->weapons($payload, $companyId),
            SupervisorFieldModule::Recommendations => $this->recommendations($payload, $companyId),
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
            'items' => ['required', 'array', 'min:1'],
            'items.*.type' => ['required', 'string', 'max:120'],
            'items.*.status' => ['required', 'string', Rule::in(['good', 'regular', 'bad'])],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
        ]);

        $statuses = array_column($data['items'], 'status');
        $outcome = match (true) {
            in_array('bad', $statuses, true) => SupervisorFieldOutcome::Critical,
            in_array('regular', $statuses, true) => SupervisorFieldOutcome::Attention,
            default => SupervisorFieldOutcome::Ok,
        };

        return new ValidatedFieldPayload($data, $outcome);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function controlBooks(array $payload, ?int $companyId): ValidatedFieldPayload
    {
        $typeRule = ['required', 'integer', 'min:1'];
        if ($companyId !== null && $companyId > 0) {
            $typeRule[] = Rule::exists('supervisor_control_book_types', 'id')->where(
                fn ($query) => $query->where('security_company_id', $companyId)->where('is_active', true),
            );
        }

        $data = $this->validate($payload, [
            'items' => ['required', 'array', 'min:1'],
            'items.*.control_book_type_id' => $typeRule,
            'items.*.novelty' => ['required', 'string', Rule::in(['yes', 'no'])],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
        ]);

        $typeNames = [];
        if ($companyId !== null && $companyId > 0) {
            $ids = collect($data['items'])->pluck('control_book_type_id')->unique()->all();
            $typeNames = SupervisorControlBookType::query()
                ->where('security_company_id', $companyId)
                ->whereKey($ids)
                ->pluck('name', 'id')
                ->all();
        }

        foreach ($data['items'] as $index => $item) {
            $data['items'][$index]['control_book_type_id'] = (int) $item['control_book_type_id'];
            $data['items'][$index]['control_book_type'] = $typeNames[(int) $item['control_book_type_id']] ?? null;
        }

        $hasNovelty = collect($data['items'])->contains(fn (array $item) => $item['novelty'] === 'yes');
        $outcome = $hasNovelty
            ? SupervisorFieldOutcome::Attention
            : SupervisorFieldOutcome::Ok;

        return new ValidatedFieldPayload($data, $outcome);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function documents(array $payload, ?int $companyId): ValidatedFieldPayload
    {
        $typeRule = ['required', 'integer', 'min:1'];
        if ($companyId !== null && $companyId > 0) {
            $typeRule[] = Rule::exists('supervisor_document_types', 'id')->where(
                fn ($query) => $query->where('security_company_id', $companyId)->where('is_active', true),
            );
        }

        $data = $this->validate($payload, [
            'items' => ['required', 'array', 'min:1'],
            'items.*.document_type_id' => $typeRule,
            'items.*.delivered' => ['required', 'integer', 'min:0', 'max:9999'],
            'items.*.pending' => ['required', 'integer', 'min:0', 'max:9999'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
        ]);

        $typeNames = [];
        if ($companyId !== null && $companyId > 0) {
            $ids = collect($data['items'])->pluck('document_type_id')->unique()->all();
            $typeNames = SupervisorDocumentType::query()
                ->where('security_company_id', $companyId)
                ->whereKey($ids)
                ->pluck('name', 'id')
                ->all();
        }

        foreach ($data['items'] as $index => $item) {
            $delivered = (int) $item['delivered'];
            $pending = (int) $item['pending'];
            if ($delivered + $pending < 1) {
                throw ValidationException::withMessages([
                    "items.$index" => 'Indique al menos una cantidad entregada o pendiente.',
                ]);
            }
            $data['items'][$index]['document_type_id'] = (int) $item['document_type_id'];
            $data['items'][$index]['delivered'] = $delivered;
            $data['items'][$index]['pending'] = $pending;
            $data['items'][$index]['document_type'] = $typeNames[(int) $item['document_type_id']] ?? null;
        }

        $hasPending = collect($data['items'])->contains(fn (array $item) => $item['pending'] > 0);
        $outcome = $hasPending
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
    private function weapons(array $payload, ?int $companyId): ValidatedFieldPayload
    {
        $typeRule = ['required', 'integer', 'min:1'];
        $brandRule = ['required', 'integer', 'min:1'];
        if ($companyId !== null && $companyId > 0) {
            $typeRule[] = Rule::exists('supervisor_weapon_types', 'id')->where(
                fn ($query) => $query->where('security_company_id', $companyId)->where('is_active', true),
            );
            $brandRule[] = Rule::exists('supervisor_weapon_brands', 'id')->where(
                fn ($query) => $query->where('security_company_id', $companyId)->where('is_active', true),
            );
        }

        $data = $this->validate($payload, [
            'weapon_type_id' => $typeRule,
            'weapon_brand_id' => $brandRule,
            'serial' => ['required', 'string', 'max:80'],
            'caliber' => ['required', 'string', 'max:40'],
            'permit_kind' => ['required', Rule::enum(SupervisorWeaponPermitKind::class)],
            'permit_number' => ['required', 'string', 'max:80'],
            'permit_expires_at' => ['required', 'date'],
            'ammo_quantity' => ['required', 'integer', 'min:0', 'max:9999'],
            'ammo_caliber' => ['required', 'string', 'max:40'],
            'photos' => ['nullable', 'array'],
            'photos.right' => ['nullable', 'string', 'max:255'],
            'photos.left' => ['nullable', 'string', 'max:255'],
            'photos.serial' => ['nullable', 'string', 'max:255'],
            'photos.brand' => ['nullable', 'string', 'max:255'],
            'photos.imprint' => ['nullable', 'string', 'max:255'],
            'photos.cleaning' => ['nullable', 'string', 'max:255'],
        ]);

        $data['weapon_type_id'] = (int) $data['weapon_type_id'];
        $data['weapon_brand_id'] = (int) $data['weapon_brand_id'];
        $data['ammo_quantity'] = (int) $data['ammo_quantity'];
        if ($companyId !== null && $companyId > 0) {
            $data['weapon_type'] = SupervisorWeaponType::query()
                ->where('security_company_id', $companyId)
                ->whereKey($data['weapon_type_id'])
                ->value('name');
            $data['weapon_brand'] = SupervisorWeaponBrand::query()
                ->where('security_company_id', $companyId)
                ->whereKey($data['weapon_brand_id'])
                ->value('name');
        }

        $expired = (string) $data['permit_expires_at'] < now()->toDateString();
        $outcome = $expired
            ? SupervisorFieldOutcome::Attention
            : SupervisorFieldOutcome::Ok;

        return new ValidatedFieldPayload($data, $outcome);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function recommendations(array $payload, ?int $companyId): ValidatedFieldPayload
    {
        $typeRule = ['required', 'integer', 'min:1'];
        if ($companyId !== null && $companyId > 0) {
            $typeRule[] = Rule::exists('supervisor_risk_types', 'id')->where(
                fn ($query) => $query->where('security_company_id', $companyId)->where('is_active', true),
            );
        }

        $photoRules = [];
        foreach (RecommendationEvidencePhotos::SLOTS as $slot) {
            $photoRules['items.*.photos.'.$slot] = ['nullable', 'string', 'max:255'];
        }

        $data = $this->validate($payload, [
            'items' => ['required', 'array', 'min:1', 'max:3'],
            'items.*.risk_type_id' => $typeRule,
            'items.*.risk' => ['required', 'string', 'min:3', 'max:2000'],
            'items.*.likelihood' => ['required', Rule::enum(SupervisorRiskLikelihood::class)],
            'items.*.impact' => ['required', Rule::enum(SupervisorRiskImpact::class)],
            'items.*.consequence' => ['required', 'string', 'min:3', 'max:2000'],
            'items.*.treatment' => ['required', 'string', 'min:3', 'max:2000'],
            'items.*.photos' => ['nullable', 'array'],
            ...$photoRules,
        ]);

        $typeNames = [];
        if ($companyId !== null && $companyId > 0) {
            $ids = collect($data['items'])->pluck('risk_type_id')->unique()->all();
            $typeNames = SupervisorRiskType::query()
                ->where('security_company_id', $companyId)
                ->whereKey($ids)
                ->pluck('name', 'id')
                ->all();
        }

        $levels = [];
        foreach ($data['items'] as $index => $item) {
            $likelihood = SupervisorRiskLikelihood::from((string) $item['likelihood']);
            $impact = SupervisorRiskImpact::from((string) $item['impact']);
            $level = RiskMatrix::level($likelihood, $impact);
            $data['items'][$index]['risk_type_id'] = (int) $item['risk_type_id'];
            $data['items'][$index]['risk_type'] = $typeNames[(int) $item['risk_type_id']] ?? null;
            $data['items'][$index]['likelihood'] = $likelihood->value;
            $data['items'][$index]['impact'] = $impact->value;
            $data['items'][$index]['risk_level'] = $level->value;
            $data['items'][$index]['priority'] = RiskMatrix::priority($level)->value;
            $data['items'][$index]['risk_score'] = RiskMatrix::score($likelihood, $impact);
            $levels[] = $level;
        }

        return new ValidatedFieldPayload($data, RiskMatrix::worstOutcome($levels));
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
