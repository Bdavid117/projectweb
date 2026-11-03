<x-layouts.error code="413" title="Los archivos son demasiado grandes">
    <p>Los soportes superan el límite de tamaño que acepta el servidor. Reduzca el peso o el número de archivos (máximo 5 MB por archivo y 5 archivos) e inténtelo de nuevo. Si el problema continúa, avise a quien administra el sistema.</p>

    <x-slot:actions>
        <x-button :href="url()->previous()" variant="primary">Volver</x-button>
    </x-slot:actions>
</x-layouts.error>
