<x-layouts.error code="403" title="No tiene acceso a esta información">
    <p>Su cuenta no tiene permiso para ver esta página o este recurso. Si cree que debería tenerlo, comuníquese con la coordinación del programa.</p>

    @if(auth()->user()?->role === \App\Enums\Role::Profesor)
        <div class="rounded-[var(--radius-lg)] bg-[var(--trz-hilo-fondo)] p-4 text-[15px] text-[var(--trz-tinta)]">
            <p class="font-medium">¿Buscaba la ficha de un estudiante?</p>
            <p class="mt-1">Solo puede verla si el estudiante aceptó acompañarlo. Pídale que acepte su solicitud; usted puede enviarla desde
                <a href="{{ route('tutor-requests.index') }}" class="font-semibold underline">Solicitar tutoría</a>.</p>
        </div>
    @endif
</x-layouts.error>
