<?php

declare(strict_types=1);

namespace App\Support\Billing;

use App\Models\CommercialPayment;
use App\Models\User;

final class CommercialPaymentAuthorization
{
    public static function authorize(User $user, CommercialPayment $payment): void
    {
        if ($user->can('platform.documents.manage')) {
            return;
        }

        if ($user->security_company_id === $payment->security_company_id) {
            return;
        }

        abort(403, 'No puede operar este pago.');
    }
}
