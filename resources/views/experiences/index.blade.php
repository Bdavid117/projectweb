<!-- Experiences index view -->
<x-layouts.app>

@section('content')

<div class="max-w-2xl mx-auto py-8">

    <x-card>
        <x-card-header>
            <h2 class="text-xl font-semibold">Mis experiencias formativas</h2>
        </x-card-header>

        <x-card-content>
            <p class="text-base text-muted mb-4">Registradas y validadas por Coordinación</p>

            @if($experiences->isEmpty())
                <p class="text-muted">No hay experiencias registradas.</p>
            @else
                <div class="space-y-4">
                    @foreach($experiences as $experience)
                        <div class="p-4 border rounded [&>h3]:font-semibold [&>h3]:text-lg">
                            <h3 class="text-primary">{{ $experience->nombre }}</h3>
                            <p class="text-sm text-muted mt-1">{{ $experience->descripcion ?? 'Sin descripción' }}</p>
                            <p class="text-xs text-muted mt-1">Registrada el: {{ $experience->updated_at->diffForHumans() }}</p>
                            <div class="mt-2">
                                <a href="{{ route('experiencias.edit', $experience->id) }}" class="text-primary hover:underline">Editar</a>
                                <form action="{{ route('experiencias.destroy', $experience->id) }}" method="POST" style="display:inline">
                                    @method('DELETE')
                                    @csrf
                                    <button onclick="return confirm('¿Está seguro de eliminar esta experiencia?');" class="text-danger hover:underline">Eliminar</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <a href="{{ route('experiencias.create') }}" class="mt-3 inline-block btn btn-primary">Registrar nueva experiencia</a>
        </x-card-content>
    </x-card>

</div>

@stop