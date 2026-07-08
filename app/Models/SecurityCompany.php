<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SecurityCompany extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'legal_name',
        'trade_name',
        'tax_id',
        'email',
        'phone',
        'logo_path',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function activeClients(): HasMany
    {
        return $this->clients()->where('is_active', true);
    }
}
