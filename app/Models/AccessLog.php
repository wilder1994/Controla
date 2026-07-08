<?php
namespace App\Models;

use App\Models\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AccessLog extends Model
{
    use BelongsToClient, HasFactory;

    protected $fillable = [
        'client_id', 'visitor_id', 'user_id', 'resident_id', 'housing_unit_id', 'vehicle_id', 'host_id', 'location_id',
        'authorized_by', 'access_type', 'entry_time', 'exit_time', 'status',
        'purpose', 'company_visited', 'screening_temp', 'qr_code', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'entry_time' => 'datetime',
            'exit_time' => 'datetime',
            'screening_temp' => 'decimal:1',
        ];
    }

    public function visitor()
    {
        return $this->belongsTo(Visitor::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function resident()
    {
        return $this->belongsTo(Resident::class);
    }

    public function housingUnit()
    {
        return $this->belongsTo(HousingUnit::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function host()
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function authorizer()
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }
}
