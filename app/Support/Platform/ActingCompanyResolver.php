<?php

declare(strict_types=1);

namespace App\Support\Platform;

use App\Models\User;

final class ActingCompanyResolver
{
    public function id(?User $user): ?int
    {
        if ($user === null) {
            return null;
        }

        if ($user->hasRole('super-admin')) {
            return SupportCompanyContext::companyId();
        }

        $companyId = (int) ($user->security_company_id ?? 0);

        return $companyId > 0 ? $companyId : null;
    }

    public function requireId(?User $user): int
    {
        $id = $this->id($user);
        abort_unless($id !== null && $id > 0, 403, 'Usuario sin empresa de seguridad asignada.');

        return $id;
    }
}
