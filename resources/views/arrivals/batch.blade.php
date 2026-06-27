@extends('plantillas.inicio')

@section('title', 'Carga masiva')

@section('menu')
    @include('menu-arrivals')
@endsection

@section('contenido')
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Carga masiva</h1>
            <p class="text-muted mb-0">Procesa varios registros y revisa los datos antes de guardarlos.</p>
        </div>
        <a href="{{ route('arrivals.create') }}" class="btn btn-outline-secondary">Agregar registro</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <div class="fw-semibold mb-2">No se pudo completar la carga masiva.</div>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card arrivals-card shadow-sm mb-4">
        <div class="card-body p-4">
            <form action="{{ route('arrivals.batch.process') }}" method="post" enctype="multipart/form-data" class="row g-4">
                @csrf

                <div class="col-12">
                    <label class="form-label" for="register_card_pdfs">Register cards PDF</label>
                    <input
                        type="file"
                        class="arrivals-upload-input @error('register_card_pdfs') is-invalid @enderror @error('register_card_pdfs.*') is-invalid @enderror"
                        id="register_card_pdfs"
                        name="register_card_pdfs[]"
                        accept="application/pdf,.pdf"
                        multiple
                    >
                    <label for="register_card_pdfs" class="arrivals-upload-box @error('register_card_pdfs') is-invalid @enderror @error('register_card_pdfs.*') is-invalid @enderror" data-upload-box tabindex="0" role="button">
                        <span class="arrivals-upload-title">Arrastra los archivos aquí</span>
                        <span class="text-muted small">o haz clic para seleccionar</span>
                        <span class="arrivals-upload-filename text-muted small" data-upload-filename>Ningún archivo seleccionado</span>
                    </label>
                    <div class="form-text">Sube uno o varios PDFs para extraer nombre, apellido y fecha de llegada antes de guardar.</div>
                    @error('register_card_pdfs')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                    @error('register_card_pdfs.*')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary" id="btn-procesar-registros">Procesar registros</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card arrivals-card shadow-sm">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                <div>
                    <h2 class="h4 mb-1">Resultados</h2>
                    <p class="text-muted mb-0">Revisa cada archivo antes de guardarlo como expediente.</p>
                </div>
                @if (count($batchRows) > 0)
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge text-bg-success">Listos: {{ $readyBatchRowCount }}</span>
                        <span class="badge text-bg-warning">Incompletos: {{ $incompleteBatchRowCount }}</span>
                        <span class="badge text-bg-danger">Errores: {{ $errorBatchRowCount }}</span>
                    </div>
                @endif
            </div>

            @if (count($batchRows) === 0)
                <div class="border rounded-4 p-4 text-center text-muted bg-body-tertiary">
                    Procesa uno o varios register cards para ver los resultados aquí.
                </div>
            @else
                <form action="{{ route('arrivals.batch.store-selected') }}" method="post">
                    @csrf

                    <div class="table-responsive">
                        <table class="table table-striped align-middle arrivals-table mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">Archivo</th>
                                    <th scope="col">Nombre</th>
                                    <th scope="col">Apellido</th>
                                    <th scope="col">Fecha de llegada</th>
                                    <th scope="col">Estado</th>
                                    <th scope="col" class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($batchRows as $row)
                                    @php
                                        $badgeClass = match ($row['status']) {
                                            'ready' => 'text-bg-success',
                                            'incomplete' => 'text-bg-warning',
                                            default => 'text-bg-danger',
                                        };

                                        $statusLabel = match ($row['status']) {
                                            'ready' => 'Listo',
                                            'incomplete' => 'Incompleto',
                                            default => 'Error',
                                        };
                                    @endphp
                                    <tr>
                                        <td class="fw-semibold">{{ $row['original_name'] }}</td>
                                        <td>{{ $row['nombre'] !== '' ? $row['nombre'] : '—' }}</td>
                                        <td>{{ $row['apellido'] !== '' ? $row['apellido'] : '—' }}</td>
                                        <td>
                                            {{ $row['fecha_llegada'] !== '' ? \Illuminate\Support\Carbon::parse($row['fecha_llegada'])->format('d/m/Y') : '—' }}
                                        </td>
                                        <td>
                                            <span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span>
                                            @if ($row['status'] === 'incomplete')
                                                <div class="small text-muted mt-2">Faltan datos obligatorios para guardar.</div>
                                            @endif
                                            @if (! empty($row['error_message']))
                                                <div class="small text-danger mt-2">{{ $row['error_message'] }}</div>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <div class="d-flex flex-column align-items-end gap-2">
                                                @if ($row['status'] === 'ready')
                                                    <div class="form-check">
                                                        <input
                                                            class="form-check-input"
                                                            type="checkbox"
                                                            name="selected_rows[]"
                                                            value="{{ $row['id'] }}"
                                                            id="selected-row-{{ $row['id'] }}"
                                                            checked
                                                        >
                                                        <label class="form-check-label small" for="selected-row-{{ $row['id'] }}">
                                                            Seleccionar
                                                        </label>
                                                    </div>
                                                @else
                                                    <span class="small text-muted">No disponible</span>
                                                @endif

                                                <a href="{{ route('arrivals.batch.edit', $row['id']) }}" class="btn btn-outline-secondary btn-sm">Revisar</a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex flex-column flex-md-row justify-content-end gap-2 pt-4">
                        <button type="submit" class="btn btn-outline-secondary" id="btn-guardar-seleccionados">Guardar seleccionados</button>
                        <button type="submit" class="btn btn-primary" id="btn-guardar-validos" formaction="{{ route('arrivals.batch.store-valid') }}">Guardar todos los válidos</button>
                    </div>
                </form>
            @endif
        </div>
    </div>
@endsection

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Upload form spinner
    const uploadForm = document.querySelector('form[action="{{ route('arrivals.batch.process') }}"]');
    const btnProcesar = document.getElementById('btn-procesar-registros');
    if (uploadForm && btnProcesar) {
        uploadForm.addEventListener('submit', () => {
            btnProcesar.disabled = true;
            btnProcesar.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Procesando...';
        });
    }

    // Save buttons spinner
    const saveForm = document.querySelector('form[action="{{ route('arrivals.batch.store-selected') }}"]');
    if (saveForm) {
        saveForm.addEventListener('submit', (e) => {
            const clicked = document.activeElement;
            if (clicked && (clicked.id === 'btn-guardar-seleccionados' || clicked.id === 'btn-guardar-validos')) {
                clicked.disabled = true;
                clicked.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Guardando...';
            }
        });
    }
});
</script>