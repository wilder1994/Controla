<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Models\Client;
use App\Models\ClientUserAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class AssignClientUsersService
{
    /** @param list<int> $userIds */
    public function assign(Client $client, array $userIds, int $companyId): int
    {
        $userIds = array_values(array_unique(array_map('intval', $userIds)));

        if ($userIds === []) {
            return 0;
        }

        $operatives = User::query()
            ->whereIn('id', $userIds)
            ->where('security_company_id', $companyId)
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['guardia', 'supervisor']))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (count($operatives) !== count($userIds)) {
            throw new InvalidArgumentException('Solo puedes asignar guardas o supervisores activos de tu empresa.');
        }

        return DB::transaction(function () use ($client, $operatives): int {
            $assigned = 0;

            foreach ($operatives as $userId) {
                ClientUserAssignment::query()->firstOrCreate(
                    ['user_id' => $userId, 'client_id' => $client->id],
                    ['is_primary' => false, 'assigned_at' => now()]
                );
                $assigned++;
            }

            return $assigned;
        });
    }

    public function unassign(Client $client, User $user, int $companyId): void
    {
        if ((int) $user->security_company_id !== $companyId) {
            throw new InvalidArgumentException('El usuario no pertenece a tu empresa.');
        }

        if (! $user->hasAnyRole(['guardia', 'supervisor'])) {
            throw new InvalidArgumentException('Solo puedes desasignar guardas o supervisores.');
        }

        ClientUserAssignment::query()
            ->where('client_id', $client->id)
            ->where('user_id', $user->id)
            ->delete();
    }
}
