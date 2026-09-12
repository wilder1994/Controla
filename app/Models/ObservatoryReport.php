<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ObservatoryReportKind;
use App\Enums\ObservatoryReporterRole;
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
        'reporter_role',
        'kind',
        'body',
        'is_anonymous',
        'reporter_name',
        'reporter_phone',
        'reported_by_user_id',
        'photo_path',
        'latitude',
        'longitude',
        'ip_hash',
    ];

    protected function casts(): array
    {
        return [
            'source' => ObservatoryReportSource::class,
            'reporter_role' => ObservatoryReporterRole::class,
            'kind' => ObservatoryReportKind::class,
            'is_anonymous' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    public function kindLabel(): string
    {
        return $this->kind instanceof ObservatoryReportKind ? $this->kind->label() : '—';
    }

    public function sourceLabel(): string
    {
        return $this->source instanceof ObservatoryReportSource ? $this->source->label() : '—';
    }

    public function roleLabel(): string
    {
        return $this->reporter_role instanceof ObservatoryReporterRole ? $this->reporter_role->label() : '';
    }

    public function originLabel(): string
    {
        $role = $this->roleLabel();

        return $role !== '' ? $this->sourceLabel().' · '.$role : $this->sourceLabel();
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

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_user_id');
    }
}
