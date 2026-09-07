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

    /**
     * @return list<array{id: int, document_number: string, name: string, email: ?string, job_title: ?string}>
     */
    public function searchWithoutUser(int $companyId, string $query, int $limit = 15): array
    {
        $term = trim($query);
        if (mb_strlen($term) < 2) {
            return [];
        }

        $like = '%'.$term.'%';

        return Employee::query()
            ->with('jobTitle')
            ->where('security_company_id', $companyId)
            ->where('is_active', true)
            ->whereNull('ceased_at')
            ->whereDoesntHave('user')
            ->where(function ($q) use ($like, $term): void {
                $q->where('document_number', 'like', $term.'%')
                    ->orWhere('document_number', 'like', $like)
                    ->orWhere('first_names', 'like', $like)
                    ->orWhere('last_name_paternal', 'like', $like)
                    ->orWhere('last_name_maternal', 'like', $like);
            })
            ->orderBy('document_number')
            ->limit($limit)
            ->get()
            ->map(fn (Employee $employee) => [
                'id' => $employee->id,
                'document_number' => $employee->document_number,
                'name' => $employee->fullName(),
                'email' => $employee->email,
                'job_title' => $employee->jobTitle?->name,
            ])
            ->values()
            ->all();
    }
}
