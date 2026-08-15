<?php
namespace App\Models;

use App\Models\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommonZoneBooking extends Model
{
    use BelongsToClient;

    protected $fillable = [
        'client_id', 'common_zone_id', 'user_id', 'housing_unit_id',
        'date', 'start_time', 'end_time', 'people_count',
        'status', 'qr_code', 'checked_in_at', 'cancelled_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'people_count' => 'integer',
            'checked_in_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(CommonZone::class, 'common_zone_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function housingUnit(): BelongsTo
    {
        return $this->belongsTo(HousingUnit::class);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'confirmed' => 'Confirmada',
            'checked_in' => 'En uso',
            'completed' => 'Completada',
            'cancelled' => 'Cancelada',
            default => 'Pendiente',
        };
    }
}