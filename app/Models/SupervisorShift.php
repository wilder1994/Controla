<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SupervisorShiftSlot;
use App\Enums\SupervisorShiftStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class SupervisorShift extends Model
{
    protected $fillable = [
        'security_company_id',
        'user_id',
        'status',
        'km_start',
        'km_end',
        'km_traveled',
        'km_start_photo_path',
        'km_end_photo_path',
        'started_at',
        'ended_at',
        'notes',
        'supervisor_fleet_vehicle_id',
        'supervisor_zone_id',
        'supervisor_shift_template_id',
        'shift_slot',
        'schedule_label',
        'route_zone',
        'km_start_selfie_path',
        'km_end_selfie_path',
        'ppe_checklist',
        'vehicle_checklist',
    ];

    protected function casts(): array
    {
        return [
            'status' => SupervisorShiftStatus::class,
            'km_start' => 'integer',
            'km_end' => 'integer',
            'km_traveled' => 'integer',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'shift_slot' => SupervisorShiftSlot::class,
            'ppe_checklist' => 'array',
            'vehicle_checklist' => 'array',
        ];
    }

    public function securityCompany(): BelongsTo
    {
        return $this->belongsTo(SecurityCompany::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(SupervisorShiftLocation::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(SupervisorShiftReview::class);
    }

    public function fieldLogs(): HasMany
    {
        return $this->hasMany(SupervisorFieldLog::class);
    }

    public function fleetVehicle(): BelongsTo
    {
        return $this->belongsTo(SupervisorFleetVehicle::class, 'supervisor_fleet_vehicle_id');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(SupervisorZone::class, 'supervisor_zone_id');
    }

    public function shiftTemplate(): BelongsTo
    {
        return $this->belongsTo(SupervisorShiftTemplate::class, 'supervisor_shift_template_id');
    }

    public function isOpen(): bool
    {
        return $this->status === SupervisorShiftStatus::Open;
    }
}
