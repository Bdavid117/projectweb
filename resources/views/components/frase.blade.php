{{-- La frase con cifras abre la pantalla en lugar de una fila de tarjetas: dice lo importante en una oración. --}}
<p {{ $attributes->merge(['class' => 'max-w-[44ch] font-display text-[22px] font-semibold leading-[1.3] text-[var(--trz-tinta)] sm:text-[30px] sm:leading-[1.2] lg:max-w-[52ch] lg:text-[34px]']) }}>{{ $slot }}</p>
