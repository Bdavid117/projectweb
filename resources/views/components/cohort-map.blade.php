@props(['rows', 'reading' => null, 'filters' => [], 'total' => null, 'initial' => 'todos'])

{{-- Mapa de avance, interactivo: una fila por año de ingreso y un punto por estudiante a la altura (horizontal) de su avance del plan.
     Cada punto es un enlace a la ficha y abre una tarjeta con sus datos. Se filtra por tipos combinables, se ubica a alguien por nombre o
     código, se acerca y se mueve, se compara con el periodo anterior, se selecciona con un recuadro y se exporta a CSV; la misma lista
     puede verse como tabla ordenable. Pulsar el año de una fila resume esa cohorte.
     Zoom y desplazamiento son tres variables CSS del marco (--k, --tx, --ty): cada punto, marca y burbuja se coloca con ellas. La posición
     de cada elemento es un dato por fila, por eso `style` solo lleva variables (--px, --r, --j, --e, --lo, --span). Sin JavaScript el mapa
     queda como una vista fija y los puntos siguen siendo enlaces. --}}
@php
    $keys = collect($filters)->pluck('key')->all();
    $active = $initial === 'todos' ? [] : array_values(array_intersect(explode(',', $initial), $keys));
    $rowCount = max(count($rows), 1);
    $rowH = max(46, (int) ceil(320 / $rowCount));
    $allDots = collect($rows)->flatMap(fn ($row, $r) => collect($row['dots'])->map(fn ($dot, $i) => $dot + ['row' => $r, 'shift' => (crc32($row['label'].$dot['label'].$i) % 17) - 8]));
    $total ??= $allDots->count();
    $matches = fn (array $tags) => collect($active)->every(fn ($kind) => in_array($kind, $tags, true));
    $shown = $allDots->filter(fn ($dot) => $matches($dot['tags'] ?? []))->count();
    $kinds = collect($filters)->pluck('label', 'key')->except('todos')->all();
    $chipOn = 'border-[var(--trz-hilo)] bg-[var(--trz-hilo-fondo)] text-[var(--trz-hilo-texto)]';
    $chipOff = 'border-[var(--trz-borde-control)] bg-[var(--trz-superficie)] text-[var(--trz-tinta)] hover:border-[var(--trz-tinta)]';
    $zoomBtn = 'inline-flex h-11 w-11 items-center justify-center rounded-[var(--radius-md)] border-[1.5px] border-[var(--trz-borde-control)] bg-[var(--trz-superficie)] text-[var(--trz-tinta)] hover:bg-[var(--trz-hilo-fondo)] disabled:cursor-not-allowed disabled:opacity-45';
    $tool = 'inline-flex min-h-11 items-center gap-2 rounded-[var(--radius-md)] border-[1.5px] px-3.5 text-[15px] font-semibold disabled:cursor-not-allowed disabled:opacity-45';
    $toolIdle = 'border-[var(--trz-borde-control)] bg-[var(--trz-superficie)] text-[var(--trz-tinta)] hover:bg-[var(--trz-hilo-fondo)]';
    $toolOn = 'border-[var(--trz-hilo)] bg-[var(--trz-hilo-fondo)] text-[var(--trz-hilo-texto)]';
    // En teléfono la barra de herramientas es solo de iconos de 44 px (cabe en una fila); el nombre queda para lectores de pantalla y reaparece desde sm.
    $toolIcon = $tool.' max-sm:w-11 max-sm:justify-center max-sm:px-0';
@endphp
<figure {{ $attributes->merge(['class' => 'cohort-map flex flex-col gap-3']) }} data-filter="{{ $active ? implode(',', $active) : 'todos' }}"
    x-data="cohortMap(@js(['tipos' => $active, 'shown' => $shown, 'total' => $total, 'rows' => $rowCount, 'labels' => collect($rows)->pluck('label')->all(), 'kinds' => $kinds, 'clusterOver' => 150]))">
    <a href="#fin-del-mapa" class="sr-only focus:not-sr-only focus:w-fit focus:rounded-[var(--radius-md)] focus:bg-[var(--trz-superficie)] focus:px-3 focus:py-2 focus:font-semibold focus:text-[var(--trz-hilo-texto)]">Saltar el mapa</a>

    @if(count($filters) > 1)
        {{-- Los tipos se combinan (todos a la vez): son botones que se pulsan o se sueltan. En el teléfono, una sola fila que se desliza. --}}
        <div role="group" aria-label="Tipo de estudiante" class="-mx-[18px] flex gap-2 overflow-x-auto px-[18px] pb-1 sm:mx-0 sm:flex-wrap sm:overflow-visible sm:px-0 sm:pb-0" x-cloak>
            @foreach($filters as $filter)
                @php $on = $filter['key'] === 'todos' ? $active === [] : in_array($filter['key'], $active, true); @endphp
                <button type="button" aria-pressed="{{ $on ? 'true' : 'false' }}" @click="toggleTipo('{{ $filter['key'] }}')"
                    :aria-pressed="isOn('{{ $filter['key'] }}') ? 'true' : 'false'"
                    :class="{ '{{ $chipOn }}': isOn('{{ $filter['key'] }}'), '{{ $chipOff }}': !isOn('{{ $filter['key'] }}') }"
                    class="inline-flex min-h-11 shrink-0 items-center gap-2 whitespace-nowrap rounded-full border-[1.5px] px-4 text-[15px] font-semibold sm:min-h-10 {{ $on ? $chipOn : $chipOff }}">
                    {{ $filter['label'] }}
                    <span class="text-sm font-medium tabular-nums opacity-80">{{ $filter['count'] }}</span>
                </button>
            @endforeach
        </div>
    @endif

    <div class="flex flex-wrap items-center gap-2" x-cloak>
        <div class="relative w-full sm:w-[300px]">
            <label class="flex h-11 items-center gap-2.5 rounded-[var(--radius-md)] border-[1.5px] border-[var(--trz-borde-control)] bg-[var(--trz-superficie)] px-3.5 focus-within:border-[var(--trz-hilo)] has-[input:focus-visible]:outline-2 has-[input:focus-visible]:outline-offset-2 has-[input:focus-visible]:outline-[color:var(--trz-foco)]">
                <x-ui-icon name="search" class="h-[18px] w-[18px] shrink-0 text-[var(--trz-texto-2)]" />
                <input type="text" role="combobox" aria-label="Ubicar a un estudiante" placeholder="Ubicar a un estudiante" autocomplete="off" aria-autocomplete="list" aria-controls="mapa-resultados"
                    :aria-expanded="open && results.length ? 'true' : 'false'" :aria-activedescendant="open && results.length ? 'mapa-opcion-' + cursor : null"
                    x-model="query" @input="search()" @focus="search()" @keydown="onSearchKey($event)" @blur="closeSoon()"
                    class="h-full w-full min-w-0 flex-1 bg-transparent text-base text-[var(--trz-tinta)] outline-none placeholder:text-[var(--trz-texto-3)] md:text-[15px]" />
            </label>
            <ul id="mapa-resultados" role="listbox" aria-label="Estudiantes que coinciden" x-show="open && results.length" x-cloak class="absolute left-0 right-0 top-full z-30 mt-1 overflow-hidden rounded-[var(--radius-lg)] border border-[var(--trz-linea)] bg-[var(--trz-superficie)] shadow-[var(--shadow-menu)]">
                <template x-for="(r, i) in results" :key="r.id">
                    <li role="option" :id="'mapa-opcion-' + i" :aria-selected="i === cursor ? 'true' : 'false'" @mousedown.prevent="locate(r)" @mouseenter="cursor = i"
                        :class="i === cursor ? 'bg-[var(--trz-hilo-fondo)]' : ''" class="flex min-h-11 cursor-pointer flex-col justify-center px-3.5 py-1.5">
                        <span class="text-[15px] font-semibold text-[var(--trz-tinta)]" x-text="r.name"></span>
                        <span class="text-[13px] text-[var(--trz-texto-2)]" x-text="'Código ' + r.code + ' · Cohorte ' + r.cohort"></span>
                    </li>
                </template>
            </ul>
        </div>

        <div role="group" aria-label="Vista del mapa" class="inline-flex overflow-hidden rounded-[var(--radius-md)] border-[1.5px] border-[var(--trz-borde-control)]">
            <button type="button" aria-pressed="true" :aria-pressed="view === 'mapa' ? 'true' : 'false'" @click="view = 'mapa'" :class="view === 'mapa' ? 'bg-[var(--trz-hilo-fondo)] text-[var(--trz-hilo-texto)]' : 'bg-[var(--trz-superficie)] text-[var(--trz-tinta)]'" class="inline-flex min-h-[41px] items-center gap-2 bg-[var(--trz-hilo-fondo)] px-3.5 max-sm:w-11 max-sm:justify-center max-sm:px-0 text-[15px] font-semibold text-[var(--trz-hilo-texto)]"><x-ui-icon name="map" class="h-[18px] w-[18px]" /><span class="max-sm:sr-only">Mapa</span></button>
            <button type="button" aria-pressed="false" :aria-pressed="view === 'tabla' ? 'true' : 'false'" @click="view = 'tabla'" :class="view === 'tabla' ? 'bg-[var(--trz-hilo-fondo)] text-[var(--trz-hilo-texto)]' : 'bg-[var(--trz-superficie)] text-[var(--trz-tinta)]'" class="inline-flex min-h-[41px] items-center gap-2 border-l-[1.5px] border-[var(--trz-borde-control)] bg-[var(--trz-superficie)] px-3.5 max-sm:w-11 max-sm:justify-center max-sm:px-0 text-[15px] font-semibold text-[var(--trz-tinta)]"><x-ui-icon name="table" class="h-[18px] w-[18px]" /><span class="max-sm:sr-only">Tabla</span></button>
        </div>

        <div class="flex flex-wrap items-center gap-2 sm:ml-auto">
            <button type="button" x-show="view === 'mapa'" aria-pressed="false" :aria-pressed="trails ? 'true' : 'false'" @click="toggleTrails()" aria-label="Ver el cambio desde el periodo anterior" title="Ver el cambio desde el periodo anterior" :class="trails ? '{{ $toolOn }}' : '{{ $toolIdle }}'" class="{{ $toolIcon }} {{ $toolIdle }}"><x-ui-icon name="trending-up" class="h-[18px] w-[18px]" /><span class="max-sm:sr-only">Cambio</span></button>
            <button type="button" x-show="view === 'mapa'" aria-pressed="false" :aria-pressed="selecting ? 'true' : 'false'" @click="toggleSelecting()" aria-label="Seleccionar con recuadro" title="Seleccionar con recuadro (o mantenga Mayús y arrastre)" :class="selecting ? '{{ $toolOn }}' : '{{ $toolIdle }}'" class="{{ $toolIcon }} {{ $toolIdle }}"><x-ui-icon name="box-select" class="h-[18px] w-[18px]" /><span class="max-sm:sr-only">Recuadro</span></button>
            <button type="button" @click="startSaving()" title="Guardar esta combinación de tipos como vista" class="{{ $toolIcon }} {{ $toolIdle }}"><x-ui-icon name="bookmark-plus" class="h-[18px] w-[18px]" /><span class="max-sm:sr-only">Guardar vista</span></button>
            <button type="button" @click="exportCsv()" aria-label="Exportar lo que se ve a CSV" title="Exportar lo que se ve a CSV" class="{{ $toolIcon }} {{ $toolIdle }}"><x-ui-icon name="download" class="h-[18px] w-[18px]" /><span class="max-sm:sr-only">CSV</span></button>
        </div>
    </div>

    <div x-show="saving || saved.length" x-cloak class="flex flex-col gap-2">
        <form x-show="saving" @submit.prevent="saveView()" class="flex flex-wrap items-center gap-2">
            <input type="text" x-ref="viewName" x-model="viewName" maxlength="40" aria-label="Nombre de la vista" placeholder="Nombre de la vista (p. ej. En riesgo y en práctica)" class="h-11 w-full rounded-[var(--radius-md)] border-[1.5px] border-[var(--trz-borde-control)] bg-[var(--trz-superficie)] px-3.5 text-base text-[var(--trz-tinta)] placeholder:text-[var(--trz-texto-3)] sm:w-[340px] md:text-[15px]" />
            <button type="submit" class="{{ $tool }} border-[var(--trz-hilo)] bg-[var(--trz-hilo)] text-[var(--trz-sobre-hilo)] hover:bg-[var(--trz-hilo-hover)]">Guardar</button>
            <button type="button" @click="saving = false" class="{{ $tool }} {{ $toolIdle }}">Cancelar</button>
            <p x-show="saveError" role="alert" class="w-full text-[13px] font-semibold text-[var(--trz-alerta)]" x-text="saveError"></p>
            <p class="w-full text-[13px] text-[var(--trz-texto-2)]">Las vistas se guardan en este navegador: no se comparten con otras personas ni con otros equipos. Recuerdan los tipos elegidos.</p>
        </form>
        <ul x-show="saved.length" class="flex flex-wrap gap-2" aria-label="Vistas guardadas">
            <template x-for="v in saved" :key="v.name">
                <li class="inline-flex items-center overflow-hidden rounded-full border-[1.5px] border-[var(--trz-borde-control)] bg-[var(--trz-superficie)]">
                    <button type="button" @click="applyView(v)" class="min-h-11 px-4 text-[15px] font-semibold text-[var(--trz-tinta)] hover:bg-[var(--trz-hilo-fondo)]" x-text="v.name"></button>
                    <button type="button" @click="removeView(v)" :aria-label="'Quitar la vista ' + v.name" class="flex h-11 w-11 items-center justify-center border-l border-[var(--trz-linea)] text-[var(--trz-texto-2)] hover:bg-[var(--trz-alerta-fondo)] hover:text-[var(--trz-alerta)]"><x-ui-icon name="x" class="h-4 w-4" /></button>
                </li>
            </template>
        </ul>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-x-6 gap-y-3">
        <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-[var(--trz-texto-2)]">
            <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-[var(--trz-hilo)]" aria-hidden="true"></span>Estudiante</span>
            <span class="inline-flex items-center gap-2"><span class="h-4 w-4 rounded-full border-[2.5px] border-[var(--trz-alerta)]" aria-hidden="true"></span>Con alerta abierta</span>
            <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full border-[2.5px] border-[var(--trz-hilo)] bg-[var(--trz-superficie)]" aria-hidden="true"></span>Sin tutoría reciente (punto hueco)</span>
            <span class="inline-flex items-center gap-2"><span class="h-4 border-l-2 border-dashed border-[var(--trz-hito-texto)]" aria-hidden="true"></span>Avance esperado de la cohorte</span>
            <span role="status" aria-live="polite" class="font-semibold text-[var(--trz-tinta)]">Mostrando <span x-text="shown">{{ $shown }}</span> de {{ $total }}</span>
        </div>

        <div x-show="view === 'mapa'" role="group" aria-label="Zoom del mapa" class="inline-flex items-center gap-1.5" x-cloak>
            <button type="button" class="{{ $zoomBtn }}" aria-label="Alejar" title="Alejar (−)" @click="zoomAt(1 / 1.6, undefined, undefined, true)" :disabled="k <= 1.001"><x-ui-icon name="zoom-out" class="h-[18px] w-[18px]" /></button>
            <span class="min-w-[4ch] text-center text-sm font-semibold tabular-nums text-[var(--trz-tinta)]" aria-live="off" x-text="Math.round(k * 100) + ' %'">100 %</span>
            <button type="button" class="{{ $zoomBtn }}" aria-label="Acercar" title="Acercar (+)" @click="zoomAt(1.6, undefined, undefined, true)" :disabled="k >= max - 0.001"><x-ui-icon name="zoom-in" class="h-[18px] w-[18px]" /></button>
            <button type="button" class="{{ $zoomBtn }}" aria-label="Ajustar a los estudiantes visibles" title="Ajustar a los estudiantes visibles" @click="fit()"><x-ui-icon name="scan" class="h-[18px] w-[18px]" /></button>
            <button type="button" class="{{ $zoomBtn }}" aria-label="Restablecer la vista" title="Restablecer la vista (0)" @click="reset()" :disabled="k <= 1.001 && tx === 0 && ty === 0"><x-ui-icon name="rotate-ccw" class="h-[18px] w-[18px]" /></button>
        </div>
    </div>

    <p class="sr-only" role="status" aria-live="polite" x-text="note"></p>



    <div x-ref="wrap" x-show="view === 'mapa'" class="relative">
        <div x-ref="viewport" tabindex="0" role="group" aria-label="Mapa de avance de los estudiantes por cohorte. Más y menos acercan, las flechas mueven la vista y cero la restablece."
            class="cohort-viewport relative flex select-none overflow-hidden rounded-[var(--radius-md)] border border-[var(--trz-linea)] bg-[var(--trz-superficie)]"
            style="--rowh: {{ $rowH }}; height: {{ $rowCount * $rowH }}px">
            <div class="relative w-[52px] shrink-0 overflow-hidden border-r border-[var(--trz-linea)] sm:w-16">
                @foreach($rows as $r => $row)
                    <button type="button" class="cohort-rowlabel text-sm text-[var(--trz-texto-2)] hover:text-[var(--trz-hilo-texto)] hover:underline" style="--r: {{ $r }}" @click="selectRow({{ $r }})" :aria-pressed="row === {{ $r }} ? 'true' : 'false'" aria-label="Resumen de la cohorte {{ $row['label'] }}">{{ $row['label'] }}</button>
                @endforeach
            </div>

            <div x-ref="plot" class="cohort-plot relative min-w-0 flex-1 overflow-hidden">
                @foreach($rows as $r => $row)
                    <span class="cohort-expected" style="--e: {{ $row['expected'] ?? 0 }}; --r: {{ $r }}" aria-hidden="true" title="Avance esperado de la cohorte {{ $row['label'] }}: {{ $row['expected'] ?? 0 }} %"></span>
                @endforeach
                @foreach($allDots as $dot)
                    @php
                        $dim = ! $matches($dot['tags'] ?? []);
                        $tag = ! empty($dot['url']) ? 'a' : 'span';
                        $prev = $dot['prevPct'] ?? null;
                    @endphp
                    @if($prev !== null && $prev !== $dot['pct'])
                        <span class="cohort-trail" @if($dot['pct'] > $prev) data-up @endif @if($dim) data-dim @endif aria-hidden="true"
                            style="--lo: {{ min($prev, $dot['pct']) }}; --span: {{ abs($dot['pct'] - $prev) }}; --r: {{ $dot['row'] }}; --j: {{ $dot['shift'] }}"></span>
                    @endif
                    <{{ $tag }} @if($tag === 'a') href="{{ $dot['url'] }}" @endif data-dot draggable="false" class="cohort-dot" @if($dot['alert']) data-alert @endif @if(in_array('sin-tutoria', $dot['tags'] ?? [], true)) data-quiet @endif @if($dot['behind'] ?? false) data-behind @endif @if($dim) data-dim aria-hidden="true" tabindex="-1" @endif
                        data-id="{{ $dot['id'] ?? '' }}" data-name="{{ $dot['name'] ?? $dot['label'] }}" data-code="{{ $dot['code'] ?? '' }}" data-cohort="{{ $dot['cohort'] ?? '' }}"
                        data-pct="{{ $dot['pct'] }}" data-papa="{{ $dot['papa'] ?? '' }}" data-papa-low="{{ ($dot['papaLow'] ?? false) ? '1' : '0' }}" data-state="{{ $dot['stateLabel'] ?? '' }}"
                        data-alerts="{{ $dot['alerts'] ?? ($dot['alert'] ? 1 : 0) }}" data-note="{{ $dot['alertNote'] ?? '' }}" data-tags="{{ implode(' ', $dot['tags'] ?? []) }}"
                        data-expected="{{ $dot['expected'] ?? 0 }}" @if($prev !== null) data-prev="{{ $prev }}" @endif @if(! empty($dot['lastTutorship'])) data-last="{{ $dot['lastTutorship'] }}" @endif
                        data-row="{{ $dot['row'] }}" data-shift="{{ $dot['shift'] }}"
                        aria-label="{{ $dot['label'] }}{{ $tag === 'a' ? '. Abrir la ficha' : '' }}"
                        style="--px: {{ $dot['pct'] }}; --r: {{ $dot['row'] }}; --j: {{ $dot['shift'] }}"></{{ $tag }}>
                @endforeach

                <template x-for="c in clusters" :key="c.key">
                    <button type="button" class="cohort-cluster" :data-alert="c.alert ? '' : null" :style="`--px: ${c.px}; --r: ${c.r}; --j: ${c.j}; --nx: ${c.nx}`" @click="zoomTo(c)"
                        :aria-label="c.n + ' estudiantes agrupados. Acercar'" x-text="c.n"></button>
                </template>
                <div x-show="box" x-cloak class="pointer-events-none absolute z-10 border-2 border-[var(--trz-hilo)] bg-[var(--trz-hilo-fondo)] opacity-60" :style="box ? `left: ${box.x}px; top: ${box.y}px; width: ${box.w}px; height: ${box.h}px` : ''"></div>
            </div>
        </div>

        <a x-ref="card" x-show="tip" x-cloak :href="tip ? tip.url : '#'" tabindex="-1" aria-hidden="true"
            :style="`left: ${card.x}px; top: ${card.y}px`"
            class="absolute z-20 flex w-[260px] flex-col gap-2.5 rounded-[var(--radius-lg)] border border-[var(--trz-linea)] bg-[var(--trz-superficie)] p-4 text-left shadow-[var(--shadow-menu)]">
            <span class="flex flex-col gap-0.5">
                <span class="font-display text-[17px] font-semibold leading-tight text-[var(--trz-tinta)]" x-text="tip ? tip.name : ''"></span>
                <span class="text-[13px] text-[var(--trz-texto-2)]" x-text="tip ? 'Código ' + tip.code + ' · Cohorte ' + tip.cohort : ''"></span>
            </span>
            <span class="flex items-center gap-3">
                <span class="h-2 flex-1 overflow-hidden rounded-full bg-[var(--trz-linea)]"><span class="block h-full rounded-full bg-[var(--trz-hilo)]" :style="`width: ${tip ? tip.pct : 0}%`"></span></span>
                <span class="text-sm font-bold tabular-nums text-[var(--trz-tinta)]" x-text="tip ? tip.pct + ' % del plan' : ''"></span>
            </span>
            <span x-show="tip && tip.expected > 0" class="text-[13px] leading-snug text-[var(--trz-texto-2)]" x-text="tip ? (tip.behind ? 'Se esperaría el ' + tip.expected + ' %: va ' + (tip.expected - tip.pct) + ' puntos por debajo.' : 'Se esperaría el ' + tip.expected + ' % para su cohorte.') : ''"></span>
            <span class="flex flex-wrap items-baseline gap-x-4 gap-y-1 text-sm">
                <span class="text-[var(--trz-texto-2)]">PAPA <b class="font-bold" :class="tip && tip.papaLow ? 'text-[var(--trz-alerta)]' : 'text-[var(--trz-tinta)]'" x-text="tip ? tip.papa : ''"></b><span x-show="tip && tip.papaLow" class="font-semibold text-[var(--trz-alerta)]"> · bajo el mínimo</span></span>
                <span class="text-[var(--trz-texto-2)]">Estado <b class="font-bold text-[var(--trz-tinta)]" x-text="tip ? tip.state : ''"></b></span>
            </span>
            <span x-show="tip && tip.prev !== null && tip.prev !== tip.pct" class="text-[13px] text-[var(--trz-texto-2)]" x-text="tip && tip.prev !== null ? 'Periodo anterior: ' + tip.prev + ' % (' + (tip.pct > tip.prev ? 'subió ' : 'bajó ') + Math.abs(tip.pct - tip.prev) + ' puntos).' : ''"></span>
            <span class="text-[13px] text-[var(--trz-texto-2)]" x-text="tip ? (tip.last ? 'Última tutoría: ' + tip.last : 'Sin tutorías registradas') : ''"></span>
            <span x-show="tip && tip.extras.length" class="flex flex-wrap gap-1.5">
                <template x-for="extra in (tip ? tip.extras : [])" :key="extra"><span class="rounded-[var(--radius-sm)] bg-[var(--trz-hilo-fondo)] px-2 py-0.5 text-xs font-semibold text-[var(--trz-hilo-texto)]" x-text="extra"></span></template>
            </span>
            <span x-show="tip && tip.alerts > 0" class="rounded-[var(--radius-sm)] bg-[var(--trz-alerta-fondo)] px-2.5 py-1.5 text-[13px] font-semibold leading-snug text-[var(--trz-alerta)]" x-text="tip ? (tip.alerts === 1 ? 'Una alerta abierta' : tip.alerts + ' alertas abiertas') + (tip.note ? ': ' + tip.note : '') : ''"></span>
            <span class="text-sm font-semibold text-[var(--trz-hilo-texto)]">Abrir la ficha →</span>
        </a>

        <div x-show="hint" x-cloak x-transition.opacity class="pointer-events-none absolute inset-x-0 top-3 z-10 mx-auto w-fit rounded-full bg-[var(--trz-tinta)] px-4 py-2 text-sm font-semibold text-[var(--trz-papel)]" role="status">Use <span x-text="mac ? '⌘' : 'Ctrl'">Ctrl</span> y la rueda para acercar el mapa</div>
    </div>

    <div x-show="view === 'mapa'" class="flex">
        <span class="w-[52px] shrink-0 sm:w-16" aria-hidden="true"></span>
        <div class="cohort-axis relative mt-1 h-5 min-w-0 flex-1 overflow-hidden text-[13px] text-[var(--trz-texto-2)]" aria-hidden="true">
            @foreach([0 => 0, 25 => 0.5, 50 => 0.5, 75 => 0.5, 100 => 1] as $tick => $anchor)
                <span class="cohort-tick" style="--t: {{ $tick }}; --a: {{ $anchor }}">{{ $tick }} %</span>
            @endforeach
            @foreach([10, 20, 30, 40, 60, 70, 80, 90] as $tick)
                <span class="cohort-tick" style="--t: {{ $tick }}; --a: 0.5" x-show="k >= 2" x-cloak>{{ $tick }} %</span>
            @endforeach
        </div>
    </div>

    {{-- Debajo del mapa, nunca encima: lo que aparece al actuar sobre él no debe empujarlo ni moverlo de lugar. --}}
    <div x-show="selected.length" x-cloak class="sticky bottom-3 z-20 flex flex-wrap items-center gap-3 rounded-[var(--radius-md)] border border-[var(--trz-hilo)] bg-[var(--trz-hilo-fondo)] px-4 py-2.5 shadow-[var(--shadow-menu)]" role="status">
        <span class="text-[15px] font-semibold text-[var(--trz-hilo-texto)]" x-text="selected.length + (selected.length === 1 ? ' estudiante seleccionado' : ' estudiantes seleccionados')"></span>
        <button type="button" @click="exportCsv(selectedItems(), 'seleccion')" class="{{ $tool }} {{ $toolIdle }}"><x-ui-icon name="download" class="h-[18px] w-[18px]" />Exportar la selección</button>
        <button type="button" @click="clearSelection()" class="{{ $tool }} {{ $toolIdle }}"><x-ui-icon name="x" class="h-[18px] w-[18px]" />Limpiar</button>
    </div>
    <section x-ref="summary" x-show="summary" x-cloak aria-label="Resumen de la cohorte" class="rounded-[var(--radius-lg)] border border-[var(--trz-linea)] bg-[var(--trz-superficie)] p-4">
        <div class="flex items-start justify-between gap-3">
            <h3 class="font-display text-lg font-semibold text-[var(--trz-tinta)]" x-text="summary ? 'Ingresaron en ' + summary.label : ''"></h3>
            <button type="button" @click="closeSummary()" aria-label="Cerrar el resumen" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-[var(--radius-md)] text-[var(--trz-texto-2)] hover:bg-[var(--trz-hilo-fondo)]"><x-ui-icon name="x" class="h-[18px] w-[18px]" /></button>
        </div>
        <dl class="mt-1 grid grid-cols-2 gap-x-6 gap-y-3 sm:grid-cols-3 lg:grid-cols-7">
            <template x-for="stat in (summary ? summary.stats : [])" :key="stat.label">
                <div class="flex flex-col"><dt class="text-[13px] text-[var(--trz-texto-2)]" x-text="stat.label"></dt><dd class="text-xl font-bold tabular-nums text-[var(--trz-tinta)]" x-text="stat.value"></dd></div>
            </template>
        </dl>
    </section>
    <p x-show="dense && view === 'mapa'" x-cloak class="text-[13px] text-[var(--trz-texto-2)]">Con tantos estudiantes los puntos no se recorren con Tab: use la vista de <b class="font-semibold text-[var(--trz-tinta)]">Tabla</b> para leerlos y abrirlos con el teclado.</p>

    <div x-show="view === 'tabla'" x-cloak class="overflow-x-auto rounded-[var(--radius-md)] border border-[var(--trz-linea)] bg-[var(--trz-superficie)]">
        <table class="w-full text-[15px]">
            <caption class="sr-only">Estudiantes que cumplen el filtro; cada columna se puede ordenar</caption>
            <thead>
                <tr class="border-b-[1.5px] border-[var(--trz-linea)] text-[13px] text-[var(--trz-texto-2)]">
                    @foreach([['name', 'Estudiante', ''], ['cohort', 'Cohorte', 'hidden sm:table-cell'], ['pct', 'Avance', ''], ['expected', 'Esperado', 'hidden md:table-cell'], ['papaNum', 'PAPA', ''], ['state', 'Estado', 'hidden sm:table-cell'], ['alerts', 'Alertas', 'hidden md:table-cell'], ['lastKey', 'Última tutoría', 'hidden lg:table-cell']] as [$key, $label, $visibility])
                        <th scope="col" class="{{ $visibility }} px-3 text-left font-semibold" :aria-sort="sortKey === '{{ $key }}' ? (sortDir === 1 ? 'ascending' : 'descending') : 'none'">
                            <button type="button" @click="sortBy('{{ $key }}')" class="inline-flex min-h-11 items-center gap-1 font-semibold hover:text-[var(--trz-tinta)]">{{ $label }}<span x-show="sortKey === '{{ $key }}'" x-text="sortDir === 1 ? '↑' : '↓'" aria-hidden="true"></span></button>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                <template x-for="s in tableRows" :key="s.id">
                    <tr class="border-b border-[var(--trz-linea)] last:border-b-0 hover:bg-[var(--trz-hilo-fondo)]">
                        <th scope="row" class="px-3 py-2.5 text-left font-normal"><a :href="s.url || null" class="font-semibold text-[var(--trz-tinta)] hover:text-[var(--trz-hilo-texto)] hover:underline" x-text="s.name"></a><span class="block text-[13px] text-[var(--trz-texto-2)]" x-text="s.code"></span></th>
                        <td class="hidden px-3 sm:table-cell" x-text="s.cohort"></td>
                        <td class="px-3 font-semibold tabular-nums" x-text="s.pct + ' %'"></td>
                        <td class="hidden px-3 tabular-nums text-[var(--trz-texto-2)] md:table-cell" x-text="s.expected + ' %'"></td>
                        <td class="px-3 font-semibold tabular-nums" :class="s.papaLow ? 'text-[var(--trz-alerta)]' : ''" x-text="s.papa"></td>
                        <td class="hidden px-3 sm:table-cell" x-text="s.state"></td>
                        <td class="hidden px-3 tabular-nums md:table-cell" x-text="s.alerts || '—'"></td>
                        <td class="hidden px-3 text-[var(--trz-texto-2)] lg:table-cell" x-text="s.last || 'Sin tutorías'"></td>
                    </tr>
                </template>
            </tbody>
        </table>
        <p x-show="tableTotal > tableRows.length" class="border-t border-[var(--trz-linea)] px-3 py-2.5 text-[13px] text-[var(--trz-texto-2)]" x-text="'Se muestran los primeros ' + tableRows.length + ' de ' + tableTotal + '. Afine el filtro o exporte el CSV para verlos todos.'"></p>
    </div>

    <p x-show="view === 'mapa'" class="text-[13px] leading-relaxed text-[var(--trz-texto-2)]">
        <span x-show="!coarse">Pase el mouse o enfoque un punto para ver sus datos; pulse para abrir la ficha. Acerque con los botones, con <span x-text="mac ? '⌘' : 'Ctrl'">Ctrl</span> y la rueda o con doble clic, y arrastre para mover el mapa; con Mayús y arrastrar dibuja un recuadro para seleccionar. Pulse el año de una fila para resumir esa cohorte.</span>
        <span x-show="coarse" x-cloak>Toque un punto para ver sus datos y vuelva a tocarlo para abrir la ficha. Acerque con los botones o con dos dedos, y arrastre para mover el mapa. «Recuadro» activa la selección con el dedo; toque el año de una fila para resumir esa cohorte.</span>
    </p>

    @if($reading)
        <figcaption class="text-base leading-relaxed text-[var(--trz-tinta)]">{{ $reading }}</figcaption>
    @endif
    <span id="fin-del-mapa" tabindex="-1" class="sr-only">Fin del mapa</span>
</figure>
