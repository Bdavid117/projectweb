@props(['class' => ''])

<div class="px-4 py-3 border-b border-[var(--border-subtle)] {{ $class }}">
    {{ $slot }}
</div>
