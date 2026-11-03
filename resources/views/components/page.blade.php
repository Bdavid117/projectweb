{{-- Contenedor de página: ancho máximo, márgenes responsivos (18 / 32 / 64 px) y ritmo vertical entre secciones. --}}
<div {{ $attributes->merge(['class' => 'mx-auto flex w-full max-w-[var(--content-max-width)] flex-col gap-9 px-[18px] pb-16 pt-8 sm:px-8 sm:pt-12 xl:px-16']) }}>
    {{ $slot }}
</div>
