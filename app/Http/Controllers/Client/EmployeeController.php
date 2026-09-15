<?php

declare(strict_types=1);

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Employee;
use App\Repositories\EmployeeRepository;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class EmployeeController extends Controller
{
    public function __construct(
        private readonly EmployeeRepository $employees,
        private readonly TenantContext $tenantContext,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Employee::class);
        $client = $this->client();
        $search = $request->string('q')->trim()->toString();

        $employees = $this->employees->paginateForClientPosts(
            (int) $client->security_company_id,
            (int) $client->id,
            $this->tenantContext->installationIds(),
            25,
            $search !== '' ? $search : null,
        );

        return view('modules.client.employees.index', [
            'employees' => $employees,
            'q' => $search,
        ]);
    }

    private function client(): Client
    {
        return Client::query()->findOrFail((int) $this->tenantContext->clientId());
    }
}
