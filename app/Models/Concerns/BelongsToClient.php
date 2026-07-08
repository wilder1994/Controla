<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Client;
use App\Models\Scopes\ClientScope;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** @mixin Model */
trait BelongsToClient
{
    public static function bootBelongsToClient(): void
    {
        static::addGlobalScope(new ClientScope());

        static::creating(function (Model $model): void {
            if ($model->getAttribute('client_id') !== null) {
                return;
            }

            $clientId = app(TenantContext::class)->clientId();

            if ($clientId !== null) {
                $model->setAttribute('client_id', $clientId);
            }
        });
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
