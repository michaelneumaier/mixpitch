<nav class="navbar-light bg-light pb-0 pt-7 pl-5 d-lg-flex items-baseline mb-2">
    <div class="container-fluid d-flex flex-row items-baseline justify-content-between">
        <div>
            <a class="navbar-brand d-inline text-dark" href="{{ url('/') }}">MixPitch</a>
            <a class="nav-link d-inline pl-2 md:pl-10 pt-1" href="{{ route('projects.index') }}">Projects</a>
        </div>
        <div class="navbar-item md:pr-20">
            @guest
            <a class="nav-link d-inline pr-5" href="{{ route('login') }}"><b>Login</b></a>
            <a class="nav-link d-inline" href="{{ route('register') }}">Register</a>
            @else
            <div x-data="{ open: false }" class="relative">
                <!-- Trigger -->
                <button @click="open = !open" class="block">
                    {{ Auth::user()->name }}
                </button>

                <!-- Dropdown Body -->
                <div x-show="open" @click.away="open = false"
                    class="absolute mt-2 py-2 rounded-md shadow-xl bg-white z-50">
                    <a href="{{ route('dashboard') }}"
                        class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Dashboard</a>
                    <div class="border-t border-gray-100"></div>
                    <a href="{{ route('logout') }}"
                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                        class="block px-4 py-2 text-gray-800 hover:bg-gray-100">
                        Logout
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                </div>
            </div>
            <div class="modal"></div>


            @endguest
        </div>
    </div>
</nav>