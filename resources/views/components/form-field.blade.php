@props([
    'label',
    'name',
    'type' => 'text',
    'placeholder' => '',
    'icon' => null,
    'help' => null,
    'value' => null,
    'rows' => 4,
])

@php
    $isTextarea = $type === 'textarea';
    $wrapperClasses = $isTextarea
        ? 'flex w-full items-start gap-2 rounded-[var(--radius-md)] border border-[var(--border-default)] bg-[var(--surface-card)] p-3 focus-within:border-[var(--focus-ring)]'
        : 'flex h-[var(--control-height)] w-full items-center gap-2 rounded-[var(--radius-md)] border border-[var(--border-default)] bg-[var(--surface-card)] px-3 focus-within:border-[var(--focus-ring)]';
@endphp

<div class="flex w-full flex-col gap-[6px]">
    <label for="{{ $name }}" class="text-xs font-semibold text-[var(--text-secondary)]">{{ $label }}</label>
    <div class="{{ $wrapperClasses }}">
        @if($type === 'select')
            <select id="{{ $name }}" name="{{ $name }}" class="w-full flex-1 appearance-none bg-transparent text-sm text-[var(--text-primary)] outline-none">
                {{ $slot }}
            </select>
        @elseif($isTextarea)
            <textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $rows }}" placeholder="{{ $placeholder }}" class="min-h-[80px] w-full flex-1 resize-none bg-transparent text-sm text-[var(--text-primary)] outline-none placeholder:text-[var(--text-muted)]">{{ $value }}</textarea>
        @else
            <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" placeholder="{{ $placeholder }}" value="{{ $value }}" class="w-full flex-1 bg-transparent text-sm text-[var(--text-primary)] outline-none placeholder:text-[var(--text-muted)]" />
        @endif
        @if($icon)
            <x-dynamic-component :component="'lucide-' . $icon" class="h-4 w-4 shrink-0 text-[var(--color-neutral-400)]" />
        @endif
    </div>
    @if($help)
        <p class="text-[11px] text-[var(--text-muted)]">{{ $help }}</p>
    @endif
</div>
