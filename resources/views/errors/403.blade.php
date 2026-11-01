<x-layouts.error code="403" title="No tiene acceso a esta información" tone="danger">
    <p>Su cuenta no tiene permiso para ver esta página o este recurso. Si cree que debería tenerlo, comuníquese con la coordinación del programa.</p>

    @if(auth()->user()?->role === \App\Enums\Role::Profesor)
        <div class="rounded-[var(--radius-md)] bg-[var(--color-info-bg)] p-3 text-[var(--color-info)]">
            <p class="font-medium">¿Buscaba la ficha de un estudiante?</p>
            <p class="mt-1">Solo puede verla si el estudiante aceptó acompañarlo. Pídale que acepte su solicitud; usted puede enviarla desde
                <a href="{{ route('tutor-requests.index') }}" class="font-semibold underline">Solicitar tutoría</a>.</p>
        </div>
    @endif
</x-layouts.error>
