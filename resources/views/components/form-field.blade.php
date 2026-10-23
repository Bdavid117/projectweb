@props([
    'label',
    'name',
    'type' => 'text',
    'placeholder' => '',
    'icon' => null,
    'help' => null,
    'value' => null,
    'rows' => 4,
    'readonly' => false,
])

@php
    $isTextarea = $type === 'textarea';
    $current = $type === 'password' ? null : old($name, $value);
    $error = isset($errors) ? $errors->first($name) : null;
    $border = $error ? 'border-[var(--color-danger)]' : 'border-[var(--border-default)]';
    $wrapperClasses = $isTextarea
        ? "flex w-full items-start gap-2 rounded-[var(--radius-md)] border {$border} bg-[var(--surface-card)] p-3 focus-within:border-[var(--focus-ring)]"
        : "flex h-[var(--control-height)] w-full items-center gap-2 rounded-[var(--radius-md)] border {$border} bg-[var(--surface-card)] px-3 focus-within:border-[var(--focus-ring)]";
@endphp

<div class="flex w-full flex-col gap-[6px]" @if($type === 'password') x-data="{ show: false }" @endif>
    <label for="{{ $name }}" class="text-xs font-semibold text-[var(--text-secondary)]">{{ $label }}</label>
    <div class="{{ $wrapperClasses }}">
        @if($type === 'select')
            <select id="{{ $name }}" name="{{ $name }}" class="w-full flex-1 appearance-none bg-transparent text-sm text-[var(--text-primary)] outline-none">
                {{ $slot }}
            </select>
        @elseif($isTextarea)
            <textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $rows }}" placeholder="{{ $placeholder }}" @if($readonly) readonly @endif class="min-h-[80px] w-full flex-1 resize-none bg-transparent text-sm text-[var(--text-primary)] outline-none placeholder:text-[var(--text-muted)]">{{ $current }}</textarea>
        @elseif($type === 'password')
            <input id="{{ $name }}" name="{{ $name }}" type="password" x-bind:type="show ? 'text' : 'password'" placeholder="{{ $placeholder }}" class="w-full flex-1 bg-transparent text-sm text-[var(--text-primary)] outline-none placeholder:text-[var(--text-muted)]" />
            <button type="button" class="-mr-1 flex h-7 w-7 shrink-0 items-center justify-center rounded-[var(--radius-sm)] text-[var(--color-neutral-500)] hover:bg-[var(--surface-sunken)]" @click="show = !show" x-bind:aria-label="show ? 'Ocultar contraseña' : 'Mostrar contraseña'" x-bind:aria-pressed="show">
                <x-lucide-eye class="h-4 w-4" x-show="show" x-cloak />
                <x-lucide-eye-off class="h-4 w-4" x-show="!show" />
            </button>
        @else
            <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" placeholder="{{ $placeholder }}" value="{{ $current }}" @if($readonly) readonly @endif class="w-full flex-1 bg-transparent text-sm text-[var(--text-primary)] outline-none placeholder:text-[var(--text-muted)]" />
        @endif
        @if($icon && $type !== 'password')
            <x-dynamic-component :component="'lucide-' . $icon" class="h-4 w-4 shrink-0 text-[var(--color-neutral-400)]" />
        @endif
    </div>
    @if($error)
        <p class="text-[11px] font-medium text-[var(--color-danger)]">{{ $error }}</p>
    @endif
    @if($help)
        <p class="text-[11px] text-[var(--text-muted)]">{{ $help }}</p>
    @endif
</div>
