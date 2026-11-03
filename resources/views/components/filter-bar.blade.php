@props(['action', 'method' => 'GET'])

{{-- Barra de filtros de un listado: búsqueda, selectores y el botón Filtrar, en una fila que se parte en pantallas chicas. --}}
<form method="{{ $method }}" action="{{ $action }}" {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-3.5']) }} role="search">
    {{ $slot }}
</form>
