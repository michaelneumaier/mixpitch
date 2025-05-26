<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $package->title }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-3 gap-6">

            {{-- Main Content --}}
            <div class="md:col-span-2 bg-white dark:bg-gray-800 shadow-xl sm:rounded-lg overflow-hidden">
                {{-- Optional Image Placeholder --}}
                {{-- <div class="h-64 bg-gray-200 dark:bg-gray-700"></div> --}}
                
                <div class="p-6 lg:p-8">
                    <h1 class="text-2xl font-medium text-gray-900 dark:text-white mb-4">
                        {{ $package->title }}
                    </h1>
                    
                    @if($package->description)
                        <div class="prose dark:prose-invert max-w-none mb-6">
                            {!! nl2br(e($package->description)) !!}
                        </div>
                    @endif

                    @if($package->deliverables)
                        <h3 class="text-lg font-semibold mb-2 dark:text-white">What you'll get:</h3>
                        <div class="prose dark:prose-invert max-w-none mb-6">
                             {!! nl2br(e($package->deliverables)) !!}
                        </div>
                    @endif

                     @if($package->requirements_prompt)
                        <h3 class="text-lg font-semibold mb-2 dark:text-white">Requirements:</h3>
                        <div class="prose dark:prose-invert max-w-none mb-6">
                             {!! nl2br(e($package->requirements_prompt)) !!}
                        </div>
                    @endif

                </div>
            </div>

            {{-- Sidebar --}}
            <div class="md:col-span-1">
                <div class="bg-white dark:bg-gray-800 shadow-xl sm:rounded-lg p-6 space-y-4">
                    <div class="text-center">
                        <span class="text-3xl font-bold text-gray-900 dark:text-white">{{ Number::currency($package->price, $package->currency) }}</span>
                    </div>

                    <div class="text-sm text-gray-600 dark:text-gray-400 space-y-2">
                         @if($package->estimated_delivery_days)
                        <div class="flex items-center justify-between">
                            <span class="flex items-center"><i class="fas fa-clock mr-2"></i> Delivery Time</span>
                            <span class="font-medium">{{ $package->estimated_delivery_days }} Day{{ $package->estimated_delivery_days > 1 ? 's' : '' }}</span>
                        </div>
                        @endif
                        <div class="flex items-center justify-between">
                            <span class="flex items-center"><i class="fas fa-redo mr-2"></i> Revisions Included</span>
                            <span class="font-medium">{{ $package->revisions_included }}</span>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                         @auth
                            @if(Auth::id() !== $package->user_id)
                                <form action="{{ route('orders.store', $package) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 focus:outline-none focus:border-green-700 focus:ring focus:ring-green-200 active:bg-green-700 disabled:opacity-25 transition">
                                        <i class="fas fa-shopping-cart mr-2"></i> Order Now
                                    </button>
                                </form>
                            @else
                                 <span class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs text-gray-500 uppercase tracking-widest bg-gray-200 dark:bg-gray-700 cursor-not-allowed">This is Your Package</span>
                                 <a href="{{ route('producer.services.packages.edit', $package) }}" class="mt-2 w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-100 focus:outline-none focus:border-gray-400 focus:ring focus:ring-gray-200 active:bg-gray-200 disabled:opacity-25 transition">Edit Package</a>
                            @endif
                        @else
                            <a href="{{ route('login') }}?redirect={{ url()->current() }}" class="w-full inline-flex justify-center items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 focus:outline-none focus:border-blue-700 focus:ring focus:ring-blue-200 active:bg-blue-700 disabled:opacity-25 transition">
                                Login to Order
                            </a>
                        @endauth
                    </div>

                    {{-- Producer Info --}}
                    <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                         <h4 class="text-md font-semibold mb-2 dark:text-white">About the Seller</h4>
                         <div class="flex items-center space-x-3">
                             <img class="h-12 w-12 rounded-full object-cover" src="{{ $package->user->profile_photo_url }}" alt="{{ $package->user->name }}">
                             <div>
                                 <div class="text-sm font-medium text-gray-900 dark:text-white"><x-user-link :user="$package->user" /></div>
                                 {{-- Add rating or other info here if available --}}
                             </div>
                         </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</x-app-layout> 