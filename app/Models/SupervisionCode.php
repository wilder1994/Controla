<?php

namespace App\Models;

use App\Models\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupervisionCode extends Model
{
    use BelongsToClient, SoftDeletes;

    protected $fillable = ['client_id', 'name', 'code', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function supervisions()
    {
        return $this->hasMany(Supervision::class);
    }
}
