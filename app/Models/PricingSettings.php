<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PricingSettings extends Model
{
    protected $fillable = [
        'unit_price_manual',
        'unit_price_hardware',
        'unit_price_supervision',
        'currency',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'unit_price_manual' => 'decimal:2',
            'unit_price_hardware' => 'decimal:2',
            'unit_price_supervision' => 'decimal:2',
            'updated_by' => 'integer',
        ];
    }

    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function current(): self
    {
        $settings = self::query()->latest('id')->first();

        if ($settings !== null) {
            return $settings;
        }

        return self::query()->create([
            'unit_price_manual' => (float) config('tenancy.pricing.default_unit_manual', 80_000),
            'unit_price_hardware' => (float) config('tenancy.pricing.default_unit_hardware', 150_000),
            'unit_price_supervision' => (float) config('tenancy.pricing.default_unit_supervision', 80_000),
            'currency' => (string) config('tenancy.pricing.currency', 'COP'),
        ]);
    }
}
