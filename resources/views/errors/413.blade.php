<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Archivos demasiado grandes · Trayectoria Estudiantil</title>
    @vite(['resources/css/app.css'])
</head>
<body class="flex h-full items-center justify-center bg-[var(--surface-page)] p-4 font-sans text-[var(--text-primary)]">
    <div class="flex w-full max-w-[460px] flex-col gap-4 rounded-[var(--radius-lg)] border border-[var(--border-subtle)] bg-[var(--surface-card)] p-8">
        <h1 class="text-xl font-semibold text-[var(--text-primary)]">Los archivos son demasiado grandes</h1>
        <p class="text-sm text-[var(--text-secondary)]">Los soportes superan el límite de tamaño que acepta el servidor. Reduzca el peso o el número de archivos (máximo 5 MB por archivo y 5 archivos) e inténtelo de nuevo. Si el problema continúa, avise a quien administra el sistema.</p>
        <a href="{{ url()->previous() }}" class="inline-flex items-center justify-center rounded-[var(--radius-md)] bg-[var(--color-primary-600)] px-[18px] py-[10px] text-sm font-semibold text-[var(--text-on-brand)] hover:bg-[var(--color-primary-700)]">Volver</a>
    </div>
</body>
</html>
