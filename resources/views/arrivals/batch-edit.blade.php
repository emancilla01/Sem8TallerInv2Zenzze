@extends('plantillas.inicio')

@section('title', 'Revisar registro')

@section('menu')
    @include('menu-arrivals')
@endsection

@section('contenido')
    @php
        $statusLabel = match ($row['status']) {
            'ready' => 'Listo',
            'incomplete' => 'Incompleto',
            default => 'Error',
        };

        $statusClass = match ($row['status']) {
            'ready' => 'text-bg-success',
            'incomplete' => 'text-bg-warning',
            default => 'text-bg-danger',
        };
    @endphp

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Revisar registro</h1>
            <p class="text-muted mb-0">Corrige los datos extraídos antes de guardarlos desde la carga masiva.</p>
        </div>
        <a href="{{ route('arrivals.batch.index') }}" class="btn btn-outline-secondary">Volver a carga masiva</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <div class="fw-semibold mb-2">No se pudo actualizar el registro.</div>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card arrivals-card shadow-sm">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
                <div>
                    <div class="text-muted small text-uppercase">Archivo</div>
                    <div class="fw-semibold">{{ $row['original_name'] }}</div>
                    <div class="small text-muted mt-2">El PDF original se conservará y se guardará como documento cuando apruebes este registro.</div>
                </div>
                <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
            </div>

            @if (! empty($row['error_message']))
                <div class="alert alert-warning" role="alert">
                    {{ $row['error_message'] }}
                </div>
            @endif

            <form action="{{ route('arrivals.batch.update', $row['id']) }}" method="post" class="row g-4">
                @csrf
                @method('PUT')

                <div class="col-12">
                    <label for="nombre" class="form-label">Nombre</label>
                    <input
                        type="text"
                        class="form-control @error('nombre') is-invalid @enderror"
                        id="nombre"
                        name="nombre"
                        value="{{ old('nombre', $formValues['nombre']) }}"
                        maxlength="255"
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
                        value="{{ old('apellido', $formValues['apellido']) }}"
                        maxlength="255"
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
                        value="{{ old('fecha_llegada', $formValues['fecha_llegada']) }}"
                    >
                    @error('fecha_llegada')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 d-flex flex-column flex-sm-row justify-content-end gap-2 pt-2">
                    <a href="{{ route('arrivals.batch.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
@endsection