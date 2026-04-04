@extends('plantillas.inicio')

@section('title', 'Nueva llegada')

@section('menu')
    @include('menu-arrivals')
@endsection

@section('contenido')
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Nueva llegada</h1>
            <p class="text-muted mb-0">Registra los datos operativos y los archivos del huésped.</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <div class="fw-semibold mb-2">No se pudo registrar la llegada.</div>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card arrivals-card shadow-sm">
        <div class="card-body p-4">
            <form action="{{ route('arrivals.store') }}" method="post" enctype="multipart/form-data" class="row g-4">
                @csrf

                <div class="col-12">
                    <label for="nombre" class="form-label">Nombre</label>
                    <input
                        type="text"
                        class="form-control @error('nombre') is-invalid @enderror"
                        id="nombre"
                        name="nombre"
                        value="{{ old('nombre') }}"
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
                        value="{{ old('apellido') }}"
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
                        value="{{ old('fecha_llegada', now()->format('Y-m-d')) }}"
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
                        class="arrivals-upload-input @error('documento') is-invalid @enderror"
                        id="documento"
                        name="documento"
                        accept=".pdf,application/pdf"
                    >
                    <label for="documento" class="arrivals-upload-box @error('documento') is-invalid @enderror">
                        <span class="arrivals-upload-title">Arrastra el archivo aquí</span>
                        <span class="text-muted small">o haz clic para seleccionar</span>
                    </label>
                    <div class="form-text">Opcional. Sube el PDF combinado del registro y contrato cuando esté disponible.</div>
                    @error('documento')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <label for="identificacion" class="form-label">Identificación</label>
                    <input
                        type="file"
                        class="arrivals-upload-input @error('identificacion') is-invalid @enderror"
                        id="identificacion"
                        name="identificacion"
                        accept=".pdf,application/pdf,image/*"
                    >
                    <label for="identificacion" class="arrivals-upload-box @error('identificacion') is-invalid @enderror">
                        <span class="arrivals-upload-title">Arrastra el archivo aquí</span>
                        <span class="text-muted small">o haz clic para seleccionar</span>
                    </label>
                    <div class="form-text">Opcional. Acepta PDF o imagen del documento de identidad.</div>
                    @error('identificacion')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 d-flex flex-column flex-sm-row justify-content-end gap-2 pt-2">
                    <a href="{{ route('home') }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
@endsection