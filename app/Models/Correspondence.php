<?php
namespace App\Models;

use App\Models\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Correspondence extends Model
{
    use BelongsToClient, HasFactory, SoftDeletes;

    protected $table = 'correspondence';

    protected $fillable = [
        'client_id', 'visitor_id', 'host_id', 'location_id', 'housing_unit_id', 'resident_id',
        'carrier', 'courier_guide', 'package_type', 'received_at', 'received_by',
        'delivered_at', 'delivered_by', 'status', 'photo_path', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
            'delivered_at' => 'datetime',
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

    public function housingUnit()
    {
        return $this->belongsTo(HousingUnit::class);
    }

    public function resident()
    {
        return $this->belongsTo(Resident::class);
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function deliverer()
    {
        return $this->belongsTo(User::class, 'delivered_by');
    }
}
