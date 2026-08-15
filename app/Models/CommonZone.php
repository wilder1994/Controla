<?php
namespace App\Models;

use App\Models\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommonZone extends Model
{
    use BelongsToClient, SoftDeletes;

    protected $fillable = [
        'client_id', 'name', 'description', 'type', 'capacity',
        'requires_approval', 'open_time', 'close_time',
        'starts_at', 'ends_at', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'requires_approval' => 'boolean',
            'open_time' => 'datetime',
            'close_time' => 'datetime',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(CommonZoneBooking::class);
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'salon' => 'Salón social',
            'piscina' => 'Piscina',
            'gimnasio' => 'Gimnasio',
            'parque' => 'Zona verde',
            'cancha' => 'Cancha deportiva',
            'biblioteca' => 'Biblioteca',
            default => 'Zona común',
        };
    }
}