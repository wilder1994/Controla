<?php
namespace App\Models;

use App\Models\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GuardLog extends Model
{
    use BelongsToClient, HasFactory, SoftDeletes;

    protected $fillable = ['client_id', 'user_id', 'location_id', 'log_time', 'type', 'shift_type', 'description'];

    protected function casts(): array
    {
        return ['log_time' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}
