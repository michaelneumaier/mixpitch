{{-- resources/views/dashboard/cards/_service_package_card.blade.php --}}
@php
    $packageUrl = route('producer.services.packages.show', $package);
    $statusColor = match($package->status ?? 'active') {
        'active' => 'from-green-100 to-emerald-100 text-green-800 border-green-200/50',
        'inactive' => 'from-gray-100 to-gray-200 text-gray-800 border-gray-200/50',
        'draft' => 'from-yellow-100 to-amber-100 text-yellow-800 border-yellow-200/50',
        default => 'from-blue-100 to-indigo-100 text-blue-800 border-blue-200/50'
    };
    $statusIcon = match($package->status ?? 'active') {
        'active' => 'fa-check-circle',
        'inactive' => 'fa-pause-circle',
        'draft' => 'fa-edit',
        default => 'fa-box'
    };
@endphp

<div class="group relative bg-white/95 backdrop-blur-sm rounded-2xl border border-white/20 shadow-xl hover:shadow-2xl transition-all duration-300 overflow-hidden">
    <!-- Gradient Border Effect -->
    <div class="absolute inset-0 bg-gradient-to-r from-amber-500/20 via-orange-500/20 to-yellow-500/20 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
    
    <a href="{{ $packageUrl }}" class="relative block m-0.5 bg-white/95 backdrop-blur-sm rounded-2xl overflow-hidden">
        <div class="flex flex-col lg:flex-row">
            {{-- Enhanced Service Package Image --}}
            <div class="relative lg:w-64 h-48 lg:h-auto bg-gradient-to-br from-gray-100 to-gray-200 overflow-hidden">
                @if($package->image_path)
                    <img src="{{ $package->imageUrl }}" 
                         alt="{{ $package->name }}"
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                @else
                    <div class="w-full h-full bg-gradient-to-br from-amber-100 via-orange-100 to-yellow-100 flex items-center justify-center">
                        <div class="text-center">
                            <i class="fas fa-box text-4xl text-amber-400/60 mb-2"></i>
                            <p class="text-sm text-gray-500 font-medium">{{ $package->name }}</p>
                        </div>
                    </div>
                @endif
                
                <!-- Service Package Type Badge -->
                <div class="absolute top-4 left-4">
                    <span class="inline-flex items-center px-3 py-2 rounded-full text-sm font-semibold bg-amber-100/90 text-amber-800 border border-amber-200/50 backdrop-blur-sm shadow-lg">
                        <i class="fas fa-box mr-2"></i>Service
                    </span>
                </div>
                
                <!-- Status Badge -->
                <div class="absolute top-4 right-4">
                    <span class="inline-flex items-center px-3 py-2 rounded-full text-sm font-semibold bg-gradient-to-r {{ $statusColor }} backdrop-blur-sm shadow-lg">
                        <i class="fas {{ $statusIcon }} mr-2"></i>
                        {{ Str::title($package->status ?? 'Active') }}
                    </span>
                </div>
            </div>

            {{-- Enhanced Service Package Info --}}
            <div class="flex-1 p-6 lg:p-8">
                <!-- Header Section -->
                <div class="mb-6">
                    <h3 class="text-xl lg:text-2xl font-bold text-gray-900 mb-2 group-hover:text-amber-600 transition-colors duration-200">
                        {{ $package->name }}
                    </h3>
                    <div class="flex items-center text-gray-600 mb-4">
                        <div class="flex items-center justify-center w-6 h-6 bg-amber-100 rounded-full mr-2">
                            <i class="fas fa-layer-group text-amber-600 text-xs"></i>
                        </div>
                        <span class="text-sm font-medium">{{ $package->category ?? 'Service Package' }}</span>
                    </div>
                </div>
                
                {{-- Enhanced Key Details Grid --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                    @if($package->price && $package->price > 0)
                        <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-4 border border-green-200/50">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-dollar-sign text-green-600 mr-2"></i>
                                <span class="text-xs font-medium text-green-700 uppercase tracking-wide">Price</span>
                            </div>
                            <div class="text-sm font-bold text-green-900">{{ Number::currency($package->price, 'USD') }}</div>
                        </div>
                    @endif
                    
                    @if($package->delivery_time)
                        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-4 border border-blue-200/50">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-truck text-blue-600 mr-2"></i>
                                <span class="text-xs font-medium text-blue-700 uppercase tracking-wide">Delivery</span>
                            </div>
                            <div class="text-sm font-bold text-blue-900">{{ $package->delivery_time }} days</div>
                        </div>
                    @endif

                    @if($package->revisions)
                        <div class="bg-gradient-to-br from-purple-50 to-indigo-50 rounded-xl p-4 border border-purple-200/50">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-redo text-purple-600 mr-2"></i>
                                <span class="text-xs font-medium text-purple-700 uppercase tracking-wide">Revisions</span>
                            </div>
                            <div class="text-sm font-bold text-purple-900">{{ $package->revisions }} included</div>
                        </div>
                    @endif
                    
                    @if($package->orders_count ?? 0 > 0)
                        <div class="bg-gradient-to-br from-indigo-50 to-blue-50 rounded-xl p-4 border border-indigo-200/50">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-shopping-cart text-indigo-600 mr-2"></i>
                                <span class="text-xs font-medium text-indigo-700 uppercase tracking-wide">Orders</span>
                            </div>
                            <div class="text-sm font-bold text-indigo-900">{{ $package->orders_count }} total</div>
                        </div>
                    @endif

                    @if($package->rating ?? 0 > 0)
                        <div class="bg-gradient-to-br from-yellow-50 to-amber-50 rounded-xl p-4 border border-yellow-200/50">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-star text-yellow-600 mr-2"></i>
                                <span class="text-xs font-medium text-yellow-700 uppercase tracking-wide">Rating</span>
                            </div>
                            <div class="text-sm font-bold text-yellow-900">{{ number_format($package->rating, 1) }}/5.0</div>
                        </div>
                    @endif

                    @if($package->views_count ?? 0 > 0)
                        <div class="bg-gradient-to-br from-teal-50 to-cyan-50 rounded-xl p-4 border border-teal-200/50">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-eye text-teal-600 mr-2"></i>
                                <span class="text-xs font-medium text-teal-700 uppercase tracking-wide">Views</span>
                            </div>
                            <div class="text-sm font-bold text-teal-900">{{ number_format($package->views_count) }}</div>
                        </div>
                    @endif
                </div>

                {{-- Enhanced Stats and Action Indicators --}}
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="text-sm text-gray-500 flex items-center">
                        <i class="fas fa-clock mr-2"></i>
                        <span>Updated {{ $package->updated_at->diffForHumans() }}</span>
                    </div>
                    
                    <div class="flex flex-wrap gap-2">
                        @if(($package->status ?? 'active') === 'active')
                            <div class="inline-flex items-center px-3 py-2 rounded-full text-xs font-semibold bg-gradient-to-r from-green-100 to-emerald-100 text-green-800 border border-green-200/50 shadow-sm">
                                <i class="fas fa-check-circle text-green-600 mr-2"></i>
                                <span>Live</span>
                            </div>
                        @elseif(($package->status ?? 'active') === 'draft')
                            <div class="inline-flex items-center px-3 py-2 rounded-full text-xs font-semibold bg-gradient-to-r from-yellow-100 to-amber-100 text-yellow-800 border border-yellow-200/50 shadow-sm">
                                <i class="fas fa-edit text-yellow-600 mr-2"></i>
                                <span>Draft</span>
                            </div>
                        @elseif(($package->status ?? 'active') === 'inactive')
                            <div class="inline-flex items-center px-3 py-2 rounded-full text-xs font-semibold bg-gradient-to-r from-gray-100 to-gray-200 text-gray-800 border border-gray-200/50 shadow-sm">
                                <i class="fas fa-pause-circle text-gray-600 mr-2"></i>
                                <span>Paused</span>
                            </div>
                        @endif
                        
                        @if($package->featured ?? false)
                            <div class="inline-flex items-center px-3 py-2 rounded-full text-xs font-semibold bg-gradient-to-r from-purple-100 to-indigo-100 text-purple-800 border border-purple-200/50 shadow-sm">
                                <i class="fas fa-star text-purple-600 mr-2"></i>
                                <span>Featured</span>
                            </div>
                        @endif
                        
                        @if($package->fast_delivery ?? false)
                            <div class="inline-flex items-center px-3 py-2 rounded-full text-xs font-semibold bg-gradient-to-r from-blue-100 to-indigo-100 text-blue-800 border border-blue-200/50 shadow-sm">
                                <i class="fas fa-bolt text-blue-600 mr-2"></i>
                                <span>Fast Delivery</span>
                            </div>
                        @endif
                        
                        @if($package->category)
                            <div class="inline-flex items-center px-3 py-2 rounded-full text-xs font-semibold bg-gradient-to-r from-gray-100 to-gray-200 text-gray-800 border border-gray-200/50 shadow-sm">
                                <i class="fas fa-tag text-gray-600 mr-2"></i>
                                <span>{{ $package->category }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </a>
</div> 