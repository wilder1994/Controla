<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SupervisorChecklistKind;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SupervisorChecklistItem extends Model
{
    protected $fillable = [
        'security_company_id',
        'kind',
        'item_key',
        'name',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'kind' => SupervisorChecklistKind::class,
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function securityCompany(): BelongsTo
    {
        return $this->belongsTo(SecurityCompany::class);
    }

    /**
     * @return array<string, string>
     */
    public static function keyedLabels(int $companyId, SupervisorChecklistKind $kind): array
    {
        return self::query()
            ->where('security_company_id', $companyId)
            ->where('kind', $kind)
            ->active()
            ->pluck('name', 'item_key')
            ->all();
    }

    /** @param Builder<self> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('name');
    }
}
