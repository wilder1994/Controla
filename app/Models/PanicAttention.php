<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PanicAttentionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PanicAttention extends Model
{
    protected $fillable = [
        'operational_alert_id',
        'security_company_id',
        'attended_by_user_id',
        'status',
        'observations',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => PanicAttentionStatus::class,
            'closed_at' => 'datetime',
        ];
    }

    public function alert(): BelongsTo
    {
        return $this->belongsTo(OperationalAlert::class, 'operational_alert_id');
    }

    public function attendee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'attended_by_user_id');
    }

    public function folio(): string
    {
        return 'PN-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    public function isOpen(): bool
    {
        return $this->status === PanicAttentionStatus::Abierto;
    }

    public function isAttendedBy(User $user): bool
    {
        return (int) $this->attended_by_user_id === (int) $user->id;
    }
}
