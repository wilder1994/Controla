<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AccessGrantLevel;
use App\Enums\AccessGrantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class UserModuleGrant extends Model
{
    protected $fillable = [
        'user_id',
        'scope',
        'scope_id',
        'module',
        'level',
    ];

    protected function casts(): array
    {
        return [
            'scope' => AccessGrantScope::class,
            'level' => AccessGrantLevel::class,
            'scope_id' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
