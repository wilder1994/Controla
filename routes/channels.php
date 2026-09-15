<?php

use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('ops.company.{id}', function (User $user, int $id): bool {
    if ($user->hasRole('super-admin')) {
        return true;
    }

    if ((int) $user->security_company_id === $id) {
        return true;
    }

    return Client::query()
        ->whereKey($user->assignedClientIds())
        ->where('security_company_id', $id)
        ->exists();
});

Broadcast::channel('ops.client.{id}', function (User $user, int $id): bool {
    return $user->canAccessClient($id);
});
