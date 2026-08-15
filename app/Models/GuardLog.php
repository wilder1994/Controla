<?php
namespace App\Models;

use App\Models\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GuardLog extends Model
{
    use BelongsToClient, HasFactory, SoftDeletes;

    protected $fillable = ['client_id', 'user_id', 'location_id', 'log_time', 'type', 'shift_type', 'description', 'latitude', 'longitude', 'signed_at', 'is_panic', 'resolved_at', 'supervision_code_id', 'supervisor_name'];

    protected function casts(): array
    {
        return [
            'log_time' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'signed_at' => 'datetime',
            'is_panic' => 'boolean',
            'resolved_at' => 'datetime',
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
}
