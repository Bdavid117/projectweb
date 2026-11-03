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
    'autocomplete' => null,
])

{{-- Campo de formulario. El control llena todo el recuadro (con 44 px de alto el toque cae siempre dentro del campo, no en un
     borde muerto) y los iconos van encima, sin recibir toques. En móvil la letra es de 16 px: con menos, iOS hace zoom al
     enfocar. El aro de foco es del recuadro y solo aparece con el control (no con el botón del ojo, que trae el suyo). --}}
@php
    $isTextarea = $type === 'textarea';
    $isSelect = $type === 'select';
    $current = $type === 'password' ? null : old($name, $value);
    $error = isset($errors) ? $errors->first($name) : null;
    $border = $error ? 'border-[var(--trz-alerta)]' : 'border-[var(--trz-borde-control)] hover:border-[var(--trz-tinta)] focus-within:border-[var(--trz-hilo)]';
    $surface = $readonly ? 'bg-[var(--trz-papel)]' : 'bg-[var(--trz-superficie)]';
    $ring = match (true) {
        $isTextarea => 'has-[textarea:focus-visible]:outline-2 has-[textarea:focus-visible]:outline-offset-2 has-[textarea:focus-visible]:outline-[color:var(--trz-foco)]',
        $isSelect => 'has-[select:focus-visible]:outline-2 has-[select:focus-visible]:outline-offset-2 has-[select:focus-visible]:outline-[color:var(--trz-foco)]',
        default => 'has-[input:focus-visible]:outline-2 has-[input:focus-visible]:outline-offset-2 has-[input:focus-visible]:outline-[color:var(--trz-foco)]',
    };
    $frame = "relative flex w-full rounded-[var(--radius-md)] border-[1.5px] {$border} {$surface} {$ring}";
    $wrapperClasses = $isTextarea ? "{$frame} items-start" : "{$frame} h-[var(--control-height)] items-center";
    $described = trim(($error ? $name.'-error ' : '').($help ? $name.'-help' : ''));
    $control = 'w-full min-w-0 flex-1 bg-transparent text-base text-[var(--trz-tinta)] outline-none placeholder:text-[var(--trz-texto-3)] md:text-[15px]';
    $hasIcon = $icon && $type !== 'password' && ! $isSelect;
    $padding = $hasIcon ? 'pl-3.5 pr-11' : 'px-3.5';
    // Fecha: el navegador pinta su propio calendario junto al del diseño. Se deja el suyo invisible, sobre el icono y activo.
    $date = $type === 'date' ? 'relative [&::-webkit-calendar-picker-indicator]:absolute [&::-webkit-calendar-picker-indicator]:right-0 [&::-webkit-calendar-picker-indicator]:top-0 [&::-webkit-calendar-picker-indicator]:h-full [&::-webkit-calendar-picker-indicator]:w-11 [&::-webkit-calendar-picker-indicator]:cursor-pointer [&::-webkit-calendar-picker-indicator]:opacity-0' : '';
@endphp

<div class="flex w-full flex-col gap-1.5" @if($type === 'password') x-data="{ show: false }" @endif>
    <label for="{{ $name }}" class="text-sm font-semibold text-[var(--trz-tinta)]">{{ $label }}</label>
    <div class="{{ $wrapperClasses }}">
        @if($isSelect)
            <select id="{{ $name }}" name="{{ $name }}" @if($error) aria-invalid="true" @endif @if($described) aria-describedby="{{ $described }}" @endif class="{{ $control }} h-full appearance-none pl-3.5 pr-10">
                {{ $slot }}
            </select>
            <x-ui-icon name="chevron-down" class="pointer-events-none absolute right-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-[var(--trz-texto-2)]" />
        @elseif($isTextarea)
            <textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $rows }}" placeholder="{{ $placeholder }}" @if($readonly) readonly @endif @if($error) aria-invalid="true" @endif @if($described) aria-describedby="{{ $described }}" @endif class="{{ $control }} min-h-[80px] resize-none p-3 leading-relaxed">{{ $current }}</textarea>
        @elseif($type === 'password')
            <input id="{{ $name }}" name="{{ $name }}" type="password" x-bind:type="show ? 'text' : 'password'" placeholder="{{ $placeholder }}" @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif @if($error) aria-invalid="true" @endif @if($described) aria-describedby="{{ $described }}" @endif class="{{ $control }} h-full pl-3.5 pr-12" />
            <button type="button" class="absolute right-0.5 top-1/2 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-[var(--radius-sm)] text-[var(--trz-texto-2)] hover:bg-[var(--trz-hilo-fondo)] focus-visible:outline-offset-0" @click="show = !show" x-bind:aria-label="show ? 'Ocultar contraseña' : 'Mostrar contraseña'" x-bind:aria-pressed="show">
                <x-ui-icon name="eye" class="h-[18px] w-[18px]" x-show="show" x-cloak />
                <x-ui-icon name="eye-off" class="h-[18px] w-[18px]" x-show="!show" />
            </button>
        @else
            <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" placeholder="{{ $placeholder }}" value="{{ $current }}" @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif @if($readonly) readonly @endif @if($error) aria-invalid="true" @endif @if($described) aria-describedby="{{ $described }}" @endif class="{{ $control }} h-full {{ $padding }} {{ $date }}" />
        @endif
        @if($hasIcon)
            <x-ui-icon :name="$icon" class="pointer-events-none absolute right-3.5 top-1/2 h-[17px] w-[17px] -translate-y-1/2 text-[var(--trz-texto-2)]" />
        @endif
    </div>
    @if($help)
        <p id="{{ $name }}-help" class="text-[13px] text-[var(--trz-texto-2)]">{{ $help }}</p>
    @endif
    @if($error)
        <p id="{{ $name }}-error" class="flex items-center gap-1.5 text-[13px] font-semibold text-[var(--trz-alerta)]">
            <x-ui-icon name="circle-alert" class="h-3.5 w-3.5 shrink-0" />
            {{ $error }}
        </p>
    @endif
</div>
