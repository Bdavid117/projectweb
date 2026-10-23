<x-layouts.app :user="$user" :nav-groups="$navGroups" title="Catálogos · Trayectoria Estudiantil">
    <div class="flex flex-col gap-5 p-4 sm:p-7">
        <div class="flex flex-col gap-[3px]">
            <h1 class="text-2xl font-semibold text-[var(--text-primary)]">Catálogos</h1>
            <p class="text-sm text-[var(--text-secondary)]">Listas maestras que alimentan los formularios del sistema</p>
        </div>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
            @foreach($catalogs as $catalog)
                <section class="flex min-w-0 flex-col rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)]">
                    <div class="flex items-center gap-2 border-b border-[var(--border-subtle)] px-4 py-[14px]">
                        <h2 class="flex-1 text-sm font-semibold text-[var(--text-primary)]">{{ $catalog['title'] }}</h2>
                        <x-status-badge :text="count($catalog['rows']).' registros'" tone="primary" />
                    </div>
                    <div class="max-h-[320px] min-w-0 overflow-auto">
                        <div class="flex min-w-[360px] gap-3 px-4 py-[10px] text-[11px] font-semibold tracking-[0.4px] text-[var(--text-muted)]">
                            @foreach($catalog['columns'] as $column)
                                <span class="flex-1">{{ mb_strtoupper($column) }}</span>
                            @endforeach
                        </div>
                        @forelse($catalog['rows'] as $row)
                            <div class="flex min-w-[360px] gap-3 border-t border-[var(--border-subtle)] px-4 py-[10px] text-sm">
                                @foreach($row as $cell)
                                    <span class="flex-1 {{ $loop->first ? 'font-semibold text-[var(--text-primary)]' : 'text-[var(--text-secondary)]' }}">{{ $cell }}</span>
                                @endforeach
                            </div>
                        @empty
                            <div class="border-t border-[var(--border-subtle)] px-4 py-6 text-center text-sm text-[var(--text-muted)]">Sin registros.</div>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>
    </div>
</x-layouts.app>
