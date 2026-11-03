@props(['href'])

{{-- Enlace «volver» del inicio de una pantalla. En móvil el área táctil mide 44 px sin mover el diseño: el margen negativo
     compensa el alto añadido. --}}
<a href="{{ $href }}" {{ $attributes->merge(['class' => 'inline-flex w-fit items-center gap-[7px] text-[15px] font-semibold text-[var(--trz-hilo-texto)] hover:underline max-md:-my-2.5 max-md:min-h-11']) }}>
    <x-ui-icon name="arrow-left" class="h-[17px] w-[17px]" />
    {{ $slot }}
</a>
