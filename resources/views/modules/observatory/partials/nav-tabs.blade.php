@php
    $indexRoute = $indexRoute ?? 'client.observatory.events.index';
    $vista = $vista ?? 'tablero';
    $tabQuery = request()->except('vista', 'page');
@endphp
<a href="{{ route($indexRoute, $tabQuery + ['vista' => 'tablero']) }}"
   @class(['admin-header-tab', 'is-active' => $vista === 'tablero'])>Tablero</a>
<a href="{{ route($indexRoute, $tabQuery + ['vista' => 'eventos']) }}"
   @class(['admin-header-tab', 'is-active' => $vista === 'eventos'])>Eventos</a>
