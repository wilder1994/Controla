<?php

declare(strict_types=1);

namespace Tests\Feature\Company;

use App\Enums\DocumentFolder;
use App\Enums\LaborHistoryDocumentType;
use App\Models\Client;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\EmployeeDocumentBatch;
use App\Models\SupervisorPost;
use App\Models\User;
use App\Support\Files\SimplePdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

final class PersonnelDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_admin_sees_documentos_listing(): void
    {
        $this->seedWithPilot();
        $admin = $this->companyAdmin();
        $employee = $this->pilotVigilante();

        $this->actingAs($admin)
            ->get(route('company.personnel-documents.index'))
            ->assertOk()
            ->assertSee('Carpetas de empleados')
            ->assertSee($employee->document_number)
            ->assertSee('Ver carpeta');
    }

    public function test_company_admin_can_index_non_contiguous_pages(): void
    {
        $this->seedWithPilot();
        $admin = $this->companyAdmin();
        $employee = $this->pilotVigilante();
        $type = LaborHistoryDocumentType::FotocopiaCedula;
        $batch = $this->uploadLote($admin, $employee, ['Pagina uno', 'Pagina dos', 'Pagina tres'], 'hv-lote.pdf');

        $this->actingAs($admin)
            ->get(route('company.personnel-documents.batch.index', [$employee, $batch]))
            ->assertOk()
            ->assertSee('Indexar lote')
            ->assertSee('Historia Laboral');

        $this->actingAs($admin)
            ->get(route('company.personnel-documents.batch.preview', [$employee, $batch]))
            ->assertOk();

        $this->actingAs($admin)
            ->post(route('company.personnel-documents.batch.store', [$employee, $batch]), [
                'slices' => [[
                    'folder' => DocumentFolder::HojaVida->value,
                    'document_type' => $type->value,
                    'display_name' => $type->suggestedName($employee),
                    'pages' => [1, 3],
                ]],
            ])
            ->assertRedirect(route('company.personnel-documents.folder', $employee));

        $document = EmployeeDocument::query()
            ->where('employee_id', $employee->id)
            ->where('document_type', $type->value)
            ->firstOrFail();

        $this->assertSame([1, 3], $document->pages);
        $this->assertTrue(is_file(storage_path('app/'.$document->disk_path)));

        $this->actingAs($admin)
            ->get(route('company.personnel-documents.folder', $employee))
            ->assertOk()
            ->assertSee('1 de 26');
    }

    public function test_supervision_only_client_cannot_enable_personnel_folders(): void
    {
        $this->seedWithPilot();
        $admin = $this->companyAdmin();
        $client = Client::query()->where('slug', 'palmas-del-ingenio')->firstOrFail();
        $client->update(['has_access' => false, 'has_supervision' => true, 'show_personnel_folders' => false]);

        $this->actingAs($admin)
            ->put(route('company.clients.update', $client), $this->clientPayload($client, [
                'has_access' => '0',
                'has_supervision' => '1',
                'show_personnel_folders' => '1',
            ]));

        $this->assertFalse($client->fresh()->show_personnel_folders);
    }

    public function test_client_panel_only_lists_employees_assigned_to_its_posts(): void
    {
        $this->seedWithPilot();
        $palmas = Client::query()->where('slug', 'palmas-del-ingenio')->firstOrFail();
        $torres = Client::query()->where('slug', 'torres-loma')->firstOrFail();
        $palmas->update(['show_personnel_folders' => true]);
        $torres->update(['has_access' => true, 'show_personnel_folders' => true]);

        $assigned = $this->pilotVigilante();
        $other = $assigned->replicate();
        $other->document_number = '1099007788';
        $other->first_names = 'Otro';
        $other->last_name_paternal = 'Gómez';
        $other->email = 'otro.gomez@sj-seguridad.test';
        $other->save();

        $post = SupervisorPost::query()->where('client_id', $palmas->id)->firstOrFail();
        $post->employees()->sync([$assigned->id]);

        $clientAdmin = User::query()->where('email', 'admin@palmasdelingenio.test')->firstOrFail();

        $this->actingAs($clientAdmin)
            ->withSession(['tenancy.active_client_id' => $palmas->id])
            ->get(route('client.personnel-documents.index'))
            ->assertOk()
            ->assertSee($assigned->document_number)
            ->assertDontSee($other->document_number);

        $this->actingAs($clientAdmin)
            ->withSession(['tenancy.active_client_id' => $palmas->id])
            ->get(route('client.personnel-documents.folder', $other))
            ->assertNotFound();

        $this->actingAs($clientAdmin)
            ->withSession(['tenancy.active_client_id' => $palmas->id])
            ->get(route('client.personnel-documents.folder', $assigned))
            ->assertOk()
            ->assertSee('Historia Laboral')
            ->assertDontSee('Cargar documentos');
    }

    public function test_client_without_flag_cannot_open_personnel_documents(): void
    {
        $this->seedWithPilot();
        $client = Client::query()->where('slug', 'palmas-del-ingenio')->firstOrFail();
        $client->update(['show_personnel_folders' => false]);
        $admin = User::query()->where('email', 'admin@palmasdelingenio.test')->firstOrFail();

        $this->actingAs($admin)
            ->withSession(['tenancy.active_client_id' => $client->id])
            ->get(route('client.personnel-documents.index'))
            ->assertForbidden();
    }

    private function companyAdmin(): User
    {
        return User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();
    }

    private function pilotVigilante(): Employee
    {
        return Employee::query()->where('document_number', '1144001122')->firstOrFail();
    }

    /** @param list<string> $pages */
    private function uploadLote(User $user, Employee $employee, array $pages, string $filename): EmployeeDocumentBatch
    {
        $path = storage_path('framework/testing/'.$filename);
        SimplePdf::writePages($path, $pages);
        $file = new UploadedFile($path, $filename, 'application/pdf', null, true);

        $this->actingAs($user)
            ->post(route('company.personnel-documents.batch.create', $employee), ['file' => $file])
            ->assertRedirect();

        return EmployeeDocumentBatch::query()
            ->where('employee_id', $employee->id)
            ->latest('id')
            ->firstOrFail();
    }

    /** @param array<string, mixed> $overrides */
    private function clientPayload(Client $client, array $overrides = []): array
    {
        return array_merge([
            'party_type' => $client->party_type->value,
            'name' => $client->name,
            'legal_name' => $client->legal_name,
            'document_type' => $client->document_type,
            'tax_id' => $client->tax_id,
            'email' => $client->email,
            'phone' => $client->phone,
            'representative_name' => $client->representative_name,
            'representative_email' => $client->representative_email,
            'structure_type_id' => $client->structure_type_id,
            'address' => $client->address,
            'city' => $client->city,
            'department' => $client->department,
            'is_active' => $client->is_active ? '1' : '0',
            'has_access' => $client->has_access ? '1' : '0',
            'has_supervision' => $client->has_supervision ? '1' : '0',
            'show_personnel_folders' => $client->show_personnel_folders ? '1' : '0',
        ], $overrides);
    }
}
