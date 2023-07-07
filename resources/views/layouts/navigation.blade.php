<nav class="navbar navbar-light navbar-expand-lg bg-light pb-0 pt-7 pl-5 d-lg-flex align-items-center mb-2">
    <div class="container-fluid">
        <div class="d-lg-flex align-items-center">
            <a class="navbar-brand d-inline text-dark" href="{{ url('/') }}">MixPitch</a>
            <a class="nav-link d-inline pl-10 pt-1" href="{{ route('projects.index') }}">Projects</a>
        </div>


        <div class="d-flex flex-row">

            <div class="navbar-item pr-20">
                @guest
                <a class="nav-link d-inline pr-5" href="{{ route('login') }}"><b>Login</b></a>
                <a class="nav-link d-inline" href="{{ route('register') }}">Register</a>
                @else
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button"
                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        {{ Auth::user()->name }}
                    </a>
                    <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                        <a class="dropdown-item" href="{{ route('dashboard') }}">Dashboard</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="{{ route('logout') }}"
                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Logout</a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                            @csrf
                        </form>
                    </div>
                </div>
                @endguest
            </div>
        </div>
    </div>
</nav>