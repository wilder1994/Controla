<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Employee;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EmployeeRepository
{
    public function paginateForCompany(
        int $companyId,
        int $perPage = 15,
        ?string $search = null,
        string $status = 'active',
    ): LengthAwarePaginator {
        $query = Employee::query()
            ->with(['jobTitle', 'collaboratorType', 'user'])
            ->where('security_company_id', $companyId);

        if ($status === 'archived') {
            $query->where('is_active', false);
        } elseif ($status !== 'all') {
            $query->where('is_active', true);
        }

        if ($search !== null && $search !== '') {
            $term = '%'.$search.'%';
            $query->where(function ($q) use ($term): void {
                $q->where('first_names', 'like', $term)
                    ->orWhere('last_name_paternal', 'like', $term)
                    ->orWhere('last_name_maternal', 'like', $term)
                    ->orWhere('document_number', 'like', $term)
                    ->orWhere('email', 'like', $term);
            });
        }

        return $query
            ->orderBy('last_name_paternal')
            ->orderBy('first_names')
            ->paginate($perPage)
            ->withQueryString();
    }
}
