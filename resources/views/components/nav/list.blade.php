@props(['groups', 'skipTabs' => false])

{{-- Lista vertical de navegación (cajón de tablet y hoja «Más» móvil). Con `skipTabs` omite lo que ya está en las pestañas. --}}
@foreach($groups as $group)
    @php
        $items = collect($group['items'])->reject(fn ($item) => $skipTabs && ($item['tab'] || ($group['tab'] ?? false)))->values();
    @endphp
    @continue($items->isEmpty())
    @if($group['label'])
        <p class="px-5 pb-1 pt-4 text-[13px] font-semibold text-[var(--trz-texto-3)]">{{ $group['label'] }}</p>
    @endif
    @foreach($items as $item)
        <a href="{{ $item['href'] }}" @if($item['active']) aria-current="page" @endif
            class="flex min-h-[52px] items-center gap-3.5 px-5 text-base {{ $item['active'] ? 'border-l-4 border-[var(--trz-hilo)] bg-[var(--trz-hilo-fondo)] pl-4 font-bold text-[var(--trz-tinta)]' : 'font-medium text-[var(--trz-texto-2)] hover:bg-[var(--trz-hilo-fondo)]' }}">
            <x-ui-icon :name="$item['icon']" class="h-5 w-5 {{ $item['active'] ? 'text-[var(--trz-hilo-texto)]' : 'text-[var(--trz-texto-3)]' }}" />
            <span>{{ $item['label'] }}</span>
        </a>
    @endforeach
@endforeach
