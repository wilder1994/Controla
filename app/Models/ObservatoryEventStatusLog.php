<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ObservatoryEventStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ObservatoryEventStatusLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'event_id',
        'from_status',
        'to_status',
        'user_id',
        'note',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'from_status' => ObservatoryEventStatus::class,
            'to_status' => ObservatoryEventStatus::class,
            'created_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(ObservatoryEvent::class, 'event_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
