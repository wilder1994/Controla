<?php

declare(strict_types=1);

namespace App\Services\Company;

use App\Models\Employee;
use App\Models\SupervisorPost;

final class LookupEmployeeForPostService
{
    /**
     * @return list<array{id: int, name: string, document: string, job: string, assigned: ?array{post_id: int, post: string, client: string, url: string}}>
     */
    public function search(int $companyId, string $query, ?int $exceptPostId = null): array
    {
        $query = trim($query);
        if (mb_strlen($query) < 2) {
            return [];
        }

        $like = '%'.$query.'%';

        return Employee::query()
            ->with(['jobTitle', 'supervisorPosts.client'])
            ->where('security_company_id', $companyId)
            ->where('is_active', true)
            ->where(function ($q) use ($like) {
                $q->where('document_number', 'like', $like)
                    ->orWhere('first_names', 'like', $like)
                    ->orWhere('last_name_paternal', 'like', $like)
                    ->orWhere('last_name_maternal', 'like', $like);
            })
            ->orderBy('last_name_paternal')
            ->orderBy('first_names')
            ->limit(12)
            ->get()
            ->map(fn (Employee $employee) => $this->payload($employee, $exceptPostId))
            ->all();
    }

    /**
     * @return array{id: int, name: string, document: string, job: string, assigned: ?array{post_id: int, post: string, client: string, url: string}}
     */
    public function payload(Employee $employee, ?int $exceptPostId = null): array
    {
        $post = $employee->supervisorPosts->first();
        $assigned = null;
        if ($post instanceof SupervisorPost && ($exceptPostId === null || (int) $post->id !== $exceptPostId)) {
            $assigned = [
                'post_id' => (int) $post->id,
                'post' => $post->name,
                'client' => $post->client?->name ?? '',
                'url' => route('company.employees.show', $employee),
            ];
        }

        return [
            'id' => (int) $employee->id,
            'name' => $employee->fullName(),
            'document' => trim($employee->document_type.' '.$employee->document_number),
            'job' => $employee->jobTitle?->name ?: 'empleado',
            'assigned' => $assigned,
        ];
    }
}
