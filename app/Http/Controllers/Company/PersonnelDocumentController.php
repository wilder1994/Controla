<?php

declare(strict_types=1);

namespace App\Http\Controllers\Company;

use App\Enums\DocumentFolder;
use App\Enums\OtherDocumentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Personnel\IndexLaborHistoryRequest;
use App\Http\Requests\Personnel\MarkLaborHistoryNaRequest;
use App\Http\Requests\Personnel\StoreLaborHistoryBatchRequest;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\EmployeeDocumentBatch;
use App\Repositories\EmployeeRepository;
use App\Services\Personnel\DeleteEmployeeDocumentService;
use App\Services\Personnel\IndexLaborHistoryPdfService;
use App\Services\Personnel\MarkLaborHistoryNotApplicableService;
use App\Services\Personnel\StoreLaborHistoryBatchService;
use App\Support\Files\StoredFileResponder;
use App\Support\Personnel\FolderChecklist;
use App\Support\Personnel\IndexedFolder;
use App\Support\Personnel\OtherSupportNamer;
use App\Support\Platform\ActingCompanyResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PersonnelDocumentController extends Controller
{
    public function __construct(
        private readonly EmployeeRepository $employees,
        private readonly StoreLaborHistoryBatchService $historyBatch,
        private readonly IndexLaborHistoryPdfService $historyIndex,
        private readonly MarkLaborHistoryNotApplicableService $historyNa,
        private readonly DeleteEmployeeDocumentService $deleteDocument,
    ) {}

    public function index(Request $request): View
    {
        $companyId = $this->companyId($request);
        $q = $request->string('q')->toString();

        return view('modules.company.personnel-documents.index', [
            'employees' => $this->employees->paginateForPersonnelDocuments(
                $companyId,
                $q !== '' ? $q : null,
            ),
            'folderTotal' => count(DocumentFolder::cases()),
            'q' => $q,
            'canUpload' => true,
        ]);
    }

    public function folder(Request $request, Employee $employee): View
    {
        $this->assertCompany($request, $employee);
        $employee->load('documents');

        $indexedChecklists = [];
        foreach (DocumentFolder::cases() as $folder) {
            $indexedChecklists[] = [
                'folder' => $folder,
                'rows' => FolderChecklist::for($employee, $folder),
                'summary' => FolderChecklist::summary($employee, $folder),
                'panel' => $folder->value.'-list',
                'na' => $folder->naRoute(),
            ];
        }

        return view('modules.company.personnel-documents.folder', [
            'employee' => $employee,
            'canUpload' => true,
            'cargar' => $request->boolean('cargar'),
            'indexedChecklists' => $indexedChecklists,
        ]);
    }

    public function storeBatch(StoreLaborHistoryBatchRequest $request, Employee $employee): RedirectResponse
    {
        $this->assertCompany($request, $employee);
        $file = $request->file('file');
        abort_if($file === null, 422);

        $batch = $this->historyBatch->execute($employee, $file);

        return redirect()->route('company.personnel-documents.batch.index', [
            'employee' => $employee,
            'batch' => $batch,
        ]);
    }

    public function batchIndex(Request $request, Employee $employee, EmployeeDocumentBatch $batch): View
    {
        $this->assertCompany($request, $employee);
        $lote = $this->batchForEmployee($employee, $batch);
        $employee->load('documents');

        $preview = route('company.personnel-documents.batch.preview', ['employee' => $employee, 'batch' => $lote]);
        $store = route('company.personnel-documents.batch.store', ['employee' => $employee, 'batch' => $lote]);

        return view('modules.company.personnel-documents.index-batch', [
            'employee' => $employee,
            'batch' => $lote,
            'storeUrl' => $store,
            'previewUrl' => $preview,
            'historyMeta' => [
                'page_count' => $lote->page_count,
                'preview_url' => $preview,
                'other_type' => OtherDocumentType::Otro->value,
                'name_suffix' => OtherSupportNamer::suffix($employee),
                'reserved' => OtherSupportNamer::reserved($employee),
                'existing_count' => OtherSupportNamer::loadedCount($employee),
                'max_others' => OtherDocumentType::MAX,
                'catalogs' => collect(DocumentFolder::cases())->map(fn (DocumentFolder $folder) => [
                    'value' => $folder->value,
                    'label' => $folder->label(),
                    'course_fields' => $folder === DocumentFolder::Cursos,
                    'other_fields' => $folder === DocumentFolder::Otros,
                    'types' => collect(IndexedFolder::types($folder))->map(fn ($type) => [
                        'value' => $type->value,
                        'label' => $type->label(),
                        'name' => $type->suggestedName($employee),
                        'req' => $type->requirement()->label(),
                        'other' => method_exists($type, 'isRepeatable') && $type->isRepeatable(),
                    ])->values(),
                ])->values(),
            ],
        ]);
    }

    public function storeBatchIndex(IndexLaborHistoryRequest $request, Employee $employee, EmployeeDocumentBatch $batch): RedirectResponse
    {
        $this->assertCompany($request, $employee);
        $lote = $this->batchForEmployee($employee, $batch);

        $slices = [];
        foreach ($request->validated('slices') as $row) {
            $pages = [];
            foreach ($row['pages'] as $page) {
                $page = (int) $page;
                abort_if($page > $lote->page_count, 422);
                if (! in_array($page, $pages, true)) {
                    $pages[] = $page;
                }
            }

            $folder = DocumentFolder::from($row['folder']);
            $slices[] = [
                'folder' => $folder,
                'type' => IndexedFolder::resolve($folder, $row['document_type']),
                'display_name' => $row['display_name'],
                'pages' => $pages,
                'taken_on' => $row['taken_on'] ?? null,
                'provider' => $row['provider'] ?? null,
                'tipo' => $row['tipo'] ?? null,
            ];
        }

        $this->historyIndex->execute($lote, $employee, $slices);

        return redirect()
            ->route('company.personnel-documents.folder', $employee)
            ->with('success', 'Lote indexado: '.count($slices).' documento(s).');
    }

    public function previewBatch(Request $request, Employee $employee, EmployeeDocumentBatch $batch): StreamedResponse
    {
        $this->assertCompany($request, $employee);
        $lote = $this->batchForEmployee($employee, $batch);

        return StoredFileResponder::stream($lote->disk_path, $lote->original_name, (string) $lote->mime, true);
    }

    public function markNa(MarkLaborHistoryNaRequest $request, Employee $employee, string $folder): RedirectResponse
    {
        $this->assertCompany($request, $employee);
        $resolved = DocumentFolder::tryFrom($folder);
        abort_if($resolved === null || $resolved === DocumentFolder::Otros, 404);

        $type = IndexedFolder::resolve($resolved, $request->string('document_type')->toString());
        $this->historyNa->execute($employee, $resolved, $type);

        return redirect()
            ->route('company.personnel-documents.folder', $employee)
            ->with('success', $type->label().' marcado como no aplica.');
    }

    public function preview(Request $request, EmployeeDocument $document): StreamedResponse
    {
        $file = $this->locate($request, $document);
        abort_unless($file->hasFile(), 404);

        return StoredFileResponder::stream($file->disk_path, $file->label(), (string) $file->mime, true);
    }

    public function download(Request $request, EmployeeDocument $document): StreamedResponse
    {
        $file = $this->locate($request, $document);
        abort_unless($file->hasFile(), 404);

        return StoredFileResponder::stream($file->disk_path, $file->label(), (string) $file->mime, false);
    }

    public function destroy(Request $request, EmployeeDocument $document): RedirectResponse
    {
        $file = $this->locate($request, $document);

        try {
            $this->deleteDocument->execute($file);
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['document' => $exception->getMessage()]);
        }

        return back()->with('success', 'Documento eliminado. Puede volver a indexarlo.');
    }

    private function companyId(Request $request): int
    {
        return app(ActingCompanyResolver::class)->requireId($request->user());
    }

    private function assertCompany(Request $request, Employee $employee): void
    {
        abort_unless((int) $employee->security_company_id === $this->companyId($request), 404);
    }

    private function batchForEmployee(Employee $employee, EmployeeDocumentBatch $batch): EmployeeDocumentBatch
    {
        abort_unless((int) $batch->employee_id === (int) $employee->id, 404);

        return $batch;
    }

    private function locate(Request $request, EmployeeDocument $document): EmployeeDocument
    {
        abort_unless((int) $document->security_company_id === $this->companyId($request), 404);

        return $document;
    }
}
