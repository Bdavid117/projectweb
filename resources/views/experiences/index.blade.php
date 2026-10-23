<x-layouts.app :user="$user" :nav-groups="$navGroups" title="{{ $isStudent ? 'Mis experiencias' : 'Experiencias' }} · Trayectoria Estudiantil">
    <div class="flex flex-col gap-5 p-4 sm:p-7">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-4">
            <div class="flex flex-1 flex-col gap-[3px]">
                <h1 class="text-2xl font-semibold text-[var(--text-primary)]">{{ $isStudent ? 'Mis experiencias formativas' : 'Experiencias formativas' }}</h1>
                <p class="text-sm text-[var(--text-secondary)]">{{ $experiences->total() }} registradas · las valida Coordinación</p>
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
                <div class="flex flex-col gap-3 rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-4 sm:flex-row sm:items-center">
                    <div class="flex min-w-0 flex-1 flex-col gap-[3px]">
                        <span class="text-sm font-semibold text-[var(--text-primary)]">{{ $row['name'] }}</span>
                        <span class="text-xs text-[var(--text-secondary)]">{{ $row['type'] }} · {{ $row['period'] }}@unless($isStudent) · {{ $row['student'] }}@endunless</span>
                    </div>
                    <x-status-badge :text="$row['validated'] ? 'Validada' : 'Pendiente'" :tone="$row['validated'] ? 'success' : 'warning'" />
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
                </div>
            @empty
                <p class="rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-6 text-center text-sm text-[var(--text-muted)]">No hay experiencias que mostrar.</p>
            @endforelse
        </div>

        <x-pagination :paginator="$experiences" noun="experiencias" />
    </div>
</x-layouts.app>
