@props(['title', 'class'])

<div class="rounded-lg border border-[var(--border-subtle)] bg-[var(--surface-card)] shadow-sm">

    @if(!empty($title))
    <div class="px-4 py-3 border-b border-[var(--border-subtle)]">
        <h3 class="font-medium text-[var(--text-primary)] {{ $titleClass: 'text-lg' }}">{{ $title }}</h3>
    </div>
    @endif

    <div class="px-4 py-5">
        @yield('content')
    </div>

</div>