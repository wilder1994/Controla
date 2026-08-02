<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LegalCorpusType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class LegalCorpusVersion extends Model
{
    protected $fillable = [
        'type',
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

    /** @return Collection<int, LegalCorpusVersion> */
    public static function currentForAllTypes(): Collection
    {
        return self::query()
            ->whereNull('superseded_at')
            ->orderBy('type')
            ->get();
    }
}
