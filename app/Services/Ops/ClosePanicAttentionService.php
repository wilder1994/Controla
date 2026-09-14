<?php

declare(strict_types=1);

namespace App\Services\Ops;

use App\Enums\PanicAttentionStatus;
use App\Models\PanicAttention;
use App\Models\User;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class ClosePanicAttentionService
{
    public function execute(PanicAttention $attention, User $user, string $observations): PanicAttention
    {
        if (! $attention->isAttendedBy($user)) {
            throw new AccessDeniedHttpException('Solo quien atendió puede cerrar la ficha.');
        }

        if (! $attention->isOpen()) {
            return $attention;
        }

        $attention->update([
            'status' => PanicAttentionStatus::Cerrado,
            'observations' => $observations,
            'closed_at' => now(),
        ]);

        return $attention->refresh();
    }

    public function saveNotes(PanicAttention $attention, User $user, string $observations): PanicAttention
    {
        if (! $attention->isAttendedBy($user) || ! $attention->isOpen()) {
            throw new AccessDeniedHttpException('No puedes editar estas observaciones.');
        }

        $attention->update(['observations' => $observations]);

        return $attention->refresh();
    }
}
