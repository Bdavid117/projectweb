{{-- La frase con cifras abre la pantalla en lugar de una fila de tarjetas: dice lo importante en una oración. --}}
<p {{ $attributes->merge(['class' => 'max-w-[44ch] font-display text-[26px] font-semibold leading-[1.2] text-[var(--trz-tinta)] sm:text-[34px] lg:max-w-[52ch]']) }}>{{ $slot }}</p>
