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
            {{-- will test signature capture and document upload features here, as well as any other arrival-related functionalities we add in 
            the future for 1.3.  --}}
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
                                    <span class="badge {{ $expediente->documentos_count > 0 ? 'text-bg-success' : 'text-bg-warning' }}">
                                        {{ $expediente->documentos_count > 0 ? 'Subido' : 'Faltante' }}
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
@endsection
