@props(['title' => 'Trayectoria Estudiantil', 'assets' => true])

<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="color-scheme" content="light dark" />
<title>{{ $title }}</title>
<link rel="icon" type="image/svg+xml" href="/favicon.svg" />
{{-- Tema guardado: se aplica antes de pintar para que no haya destello. Sin preferencia, manda el sistema. --}}
<script>try{var t=localStorage.getItem('tema');if(t==='dark'||t==='light'){document.documentElement.dataset.theme=t;}}catch(e){}</script>
@if($assets)
    @vite(['resources/css/app.css', 'resources/js/app.js'])
@else
    @vite(['resources/css/app.css'])
@endif
