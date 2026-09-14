<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OperationalAlertType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class OperationalAlert extends Model
{
    protected $fillable = [
        'type',
        'security_company_id',
        'actor_user_id',
        'client_id',
        'installation_id',
        'supervisor_post_id',
        'title',
        'body',
        'latitude',
        'longitude',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'type' => OperationalAlertType::class,
            'latitude' => 'float',
            'longitude' => 'float',
            'payload' => 'array',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function installation(): BelongsTo
    {
        return $this->belongsTo(Installation::class);
    }

    public function attention(): HasOne
    {
        return $this->hasOne(PanicAttention::class);
    }
}
