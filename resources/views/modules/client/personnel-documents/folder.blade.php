<x-client-layout :title="'Carpeta · '.$employee->fullName()">
    @include('modules.personnel-documents.partials.folder-body', [
        'employee' => $employee,
        'indexedChecklists' => $indexedChecklists,
        'canUpload' => false,
        'cargar' => false,
        'indexRoute' => route('client.personnel-documents.index'),
        'previewRoute' => 'client.personnel-documents.preview',
        'downloadRoute' => 'client.personnel-documents.download',
        'destroyRoute' => 'company.personnel-documents.destroy',
        'batchCreateRoute' => route('client.personnel-documents.index'),
    ])
</x-client-layout>
