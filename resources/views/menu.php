<nav
    class="navbar navbar-expand-sm navbar-dark bg-primary"
   >
    <a class="navbar-brand d-flex align-items-center gap-2" href="{{ url('/dashboard') }}">
        <img src="{{ asset('logo.png') }}" alt="Logo" style="height:56px;" />        
    </a>
    
    <div class="collapse navbar-collapse" id="collapsibleNavId">
        <ul class="navbar-nav me-auto mt-2 mt-lg-0">
            <li class="nav-item">
                <a class="nav-link active" href="{{ url('/categorias') }}" aria-current="page"
                    >Categorias <span class="visually-hidden">(current)</span></a
                >
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="{{ url('/personal') }}" aria-current="page"
                    >Personal <span class="visually-hidden">(current)</span></a
                >
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="{{ url('/periodos') }}" aria-current="page"
                    >Periodos <span class="visually-hidden">(current)</span></a
                >
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="{{ url('/carreras') }}" aria-current="page"
                    >Carreras <span class="visually-hidden">(current)</span></a
                >
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="{{ url('/materias') }}" aria-current="page"
                    >Materias <span class="visually-hidden">(current)</span></a
                >
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="{{ url('/grupos') }}" aria-current="page"
                    >Grupos <span class="visually-hidden">(current)</span></a
                >
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="{{ url('/espaciosdetrabajo') }}" aria-current="page"
                    >Espacios de Trabajo <span class="visually-hidden">(current)</span></a
                >
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="{{ url('/software') }}" aria-current="page"
                    >Software <span class="visually-hidden">(current)</span></a
                >
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="{{ url('/entradas') }}" aria-current="page"
                    >Entradas <span class="visually-hidden">(current)</span></a
                >
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="{{ route('salidas.index') }}" aria-current="page"
                    >Salidas <span class="visually-hidden">(current)</span></a
                >
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="{{ route('ecm_equcommob.index') }}" aria-current="page"
                    >ECM - Inventario <span class="visually-hidden">(current)</span></a
                >
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="consultasDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    Consultas al inventario
                </a>
                <ul class="dropdown-menu" aria-labelledby="consultasDropdown">
                    <li><a class="dropdown-item" href="{{ route('consultas.categorias') }}">Por Categoría</a></li>
                    <li><a class="dropdown-item" href="{{ route('consultas.personal') }}">Por Personal</a></li>
                    <li><a class="dropdown-item" href="{{ route('consultas.espacios') }}">Por Espacios de Trabajo</a></li>
                    <li><a class="dropdown-item" href="{{ route('consultas.software_equipo') }}">Software por Equipo</a></li>
                    <li><a class="dropdown-item" href="{{ route('consultas.software_instalado') }}">Por Software instalado</a></li>
                    <li><a class="dropdown-item" href="{{ route('consultas.grupos_software') }}">Por Grupos que solicitaron el Software</a></li>
                    <li><a class="dropdown-item" href="{{ route('consultas.carreras_software') }}">Por Carreras que solicitaron el Software</a></li>
                </ul>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="{{ url('/consultas-vue') }}" aria-current="page"
                    >Consultas con VUE API‑Rest <span class="visually-hidden">(current)</span></a
                >
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="logout" aria-current="page"
                    >Logout <span class="visually-hidden">(current)</span></a
                >
            </li>
            
        </ul>
        {{-- <form class="d-flex my-2 my-lg-0">
            <input
                class="form-control me-sm-2"
                type="text"
                placeholder="Buscar nombre"
            />
            <button class="btn btn-outline-success my-2 my-sm-0" type="submit">
                Buscar
            </button>
        </form> --}}
    </div>
</nav>