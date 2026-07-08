<?php
namespace App\Models;

use App\Models\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HousingUnit extends Model
{
    use BelongsToClient, HasFactory, SoftDeletes;

    protected $fillable = ['client_id', 'building_id', 'unit_number', 'floor', 'type', 'notes', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function residents()
    {
        return $this->belongsToMany(Resident::class, 'resident_housing_unit')
            ->withPivot('is_primary', 'relationship_type')
            ->withTimestamps();
    }

    public function accessLogs()
    {
        return $this->hasMany(AccessLog::class);
    }

    public function correspondence()
    {
        return $this->hasMany(Correspondence::class);
    }

    public function getFullLabelAttribute()
    {
        $building = $this->building;
        $bName = $building ? $building->name : '?';
        return "{$bName} - {$this->unit_number}";
    }
}
