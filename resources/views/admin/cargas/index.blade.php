<x-layouts.app :user="$user" :nav-groups="$navGroups" title="Cargas desde Excel · Trayectoria Estudiantil">
    <div class="flex flex-col gap-5 p-4 sm:p-7">
        <div class="flex flex-col gap-[3px]">
            <h1 class="text-2xl font-semibold text-[var(--text-primary)]">Cargas desde Excel</h1>
            <p class="text-sm text-[var(--text-secondary)]">Historial de importaciones masivas de datos</p>
        </div>

        <div class="flex items-start gap-[10px] rounded-[var(--radius-lg)] bg-[var(--color-info-bg)] p-[14px]">
            <x-ui-icon name="info" class="h-4 w-4 shrink-0 text-[var(--color-info)]" />
            <p class="text-sm text-[var(--text-secondary)]">La importación de archivos aún no está habilitada. Aquí se listarán los lotes procesados con sus filas correctas y con error.</p>
        </div>

        <div class="flex min-w-0 flex-col rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)]">
            <div class="min-w-0 overflow-x-auto">
                <div class="flex min-w-[720px] gap-3 px-4 py-[11px] text-[11px] font-semibold tracking-[0.4px] text-[var(--text-muted)]">
                    <span class="w-[130px]">FECHA</span>
                    <span class="w-[120px]">TIPO</span>
                    <span class="flex-1">ARCHIVO</span>
                    <span class="w-[160px]">RESPONSABLE</span>
                    <span class="w-[70px]">CORRECTAS</span>
                    <span class="w-[70px]">ERRORES</span>
                </div>
                @forelse($batches as $batch)
                    <div class="flex min-w-[720px] items-center gap-3 border-t border-[var(--border-subtle)] px-4 py-[11px] text-sm">
                        <span class="w-[130px] text-[var(--text-secondary)]">{{ $batch->created_at->format('d/m/Y H:i') }}</span>
                        <span class="w-[120px] text-[var(--text-secondary)]">{{ ucfirst($batch->tipo) }}</span>
                        <span class="flex-1 truncate font-semibold text-[var(--text-primary)]">{{ $batch->archivo }}</span>
                        <span class="w-[160px] truncate text-[var(--text-secondary)]">{{ $batch->user->name }}</span>
                        <span class="w-[70px] text-[var(--color-success)]">{{ $batch->filas_ok }}</span>
                        <span class="w-[70px] {{ $batch->filas_error > 0 ? 'font-semibold text-[var(--color-danger)]' : 'text-[var(--text-secondary)]' }}">{{ $batch->filas_error }}</span>
                    </div>
                @empty
                    <div class="border-t border-[var(--border-subtle)] px-4 py-8 text-center text-sm text-[var(--text-muted)]">Aún no se ha importado ningún archivo.</div>
                @endforelse
            </div>
        </div>

        <x-pagination :paginator="$batches" noun="cargas" />
    </div>
</x-layouts.app>
