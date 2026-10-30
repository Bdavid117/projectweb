<x-layouts.app :user="$user" :nav-groups="$navGroups" title="Solicitar tutoría · Trayectoria Estudiantil">
    <div class="flex flex-col gap-5 p-4 sm:p-7">
        <div class="flex flex-col gap-[3px]">
            <h1 class="text-2xl font-semibold text-[var(--text-primary)]">Solicitar tutoría</h1>
            <p class="text-sm text-[var(--text-secondary)]">Para ver la información de un estudiante y registrar tutorías, el estudiante debe aceptar su solicitud.</p>
        </div>

        @if($errors->has('student_id') || $errors->has('mensaje'))
            <div class="flex gap-[10px] rounded-[var(--radius-md)] bg-[var(--color-danger-bg)] p-3">
                <x-ui-icon name="circle-alert" class="h-4 w-4 shrink-0 text-[var(--color-danger)]" />
                <p class="text-sm font-semibold text-[var(--color-danger)]">{{ $errors->first('student_id') ?: $errors->first('mensaje') }}</p>
            </div>
        @endif

        <div class="flex flex-col gap-3 rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-4">
            <h2 class="text-sm font-semibold text-[var(--text-primary)]">Buscar estudiante</h2>
            <form method="GET" action="{{ route('tutor-requests.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <div class="flex h-[var(--control-height)] flex-1 items-center gap-2 rounded-[var(--radius-md)] border border-[var(--border-default)] px-3">
                    <x-ui-icon name="search" class="h-4 w-4 text-[var(--color-neutral-400)]" />
                    <input type="text" name="q" value="{{ $search }}" placeholder="Nombre o código del estudiante" aria-label="Nombre o código del estudiante" class="w-full bg-transparent text-sm text-[var(--text-primary)] outline-none placeholder:text-[var(--text-muted)]" />
                </div>
                <x-button variant="secondary" type="submit">Buscar</x-button>
            </form>

            @if(! $searched)
                <p class="text-sm text-[var(--text-muted)]">Escriba al menos {{ $minSearch }} letras del nombre o del código. Solo verá el nombre, el código y la cohorte hasta que el estudiante acepte.</p>
            @else
                <div class="flex flex-col">
                    @forelse($results as $row)
                        <div class="flex flex-col gap-3 border-t border-[var(--border-subtle)] py-3 lg:flex-row lg:items-center">
                            <div class="flex min-w-0 flex-1 flex-col gap-[2px]">
                                <span class="text-sm font-semibold text-[var(--text-primary)]">{{ $row['name'] }}</span>
                                <span class="text-xs text-[var(--text-secondary)]">Código {{ $row['code'] }} · Cohorte {{ $row['cohort'] }}</span>
                            </div>
                            @if($row['state'] === 'available')
                                <form method="POST" action="{{ route('tutor-requests.store') }}" class="flex flex-col gap-2 sm:flex-row sm:items-center">
                                    @csrf
                                    <input type="hidden" name="student_id" value="{{ $row['id'] }}" />
                                    <input type="text" name="mensaje" maxlength="500" placeholder="Mensaje para el estudiante (opcional)" aria-label="Mensaje para {{ $row['name'] }}" class="h-[var(--control-height)] w-full rounded-[var(--radius-md)] border border-[var(--border-default)] bg-transparent px-3 text-sm text-[var(--text-primary)] outline-none placeholder:text-[var(--text-muted)] focus:border-[var(--focus-ring)] sm:w-[280px]" />
                                    <x-button variant="primary" type="submit" icon="user-plus">Enviar solicitud</x-button>
                                </form>
                            @elseif($row['state'] === 'pending')
                                <x-status-badge text="Solicitud pendiente" tone="warning" />
                            @else
                                <x-status-badge text="Ya la acompaña" tone="success" />
                            @endif
                        </div>
                    @empty
                        <p class="border-t border-[var(--border-subtle)] pt-4 text-sm text-[var(--text-muted)]">No se encontró ningún estudiante con «{{ $search }}».</p>
                    @endforelse
                </div>
            @endif
        </div>

        <div class="flex flex-col rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)]">
            <div class="px-4 py-[14px]">
                <h2 class="text-sm font-semibold text-[var(--text-primary)]">Mis solicitudes enviadas</h2>
            </div>
            @forelse($sent as $row)
                <div class="flex flex-col items-start gap-2 border-t border-[var(--border-subtle)] px-4 py-3 sm:flex-row sm:items-center sm:gap-3">
                    <div class="flex min-w-0 flex-1 flex-col gap-[2px]">
                        <span class="text-sm font-semibold text-[var(--text-primary)]">{{ $row['student'] }}</span>
                        <span class="text-xs text-[var(--text-secondary)]">Código {{ $row['code'] }} · enviada el {{ $row['sentAt'] }}@if($row['answeredAt'] !== '—') · respondida el {{ $row['answeredAt'] }}@endif</span>
                    </div>
                    <x-status-badge :text="$row['status']" :tone="$row['tone']" />
                    @if($row['canCancel'])
                        <form method="POST" action="{{ route('tutor-requests.destroy', $row['id']) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm font-semibold text-[var(--color-danger)]">Retirar</button>
                        </form>
                    @elseif($row['profileUrl'])
                        <a href="{{ $row['profileUrl'] }}" class="text-sm font-semibold text-[var(--color-primary-600)]">Ver ficha</a>
                    @endif
                </div>
            @empty
                <p class="border-t border-[var(--border-subtle)] px-4 py-6 text-center text-sm text-[var(--text-muted)]">Aún no ha enviado solicitudes. Busque a un estudiante para empezar.</p>
            @endforelse
        </div>

        <x-pagination :paginator="$sent" noun="solicitudes" />
    </div>
</x-layouts.app>
