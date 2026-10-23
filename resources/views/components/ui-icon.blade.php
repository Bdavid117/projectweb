@props(['name'])

@if(in_array($name, config('animated-icons.names'), true))
    <span {{ $attributes->merge(['class' => 'inline-flex shrink-0 [&>*]:h-full [&>*]:w-full']) }} aria-hidden="true"><{{ 'animated-lucide-' . $name }} size="100%" data-animated-icon></{{ 'animated-lucide-' . $name }}></span>
@else
    <x-dynamic-component :component="'lucide-' . $name" {{ $attributes }} />
@endif
