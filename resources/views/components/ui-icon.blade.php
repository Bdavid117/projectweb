@props(['name', 'size' => null])

@php
    $animated = config('animated-icons.names');
    $target = config('animated-icons.aliases')[$name] ?? $name;
    $isAnimated = in_array($target, $animated, true);
@endphp

@if($isAnimated)
    <span {{ $attributes->merge(['class' => 'inline-flex shrink-0']) }} aria-hidden="true"><animate-icons name="{{ $target }}" library="lucide" is-animated="false"></animate-icons></span>
@else
    <x-dynamic-component :component="'lucide-' . $name" {{ $attributes }} />
@endif
