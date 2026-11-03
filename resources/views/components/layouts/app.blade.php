@props(['user', 'navGroups', 'title' => null])

<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <x-head :title="$title ?? 'Trayectoria Estudiantil'" />
</head>
<body class="min-h-full bg-[var(--trz-papel)] font-sans text-[var(--trz-tinta)] antialiased">
    <a href="#contenido" class="sr-only focus:not-sr-only focus:fixed focus:left-3 focus:top-3 focus:z-[60] focus:rounded-[var(--radius-md)] focus:bg-[var(--trz-superficie)] focus:px-4 focus:py-2 focus:font-semibold focus:text-[var(--trz-tinta)]">Saltar al contenido</a>
    <x-topbar :user="$user" :groups="$navGroups" :title="$title" />
    <x-nav.drawer :groups="$navGroups" />

    <main id="contenido" class="min-w-0 pb-[var(--tabbar-height)] md:pb-0 print:pb-0">
        @if(session('status'))
            <div class="mx-auto w-full max-w-[var(--content-max-width)] px-[18px] pt-6 sm:px-8 xl:px-16">
                <x-flash :message="session('status')" />
            </div>
        @endif
        {{ $slot }}
    </main>

    <x-nav.tabbar :groups="$navGroups" :user="$user" />
</body>
</html>
