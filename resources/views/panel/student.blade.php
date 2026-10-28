<x-layouts.app :user="$user" :nav-groups="$navGroups" title="Mi panel · Trayectoria Estudiantil">
    <div class="flex flex-col gap-5 p-4 sm:p-7">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-4">
            <div class="flex flex-1 flex-col gap-[3px]">
                <h1 class="text-2xl font-semibold text-[var(--text-primary)]">Mi panel</h1>
                <p class="text-sm text-[var(--text-secondary)]">@if($dashboard){{ $dashboard['header']['name'] }} · {{ $dashboard['header']['code'] }} · {{ $dashboard['header']['cohort'] }}@else Tu usuario aún no tiene ficha de estudiante @endif</p>
            </div>
            <x-button variant="primary" icon="plus" href="/solicitudes/nueva">Nueva solicitud</x-button>
        </div>

        @if($dashboard)
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                @foreach($dashboard['indicators'] as $indicator)
                    <x-indicator-card :label="$indicator['label']" :value="$indicator['value']" :detail="$indicator['detail']" />
                @endforeach
            </div>

            <div class="flex flex-col gap-4 lg:flex-row">
                <div class="flex min-w-0 flex-1 flex-col rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)]">
                    <div class="flex items-center gap-3 px-4 py-[14px]">
                        <h2 class="flex-1 text-sm font-semibold text-[var(--text-primary)]">Mis solicitudes recientes</h2>
                        <a href="/solicitudes" class="text-sm font-medium text-[var(--color-primary-600)]">Ver todas</a>
                    </div>
                    @forelse($dashboard['requests'] as $row)
                        <a href="{{ route('requests.show', $row['id']) }}" class="flex items-center gap-3 border-t border-[var(--border-subtle)] px-4 py-3">
                            <div class="flex min-w-0 flex-1 flex-col gap-[2px]">
                                <span class="text-sm font-medium text-[var(--text-primary)]">{{ $row['type'] }}</span>
                                <span class="text-xs text-[var(--text-muted)]">Radicada {{ $row['when'] }}</span>
                            </div>
                            <x-status-badge :text="$row['status'][0]" :tone="$row['status'][1]" />
                        </a>
                    @empty
                        <p class="border-t border-[var(--border-subtle)] px-4 py-6 text-sm text-[var(--text-muted)]">Aún no has radicado solicitudes.</p>
                    @endforelse
                </div>

                <div class="flex flex-col gap-4 lg:w-[340px]">
                    <div class="flex flex-col gap-2 rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-4">
                        <h2 class="text-sm font-semibold text-[var(--text-primary)]">Mi proceso</h2>
                        <p class="text-xs text-[var(--text-secondary)]">{{ $dashboard['header']['tutor'] }}</p>
                        <a href="/trabajo-de-grado" class="text-sm text-[var(--color-primary-600)]">Trabajo de grado: {{ $dashboard['thesis'] ? $dashboard['thesis']['status'] : 'sin registrar' }}</a>
                        <a href="/practica" class="text-sm text-[var(--color-primary-600)]">Práctica: {{ $dashboard['internship'] ? $dashboard['internship']['period'] : 'sin registrar' }}</a>
                        <a href="/historia-academica" class="text-sm text-[var(--color-primary-600)]">Ver mi historia académica</a>
                        <a href="/experiencias" class="text-sm text-[var(--color-primary-600)]">Ver mis experiencias</a>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-layouts.app>
