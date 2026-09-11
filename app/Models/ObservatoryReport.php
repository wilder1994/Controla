<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ObservatoryReportKind;
use App\Enums\ObservatoryReportSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

final class ObservatoryReport extends Model
{
    protected $fillable = [
        'event_id',
        'client_id',
        'installation_id',
        'source',
        'kind',
        'body',
        'is_anonymous',
        'reporter_name',
        'reporter_phone',
        'photo_path',
        'ip_hash',
    ];

    protected function casts(): array
    {
        return [
            'source' => ObservatoryReportSource::class,
            'kind' => ObservatoryReportKind::class,
            'is_anonymous' => 'boolean',
        ];
    }

    public function kindLabel(): string
    {
        return $this->kind instanceof ObservatoryReportKind ? $this->kind->label() : '—';
    }

    public function sourceLabel(): string
    {
        return $this->source instanceof ObservatoryReportSource ? $this->source->label() : '—';
    }

    public function reporterLabel(): string
    {
        if ($this->is_anonymous) {
            return 'Anónimo';
        }

        $name = trim((string) $this->reporter_name);

        return $name !== '' ? $name : 'Identificado';
    }

    public function photoUrl(): ?string
    {
        if (! filled($this->photo_path)) {
            return null;
        }

        return Storage::disk('public')->url($this->photo_path);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(ObservatoryEvent::class, 'event_id');
    }

    public function installation(): BelongsTo
    {
        return $this->belongsTo(Installation::class);
    }
}
