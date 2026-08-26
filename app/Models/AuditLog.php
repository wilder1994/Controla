<?php

namespace App\Models;

use App\Models\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    use BelongsToClient;

    protected $fillable = [
        'client_id', 'user_id', 'action',
        'auditable_type', 'auditable_id',
        'old_values', 'new_values',
        'ip_address', 'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function actorName(): string
    {
        return $this->user?->name ?? 'Sistema';
    }

    public function scopeByEntity(Builder $query, Model $model): Builder
    {
        return $query->where('auditable_type', $model::class)
            ->where('auditable_id', $model->getKey());
    }

    public function actionLabel(): string
    {
        return ucfirst(str_replace('.', ' ', (string) $this->action));
    }
}
