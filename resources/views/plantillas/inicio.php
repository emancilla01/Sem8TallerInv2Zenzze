<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    @vite(['resources/js/app2.ts'])
    <link rel="stylesheet" href="{{ asset('css/photo-thumb.css') }}">
</head>
<body>
    <div class="row">
            <div class="col">
                @yield('menu')
            </div>
        </div>
    <div class="container" style="padding-bottom:80px;">
                
        <div class="row">
            <div class="col">
                @yield('contenido')
            </div>
        </div>

        <nav class="navbar fixed-bottom navbar-dark bg-primary">
        <div class="container-fluid justify-content-center">
            <span class="navbar-text text-center w-100">
                <?php
                echo auth()->user()->name . "<br>"; 
                echo auth()->user()->email;
                ?>
            </span>
        </div>
    </nav>
    </div>
</body>
</html>