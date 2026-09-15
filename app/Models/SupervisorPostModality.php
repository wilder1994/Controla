<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SupervisorPostModality extends Model
{
    protected $fillable = [
        'security_company_id',
        'hours',
        'name',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'hours' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function securityCompany(): BelongsTo
    {
        return $this->belongsTo(SecurityCompany::class);
    }

    public function label(): string
    {
        $base = $this->hours.' h';
        $name = trim((string) $this->name);

        return $name !== '' ? $base.' · '.$name : $base;
    }

    /** @param Builder<self> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('hours');
    }

    /**
     * @return array<int, string>
     */
    public static function optionsForCompany(int $companyId, ?int $includeHours = null): array
    {
        $rows = self::query()
            ->where('security_company_id', $companyId)
            ->where(function ($q) use ($includeHours) {
                $q->where('is_active', true);
                if ($includeHours !== null && $includeHours > 0) {
                    $q->orWhere('hours', $includeHours);
                }
            })
            ->orderBy('sort_order')
            ->orderBy('hours')
            ->get();

        $options = [];
        foreach ($rows as $row) {
            $options[$row->hours] = $row->label();
        }

        if ($includeHours !== null && $includeHours > 0 && ! isset($options[$includeHours])) {
            $options[$includeHours] = $includeHours.' h';
        }

        return $options;
    }
}
