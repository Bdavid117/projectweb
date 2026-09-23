@props(['text', 'tone' => 'success'])

@php
    $tones = [
        'success' => 'bg-[var(--color-success-bg)] text-[var(--color-success)]',
        'warning' => 'bg-[var(--color-warning-bg)] text-[var(--color-warning)]',
        'danger' => 'bg-[var(--color-danger-bg)] text-[var(--color-danger)]',
        'info' => 'bg-[var(--color-info-bg)] text-[var(--color-info)]',
        'primary' => 'bg-[var(--color-primary-50)] text-[var(--color-primary-600)]',
        'accent' => 'bg-[var(--color-accent-100)] text-[var(--color-accent-600)]',
        'restricted' => 'bg-[var(--color-restricted-bg)] text-[var(--color-restricted)]',
    ];
@endphp
<span class="inline-flex items-center rounded-full px-[11px] py-[5px] text-xs font-semibold {{ $tones[$tone] }}">
    {{ $text }}
</span>
