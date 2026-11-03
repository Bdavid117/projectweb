@props(['name' => 'q', 'value' => '', 'placeholder' => 'Buscar', 'label' => null])

<label class="relative flex h-[var(--control-height)] min-w-[220px] flex-1 items-center gap-2.5 rounded-[var(--radius-md)] border-[1.5px] border-[var(--trz-borde-control)] bg-[var(--trz-superficie)] px-3.5 hover:border-[var(--trz-tinta)] focus-within:border-[var(--trz-hilo)] has-[input:focus-visible]:outline-2 has-[input:focus-visible]:outline-offset-2 has-[input:focus-visible]:outline-[color:var(--trz-foco)]">
    <x-ui-icon name="search" class="h-[18px] w-[18px] shrink-0 text-[var(--trz-texto-2)]" />
    <span class="sr-only">{{ $label ?? $placeholder }}</span>
    <input type="text" name="{{ $name }}" value="{{ $value }}" placeholder="{{ $placeholder }}" class="w-full min-w-0 flex-1 bg-transparent text-base text-[var(--trz-tinta)] outline-none placeholder:text-[var(--trz-texto-3)] md:text-[15px]" />
</label>
