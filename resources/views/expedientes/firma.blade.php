@extends('plantillas.inicio')

@section('title', 'Firmar - ' . ($expediente->nombre ?? 'Expediente'))

@section('menu')
    @include('menu-arrivals')
@endsection

@section('contenido')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">Firmar: {{ $expediente->nombre }} {{ $expediente->apellido }}</h1>
            <p class="text-muted">Prueba de captura de firma y estampado sobre PDF.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="mb-3">
                <strong>Documento:</strong>
                @if($documento)
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <div>{{ $documento->original_name ?: basename($documento->path) }}</div>
                        <a href="{{ asset('storage/' . $documento->path) }}" target="_blank" class="btn btn-outline-primary btn-sm">Abrir original</a>
                    </div>
                @else
                    <div class="text-muted">No hay documentos asociados a este expediente.</div>
                @endif
            </div>

            <form id="firma-form" method="post" action="{{ route('expedientes.firma.store', $expediente->id) }}">
                @csrf
                <div class="mb-2">
                    <label class="form-label">Dibuja tu firma</label>
                    <canvas id="signature-pad" class="border rounded" style="width:100%;height:200px;"></canvas>
                </div>

                <input type="hidden" name="firma_base64" id="firma_base64">

                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-secondary" id="clear-signature">Limpiar firma</button>
                    <button type="submit" class="btn btn-primary">Guardar firma</button>
                </div>
            </form>

            @if(session('signed_path'))
                <div class="mt-3">
                    <a href="{{ asset('storage/' . session('signed_path')) }}" target="_blank" class="btn btn-success">Abrir PDF firmado</a>
                </div>
            @endif
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
        <script>
document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('signature-pad');
    const form = document.getElementById('firma-form');
    const clearBtn = document.getElementById('clear-signature');
    const hiddenInput = document.getElementById('firma_base64');
    let signaturePad;

    function resizeCanvas() {
        const rect = canvas.getBoundingClientRect();
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        canvas.width = Math.round(rect.width * ratio);
        canvas.height = Math.round(rect.height * ratio);
        const ctx = canvas.getContext('2d');
        ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
        if (signaturePad && signaturePad.toData) {
            const data = signaturePad.toData();
            signaturePad.clear();
            signaturePad.fromData(data);
        }
    }

    function init() {
        resizeCanvas();
        signaturePad = new SignaturePad(canvas, { backgroundColor: 'rgba(255,255,255,0)' });
    }

    init();
    window.addEventListener('resize', () => {
        clearTimeout(window._sigResizeTimeout);
        window._sigResizeTimeout = setTimeout(() => {
            resizeCanvas();
        }, 50);
    });

    clearBtn.addEventListener('click', () => signaturePad.clear());

    form.addEventListener('submit', (e) => {
        if (!signaturePad || signaturePad.isEmpty()) {
            e.preventDefault();
            alert('Por favor dibuja la firma antes de guardar.');
            return;
        }
        hiddenInput.value = signaturePad.toDataURL('image/png');
    });
});
    </script>

@endsection

