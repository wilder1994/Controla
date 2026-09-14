<?php

declare(strict_types=1);

namespace App\Enums;

enum OperationalAlertType: string
{
    case Panic = 'panic';
    case Observatory = 'observatory';
    case ServiceChange = 'service_change';
}
