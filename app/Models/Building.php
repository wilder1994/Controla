<?php
namespace App\Models;

use App\Models\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Building extends Model
{
    use BelongsToClient, HasFactory, SoftDeletes;

    protected $fillable = ['client_id', 'code', 'name', 'address', 'type', 'location_id', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function housingUnits()
    {
        return $this->hasMany(HousingUnit::class);
    }
}
