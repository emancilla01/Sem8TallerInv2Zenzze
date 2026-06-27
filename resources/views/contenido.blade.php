@extends('plantillas.inicio')

@section('title', 'Llegadas')

@section('menu')
    @include('menu-arrivals')
@endsection

@section('contenido')
    @php
        $nombreDirection = $sort === 'nombre' && $direction === 'asc' ? 'desc' : 'asc';
        $apellidoDirection = $sort === 'apellido' && $direction === 'asc' ? 'desc' : 'asc';
        $nombreIndicator = $sort === 'nombre'
            ? ($direction === 'asc' ? '↑' : '↓')
            : '';
        $apellidoIndicator = $sort === 'apellido'
            ? ($direction === 'asc' ? '↑' : '↓')
            : '';
    @endphp

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <!-- contains latest updates from 1.2 -->
            {{-- will test signature capture and document upload features here, as well as any other arrival-related functionalities we add 
            in the future for 1.3.  --}}
            {{-- zennze2 and testing has the text parsing testing that didnt work.  --}}
            <h1 class="h2 mb-1">Llegadas TEST2</h1>
            <p class="text-muted mb-0">Gestiona las llegadas actuales y los documentos</p>
        </div>
        <a href="{{ route('arrivals.create') }}" class="btn btn-primary">Agregar registro</a>
    </div>

    <div class="card arrivals-card shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('home') }}" method="get" role="search">
                @if ($sort)
                    <input type="hidden" name="sort" value="{{ $sort }}">
                    <input type="hidden" name="direction" value="{{ $direction }}">
                @endif

                <label for="guest-search" class="form-label">Buscar por nombre o apellido del huésped</label>
                <input
                    type="search"
                    class="form-control"
                    id="guest-search"
                    name="search"
                    placeholder="Ingrese el nombre del huésped"
                    value="{{ request('search') }}"
                    autocomplete="off"
                >
            </form>
        </div>
    </div>

    <div class="card arrivals-card shadow-sm">
        <div class="card-body p-0">
            <div class="arrivals-table-wrapper">
                <table class="table table-striped table-hover mb-0 align-middle arrivals-table">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">
                                <a
                                    href="{{ route('home', array_filter(['search' => request('search'), 'sort' => 'nombre', 'direction' => $nombreDirection])) }}"
                                    class="link-body-emphasis text-decoration-none d-inline-flex align-items-center gap-1"
                                >
                                    <span>Nombre</span>
                                    @if ($nombreIndicator)
                                        <span aria-hidden="true">{{ $nombreIndicator }}</span>
                                    @endif
                                </a>
                            </th>
                            <th scope="col">
                                <a
                                    href="{{ route('home', array_filter(['search' => request('search'), 'sort' => 'apellido', 'direction' => $apellidoDirection])) }}"
                                    class="link-body-emphasis text-decoration-none d-inline-flex align-items-center gap-1"
                                >
                                    <span>Apellido</span>
                                    @if ($apellidoIndicator)
                                        <span aria-hidden="true">{{ $apellidoIndicator }}</span>
                                    @endif
                                </a>
                            </th>
                            <th scope="col">Fecha de llegada</th>
                            <th scope="col">Estado del documento</th>
                            <th scope="col">Estado de la identificación</th>
                            <th scope="col" class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($expedientes as $expediente)
                            <tr>
                                <td>{{ $expediente->nombre }}</td>
                                <td>{{ $expediente->apellido }}</td>
                                <td>{{ optional($expediente->fecha_llegada)->format('d/m/Y') }}</td>
                                <td>
                                    @if ($expediente->signed_documentos_count > 0)
                                        <span class="badge text-bg-success">Firmado</span>
                                    @elseif ($expediente->documentos_count > 0)
                                        <span class="badge text-bg-warning">Subido</span>
                                    @else
                                        <span class="badge text-bg-danger">Faltante</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ filled($expediente->identificacion_path) ? 'text-bg-success' : 'text-bg-warning' }}">
                                        {{ filled($expediente->identificacion_path) ? 'Subido' : 'Faltante' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end">
                                        <div class="btn-group">
                                            <a href="{{ route('expedientes.show', $expediente->id) }}" class="btn btn-primary btn-sm">Ver</a>
                                            @php $primerDoc = $expediente->documentos->first(); @endphp
                                            <button
                                                type="button"
                                                class="btn btn-outline-primary btn-sm btn-firmar"
                                                @if($primerDoc)
                                                    data-firma-url="{{ route('expedientes.firma.store', $expediente->id) }}"
                                                    data-documento-url="{{ asset('storage/' . $primerDoc->path) }}"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#firmarModal"
                                                @else
                                                    disabled
                                                    title="Sin documento"
                                                @endif
                                            >Firmar</button>
                                            <button
                                                type="button"
                                                class="btn btn-outline-secondary btn-sm dropdown-toggle"
                                                data-bs-toggle="dropdown"
                                                aria-expanded="false"
                                            >
                                                Más
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('expedientes.edit', $expediente->id) }}">
                                                        {{ $expediente->documentos_count > 0 ? 'Agregar documento' : 'Subir documento' }}
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('expedientes.edit', $expediente->id) }}">
                                                        {{ filled($expediente->identificacion_path) ? 'Reemplazar ID' : 'Subir ID' }}
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item" href="{{ route('expedientes.edit', $expediente->id) }}">Editar</a></li>
                                                <li>
                                                    <form action="{{ route('expedientes.destroy', $expediente->id) }}" method="post" onsubmit="return confirm('¿Estás seguro de eliminar este registro?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">
                                                        <button type="submit" class="dropdown-item text-danger">Eliminar</button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    No hay llegadas registradas hoy.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($expedientes->hasPages())
            <div class="card-footer bg-transparent border-top">
                {{ $expedientes->links() }}
            </div>
        @endif
    </div>

{{-- Firma Modal --}}
<div class="modal fade" id="firmarModal" tabindex="-1" aria-labelledby="firmarModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="firmarModalLabel">Firmar tarjeta de registro</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div id="firma-alert" class="alert d-none mb-3"></div>
                <div class="mb-3">
                    <p class="text-muted small mb-1">El huésped debe leer el documento antes de firmar.</p>
                    <iframe id="firma-iframe" src="" style="width:100%;height:420px;border:1px solid #dee2e6;border-radius:4px;"></iframe>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="firma-leido">
                    <label class="form-check-label" for="firma-leido">He leído y acepto el contenido de esta tarjeta de registro</label>
                </div>
                <div class="mb-2">
                    <label class="form-label text-muted">Dibuja tu firma</label>
                    <canvas id="firma-canvas" class="border rounded d-block" style="width:100%;height:180px;opacity:0.4;pointer-events:none;"></canvas>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="firma-limpiar" disabled>Limpiar</button>
                <button type="button" class="btn btn-primary" id="firma-guardar" disabled>Firmar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('firmarModal');
    const iframe = document.getElementById('firma-iframe');
    const canvas = document.getElementById('firma-canvas');
    const checkbox = document.getElementById('firma-leido');
    const btnLimpiar = document.getElementById('firma-limpiar');
    const btnGuardar = document.getElementById('firma-guardar');
    const alertEl = document.getElementById('firma-alert');
    let signaturePad = null;
    let firmaUrl = null;

    function initCanvas() {
        const rect = canvas.getBoundingClientRect();
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        canvas.width = Math.round(rect.width * ratio);
        canvas.height = Math.round(rect.height * ratio);
        canvas.getContext('2d').setTransform(ratio, 0, 0, ratio, 0, 0);
        signaturePad = new SignaturePad(canvas, { backgroundColor: 'rgba(255,255,255,0)' });
    }

    document.querySelectorAll('.btn-firmar').forEach(btn => {
        btn.addEventListener('click', () => {
            firmaUrl = btn.dataset.firmaUrl;
            iframe.src = btn.dataset.documentoUrl;
            checkbox.checked = false;
            canvas.style.opacity = '0.4';
            canvas.style.pointerEvents = 'none';
            btnLimpiar.disabled = true;
            btnGuardar.disabled = true;
            alertEl.classList.add('d-none');
            alertEl.textContent = '';
            if (signaturePad) signaturePad.clear();
        });
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
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        fetch(firmaUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify({ firma_base64: signaturePad.toDataURL('image/png') })
        })
        .then(async r => {
            const text = await r.text();
            let data;
            try { data = JSON.parse(text); } catch {
                showAlert('danger', 'Error del servidor (HTTP ' + r.status + ').');
                btnGuardar.disabled = false;
                btnGuardar.textContent = 'Firmar';
                return;
            }
            if (data.success) {
                modal.querySelector('[data-bs-dismiss="modal"]').click();
                window.location.reload();
            } else {
                showAlert('danger', data.error || 'Error desconocido.');
                btnGuardar.disabled = false;
                btnGuardar.textContent = 'Firmar';
            }
        })
        .catch(err => {
            showAlert('danger', 'Error de red: ' + (err.message || 'Intenta de nuevo.'));
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
@endsection
