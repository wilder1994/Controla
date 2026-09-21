<x-client-layout title="Resumen" :wide="true">
    @include('modules.ops.sig-board', ['sigBoard' => $sigBoard, 'compact' => false, 'sigLiveUrl' => route('client.sig.live')])
</x-client-layout>
