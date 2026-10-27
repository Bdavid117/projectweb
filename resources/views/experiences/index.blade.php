<x-layouts.app :user="$user" :nav-groups="$navGroups" title="{{ $isStudent ? 'Mis experiencias' : 'Experiencias' }} · Trayectoria Estudiantil">
    <div class="flex flex-col gap-5 p-4 sm:p-7">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-4">
            <div class="flex flex-1 flex-col gap-[3px]">
                <h1 class="text-2xl font-semibold text-[var(--text-primary)]">{{ $isStudent ? 'Mis experiencias formativas' : 'Experiencias formativas' }}</h1>
                <p class="text-sm text-[var(--text-secondary)]">{{ $experiences->total() }} registradas · las valida Coordinación@unless($canCreate || $canReview) · solo lectura@endunless</p>
            </div>
            @if($canCreate)
                <x-button variant="primary" icon="plus" :href="route('experiencias.create')">Registrar experiencia</x-button>
            @endif
        </div>

        <form method="GET" action="{{ route('experiencias.index') }}" class="flex items-center gap-3 rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-[14px]">
            <x-filter-select name="estado" all="Estado: todos" :options="$statusOptions" :selected="$filters['estado']" />
            <x-button variant="secondary" type="submit">Filtrar</x-button>
        </form>

        <div class="flex flex-col gap-3">
            @forelse($experiences as $row)
                <div class="flex flex-col gap-3 rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-4 sm:flex-row sm:flex-wrap sm:items-center">
                    <div class="flex min-w-0 flex-1 flex-col gap-[3px]">
                        <span class="text-sm font-semibold text-[var(--text-primary)]">{{ $row['name'] }}</span>
                        <span class="text-xs text-[var(--text-secondary)]">{{ $row['type'] }} · {{ $row['period'] }}@unless($isStudent) · {{ $row['student'] }}@endunless</span>
                    </div>
                    @php
                        [$badgeText, $badgeTone] = match ($row['status']) {
                            'validada' => ['Validada', 'success'],
                            'rechazada' => ['Rechazada', 'danger'],
                            default => ['Pendiente', 'warning'],
                        };
                    @endphp
                    <x-status-badge :text="$badgeText" :tone="$badgeTone" />
                    <div class="flex items-center gap-3">
                        @if($row['canValidate'])
                            <form method="POST" action="{{ route('experiencias.validate', $row['id']) }}">
                                @csrf
                                <button type="submit" class="text-sm font-semibold text-[var(--color-primary-600)]">Validar</button>
                            </form>
                        @endif
                        @if($row['canEdit'])
                            <a href="{{ route('experiencias.edit', $row['id']) }}" class="text-sm font-semibold text-[var(--color-primary-600)]">Editar</a>
                        @endif
                    </div>
                    @if($row['canReject'])
                        <form method="POST" action="{{ route('experiencias.reject', $row['id']) }}" class="flex w-full flex-col gap-2 sm:basis-full sm:flex-row sm:items-center">
                            @csrf
                            <input type="text" name="motivo" required maxlength="1000" placeholder="Motivo del rechazo" aria-label="Motivo del rechazo" class="h-[var(--control-height)] w-full flex-1 rounded-[var(--radius-md)] border border-[var(--border-default)] bg-[var(--surface-card)] px-3 text-sm text-[var(--text-primary)] outline-none placeholder:text-[var(--text-muted)] focus:border-[var(--focus-ring)]" />
                            <button type="submit" class="text-sm font-semibold text-[var(--color-danger)]">Rechazar</button>
                        </form>
                    @endif
                    @if($row['reason'])
                        <p class="w-full rounded-[var(--radius-md)] bg-[var(--color-danger-bg)] px-3 py-2 text-xs text-[var(--color-danger)] sm:basis-full">Motivo del rechazo: {{ $row['reason'] }}</p>
                    @endif
                </div>
            @empty
                <p class="rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-6 text-center text-sm text-[var(--text-muted)]">No hay experiencias que mostrar.</p>
            @endforelse
        </div>

        <x-pagination :paginator="$experiences" noun="experiencias" />
    </div>
</x-layouts.app>
