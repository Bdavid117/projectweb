<x-layouts.app :user="$user" :nav-groups="$navGroups" title="Usuarios y roles · Trayectoria Estudiantil">
    <div class="flex flex-col gap-5 p-4 sm:p-7">
        <div class="flex flex-col gap-[3px]">
            <h1 class="text-2xl font-semibold text-[var(--text-primary)]">Usuarios y roles</h1>
            <p class="text-sm text-[var(--text-secondary)]">{{ $users->total() }} cuentas con acceso al sistema</p>
        </div>

        <div class="flex flex-wrap gap-3">
            @foreach($roleOptions as $value => $label)
                <div class="flex min-w-[140px] flex-col gap-[2px] rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] px-4 py-3">
                    <span class="text-xs text-[var(--text-muted)]">{{ $label }}</span>
                    <span class="text-xl font-semibold text-[var(--text-primary)]">{{ $totals[$value] ?? 0 }}</span>
                </div>
            @endforeach
        </div>

        <form method="GET" action="{{ route('users.index') }}" class="flex flex-col gap-3 rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-[14px] lg:flex-row lg:items-center">
            <div class="flex h-[var(--control-height)] flex-1 items-center gap-2 rounded-[var(--radius-md)] border border-[var(--border-default)] px-3">
                <x-ui-icon name="search" class="h-4 w-4 text-[var(--color-neutral-400)]" />
                <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Buscar por nombre o correo" class="w-full bg-transparent text-sm text-[var(--text-primary)] outline-none placeholder:text-[var(--text-muted)]" />
            </div>
            <x-filter-select name="rol" all="Rol: todos" :options="$roleOptions" :selected="$filters['rol']" />
            <x-button variant="secondary" type="submit">Filtrar</x-button>
        </form>

        <div class="hidden min-w-0 flex-col rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] sm:flex">
            <div class="min-w-0 overflow-x-auto">
                <div class="flex min-w-[640px] gap-3 px-4 py-[11px] text-[11px] font-semibold tracking-[0.4px] text-[var(--text-muted)]">
                    <span class="flex-1">NOMBRE</span>
                    <span class="w-[260px]">CORREO</span>
                    <span class="w-[130px]">ROL</span>
                    <span class="w-[90px]">ESTADO</span>
                </div>
                @forelse($users as $row)
                    <div class="flex min-w-[640px] items-center gap-3 border-t border-[var(--border-subtle)] px-4 py-[11px] text-sm">
                        <span class="flex-1 font-semibold text-[var(--text-primary)]">{{ $row->name }}</span>
                        <span class="w-[260px] truncate text-[var(--text-secondary)]">{{ $row->email }}</span>
                        <span class="w-[130px] text-[var(--text-secondary)]">{{ $row->role->label() }}</span>
                        <span class="w-[90px]"><x-status-badge :text="$row->active ? 'Activo' : 'Inactivo'" :tone="$row->active ? 'success' : 'danger'" /></span>
                    </div>
                @empty
                    <div class="border-t border-[var(--border-subtle)] px-4 py-8 text-center text-sm text-[var(--text-muted)]">No hay usuarios que coincidan con los filtros.</div>
                @endforelse
            </div>
        </div>

        <div class="flex flex-col gap-3 sm:hidden">
            @forelse($users as $row)
                <div class="flex flex-col gap-1 rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-3">
                    <div class="flex items-center gap-2">
                        <span class="flex-1 text-sm font-medium text-[var(--text-primary)]">{{ $row->name }}</span>
                        <x-status-badge :text="$row->active ? 'Activo' : 'Inactivo'" :tone="$row->active ? 'success' : 'danger'" />
                    </div>
                    <p class="text-xs text-[var(--text-secondary)]">{{ $row->email }} · {{ $row->role->label() }}</p>
                </div>
            @empty
                <p class="rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-4 text-center text-sm text-[var(--text-muted)]">No hay usuarios que coincidan con los filtros.</p>
            @endforelse
        </div>

        <x-pagination :paginator="$users" noun="usuarios" />
    </div>
</x-layouts.app>
