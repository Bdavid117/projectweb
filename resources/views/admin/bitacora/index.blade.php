<x-layouts.app :user="$user" :nav-groups="$navGroups" title="Bitácora · Trayectoria Estudiantil">
    <div class="flex flex-col gap-5 p-4 sm:p-7">
        <div class="flex flex-col gap-[3px]">
            <h1 class="text-2xl font-semibold text-[var(--text-primary)]">Bitácora</h1>
            <p class="text-sm text-[var(--text-secondary)]">Registro de cambios de estado en solicitudes, con responsable y fecha · RNF-05</p>
        </div>

        <div class="flex min-w-0 flex-col rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)]">
            <div class="min-w-0 overflow-x-auto">
                <div class="flex min-w-[720px] gap-3 px-4 py-[11px] text-[11px] font-semibold tracking-[0.4px] text-[var(--text-muted)]">
                    <span class="w-[130px]">FECHA</span>
                    <span class="w-[170px]">USUARIO</span>
                    <span class="flex-1">REGISTRO</span>
                    <span class="w-[220px]">CAMBIO</span>
                </div>
                @forelse($entries as $entry)
                    <div class="flex min-w-[720px] items-center gap-3 border-t border-[var(--border-subtle)] px-4 py-[11px] text-sm">
                        <span class="w-[130px] text-[var(--text-secondary)]">{{ $entry['when'] }}</span>
                        <span class="w-[170px] truncate text-[var(--text-secondary)]">{{ $entry['who'] }}</span>
                        <a href="{{ $entry['url'] }}" class="flex-1 truncate font-semibold text-[var(--text-primary)]">{{ $entry['what'] }}</a>
                        <span class="w-[220px] text-[var(--text-secondary)]">{{ $entry['from'] }} → {{ $entry['to'] }}</span>
                    </div>
                @empty
                    <div class="border-t border-[var(--border-subtle)] px-4 py-8 text-center text-sm text-[var(--text-muted)]">Aún no hay actividad registrada.</div>
                @endforelse
            </div>
        </div>

        <x-pagination :paginator="$entries" noun="registros" />
    </div>
</x-layouts.app>
