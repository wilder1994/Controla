<?php

declare(strict_types=1);

namespace App\Support\Company;

use App\Models\Client;
use App\Models\User;
use App\Support\Auth\AssignableRoles;
use Illuminate\Support\Collection;

final class InstallationSiteAdmins
{
    /**
     * @param  Collection<int, int>|list<int>  $clientIds
     * @return list<array{id: int, name: string, label: string, client_id: int}>
     */
    public static function optionsForClients(Collection|array $clientIds): array
    {
        $ids = Collection::wrap($clientIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return [];
        }

        return User::query()
            ->role(AssignableRoles::CLIENT_INSTALLATION_ADMIN)
            ->whereHas('clients', fn ($q) => $q->whereIn('clients.id', $ids))
            ->with(['clients:id'])
            ->orderBy('name')
            ->get()
            ->flatMap(static function (User $user) {
                return $user->clients->map(static fn (Client $client): array => [
                    'id' => (int) $user->id,
                    'name' => $user->name,
                    'label' => self::label($user),
                    'client_id' => (int) $client->id,
                ]);
            })
            ->values()
            ->all();
    }

    public static function label(?User $user): string
    {
        if ($user === null) {
            return '—';
        }

        $job = trim((string) $user->job_title);

        return $job !== '' ? $user->name.' · '.$job : $user->name;
    }
}
