@php
    // throttle:5,1 → 5 intentos por minuto; el middleware agrega Retry-After (segundos que faltan).
    $retry = null;
    if (isset($exception) && method_exists($exception, 'getHeaders')) {
        $retry = $exception->getHeaders()['Retry-After'] ?? null;
    }
    $retry = is_numeric($retry) ? (int) $retry : null;
@endphp

<x-layouts.error code="429" title="Demasiados intentos">
    <p>Hizo demasiados intentos seguidos. Para proteger las cuentas, el sistema los bloquea por un momento.</p>

    @if($retry)
        <p>Espere <span class="font-semibold text-[var(--trz-tinta)]">{{ $retry }} {{ $retry === 1 ? 'segundo' : 'segundos' }}</span> antes de volver a intentarlo.</p>
    @else
        <p>Espere un minuto antes de volver a intentarlo.</p>
    @endif

    <x-slot:actions>
        <x-button :href="route('login.show')" variant="primary">Volver al inicio de sesión</x-button>
    </x-slot:actions>
</x-layouts.error>
