<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Hotel Check-In')</title>
    @vite(['resources/js/app.ts'])
    {{-- <link rel="stylesheet" href="{{ asset('css/photo-thumb.css') }}"> --}}
</head>
<body>
    <div class="d-flex flex-column min-vh-100 arrivals-shell">
        @hasSection('menu')
            @yield('menu')
        @endif

        <main class="container my-4 flex-grow-1 arrivals-main">
            @yield('contenido')
        </main>

        <footer class="border-top py-3 mt-auto arrivals-footer">
            <div class="container text-center small">
                @yield('footer_text', 'Sistema de documentos de check-in del hotel')
            </div>
        </footer>
    </div>
</body>
</html>