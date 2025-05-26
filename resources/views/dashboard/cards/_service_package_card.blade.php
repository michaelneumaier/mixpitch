{{-- resources/views/dashboard/cards/_service_package_card.blade.php --}}
@php
    // Assuming producers manage their packages
    $packageUrl = route('producer.services.packages.edit', $package);
    // Add logic to fetch order count if needed, e.g., loadMissing('orders')
    // $orderCount = $package->orders_count ?? $package->orders->count(); 
@endphp
<div class="mb-4 rounded-lg shadow-sm overflow-hidden border border-base-300 hover:shadow-md transition-all">
    <a href="{{ $packageUrl }}" class="block">
        <div class="flex flex-col md:flex-row">
            {{-- Package Image Placeholder --}}
            <div class="w-full md:w-40 h-40 bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center">
                 <i class="fas fa-box-open text-white text-4xl opacity-80"></i>
            </div>

            {{-- Package Info --}}
            <div class="p-4 flex-grow">
                <div class="flex flex-col md:flex-row md:items-start justify-between">
                    <div>
                        <span class="inline-block bg-indigo-100 text-indigo-800 text-xs font-semibold mr-2 px-2.5 py-0.5 rounded dark:bg-indigo-200 dark:text-indigo-800">Service Pkg</span>
                        <h4 class="text-lg font-semibold text-gray-800 inline">{{ $package->title }}</h4>
                        {{-- <div class="text-sm text-gray-500 mt-1">Managed by You</div> --}}
                    </div>
                     <span class="inline-flex mt-2 md:mt-0 px-3 py-1 rounded-full text-sm font-medium {{ $package->is_published ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                        {{ $package->is_published ? 'Published' : 'Draft' }}
                    </span>
                </div>
                
                {{-- Key Details --}}
                 <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-sm text-gray-600">
                    <div class="flex items-center">
                        <i class="fas fa-dollar-sign mr-1.5 text-green-500"></i>
                        <span class="font-medium">{{ Number::currency($package->price, $package->currency) }}</span>
                    </div>
                     @if($package->estimated_delivery_days)
                    <div class="flex items-center">
                        <i class="fas fa-clock mr-1.5 text-gray-500"></i>
                        <span>{{ $package->estimated_delivery_days }} Day Delivery</span>
                    </div>
                    @endif
                    <div class="flex items-center">
                        <i class="fas fa-redo mr-1.5 text-blue-500"></i>
                        <span>{{ $package->revisions_included }} Revisions</span>
                    </div>
                 </div>

                {{-- Stats --}}
                <div class="mt-4 flex flex-wrap items-center justify-between">
                    <div class="text-xs text-gray-500">
                        <span>Updated: {{ $package->updated_at->diffForHumans() }}</span>
                    </div>
                    <div class="mt-2 md:mt-0 flex gap-2">
                       {{-- Optionally show order count --}}
                       {{-- <div class="bg-gray-100 text-gray-700 text-xs px-2 py-1 rounded-full border border-gray-200 flex items-center">
                            <i class="fas fa-shopping-cart text-gray-400 mr-1"></i>
                            <span>{{ $orderCount }} {{ Str::plural('Order', $orderCount) }}</span>
                        </div> --}}
                    </div>
                </div>
            </div>
        </div>
    </a>
</div> 