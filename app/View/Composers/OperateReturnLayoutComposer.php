<?php

declare(strict_types=1);

namespace App\View\Composers;

use App\Models\Client;
use App\Support\Company\CompanyOperateContext;
use Illuminate\View\View;

final class OperateReturnLayoutComposer
{
    public function compose(View $view): void
    {
        $operateReturn = [
            'active' => false,
            'client_id' => null,
            'client_name' => null,
            'mode' => null,
            'mode_label' => null,
        ];

        if (CompanyOperateContext::isActive()) {
            $clientId = CompanyOperateContext::clientId();
            $client = $clientId !== null
                ? Client::query()->find($clientId)
                : null;

            if ($client !== null) {
                $mode = CompanyOperateContext::mode()
                    ?? (request()->routeIs('client.*')
                        ? CompanyOperateContext::MODE_CLIENTE
                        : CompanyOperateContext::MODE_PORTERIA);

                $operateReturn = [
                    'active' => true,
                    'client_id' => $client->id,
                    'client_name' => $client->name,
                    'mode' => $mode,
                    'mode_label' => $mode === CompanyOperateContext::MODE_CLIENTE
                        ? 'panel del conjunto'
                        : 'portería',
                ];
            }
        }

        $view->with('operateReturn', $operateReturn);
    }
}
