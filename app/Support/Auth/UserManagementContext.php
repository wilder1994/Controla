<?php

declare(strict_types=1);

namespace App\Support\Auth;

enum UserManagementContext: string
{
    case Platform = 'platform';
    case Company = 'company';
    case Client = 'client';
}
