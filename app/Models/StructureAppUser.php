<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class StructureAppUser extends Model
{
    use BelongsToClient, SoftDeletes;

    protected $fillable = [
        'client_id',
        'member_id',
        'username',
        'email',
        'password',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(StructureMember::class, 'member_id');
    }

    public function getLoginEmailAttribute(): string
    {
        $client = $this->client;

        return $client
            ? "{$this->username}@{$client->login_suffix}"
            : $this->username;
    }
}
