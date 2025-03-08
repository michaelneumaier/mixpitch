<nav class="navbar-light bg-base-100 pb-0 pt-7 px-2 md:pl-5 d-lg-flex items-baseline mb-2">
    <div class="container-fluid flex items-baseline justify-between">
        <div class="flex items-center">
            <a class="text-2xl font-bold md:pl-4 md:mr-2 text-dark" href="{{ url('/') }}">MixPitch</a>
            <a class="nav-link pl-2 md:pl-10" href="{{ route('projects.index') }}">Projects</a>
            <a class="nav-link pl-2 md:pl-10" href="{{ route('pricing') }}">Pricing</a>
            <a class="nav-link pl-2 md:pl-10" href="{{ route('about') }}">About</a>
        </div>
        <div class="md:pr-10 flex items-center space-x-4">
            @auth
                <livewire:notification-list />
            @endauth
            <livewire:auth-dropdown />
        </div>
    </div>
</nav>