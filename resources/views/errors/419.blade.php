<x-layouts.error code="419" title="La sesión expiró" icon="history" tone="warning">
    <p>Por seguridad, su sesión caducó tras un rato de inactividad. Recargue la página para continuar o vuelva a iniciar sesión; si estaba llenando un formulario, tendrá que enviarlo de nuevo.</p>

    <x-slot:actions>
        <x-button :href="url()->previous()" variant="primary">Recargar la página</x-button>
        <x-button :href="route('login.show')" variant="secondary">Iniciar sesión</x-button>
    </x-slot:actions>
</x-layouts.error>
