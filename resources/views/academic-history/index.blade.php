<x-layouts.app :user="$user" :nav-groups="$navGroups" title="Mi historia académica · Trayectoria Estudiantil">
    <div class="flex flex-col gap-5 p-4 sm:p-7">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-4">
            <div class="flex flex-1 flex-col gap-[3px]">
                <h1 class="text-2xl font-semibold text-[var(--text-primary)]">Mi historia académica</h1>
                <p class="text-sm text-[var(--text-secondary)]">Registros académicos por periodo</p>
            </div>
        </div>

        @if($records->count() > 0)
        <div class="rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] overflow-hidden">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-[var(--border-subtle)] bg-[var(--surface-sunken)]">
                        <th class="px-4 py-3 font-medium text-[var(--text-muted)]">Periodo</th>
                        <th class="px-4 py-3 font-medium text-[var(--text-muted)]">PAPA</th>
                        <th class="px-4 py-3 font-medium text-[var(--text-muted)]">Promedio periodo</th>
                        <th class="px-4 py-3 font-medium text-[var(--text-muted)]">Créditos cursados</th>
                        <th class="px-4 py-3 font-medium text-[var(--text-muted)]">Avance %</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--border-subtle)]">
                    @foreach($records as $record)
                    <tr class="hover:bg-[var(--surface-sunken)]">
                        <td class="px-4 py-3 text-[var(--text-secondary)]">{{ $record->term->codigo }}</td>
                        <td class="px-4 py-3 text-[var(--text-primary)]">{{ number_format((float) $record->papa, 1) }}</td>
                        <td class="px-4 py-3 text-[var(--text-primary)]">{{ $record->promedio_periodo !== null ? number_format((float) $record->promedio_periodo, 1) : '-' }}</td>
                        <td class="px-4 py-3 text-[var(--text-primary)]">{{ $record->creditos_cursados ?? '-' }}</td>
                        <td class="px-4 py-3 text-[var(--text-primary)]">{{ (int) round((float) $record->porcentaje_avance) }}%</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="flex flex-col items-center justify-center py-20 text-center">
            <x-lucide-trending-up class="h-16 w-16 text-[var(--text-muted)]" />
            <h2 class="text-lg font-semibold text-[var(--text-primary)] mt-4">No hay registros académicos</h2>
            <p class="text-sm text-[var(--text-muted)] mt-2">Aún no se han registrado tus registros académicos.</p>
        </div>
        @endif
    </div>
</x-layouts.app>