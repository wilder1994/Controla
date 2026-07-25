<?php
namespace App\Models;

use App\Models\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Resident extends Model
{
    use BelongsToClient, HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id', 'user_id', 'document_type', 'document_number', 'first_name', 'last_name',
        'birth_date', 'blood_type', 'phone', 'email', 'photo_path', 'resident_type', 'is_active', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'birth_date' => 'date',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function housingUnits()
    {
        return $this->belongsToMany(HousingUnit::class, 'resident_housing_unit')
            ->withPivot('is_primary', 'relationship_type')
            ->withTimestamps();
    }

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }

    public function accessLogs()
    {
        return $this->hasMany(AccessLog::class);
    }

    public function correspondence()
    {
        return $this->hasMany(Correspondence::class);
    }

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getPrimaryUnitAttribute()
    {
        return $this->housingUnits()->wherePivot('is_primary', true)->first();
    }
}
