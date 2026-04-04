@extends('plantillas.inicio')

@section('title', 'Prueba de extracción PDF')

@section('menu')
    @include('menu-arrivals')
@endsection

@section('contenido')
    <div class="d-flex flex-column gap-4">
        <div>
            <h1 class="h2 mb-1">Prueba de extracción PDF</h1>
            <p class="text-muted mb-0">Carga una tarjeta de registro en PDF para visualizar el texto extraído en crudo.</p>
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
                <form action="{{ route('pdf-extraction.store') }}" method="post" enctype="multipart/form-data" class="row g-4">
                    @csrf

                    <div class="col-12">
                        <label for="register_card_pdf" class="form-label">Register card PDF</label>
                        <input
                            type="file"
                            class="arrivals-upload-input @error('register_card_pdf') is-invalid @enderror"
                            id="register_card_pdf"
                            name="register_card_pdf"
                            accept=".pdf,application/pdf"
                            required
                        >
                        <label for="register_card_pdf" class="arrivals-upload-box @error('register_card_pdf') is-invalid @enderror" data-upload-box tabindex="0" role="button">
                            <span class="arrivals-upload-title">Arrastra el archivo aquí</span>
                            <span class="text-muted small">o haz clic para seleccionar</span>
                            <span class="arrivals-upload-filename text-muted small" data-upload-filename>Ningún archivo seleccionado</span>
                        </label>
                        <div class="form-text">Solo se usa extracción de texto desde el PDF. No se aplica OCR.</div>
                        @error('register_card_pdf')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">Extraer texto</button>
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
                    <div class="text-muted">Los valores parseados aparecerán aquí después de cargar un PDF.</div>
                @endif
            </div>
        </div>

        <div class="card arrivals-card shadow-sm">
            <div class="card-body p-4">
                <h2 class="h5 mb-3">Texto extraído</h2>

                @if ($rawText !== null)
                    <pre class="mb-0 p-3 border rounded bg-white" style="white-space: pre-wrap; min-height: 240px;">{{ $rawText !== '' ? $rawText : 'No se encontró texto legible en el PDF.' }}</pre>
                @else
                    <div class="text-muted">El texto extraído aparecerá aquí después de cargar un PDF.</div>
                @endif
            </div>
        </div>

        @if ($parserDebug !== null)
            <div class="card arrivals-card shadow-sm">
                <div class="card-body p-4 d-flex flex-column gap-4">
                    <div>
                        <h2 class="h5 mb-3">Debug del parser</h2>
                        <p class="text-muted mb-0">Esto muestra cómo el parser está leyendo el texto extraído para poder ajustar el formato real del PDF.</p>
                    </div>

                    <div>
                        <div class="small text-muted mb-2">Líneas normalizadas</div>
                        <pre class="mb-0 p-3 border rounded bg-white" style="white-space: pre-wrap; max-height: 320px; overflow: auto;">@foreach ($parserDebug['prepared_lines'] as $index => $line){{ str_pad((string) ($index + 1), 3, ' ', STR_PAD_LEFT) }}. {{ $line }}
@endforeach</pre>
                    </div>

                    <div class="row g-3">
                        <div class="col-lg-4">
                            <div class="small text-muted mb-2">Bloque de etiquetas</div>
                            <pre class="mb-0 p-3 border rounded bg-white" style="white-space: pre-wrap; min-height: 180px;">{{ $parserDebug['sequential_match'] !== null ? implode("\n", $parserDebug['sequential_match']['labels']) : 'No detectado' }}</pre>
                        </div>
                        <div class="col-lg-4">
                            <div class="small text-muted mb-2">Valores alineados</div>
                            <pre class="mb-0 p-3 border rounded bg-white" style="white-space: pre-wrap; min-height: 180px;">{{ $parserDebug['sequential_match'] !== null ? implode("\n", $parserDebug['sequential_match']['values']) : 'No detectado' }}</pre>
                        </div>
                        <div class="col-lg-4">
                            <div class="small text-muted mb-2">Mapa etiqueta → valor</div>
                            <pre class="mb-0 p-3 border rounded bg-white" style="white-space: pre-wrap; min-height: 180px;">@if ($parserDebug['sequential_match'] !== null)@foreach ($parserDebug['sequential_match']['mapped_values'] as $label => $value){{ $label }} => {{ $value }}
@endforeach @else No detectado @endif</pre>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection