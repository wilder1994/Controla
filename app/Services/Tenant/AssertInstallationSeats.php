<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Models\SecurityCompany;
use Illuminate\Validation\ValidationException;

final class AssertInstallationSeats
{
    public const ACTION_CREATE_CLIENT = 'create_client';

    public const ACTION_CREATE_INSTALLATION = 'create_installation';

    public const ACTION_REACTIVATE = 'reactivate';

    public function execute(SecurityCompany $company, string $action, ?int $exceptInstallationId = null): void
    {
        $max = (int) ($company->max_clients ?: 0);
        $used = $company->installationSeatsCount($exceptInstallationId);

        if ($max >= 1 && $used < $max) {
            return;
        }

        $messages = [
            self::ACTION_CREATE_CLIENT => 'No puedes crear este cliente: no hay cupo de instalaciones ('.$used.'/'.$max.'). Amplía el paquete o archiva una instalación de otro cliente para liberar cupo.',
            self::ACTION_CREATE_INSTALLATION => 'No puedes crear esta instalación: no hay cupo ('.$used.'/'.$max.'). Amplía el paquete o archiva otra instalación.',
            self::ACTION_REACTIVATE => 'No puedes reactivar esta instalación: el cupo está lleno ('.$used.'/'.$max.'). Archiva otra o amplía el paquete.',
        ];

        throw ValidationException::withMessages([
            'package' => $messages[$action] ?? $messages[self::ACTION_CREATE_INSTALLATION],
        ]);
    }
}
