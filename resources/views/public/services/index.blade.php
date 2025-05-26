<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Browse Service Packages') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

             {{-- Filter Form --}}
            <div class="mb-6 bg-white dark:bg-gray-800 shadow-md rounded-lg p-4">
                <form method="GET" action="{{ route('public.services.index') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                    <div>
                        <label for="q" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Search</label>
                        <input type="text" name="q" id="q" value="{{ $filters['q'] ?? '' }}" placeholder="Keyword, producer..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                             <label for="price_min" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Min Price</label>
                             <input type="number" name="price_min" id="price_min" value="{{ $filters['price_min'] ?? '' }}" placeholder="$" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                         <div>
                             <label for="price_max" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Max Price</label>
                             <input type="number" name="price_max" id="price_max" value="{{ $filters['price_max'] ?? '' }}" placeholder="$" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                    </div>
                    <div>
                         <label for="delivery_time_max" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Max Delivery (Days)</label>
                         <input type="number" name="delivery_time_max" id="delivery_time_max" value="{{ $filters['delivery_time_max'] ?? '' }}" placeholder="e.g., 7" min="1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                    <div class="flex space-x-2">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 active:bg-blue-700 focus:outline-none focus:border-blue-700 focus:ring focus:ring-blue-200 disabled:opacity-25 transition">Filter</button>
                        <a href="{{ route('public.services.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 active:bg-gray-400 focus:outline-none focus:border-gray-400 focus:ring focus:ring-gray-200 disabled:opacity-25 transition">Clear</a>
                    </div>
                </form>
            </div>
            {{-- End Filter Form --}}

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6 lg:p-8 bg-white dark:bg-gray-800 dark:bg-gradient-to-bl dark:from-gray-700/50 dark:via-transparent">
                    
                    <h1 class="text-2xl font-medium text-gray-900 dark:text-white mb-6">
                        {{ __('Available Services') }}
                    </h1>

                    @if ($packages->isEmpty())
                        <div class="text-center py-12">
                             <i class="fas fa-search text-gray-400 text-4xl mb-3"></i>
                             <p class="text-gray-500 dark:text-gray-400">No service packages match your criteria.</p>
                             <a href="{{ route('public.services.index') }}" class="mt-4 inline-block text-blue-500 hover:text-blue-700 underline">Clear filters</a>
                        </div>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach ($packages as $package)
                                <div class="flex flex-col bg-gray-50 dark:bg-gray-900 rounded-lg shadow-md overflow-hidden transform transition duration-500 hover:scale-105">
                                    {{-- Optional Image Placeholder --}}
                                    {{-- <div class="h-48 bg-gray-200 dark:bg-gray-700"></div> --}}
                                    
                                    <div class="p-4 flex flex-col flex-grow">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ $package->title }}</h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">by <x-user-link :user="$package->user" /> </p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3 flex-grow">{{ Str::limit($package->description, 100) }}</p>
                                        
                                        <div class="flex justify-between items-center mt-auto pt-3 border-t border-gray-200 dark:border-gray-700">
                                            <span class="text-lg font-bold text-gray-900 dark:text-white">{{ Number::currency($package->price, $package->currency) }}</span>
                                            {{-- Link to detail page (implement later) --}}
                                            {{-- <a href="{{ route('public.services.show', $package->slug) }}" class="inline-flex items-center px-3 py-1 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:outline-none focus:border-indigo-700 focus:ring focus:ring-indigo-200 active:bg-indigo-700 disabled:opacity-25 transition">Details</a> --}}
                                            
                                            {{-- Order Button Form --}}
                                            @auth
                                                {{-- Prevent producer from ordering their own package --}}
                                                @if(Auth::id() !== $package->user_id)
                                                    <form action="{{ route('orders.store', $package) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="inline-flex items-center px-3 py-1 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 focus:outline-none focus:border-green-700 focus:ring focus:ring-green-200 active:bg-green-700 disabled:opacity-25 transition">Order Now</button>
                                                    </form>
                                                @else
                                                    <span class="inline-flex items-center px-3 py-1 border border-transparent rounded-md font-semibold text-xs text-gray-500 uppercase tracking-widest bg-gray-200 dark:bg-gray-700 cursor-not-allowed">Your Package</span>
                                                @endif
                                            @else
                                                {{-- Link to login if not authenticated --}}
                                                <a href="{{ route('login') }}?redirect={{ url()->current() }}" class="inline-flex items-center px-3 py-1 bg-gray-400 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-500 focus:outline-none focus:border-gray-700 focus:ring focus:ring-gray-200 active:bg-gray-700 disabled:opacity-25 transition">Login to Order</a>
                                            @endauth
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-8">
                            {{ $packages->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 