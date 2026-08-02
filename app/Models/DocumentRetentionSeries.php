<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentRetentionSeries extends Model
{
    protected $fillable = [
        'series',
        'subseries',
        'retention_label',
        'retention_days',
        'disposition',
        'legal_basis',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'retention_days' => 'integer',
            'sort_order' => 'integer',
        ];
    }
}
