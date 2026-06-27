@extends('plantillas.inicio')

@section('title', $expediente->nombre)

@section('menu')
    @include('menu-arrivals')
@endsection

@section('contenido')
    @php
        $identificacionUrl = filled($expediente->identificacion_path) ? asset('storage/' . $expediente->identificacion_path) : null;
        $identificacionExtension = filled($expediente->identificacion_path)
            ? strtolower(pathinfo($expediente->identificacion_path, PATHINFO_EXTENSION))
            : null;
        $identificacionEsImagen = in_array($identificacionExtension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
        $identificacionEsPdf = $identificacionExtension === 'pdf';
    @endphp

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">{{ $expediente->nombre }} {{ $expediente->apellido }}</h1>
            <p class="text-muted mb-0">Fecha de llegada: {{ optional($expediente->fecha_llegada)->format('d/m/Y') }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('expedientes.edit', $expediente->id) }}" class="btn btn-outline-secondary">Editar</a>
            @php $primerDocFirma = $expediente->documentos->where('path', '!=', null)->first(function($d) { return str_ends_with($d->path, '.pdf'); }) ?? $expediente->documentos->first(); @endphp
            @if($primerDocFirma)
            <button type="button" class="btn btn-outline-primary"
                data-bs-toggle="modal" data-bs-target="#firmarModal"
                data-firma-url="{{ route('expedientes.firma.store', $expediente->id) }}"
                data-documento-url="{{ asset('storage/' . $primerDocFirma->path) }}"
            >Firmar</button>
            @else
            <button type="button" class="btn btn-outline-primary" disabled>Firmar</button>
            @endif
            <form action="{{ route('expedientes.destroy', $expediente->id) }}" method="post" onsubmit="return confirm('¿Estás seguro de eliminar este registro?')">
                @csrf
                @method('DELETE')
                <input type="hidden" name="redirect_to" value="{{ route('database.index') }}">
                <button type="submit" class="btn btn-danger">Eliminar</button>
            </form>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-lg-6">
            <div class="card arrivals-card shadow-sm h-100">
                <div class="card-body d-flex flex-column gap-3">
                    <div class="row g-3">
                        <div class="col-12 col-sm-6">
                            <div class="small text-muted">Nombre</div>
                            <div class="fw-semibold">{{ $expediente->nombre }}</div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="small text-muted">Apellido</div>
                            <div class="fw-semibold">{{ $expediente->apellido }}</div>
                        </div>
                        <div class="col-12">
                            <div class="small text-muted">Fecha de llegada</div>
                            <div class="fw-semibold">{{ optional($expediente->fecha_llegada)->format('d/m/Y') }}</div>
                        </div>
                    </div>

                    <div>
                        <h2 class="h5 mb-1">Documento</h2>
                        <p class="text-muted mb-0">PDF combinado del registro y contrato.</p>
                    </div>

                    @if ($expediente->documentos->isNotEmpty())
                        <div class="d-flex flex-column gap-2 mt-auto">
                            @foreach ($expediente->documentos as $documento)
                                <div class="d-flex justify-content-between align-items-center border rounded px-3 py-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <span>{{ $documento->original_name ?: basename($documento->path) }}</span>
                                        @if($documento->signed_at)
                                            <span class="badge text-bg-success">Firmado</span>
                                        @else
                                            <span class="badge text-bg-warning">Subido</span>
                                        @endif
                                    </div>
                                    <a href="{{ asset('storage/' . $documento->path) }}" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-sm">
                                        Abrir
                                    </a>
                                </div>
                            @endforeach
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
                                style="max-height: 300px;"
                            >
                        </div>

                        <div>
                            <a href="{{ $identificacionUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary">
                                Abrir identificación
                            </a>
                        </div>
                    @elseif ($identificacionEsPdf)
                        <div>
                            <iframe
                                src="{{ $identificacionUrl }}"
                                title="Identificación de {{ $expediente->nombre }} {{ $expediente->apellido }}"
                                class="w-100 rounded border"
                                style="height: 400px;"
                            ></iframe>
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

{{-- We append extra HTML after @endsection so the modal renders outside the card --}}
@section('contenido')
@append

{{-- Firma Modal --}}
<div class="modal fade" id="firmarModal" tabindex="-1" aria-labelledby="firmarModalLabelShow" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="firmarModalLabelShow">Firmar tarjeta de registro</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div id="firma-alert-show" class="alert d-none mb-3"></div>
                <div class="mb-3">
                    <p class="text-muted small mb-1">El huésped debe leer el documento antes de firmar.</p>
                    <iframe id="firma-iframe-show" src="" style="width:100%;height:420px;border:1px solid #dee2e6;border-radius:4px;"></iframe>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="firma-leido-show">
                    <label class="form-check-label" for="firma-leido-show">
                        He leído y acepto el contenido de esta tarjeta de registro
                    </label>
                </div>
                <div class="mb-2">
                    <label class="form-label text-muted">Dibuja tu firma</label>
                    <canvas id="firma-canvas-show" class="border rounded d-block" style="width:100%;height:180px;opacity:0.4;pointer-events:none;"></canvas>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="firma-limpiar-show" disabled>Limpiar</button>
                <button type="button" class="btn btn-primary" id="firma-guardar-show" disabled>Firmar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('firmarModal');
    const iframe = document.getElementById('firma-iframe-show');
    const canvas = document.getElementById('firma-canvas-show');
    const checkbox = document.getElementById('firma-leido-show');
    const btnLimpiar = document.getElementById('firma-limpiar-show');
    const btnGuardar = document.getElementById('firma-guardar-show');
    const alertEl = document.getElementById('firma-alert-show');
    const firmaUrl = document.querySelector('[data-firma-url]')?.dataset.firmaUrl;
    let signaturePad = null;

    function initCanvas() {
        const rect = canvas.getBoundingClientRect();
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        canvas.width = Math.round(rect.width * ratio);
        canvas.height = Math.round(rect.height * ratio);
        canvas.getContext('2d').setTransform(ratio, 0, 0, ratio, 0, 0);
        signaturePad = new SignaturePad(canvas, { backgroundColor: 'rgba(255,255,255,0)' });
    }

    document.querySelector('[data-bs-target="#firmarModal"]')?.addEventListener('click', (e) => {
        iframe.src = e.currentTarget.dataset.documentoUrl;
        checkbox.checked = false;
        canvas.style.opacity = '0.4';
        canvas.style.pointerEvents = 'none';
        btnLimpiar.disabled = true;
        btnGuardar.disabled = true;
        alertEl.classList.add('d-none');
        if (signaturePad) signaturePad.clear();
    });

    modal.addEventListener('shown.bs.modal', () => {
        if (!signaturePad) initCanvas(); else signaturePad.clear();
    });

    modal.addEventListener('hidden.bs.modal', () => { iframe.src = ''; });

    checkbox.addEventListener('change', () => {
        const on = checkbox.checked;
        canvas.style.opacity = on ? '1' : '0.4';
        canvas.style.pointerEvents = on ? 'auto' : 'none';
        btnLimpiar.disabled = !on;
        btnGuardar.disabled = !on;
    });

    btnLimpiar.addEventListener('click', () => signaturePad && signaturePad.clear());

    btnGuardar.addEventListener('click', () => {
        if (!signaturePad || signaturePad.isEmpty()) {
            showAlert('danger', 'Por favor dibuja la firma antes de guardar.');
            return;
        }
        btnGuardar.disabled = true;
        btnGuardar.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Firmando...';
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        fetch(firmaUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify({ firma_base64: signaturePad.toDataURL('image/png') })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                modal.querySelector('[data-bs-dismiss="modal"]').click();
                window.location.reload();
            } else {
                showAlert('danger', data.error || 'Error desconocido.');
                btnGuardar.disabled = false;
                btnGuardar.textContent = 'Firmar';
            }
        })
        .catch(() => {
            showAlert('danger', 'Error de red. Intenta de nuevo.');
            btnGuardar.disabled = false;
            btnGuardar.textContent = 'Firmar';
        });
    });

    function showAlert(type, msg) {
        alertEl.className = 'alert alert-' + type;
        alertEl.textContent = msg;
    }
});
</script>