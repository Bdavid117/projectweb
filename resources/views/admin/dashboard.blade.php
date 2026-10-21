<!-- Admin dashboard view -->
<x-layouts.app>

@section('content')

<div class="max-w-5xl mx-auto py-8">

    <x-card>
        <x-card-header>
            <h2 class="text-xl font-semibold">Panel de Coordinación</h2>
        </x-card-header>

        <x-card-content>
            <p class="text-base text-muted mb-6">Bienvenido al panel de administración. Aquí puede gestionar los distintos aspectos del sistema.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                <a href="{{ route('users.index') }}" class="group p-4 border rounded hover:border-primary transition-colors">
                    <x-lucide class="w-6 h-6 inline-block align-middle" stroke-width="1.5" />
                    <div class="ml-3">
                        <h3 class="text-base font-medium">Usuarios y Roles</h3>
                        <p class="text-sm text-muted mt-1">Gestionar perfiles y permisos</p>
                    </div>
                </a>

                <a href="{{ route('cargas.index') }}" class="group p-4 border rounded hover:border-primary transition-colors">
                    <x-lucide class="w-6 h-6 inline-block align-middle" stroke-width="1.5" />
                    <div class="ml-3">
                        <h3 class="text-base font-medium">Cargas desde Excel</h3>
                        <p class="text-sm text-muted mt-1">Importar datos masivos</p>
                    </div>
                </a>

                <a href="{{ route('catalogos.index') }}" class="group p-4 border rounded hover:border-primary transition-colors">
                    <x-lucide class="w-6 h-6 inline-block align-middle" stroke-width="1.5" />
                    <div class="ml-3">
                        <h3 class="text-base font-medium">Catálogos</h3>
                        <p class="text-sm text-muted mt-1">Catálogos maestros</p>
                    </div>
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
                <a href="{{ route('bitacora.index') }}" class="group p-4 border rounded hover:border-primary transition-colors">
                    <x-lucide class="w-6 h-6 inline-block align-middle" stroke-width="1.5" />
                    <div class="ml-3">
                        <h3 class="text-base font-medium">Bitácora</h3>
                        <p class="text-sm text-muted mt-1">Registro de actividades</p>
                    </div>
                </a>
            </div>
        </x-card-content>
    </x-card>

</div>

@stop