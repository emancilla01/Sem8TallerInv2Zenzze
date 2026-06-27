<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Hotel Check-In')</title>
    @vite(['resources/js/app.ts'])
    {{-- <link rel="stylesheet" href="{{ asset('css/photo-thumb.css') }}"> --}}
</head>
<body>
    <div class="d-flex flex-column min-vh-100 arrivals-shell">
        @hasSection('menu')
            @yield('menu')
        @endif

        <main class="container my-4 grow arrivals-main">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            @endif

            @if (session('warning'))
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    {{ session('warning') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            @endif

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