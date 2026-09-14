<?php

declare(strict_types=1);

namespace App\Enums;

enum AccessGrantScope: string
{
    case Company = 'company';
    case Client = 'client';
    case Installation = 'installation';
}
