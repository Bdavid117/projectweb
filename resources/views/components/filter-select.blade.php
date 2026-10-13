@props(['name', 'all', 'options', 'selected' => null])

<select name="{{ $name }}" aria-label="{{ $all }}" class="h-[var(--control-height)] rounded-[var(--radius-md)] border border-[var(--border-default)] bg-[var(--surface-card)] px-3 text-sm text-[var(--text-secondary)]">
    <option value="">{{ $all }}</option>
    @foreach($options as $value => $label)
        <option value="{{ $value }}" @selected((string) $selected === (string) $value)>{{ $label }}</option>
    @endforeach
</select>
