@extends('plantillas.inicio')

@section('title', 'Prueba OCR de register card')

@section('menu')
    @include('menu-arrivals')
@endsection

@section('contenido')
    <div class="d-flex flex-column gap-4">
        <div>
            <h1 class="h2 mb-1">Prueba OCR de register card</h1>
            <p class="text-muted mb-0">Carga un PDF original de Opera. El sistema convertirá la primera página a imagen, ejecutará OCR y mostrará los campos detectados.</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <div class="fw-semibold mb-2">No se pudo procesar el PDF.</div>
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card arrivals-card shadow-sm">
            <div class="card-body p-4">
                <form action="{{ route('register-card-ocr.store') }}" method="post" enctype="multipart/form-data" class="row g-4">
                    @csrf

                    <div class="col-12">
                        <label for="register_card_pdf" class="form-label">Register card PDF</label>
                        <input
                            type="file"
                            class="form-control @error('register_card_pdf') is-invalid @enderror"
                            id="register_card_pdf"
                            name="register_card_pdf"
                            accept=".pdf,application/pdf"
                            required
                        >
                        <div class="form-text">Se convertirá únicamente la primera página del PDF para el OCR.</div>
                        @error('register_card_pdf')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">Procesar OCR</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card arrivals-card shadow-sm">
            <div class="card-body p-4">
                <h2 class="h5 mb-3">Campos detectados</h2>

                @if ($parsedFields !== null)
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="border rounded bg-white h-100 p-3">
                                <div class="small text-muted mb-1">Nombre</div>
                                <div class="fw-semibold">{{ $parsedFields['nombre'] ?? 'No encontrado' }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded bg-white h-100 p-3">
                                <div class="small text-muted mb-1">Apellido</div>
                                <div class="fw-semibold">{{ $parsedFields['apellido'] ?? 'No encontrado' }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded bg-white h-100 p-3">
                                <div class="small text-muted mb-1">Fecha de llegada</div>
                                <div class="fw-semibold">{{ $parsedFields['fecha_llegada'] ?? 'No encontrada' }}</div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="text-muted">Los valores parseados aparecerán aquí después de procesar un PDF.</div>
                @endif
            </div>
        </div>

        <div class="card arrivals-card shadow-sm">
            <div class="card-body p-4">
                <h2 class="h5 mb-3">Vista previa de la imagen</h2>

                @if ($imagePreview !== null)
                    <img src="{{ $imagePreview }}" alt="Vista previa de la primera página convertida" class="img-fluid border rounded bg-white">
                @else
                    <div class="text-muted">La vista previa de la primera página convertida aparecerá aquí.</div>
                @endif
            </div>
        </div>

        <div class="card arrivals-card shadow-sm">
            <div class="card-body p-4">
                <h2 class="h5 mb-3">Texto OCR</h2>

                @if ($rawOcrText !== null)
                    <pre class="mb-0 p-3 border rounded bg-white" style="white-space: pre-wrap; min-height: 240px;">{{ $rawOcrText !== '' ? $rawOcrText : 'No se detectó texto útil en la imagen.' }}</pre>
                @else
                    <div class="text-muted">El texto OCR aparecerá aquí después de procesar un PDF.</div>
                @endif
            </div>
        </div>
    </div>
@endsection