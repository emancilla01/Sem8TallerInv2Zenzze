@extends('plantillas.inicio')

@section('menu')
    @include('menu')
@endsection

@section('contenido')
    <div class="py-5">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h2 class="mb-3">{{ $titulo }}</h2>
                <p class="mb-0">Esta placeholder ya tiene ruta y vista base. Aqui puedes empezar a construir el contenido real de {{ strtolower($titulo) }}.</p>
            </div>
        </div>
    </div>
@endsection