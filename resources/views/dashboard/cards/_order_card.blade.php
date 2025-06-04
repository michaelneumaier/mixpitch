{{-- resources/views/dashboard/cards/_order_card.blade.php --}}
@php
    $orderUrl = route('orders.show', $order);
    $statusColor = match($order->status) {
        'pending' => 'from-yellow-100 to-amber-100 text-yellow-800 border-yellow-200/50',
        'processing' => 'from-blue-100 to-indigo-100 text-blue-800 border-blue-200/50',
        'completed' => 'from-green-100 to-emerald-100 text-green-800 border-green-200/50',
        'cancelled' => 'from-red-100 to-pink-100 text-red-800 border-red-200/50',
        'refunded' => 'from-gray-100 to-gray-200 text-gray-800 border-gray-200/50',
        default => 'from-gray-100 to-gray-200 text-gray-800 border-gray-200/50'
    };
    $statusIcon = match($order->status) {
        'pending' => 'fa-clock',
        'processing' => 'fa-cog',
        'completed' => 'fa-check-circle',
        'cancelled' => 'fa-times-circle',
        'refunded' => 'fa-undo',
        default => 'fa-question-circle'
    };
@endphp

<div class="group relative bg-white/95 backdrop-blur-sm rounded-2xl border border-white/20 shadow-xl hover:shadow-2xl transition-all duration-300 overflow-hidden">
    <!-- Gradient Border Effect -->
    <div class="absolute inset-0 bg-gradient-to-r from-green-500/20 via-emerald-500/20 to-teal-500/20 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
    
    <a href="{{ $orderUrl }}" class="relative block m-0.5 bg-white/95 backdrop-blur-sm rounded-2xl overflow-hidden">
        <div class="flex flex-col lg:flex-row">
            {{-- Enhanced Service Package Image --}}
            <div class="relative lg:w-64 h-48 lg:h-auto bg-gradient-to-br from-gray-100 to-gray-200 overflow-hidden">
                @if($order->servicePackage && $order->servicePackage->image_path)
                    <img src="{{ $order->servicePackage->imageUrl }}" 
                         alt="{{ $order->servicePackage->name }}"
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                @else
                    <div class="w-full h-full bg-gradient-to-br from-green-100 via-emerald-100 to-teal-100 flex items-center justify-center">
                        <div class="text-center">
                            <i class="fas fa-shopping-cart text-4xl text-green-400/60 mb-2"></i>
                            <p class="text-sm text-gray-500 font-medium">{{ $order->servicePackage ? $order->servicePackage->name : 'Order' }}</p>
                        </div>
                    </div>
                @endif
                
                <!-- Order Type Badge -->
                <div class="absolute top-4 left-4">
                    <span class="inline-flex items-center px-3 py-2 rounded-full text-sm font-semibold bg-green-100/90 text-green-800 border border-green-200/50 backdrop-blur-sm shadow-lg">
                        <i class="fas fa-shopping-cart mr-2"></i>Order
                    </span>
                </div>
                
                <!-- Status Badge -->
                <div class="absolute top-4 right-4">
                    <span class="inline-flex items-center px-3 py-2 rounded-full text-sm font-semibold bg-gradient-to-r {{ $statusColor }} backdrop-blur-sm shadow-lg">
                        <i class="fas {{ $statusIcon }} mr-2"></i>
                        {{ Str::title(str_replace('_', ' ', $order->status)) }}
                    </span>
                </div>
            </div>

            {{-- Enhanced Order Info --}}
            <div class="flex-1 p-6 lg:p-8">
                <!-- Header Section -->
                <div class="mb-6">
                    <h3 class="text-xl lg:text-2xl font-bold text-gray-900 mb-2 group-hover:text-green-600 transition-colors duration-200">
                        {{ $order->servicePackage ? $order->servicePackage->name : 'Order #' . $order->id }}
                    </h3>
                    <div class="flex items-center text-gray-600 mb-4">
                        <div class="flex items-center justify-center w-6 h-6 bg-green-100 rounded-full mr-2">
                            <i class="fas fa-box text-green-600 text-xs"></i>
                        </div>
                        <span class="text-sm font-medium">Service Package Order</span>
                    </div>
                </div>
                
                {{-- Enhanced Key Details Grid --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                    @if($order->total_amount && $order->total_amount > 0)
                        <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-4 border border-green-200/50">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-dollar-sign text-green-600 mr-2"></i>
                                <span class="text-xs font-medium text-green-700 uppercase tracking-wide">Total</span>
                            </div>
                            <div class="text-sm font-bold text-green-900">{{ Number::currency($order->total_amount, 'USD') }}</div>
                        </div>
                    @endif
                    
                    @if($order->created_at)
                        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-4 border border-blue-200/50">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-calendar-plus text-blue-600 mr-2"></i>
                                <span class="text-xs font-medium text-blue-700 uppercase tracking-wide">Ordered</span>
                            </div>
                            <div class="text-sm font-bold text-blue-900">{{ $order->created_at->format('M d, Y') }}</div>
                        </div>
                    @endif

                    @if($order->servicePackage && $order->servicePackage->user)
                        <div class="bg-gradient-to-br from-purple-50 to-indigo-50 rounded-xl p-4 border border-purple-200/50">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-user-cog text-purple-600 mr-2"></i>
                                <span class="text-xs font-medium text-purple-700 uppercase tracking-wide">Provider</span>
                            </div>
                            <div class="text-sm font-bold text-purple-900">
                                @if(isset($components) && isset($components['user-link']))
                                    <x-user-link :user="$order->servicePackage->user" />
                                @else
                                    {{ $order->servicePackage->user->name }}
                                @endif
                            </div>
                        </div>
                    @endif
                    
                    @if($order->servicePackage && $order->servicePackage->delivery_time)
                        <div class="bg-gradient-to-br from-amber-50 to-orange-50 rounded-xl p-4 border border-amber-200/50">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-truck text-amber-600 mr-2"></i>
                                <span class="text-xs font-medium text-amber-700 uppercase tracking-wide">Delivery</span>
                            </div>
                            <div class="text-sm font-bold text-amber-900">{{ $order->servicePackage->delivery_time }} days</div>
                        </div>
                    @endif

                    @if($order->servicePackage && $order->servicePackage->revisions)
                        <div class="bg-gradient-to-br from-indigo-50 to-blue-50 rounded-xl p-4 border border-indigo-200/50">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-redo text-indigo-600 mr-2"></i>
                                <span class="text-xs font-medium text-indigo-700 uppercase tracking-wide">Revisions</span>
                            </div>
                            <div class="text-sm font-bold text-indigo-900">{{ $order->servicePackage->revisions }} included</div>
                        </div>
                    @endif

                    @if($order->payment_status)
                        <div class="bg-gradient-to-br from-teal-50 to-cyan-50 rounded-xl p-4 border border-teal-200/50">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-credit-card text-teal-600 mr-2"></i>
                                <span class="text-xs font-medium text-teal-700 uppercase tracking-wide">Payment</span>
                            </div>
                            <div class="text-sm font-bold text-teal-900">{{ Str::title(str_replace('_', ' ', $order->payment_status)) }}</div>
                        </div>
                    @endif
                </div>

                {{-- Enhanced Stats and Action Indicators --}}
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="text-sm text-gray-500 flex items-center">
                        <i class="fas fa-clock mr-2"></i>
                        <span>Updated {{ $order->updated_at->diffForHumans() }}</span>
                    </div>
                    
                    <div class="flex flex-wrap gap-2">
                        @if($order->status === 'pending')
                            <div class="inline-flex items-center px-3 py-2 rounded-full text-xs font-semibold bg-gradient-to-r from-yellow-100 to-amber-100 text-yellow-800 border border-yellow-200/50 shadow-sm animate-pulse">
                                <i class="fas fa-hourglass-half text-yellow-600 mr-2"></i>
                                <span>Awaiting Processing</span>
                            </div>
                        @elseif($order->status === 'processing')
                            <div class="inline-flex items-center px-3 py-2 rounded-full text-xs font-semibold bg-gradient-to-r from-blue-100 to-indigo-100 text-blue-800 border border-blue-200/50 shadow-sm">
                                <i class="fas fa-cog fa-spin text-blue-600 mr-2"></i>
                                <span>In Progress</span>
                            </div>
                        @elseif($order->status === 'completed')
                            <div class="inline-flex items-center px-3 py-2 rounded-full text-xs font-semibold bg-gradient-to-r from-green-100 to-emerald-100 text-green-800 border border-green-200/50 shadow-sm">
                                <i class="fas fa-check-circle text-green-600 mr-2"></i>
                                <span>Completed</span>
                            </div>
                        @elseif($order->status === 'cancelled')
                            <div class="inline-flex items-center px-3 py-2 rounded-full text-xs font-semibold bg-gradient-to-r from-red-100 to-pink-100 text-red-800 border border-red-200/50 shadow-sm">
                                <i class="fas fa-times-circle text-red-600 mr-2"></i>
                                <span>Cancelled</span>
                            </div>
                        @endif
                        
                        @if($order->payment_status === 'paid')
                            <div class="inline-flex items-center px-3 py-2 rounded-full text-xs font-semibold bg-gradient-to-r from-green-100 to-emerald-100 text-green-800 border border-green-200/50 shadow-sm">
                                <i class="fas fa-check text-green-600 mr-2"></i>
                                <span>Paid</span>
                            </div>
                        @elseif($order->payment_status === 'pending')
                            <div class="inline-flex items-center px-3 py-2 rounded-full text-xs font-semibold bg-gradient-to-r from-yellow-100 to-amber-100 text-yellow-800 border border-yellow-200/50 shadow-sm">
                                <i class="fas fa-clock text-yellow-600 mr-2"></i>
                                <span>Payment Pending</span>
                            </div>
                        @endif
                        
                        @if($order->servicePackage && $order->servicePackage->category)
                            <div class="inline-flex items-center px-3 py-2 rounded-full text-xs font-semibold bg-gradient-to-r from-gray-100 to-gray-200 text-gray-800 border border-gray-200/50 shadow-sm">
                                <i class="fas fa-tag text-gray-600 mr-2"></i>
                                <span>{{ $order->servicePackage->category }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </a>
</div> 