<nav class="navbar-light bg-base-100 pb-0 pt-7 px-2 md:pl-5 d-lg-flex items-baseline mb-2">
    <div class="container-fluid flex items-baseline justify-between">
        <div class="flex items-center">
            <a class="text-2xl font-bold md:pl-4 md:mr-2 text-dark" href="{{ url('/') }}">MixPitch</a>
            <a class="nav-link pl-2 md:pl-10 pt-1" href="{{ route('projects.index') }}">Projects</a>
        </div>
        <div class="md:pr-20">
            @guest
            <livewire:auth-dropdown />

            <!-- <a class="nav-link d-inline pr-1 md:pr-5" href="{{ route('login') }}"><b>Login</b></a>
            <a class="nav-link d-inline" href="{{ route('register') }}">Register</a> -->
            @else
            <div x-data="{ open: false }" @click.away="open = false" class="relative">
                <!-- Trigger -->
                <button @click="open = !open" class="block">
                    {{ Auth::user()->name }}
                </button>

                <!-- Dropdown Body -->
                <div x-cloak x-show="open"
                    class="origin-top-right absolute z-50 right-0 mt-2 w-36 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5">
                    <div class="py-1" role="menu" aria-orientation="vertical" aria-labelledby="options-menu">
                        <a href="{{ route('dashboard') }}"
                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                            role="menuitem">Dashboard</a>
                        <div class="border-t border-gray-100"></div>
                        <a href="{{ route('logout') }}"
                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                            Logout
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                            @csrf
                        </form>
                    </div>
                </div>
            </div>

            @endguest
        </div>
    </div>
</nav>