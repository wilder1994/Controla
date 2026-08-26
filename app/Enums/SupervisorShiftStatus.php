<?php

declare(strict_types=1);

namespace App\Enums;

enum SupervisorShiftStatus: string
{
    case Open = 'open';
    case Closed = 'closed';
}
