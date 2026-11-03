@props(['name', 'all', 'options', 'selected' => null])

<div class="relative">
    <select name="{{ $name }}" aria-label="{{ $all }}" class="h-[var(--control-height)] w-full appearance-none rounded-[var(--radius-md)] border-[1.5px] border-[var(--trz-borde-control)] bg-[var(--trz-superficie)] pl-3.5 pr-10 text-base text-[var(--trz-tinta)] md:text-[15px] hover:border-[var(--trz-tinta)]">
        <option value="">{{ $all }}</option>
        @foreach($options as $value => $label)
            <option value="{{ $value }}" @selected((string) $selected === (string) $value)>{{ $label }}</option>
        @endforeach
    </select>
    <x-ui-icon name="chevron-down" class="pointer-events-none absolute right-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-[var(--trz-texto-2)]" />
</div>
