@extends('plantillas.inicio')

@section('title', 'Base de datos')

@section('menu')
    @include('menu-arrivals')
@endsection

@section('contenido')
    @php
        $nombreDirection = $sort === 'nombre' && $direction === 'asc' ? 'desc' : 'asc';
        $apellidoDirection = $sort === 'apellido' && $direction === 'asc' ? 'desc' : 'asc';
        $fechaLlegadaDirection = $sort === 'fecha_llegada' && $direction === 'asc' ? 'desc' : 'asc';
        $nombreIndicator = $sort === 'nombre' ? ($direction === 'asc' ? '↑' : '↓') : '';
        $apellidoIndicator = $sort === 'apellido' ? ($direction === 'asc' ? '↑' : '↓') : '';
        $fechaLlegadaIndicator = $sort === 'fecha_llegada' ? ($direction === 'asc' ? '↑' : '↓') : '';
    @endphp

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Base de datos</h1>
            <p class="text-muted mb-0">Consulta y administra el historial de expedientes</p>
        </div>
    </div>

    <div class="card arrivals-card shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('database.index') }}" method="get" role="search">
                @if ($sort)
                    <input type="hidden" name="sort" value="{{ $sort }}">
                    <input type="hidden" name="direction" value="{{ $direction }}">
                @endif

                <label for="archive-search" class="form-label">Buscar por nombre o apellido del huésped</label>
                <input
                    type="search"
                    class="form-control"
                    id="archive-search"
                    name="search"
                    placeholder="Ingrese el nombre o apellido del huésped"
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
                                    href="{{ route('database.index', array_filter(['search' => request('search'), 'sort' => 'nombre', 'direction' => $nombreDirection])) }}"
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
                                    href="{{ route('database.index', array_filter(['search' => request('search'), 'sort' => 'apellido', 'direction' => $apellidoDirection])) }}"
                                    class="link-body-emphasis text-decoration-none d-inline-flex align-items-center gap-1"
                                >
                                    <span>Apellido</span>
                                    @if ($apellidoIndicator)
                                        <span aria-hidden="true">{{ $apellidoIndicator }}</span>
                                    @endif
                                </a>
                            </th>
                            <th scope="col">
                                <a
                                    href="{{ route('database.index', array_filter(['search' => request('search'), 'sort' => 'fecha_llegada', 'direction' => $fechaLlegadaDirection])) }}"
                                    class="link-body-emphasis text-decoration-none d-inline-flex align-items-center gap-1"
                                >
                                    <span>Fecha de llegada</span>
                                    @if ($fechaLlegadaIndicator)
                                        <span aria-hidden="true">{{ $fechaLlegadaIndicator }}</span>
                                    @endif
                                </a>
                            </th>
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
                                    <span class="badge {{ filled($expediente->identificacion_path) ? 'text-bg-success' : 'text-bg-danger' }}">
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
                                    No hay expedientes registrados.
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

@endsection