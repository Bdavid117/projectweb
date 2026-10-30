<x-layouts.app :user="$user" :nav-groups="$navGroups" title="Mi tutor · Trayectoria Estudiantil">
    <div class="flex flex-col gap-5 p-4 sm:p-7">
        <div class="flex flex-col gap-[3px]">
            <h1 class="text-2xl font-semibold text-[var(--text-primary)]">Mi tutor</h1>
            <p class="text-sm text-[var(--text-secondary)]">Usted decide quién puede ver su información como tutor · Periodo {{ $termLabel }}</p>
        </div>

        @if($errors->has('tutor'))
            <div class="flex gap-[10px] rounded-[var(--radius-md)] bg-[var(--color-danger-bg)] p-3">
                <x-ui-icon name="circle-alert" class="h-4 w-4 shrink-0 text-[var(--color-danger)]" />
                <p class="text-sm font-semibold text-[var(--color-danger)]">{{ $errors->first('tutor') }}</p>
            </div>
        @endif

        @if(! $hasStudent)
            <p class="rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-6 text-center text-sm text-[var(--text-muted)]">Su usuario no tiene una ficha de estudiante, así que no puede compartir su información.</p>
        @else
            <div class="flex flex-col rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)]">
                <div class="flex flex-col gap-[2px] px-4 py-[14px]">
                    <h2 class="text-sm font-semibold text-[var(--text-primary)]">Solicitudes pendientes</h2>
                    <p class="text-xs text-[var(--text-muted)]">Si acepta, esa persona podrá ver su ficha, solicitudes, experiencias y tutorías, y registrar tutorías.</p>
                </div>
                @forelse($pending as $row)
                    <div class="flex flex-col gap-3 border-t border-[var(--border-subtle)] px-4 py-3 lg:flex-row lg:items-center">
                        <div class="flex min-w-0 flex-1 flex-col gap-[2px]">
                            <span class="text-sm font-semibold text-[var(--text-primary)]">{{ $row['tutor'] }}</span>
                            <span class="text-xs text-[var(--text-secondary)]">Enviada el {{ $row['sentAt'] }}</span>
                            @if($row['message'])
                                <p class="whitespace-pre-line text-sm text-[var(--text-secondary)]">«{{ $row['message'] }}»</p>
                            @endif
                        </div>
                        <div class="flex gap-2">
                            <form method="POST" action="{{ $row['acceptUrl'] }}">
                                @csrf
                                <x-button variant="primary" type="submit" icon="circle-check">Aceptar</x-button>
                            </form>
                            <form method="POST" action="{{ $row['rejectUrl'] }}">
                                @csrf
                                <x-button variant="secondary" type="submit">Rechazar</x-button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="border-t border-[var(--border-subtle)] px-4 py-6 text-center text-sm text-[var(--text-muted)]">No tiene solicitudes pendientes.</p>
                @endforelse
            </div>

            <div class="flex flex-col rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)]">
                <div class="px-4 py-[14px]">
                    <h2 class="text-sm font-semibold text-[var(--text-primary)]">Tutor actual</h2>
                </div>
                @if($current)
                    <div class="flex flex-col gap-3 border-t border-[var(--border-subtle)] px-4 py-3 lg:flex-row lg:items-center">
                        <div class="flex min-w-0 flex-1 flex-col gap-[2px]">
                            <span class="text-sm font-semibold text-[var(--text-primary)]">{{ $current['name'] }}</span>
                            <span class="text-xs text-[var(--text-secondary)]">Comparte su información desde el {{ $current['since'] }}</span>
                        </div>
                        {{-- El texto viaja en un atributo data- (HTML lo escapa) y no dentro de un literal de JavaScript: el nombre del tutor es dato del usuario. --}}
                        <form method="POST" action="{{ route('my-tutor.revoke') }}" x-data data-confirm="¿Dejar de compartir su información con {{ $current['name'] }}? Perderá el acceso de inmediato." @submit="if (! confirm($el.dataset.confirm)) $event.preventDefault()">
                            @csrf
                            @method('DELETE')
                            <x-button variant="secondary" type="submit">Dejar de compartir</x-button>
                        </form>
                    </div>
                @else
                    <p class="border-t border-[var(--border-subtle)] px-4 py-6 text-center text-sm text-[var(--text-muted)]">No comparte su información con ningún tutor en este periodo.</p>
                @endif
            </div>

            @if(count($history) > 0)
                <div class="flex flex-col rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)]">
                    <div class="px-4 py-[14px]">
                        <h2 class="text-sm font-semibold text-[var(--text-primary)]">Historial</h2>
                    </div>
                    @foreach($history as $row)
                        <div class="flex items-center gap-3 border-t border-[var(--border-subtle)] px-4 py-3">
                            <span class="min-w-0 flex-1 text-sm text-[var(--text-primary)]">{{ $row['tutor'] }}</span>
                            <span class="text-xs text-[var(--text-secondary)]">{{ $row['date'] }}</span>
                            <x-status-badge :text="$row['status']" :tone="$row['tone']" />
                        </div>
                    @endforeach
                </div>
            @endif
        @endif
    </div>
</x-layouts.app>
