@props(['items'])

@forelse($items as $item)
    <a href="{{ $item['url'] }}" class="flex items-start gap-3 border-b border-[var(--border-subtle)] px-4 py-3 last:border-b-0 hover:bg-[var(--surface-page)]">
        <x-dynamic-component :component="'lucide-' . $item['icon']" class="mt-[2px] h-4 w-4 text-[var(--color-primary-600)]" />
        <span class="flex min-w-0 flex-col gap-[2px]">
            <span class="text-sm text-[var(--text-primary)]">{{ $item['text'] }}</span>
            <span class="text-xs text-[var(--text-muted)]">{{ $item['meta'] }}</span>
        </span>
    </a>
@empty
    <p class="px-4 py-6 text-center text-sm text-[var(--text-muted)]">No tiene notificaciones pendientes.</p>
@endforelse
