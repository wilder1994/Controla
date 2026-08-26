<?php

declare(strict_types=1);

namespace App\Services\Company;

use App\Enums\SupervisorChecklistKind;
use App\Models\SupervisorChecklistItem;
use App\Models\SupervisorShiftTemplate;
use App\Models\SupervisorZone;
use App\Support\Supervision\ShiftIntakeCatalog;
use Illuminate\Support\Str;

final class SeedSupervisorIntakeDefaultsService
{
    public function __construct(
        private readonly ShiftIntakeCatalog $defaults,
    ) {}

    public function execute(int $companyId): void
    {
        if (! SupervisorZone::query()->where('security_company_id', $companyId)->exists()) {
            foreach (['Norte', 'Sur', 'Centro'] as $index => $name) {
                SupervisorZone::query()->create([
                    'security_company_id' => $companyId,
                    'name' => $name,
                    'is_active' => true,
                    'sort_order' => ($index + 1) * 10,
                ]);
            }
        }

        if (! SupervisorShiftTemplate::query()->where('security_company_id', $companyId)->exists()) {
            SupervisorShiftTemplate::query()->create([
                'security_company_id' => $companyId,
                'name' => 'Día',
                'starts_at' => '06:00',
                'ends_at' => '18:00',
                'is_active' => true,
                'sort_order' => 10,
            ]);
            SupervisorShiftTemplate::query()->create([
                'security_company_id' => $companyId,
                'name' => 'Noche',
                'starts_at' => '18:00',
                'ends_at' => '06:00',
                'is_active' => true,
                'sort_order' => 20,
            ]);
        }

        $this->seedChecks($companyId, SupervisorChecklistKind::Ppe, $this->defaults->ppe());
        $this->seedChecks($companyId, SupervisorChecklistKind::Vehicle, $this->defaults->vehicle());
    }

    /** @param array<string, string> $items */
    private function seedChecks(int $companyId, SupervisorChecklistKind $kind, array $items): void
    {
        $exists = SupervisorChecklistItem::query()
            ->where('security_company_id', $companyId)
            ->where('kind', $kind)
            ->exists();

        if ($exists) {
            return;
        }

        $order = 10;
        foreach ($items as $key => $name) {
            SupervisorChecklistItem::query()->create([
                'security_company_id' => $companyId,
                'kind' => $kind,
                'item_key' => $key,
                'name' => $name,
                'is_active' => true,
                'sort_order' => $order,
            ]);
            $order += 10;
        }
    }

    public function nextKey(string $name): string
    {
        $slug = Str::slug($name, '_');

        return $slug !== '' ? $slug : 'item_'.Str::lower(Str::random(6));
    }
}
