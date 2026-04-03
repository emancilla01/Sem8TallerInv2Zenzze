@extends('plantillas.inicio')

@section('title', 'Editar expediente')

@section('menu')
    @include('menu-arrivals')
@endsection

@section('contenido')
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Editar expediente</h1>
            <p class="text-muted mb-0">Actualiza los datos operativos y reemplaza archivos si es necesario.</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <div class="fw-semibold mb-2">No se pudo actualizar el expediente.</div>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card arrivals-card shadow-sm">
        <div class="card-body p-4">
            <form action="{{ route('expedientes.update', $expediente->id) }}" method="post" enctype="multipart/form-data" class="row g-4">
                @csrf
                @method('PUT')

                <div class="col-12">
                    <label for="nombre" class="form-label">Nombre</label>
                    <input
                        type="text"
                        class="form-control @error('nombre') is-invalid @enderror"
                        id="nombre"
                        name="nombre"
                        value="{{ old('nombre', $expediente->nombre) }}"
                        maxlength="255"
                        required
                    >
                    @error('nombre')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label for="apellido" class="form-label">Apellido</label>
                    <input
                        type="text"
                        class="form-control @error('apellido') is-invalid @enderror"
                        id="apellido"
                        name="apellido"
                        value="{{ old('apellido', $expediente->apellido) }}"
                        maxlength="255"
                        required
                    >
                    @error('apellido')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label for="fecha_llegada" class="form-label">Fecha de llegada</label>
                    <input
                        type="date"
                        class="form-control @error('fecha_llegada') is-invalid @enderror"
                        id="fecha_llegada"
                        name="fecha_llegada"
                        value="{{ old('fecha_llegada', optional($expediente->fecha_llegada)->format('Y-m-d')) }}"
                        required
                    >
                    @error('fecha_llegada')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <label for="documento" class="form-label">Documento PDF</label>
                    <input
                        type="file"
                        class="form-control @error('documento') is-invalid @enderror"
                        id="documento"
                        name="documento"
                        accept=".pdf,application/pdf"
                    >
                    <div class="form-text">
                        @if (filled($expediente->documento_path))
                            Deja este campo vacío para conservar el documento actual.
                        @else
                            Opcional. Puedes subir el PDF más adelante.
                        @endif
                    </div>
                    @error('documento')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <label for="identificacion" class="form-label">Identificación</label>
                    <input
                        type="file"
                        class="form-control @error('identificacion') is-invalid @enderror"
                        id="identificacion"
                        name="identificacion"
                        accept=".pdf,application/pdf,image/*"
                    >
                    <div class="form-text">
                        @if (filled($expediente->identificacion_path))
                            Deja este campo vacío para conservar la identificación actual.
                        @else
                            Opcional. Puedes subir la identificación más adelante.
                        @endif
                    </div>
                    @error('identificacion')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 d-flex flex-column flex-sm-row justify-content-end gap-2 pt-2">
                    <a href="{{ route('expedientes.show', $expediente->id) }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
@endsection