@php
    $summary = collect($roleOptions)->map(fn ($label, $value) => ($totals[$value] ?? 0).' '.mb_strtolower($label).(($totals[$value] ?? 0) === 1 ? '' : 's'))->implode(', ');
@endphp
<x-layouts.app :user="$user" :nav-groups="$navGroups" title="Usuarios y roles · Trayectoria Estudiantil">
    <x-page>
        <x-page-header title="Usuarios y roles" :subtitle="$users->total().' cuentas con acceso al sistema: '.$summary.'.'" />

        <div class="flex items-center gap-3 rounded-[var(--radius-lg)] bg-[var(--trz-hilo-fondo)] px-4 py-3.5">
            <x-ui-icon name="info" class="h-5 w-5 shrink-0 text-[var(--trz-hilo-texto)]" />
            <p class="text-[15px] leading-snug text-[var(--trz-tinta)]">Por ahora esta pantalla es de solo lectura. Crear usuarios, cambiar roles y desactivar cuentas está planeado.</p>
        </div>

        <x-filter-bar :action="route('users.index')">
            <x-search-field name="q" :value="$filters['q']" placeholder="Buscar por nombre o correo" />
            <div class="w-full sm:w-[190px]"><x-filter-select name="rol" all="Rol: todos" :options="$roleOptions" :selected="$filters['rol']" /></div>
            <x-button variant="secondary" type="submit">Filtrar</x-button>
        </x-filter-bar>

        @if($users->isEmpty())
            <x-empty-state kind="historia" title="No hay usuarios que coincidan con los filtros." />
        @else
            <table class="hidden w-full text-[15px] md:table">
                <caption class="sr-only">Usuarios y roles</caption>
                <thead>
                    <tr class="border-b-[1.5px] border-[var(--trz-linea)] text-[13px] font-semibold text-[var(--trz-texto-2)]">
                        <th scope="col" class="py-2.5 pr-4 text-left font-semibold">Nombre</th>
                        <th scope="col" class="px-4 py-2.5 text-left font-semibold">Correo</th>
                        <th scope="col" class="px-4 py-2.5 text-left font-semibold">Rol</th>
                        <th scope="col" class="py-2.5 pl-4 text-left font-semibold">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $row)
                        <tr class="border-b border-[var(--trz-linea)]">
                            <th scope="row" class="py-3.5 pr-4 text-left font-semibold text-[var(--trz-tinta)]">{{ $row->name }}</th>
                            <td class="px-4 py-3.5 text-[var(--trz-texto-2)]">{{ $row->email }}</td>
                            <td class="px-4 py-3.5 text-[var(--trz-tinta)]">{{ $row->role->label() }}</td>
                            <td class="py-3.5 pl-4"><x-status-badge :text="$row->active ? 'Activo' : 'Inactivo'" :tone="$row->active ? 'success' : 'neutral'" :icon="$row->active ? null : 'circle'" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <ul class="flex flex-col md:hidden">
                @foreach($users as $row)
                    <li class="flex flex-col gap-1.5 border-b border-[var(--trz-linea)] py-3.5 first:border-t">
                        <span class="flex items-center justify-between gap-3">
                            <span class="text-base font-semibold text-[var(--trz-tinta)]">{{ $row->name }}</span>
                            <x-status-badge :text="$row->active ? 'Activo' : 'Inactivo'" :tone="$row->active ? 'success' : 'neutral'" :icon="$row->active ? null : 'circle'" />
                        </span>
                        <span class="text-[13px] text-[var(--trz-texto-2)]">{{ $row->email }} · {{ $row->role->label() }}</span>
                    </li>
                @endforeach
            </ul>
        @endif

        <x-pagination :paginator="$users" noun="usuarios" />
    </x-page>
</x-layouts.app>
