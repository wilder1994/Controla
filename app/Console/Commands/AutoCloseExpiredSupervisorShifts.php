<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Company\AutoCloseExpiredSupervisorShiftsService;
use Illuminate\Console\Command;

final class AutoCloseExpiredSupervisorShifts extends Command
{
    protected $signature = 'supervision:auto-close-shifts';

    protected $description = 'Cierra turnos de Supervisión abiertos tras el fin de plantilla + 30 min de gabela';

    public function handle(AutoCloseExpiredSupervisorShiftsService $service): int
    {
        $count = $service->execute();
        $this->info("Turnos cerrados automáticamente: {$count}");

        return self::SUCCESS;
    }
}
