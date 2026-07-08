<?php
namespace App\Models;

use App\Models\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Visitor extends Model
{
    use BelongsToClient, HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id', 'document_type', 'document_number', 'first_name', 'last_name',
        'phone', 'email', 'nationality', 'company', 'photo_path',
        'visitor_type', 'birth_date', 'notes',
    ];

    protected function casts(): array
    {
        return ['birth_date' => 'date'];
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }

    public function accessLogs()
    {
        return $this->hasMany(AccessLog::class);
    }

    public function preAuthorizations()
    {
        return $this->hasMany(PreAuthorization::class);
    }

    public function documents()
    {
        return $this->hasMany(VisitorDocument::class);
    }

    public function correspondence()
    {
        return $this->hasMany(Correspondence::class);
    }
}
