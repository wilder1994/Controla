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
        'observatory_report_type_id',
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
            'is_anonymous' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    public function reportType(): BelongsTo
    {
        return $this->belongsTo(ObservatoryReportType::class, 'observatory_report_type_id');
    }

    public function kindLabel(): string
    {
        if ($this->reportType instanceof ObservatoryReportType) {
            return $this->reportType->name;
        }

        return ObservatoryReportKind::tryFrom((string) $this->kind)?->label()
            ?: (filled($this->kind) ? (string) $this->kind : '—');
    }

    public function typeColor(): string
    {
        return $this->reportType instanceof ObservatoryReportType
            ? $this->reportType->color
            : '#94a3b8';
    }

    public function typeLevel(): int
    {
        return $this->reportType instanceof ObservatoryReportType
            ? (int) $this->reportType->level
            : 1;
    }

    public function typeSlug(): string
    {
        return $this->reportType instanceof ObservatoryReportType
            ? $this->reportType->slug
            : (string) $this->kind;
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
