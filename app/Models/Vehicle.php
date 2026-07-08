<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use BelongsToClient, HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id', 'structure_id', 'visitor_id', 'user_id', 'resident_id',
        'plate', 'brand', 'model', 'color', 'type', 'photo_path',
        'assigned_parking_spot', 'tag_rfid', 'soat_expires_at', 'license_expires_at', 'is_visitor_vehicle',
    ];

    protected function casts(): array
    {
        return [
            'soat_expires_at' => 'date',
            'license_expires_at' => 'date',
            'is_visitor_vehicle' => 'boolean',
        ];
    }

    public function structure(): BelongsTo
    {
        return $this->belongsTo(Structure::class);
    }

    public function visitor()
    {
        return $this->belongsTo(Visitor::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function resident()
    {
        return $this->belongsTo(Resident::class);
    }

    public function accessLogs()
    {
        return $this->hasMany(AccessLog::class);
    }
}
