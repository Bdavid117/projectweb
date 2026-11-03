<x-layouts.app :user="$user" :nav-groups="$navGroups" title="Cargas desde Excel · Trayectoria Estudiantil">
    <x-page>
        <x-page-header title="Cargas desde Excel" subtitle="Historial de importaciones masivas de datos." />

        <div class="flex items-center gap-3 rounded-[var(--radius-lg)] bg-[var(--trz-hilo-fondo)] px-4 py-3.5">
            <x-ui-icon name="info" class="h-5 w-5 shrink-0 text-[var(--trz-hilo-texto)]" />
            <p class="text-[15px] leading-snug text-[var(--trz-tinta)]">La importación de archivos aún no está habilitada. Aquí se listarán los lotes procesados con sus filas correctas y con error.</p>
        </div>

        @if($batches->isEmpty())
            <x-empty-state kind="cargas" title="Aún no se ha importado ningún archivo." text="Cuando la importación esté habilitada, aquí aparecerá cada lote con sus filas correctas y con error." />
        @else
            <table class="hidden w-full text-[15px] md:table">
                <caption class="sr-only">Cargas desde Excel</caption>
                <thead>
                    <tr class="border-b-[1.5px] border-[var(--trz-linea)] text-[13px] font-semibold text-[var(--trz-texto-2)]">
                        <th scope="col" class="py-2.5 pr-4 text-left font-semibold">Fecha</th>
                        <th scope="col" class="px-4 py-2.5 text-left font-semibold">Tipo</th>
                        <th scope="col" class="px-4 py-2.5 text-left font-semibold">Archivo</th>
                        <th scope="col" class="px-4 py-2.5 text-left font-semibold">Responsable</th>
                        <th scope="col" class="px-4 py-2.5 text-right font-semibold">Correctas</th>
                        <th scope="col" class="py-2.5 pl-4 text-right font-semibold">Con errores</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($batches as $batch)
                        <tr class="border-b border-[var(--trz-linea)]">
                            <td class="py-3.5 pr-4 text-[var(--trz-texto-2)]">{{ $batch->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3.5 text-[var(--trz-tinta)]">{{ ucfirst($batch->tipo) }}</td>
                            <th scope="row" class="px-4 py-3.5 text-left font-semibold text-[var(--trz-tinta)]">{{ $batch->archivo }}</th>
                            <td class="px-4 py-3.5 text-[var(--trz-texto-2)]">{{ $batch->user->name }}</td>
                            <td class="px-4 py-3.5 text-right text-[var(--trz-tinta)]">{{ $batch->filas_ok }}</td>
                            <td class="py-3.5 pl-4 text-right">
                                @if($batch->filas_error > 0)
                                    <span class="inline-flex items-center justify-end gap-1.5 font-bold text-[var(--trz-alerta)]"><x-ui-icon name="triangle-alert" class="h-[15px] w-[15px]" />{{ $batch->filas_error }}</span>
                                @else
                                    <span class="text-[var(--trz-texto-2)]">0</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <ul class="flex flex-col md:hidden">
                @foreach($batches as $batch)
                    <li class="flex flex-col gap-1.5 border-b border-[var(--trz-linea)] py-3.5 first:border-t">
                        <span class="text-base font-semibold text-[var(--trz-tinta)]">{{ $batch->archivo }}</span>
                        <span class="text-[13px] text-[var(--trz-texto-2)]">{{ $batch->created_at->format('d/m/Y H:i') }} · {{ ucfirst($batch->tipo) }} · {{ $batch->user->name }}</span>
                        <span class="flex items-center gap-4 text-[13px]">
                            <span class="inline-flex items-center gap-1.5 text-[var(--trz-tinta)]"><x-ui-icon name="circle-check" class="h-3.5 w-3.5 text-[var(--trz-logro)]" />{{ $batch->filas_ok }} correctas</span>
                            @if($batch->filas_error > 0)
                                <span class="inline-flex items-center gap-1.5 font-bold text-[var(--trz-alerta)]"><x-ui-icon name="triangle-alert" class="h-3.5 w-3.5" />{{ $batch->filas_error }} con error</span>
                            @else
                                <span class="text-[var(--trz-texto-2)]">0 errores</span>
                            @endif
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif

        <x-pagination :paginator="$batches" noun="cargas" />
    </x-page>
</x-layouts.app>
