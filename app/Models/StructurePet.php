<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PetSpecies;
use App\Models\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class StructurePet extends Model
{
    use BelongsToClient, SoftDeletes;

    protected $fillable = [
        'client_id',
        'structure_id',
        'name',
        'species',
        'breed',
        'is_potentially_dangerous',
        'vaccination_card_path',
    ];

    protected function casts(): array
    {
        return [
            'species' => PetSpecies::class,
            'is_potentially_dangerous' => 'boolean',
        ];
    }

    public function structure(): BelongsTo
    {
        return $this->belongsTo(Structure::class);
    }
}
