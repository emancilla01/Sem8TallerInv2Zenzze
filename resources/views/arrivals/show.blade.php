@extends('plantillas.inicio')

@section('title', $expediente->nombre)

@section('menu')
    @include('menu-arrivals')
@endsection

@section('contenido')
    @php
        $documentoUrl = filled($expediente->documento_path) ? asset('storage/' . $expediente->documento_path) : null;
        $identificacionUrl = filled($expediente->identificacion_path) ? asset('storage/' . $expediente->identificacion_path) : null;
        $identificacionExtension = filled($expediente->identificacion_path)
            ? strtolower(pathinfo($expediente->identificacion_path, PATHINFO_EXTENSION))
            : null;
        $identificacionEsImagen = in_array($identificacionExtension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
    @endphp

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">{{ $expediente->nombre }}</h1>
            <p class="text-muted mb-0">Fecha: {{ $expediente->created_at?->format('d/m/Y') ?? 'Sin fecha' }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('expedientes.edit', $expediente->id) }}" class="btn btn-outline-secondary">Editar</a>
            <form action="{{ route('expedientes.destroy', $expediente->id) }}" method="post">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">Eliminar</button>
            </form>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-lg-6">
            <div class="card arrivals-card shadow-sm h-100">
                <div class="card-body d-flex flex-column gap-3">
                    <div>
                        <h2 class="h5 mb-1">Documento</h2>
                        <p class="text-muted mb-0">PDF combinado del registro y contrato.</p>
                    </div>

                    @if ($documentoUrl)
                        <div class="mt-auto">
                            <a href="{{ $documentoUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-primary">
                                Abrir documento
                            </a>
                        </div>
                    @else
                        <p class="text-muted mb-0 mt-auto">Documento pendiente de carga.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card arrivals-card shadow-sm h-100">
                <div class="card-body d-flex flex-column gap-3">
                    <div>
                        <h2 class="h5 mb-1">Identificación</h2>
                        <p class="text-muted mb-0">Vista previa o acceso directo al archivo de identificación.</p>
                    </div>

                    @if (! $identificacionUrl)
                        <p class="text-muted mb-0 mt-auto">Identificación pendiente de carga.</p>
                    @elseif ($identificacionEsImagen)
                        <div>
                            <img
                                src="{{ $identificacionUrl }}"
                                alt="Identificación de {{ $expediente->nombre }}"
                                class="img-fluid rounded border"
                            >
                        </div>

                        <div>
                            <a href="{{ $identificacionUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary">
                                Abrir identificación
                            </a>
                        </div>
                    @else
                        <div class="mt-auto">
                            <a href="{{ $identificacionUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-primary">
                                Abrir identificación
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection