<?php
namespace App\Models;

use App\Models\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supervision extends Model
{
    use BelongsToClient, SoftDeletes;

    protected $fillable = [
        'client_id',
        'user_id',
        'location_id',
        'supervision_code_id',
        'supervisor_name',
        'log_time',
        'type',
        'shift_type',
        'description',
        'latitude',
        'longitude',
        'signed_at',
    ];

    protected function casts(): array
    {
        return [
            'log_time' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'signed_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function supervisionCode()
    {
        return $this->belongsTo(SupervisionCode::class);
    }

    public function attachments()
    {
        return $this->hasMany(SupervisionAttachment::class);
    }
}