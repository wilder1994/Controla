<?php

declare(strict_types=1);

namespace App\Services\Ops;

use App\Enums\OperationalAlertType;
use App\Enums\PanicAttentionStatus;
use App\Models\OperationalAlert;
use App\Models\PanicAttention;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class ClaimPanicAttentionService
{
    public function execute(User $user, int $alertId): PanicAttention
    {
        if (! $user->can('ops.panic.attend')) {
            throw new AccessDeniedHttpException('No puedes atender pánicos.');
        }

        $companyId = (int) ($user->security_company_id ?? 0);
        if ($companyId < 1) {
            throw new InvalidArgumentException('No hay empresa para atender el pánico.');
        }

        return DB::transaction(function () use ($user, $alertId, $companyId): PanicAttention {
            $alert = OperationalAlert::query()
                ->whereKey($alertId)
                ->where('security_company_id', $companyId)
                ->where('type', OperationalAlertType::Panic->value)
                ->lockForUpdate()
                ->firstOrFail();

            $existing = PanicAttention::query()
                ->where('operational_alert_id', $alert->id)
                ->first();

            if ((int) $alert->actor_user_id === (int) $user->id) {
                throw new AccessDeniedHttpException('Quien activó el pánico no puede atenderlo.');
            }

            if ($existing !== null) {
                if ($existing->isAttendedBy($user)) {
                    return $existing;
                }

                throw new ConflictHttpException('Este pánico ya lo está atendiendo otro usuario.');
            }

            return PanicAttention::query()->create([
                'operational_alert_id' => $alert->id,
                'security_company_id' => $companyId,
                'attended_by_user_id' => $user->id,
                'status' => PanicAttentionStatus::Abierto,
            ]);
        });
    }
}
