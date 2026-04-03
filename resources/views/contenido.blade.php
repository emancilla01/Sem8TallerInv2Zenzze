@extends('plantillas.inicio')

@section('title', 'Llegadas')

@section('menu')
    @include('menu-arrivals')
@endsection

@section('contenido')
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Llegadas</h1>
            <p class="text-muted mb-0">Gestiona las llegadas actuales y los documentos</p>
        </div>
        <a href="{{ route('arrivals.create') }}" class="btn btn-primary">Nueva llegada</a>
    </div>

    <div class="card arrivals-card shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('home') }}" method="get" role="search">
                <label for="guest-search" class="form-label">Buscar por nombre del huésped</label>
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
                            <th scope="col">Nombre del huésped</th>
                            <th scope="col">Fecha</th>
                            <th scope="col">Estado del documento</th>
                            <th scope="col">Estado de la identificación</th>
                            <th scope="col" class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($expedientes as $expediente)
                            <tr>
                                <td>{{ $expediente->nombre }}</td>
                                <td>{{ $expediente->created_at?->format('d/m/Y') ?? 'Sin fecha' }}</td>
                                <td>
                                    <span class="badge {{ filled($expediente->documento_path) ? 'text-bg-success' : 'text-bg-warning' }}">
                                        {{ filled($expediente->documento_path) ? 'Subido' : 'Faltante' }}
                                    </span>
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
                                                        {{ filled($expediente->documento_path) ? 'Reemplazar documento' : 'Subir documento' }}
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
                                                    <form action="{{ route('expedientes.destroy', $expediente->id) }}" method="post">
                                                        @csrf
                                                        @method('DELETE')
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
                                <td colspan="5" class="text-center py-4 text-muted">
                                    No hay llegadas registradas hoy.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
