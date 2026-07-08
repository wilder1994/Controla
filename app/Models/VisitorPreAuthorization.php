<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AuthorizationStatus;
use App\Enums\VisitorCategory;
use App\Models\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class VisitorPreAuthorization extends Model
{
    use BelongsToClient, SoftDeletes;

    protected $fillable = [
        'client_id',
        'structure_id',
        'member_id',
        'visitor_name',
        'visitor_document',
        'visitor_category',
        'valid_for_date',
        'qr_auth_token',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'visitor_category' => VisitorCategory::class,
            'status' => AuthorizationStatus::class,
            'valid_for_date' => 'date',
        ];
    }

    public function structure(): BelongsTo
    {
        return $this->belongsTo(Structure::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(StructureMember::class, 'member_id');
    }
}
