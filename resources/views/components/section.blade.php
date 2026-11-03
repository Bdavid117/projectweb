@props(['title', 'note' => null, 'headingId' => null])

{{-- Sección de pantalla: título en la tipografía de títulos, nota breve y contenido; el slot `aside` va a la derecha del título. --}}
<section {{ $attributes->merge(['class' => 'flex min-w-0 flex-col gap-4']) }} @if($headingId) aria-labelledby="{{ $headingId }}" @endif>
    <div class="flex items-end justify-between gap-4">
        <div class="flex min-w-0 flex-col gap-1.5">
            <h2 @if($headingId) id="{{ $headingId }}" @endif class="text-[22px] font-semibold leading-tight text-[var(--trz-tinta)] sm:text-[26px]">{{ $title }}</h2>
            @if($note)
                <p class="text-[15px] leading-relaxed text-[var(--trz-texto-2)]">{{ $note }}</p>
            @endif
        </div>
        @isset($aside)
            <div class="shrink-0">{{ $aside }}</div>
        @endisset
    </div>
    {{ $slot }}
</section>
