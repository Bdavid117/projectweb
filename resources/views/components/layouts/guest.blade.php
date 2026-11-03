@props(['title' => null])

<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <x-head :title="$title ?? 'Trayectoria Estudiantil'" />
</head>
<body class="h-full bg-[var(--trz-papel)] font-sans text-[var(--trz-tinta)] antialiased">
    {{ $slot }}
</body>
</html>
