<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CompanyPackageSku;
use App\Enums\LegalCorpusType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class LegalCorpusVersion extends Model
{
    protected $fillable = [
        'type',
        'package_sku',
        'version',
        'title',
        'content',
        'effective_from',
        'superseded_at',
        'content_hash',
    ];

    protected function casts(): array
    {
        return [
            'type' => LegalCorpusType::class,
            'effective_from' => 'date',
            'superseded_at' => 'datetime',
        ];
    }

    public function isCurrent(): bool
    {
        return $this->superseded_at === null;
    }

    public function packageSkuEnum(): ?CompanyPackageSku
    {
        if ($this->package_sku === null || $this->package_sku === '') {
            return null;
        }

        return CompanyPackageSku::tryFrom($this->package_sku);
    }

    public function isGlobal(): bool
    {
        return $this->package_sku === null;
    }

    /** Documentos globales vigentes (sin contrato por SKU). */
    /** @return Collection<int, LegalCorpusVersion> */
    public static function currentGlobals(): Collection
    {
        return self::query()
            ->whereNull('superseded_at')
            ->whereNull('package_sku')
            ->where('type', '!=', LegalCorpusType::Contract->value)
            ->orderBy('type')
            ->get();
    }

    /** Corpus vigente para contratación de un SKU: globales + contrato del plan. */
    /** @return Collection<int, LegalCorpusVersion> */
    public static function currentForPackage(?CompanyPackageSku $sku): Collection
    {
        $globals = self::currentGlobals();

        if ($sku === null) {
            return $globals;
        }

        $contract = self::query()
            ->whereNull('superseded_at')
            ->where('type', LegalCorpusType::Contract->value)
            ->where('package_sku', $sku->value)
            ->first();

        if ($contract === null) {
            return $globals;
        }

        return $globals->prepend($contract)->values();
    }

    /**
     * @deprecated Prefer currentForPackage / currentGlobals
     * @return Collection<int, LegalCorpusVersion>
     */
    public static function currentForAllTypes(): Collection
    {
        return self::query()
            ->whereNull('superseded_at')
            ->orderByRaw("CASE WHEN type = 'contract' THEN 0 ELSE 1 END")
            ->orderBy('package_sku')
            ->orderBy('type')
            ->get();
    }
}
