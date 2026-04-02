@extends('plantillas.inicio')

@section('title', 'Llegadas')

@section('menu')
    @include('menu-arrivals')
@endsection

@section('contenido')
    @php
        $arrivals = [
            [
                'id' => 1,
                'guest_name' => 'Emma Johnson',
                'date' => '2026-03-31',
                'document_status' => 'Subido',
                'id_status' => 'Faltante',
            ],
            [
                'id' => 2,
                'guest_name' => 'Liam Carter',
                'date' => '2026-04-01',
                'document_status' => 'Faltante',
                'id_status' => 'Subido',
            ],
            [
                'id' => 3,
                'guest_name' => 'Sophia Martinez',
                'date' => '2026-04-02',
                'document_status' => 'Subido',
                'id_status' => 'Subido',
            ],
            [
                'id' => 4,
                'guest_name' => 'Noah Thompson',
                'date' => '2026-04-03',
                'document_status' => 'Faltante',
                'id_status' => 'Faltante',
            ],
            [
                'id' => 5,
                'guest_name' => 'Olivia Davis',
                'date' => '2026-04-04',
                'document_status' => 'Subido',
                'id_status' => 'Faltante',
            ],
        ];
    @endphp

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Llegadas</h1>
            <p class="text-muted mb-0">Gestiona las llegadas actuales y los documentos</p>
        </div>
        <a href="#" class="btn btn-primary">Nueva llegada</a>
    </div>

    <div class="card arrivals-card shadow-sm mb-4">
        <div class="card-body">
            <form action="#" method="get" role="search">
                <label for="guest-search" class="form-label">Buscar por nombre del huésped</label>
                <input
                    type="search"
                    class="form-control"
                    id="guest-search"
                    name="guest_search"
                    placeholder="Ingrese el nombre del huésped"
                    autocomplete="off"
                >
            </form>
        </div>
    </div>

    <div class="card arrivals-card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0 align-middle arrivals-table">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Nombre del huésped</th>
                            <th scope="col">Fecha</th>
                            <th scope="col">Estado del documento</th>
                            <th scope="col">Estado de la identificación</th>
                            <th scope="col" class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($arrivals as $arrival)
                            <tr>
                                <td>{{ $arrival['guest_name'] }}</td>
                                <td>{{ $arrival['date'] }}</td>
                                <td>
                                    <span class="badge {{ $arrival['document_status'] === 'Subido' ? 'text-bg-success' : 'text-bg-warning' }}">
                                        {{ $arrival['document_status'] }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge {{ $arrival['id_status'] === 'Subido' ? 'text-bg-success' : 'text-bg-warning' }}">
                                        {{ $arrival['id_status'] }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end">
                                        <div class="btn-group">
                                            <a href="#" class="btn btn-primary btn-sm">Ver</a>
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
                                                    <a class="dropdown-item" href="#">
                                                        {{ $arrival['document_status'] === 'Subido' ? 'Reemplazar documento' : 'Subir documento' }}
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="#">
                                                        {{ $arrival['id_status'] === 'Subido' ? 'Reemplazar ID' : 'Subir ID' }}
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item" href="#">Editar</a></li>
                                                <li><a class="dropdown-item text-danger" href="#">Eliminar</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
