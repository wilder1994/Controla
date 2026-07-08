<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

final class ClientScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $context = app(TenantContext::class);

        if (! $context->isScopingEnabled()) {
            return;
        }

        $clientId = $context->clientId();

        if ($clientId === null) {
            return;
        }

        $builder->where($model->getTable().'.client_id', $clientId);
    }
}
