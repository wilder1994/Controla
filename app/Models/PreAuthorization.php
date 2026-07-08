<?php
namespace App\Models;

use App\Models\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PreAuthorization extends Model
{
    use BelongsToClient, HasFactory;

    protected $fillable = [
        'client_id', 'visitor_id', 'host_id', 'location_id',
        'scheduled_date', 'scheduled_time', 'expires_at',
        'status', 'qr_code', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'scheduled_time' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function visitor()
    {
        return $this->belongsTo(Visitor::class);
    }

    public function host()
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}
