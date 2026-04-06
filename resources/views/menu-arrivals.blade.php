<nav class="navbar navbar-expand navbar-dark arrivals-navbar">
    <div class="container">
        <a class="navbar-brand" href="{{ route('home') }}">Hotel Check-In</a>
        <ul class="navbar-nav flex-row flex-wrap gap-2 ms-auto">
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('home', 'expedientes.show', 'expedientes.edit') ? 'active' : '' }}" aria-current="{{ request()->routeIs('home', 'expedientes.show', 'expedientes.edit') ? 'page' : 'false' }}" href="{{ route('home') }}">Llegadas</a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('database.index') ? 'active' : '' }}" aria-current="{{ request()->routeIs('database.index') ? 'page' : 'false' }}" href="{{ route('database.index') }}">Base de datos</a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('arrivals.create', 'arrivals.batch.*') ? 'active' : '' }}" aria-current="{{ request()->routeIs('arrivals.create', 'arrivals.batch.*') ? 'page' : 'false' }}" href="{{ route('arrivals.create') }}">Agregar registro</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">Cerrar sesión</a>
            </li>
        </ul>
    </div>
</nav>