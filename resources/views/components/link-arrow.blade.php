@props(['href', 'icon' => 'arrow-right'])

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'inline-flex w-fit items-center gap-1.5 max-md:-my-2.5 max-md:min-h-11 text-[15px] font-semibold text-[var(--trz-hilo-texto)] hover:underline']) }}>
    {{ $slot }}
    <x-ui-icon :name="$icon" class="h-4 w-4" />
</a>
