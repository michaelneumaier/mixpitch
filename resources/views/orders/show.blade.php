@php
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\OrderFile;
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Order Details') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6 lg:p-8 bg-white dark:bg-gray-800 dark:bg-gradient-to-bl dark:from-gray-700/50 dark:via-transparent">
                    
                    {{-- Basic Order Header Info --}}
                    <div class="mb-6 pb-4 border-b border-gray-200 dark:border-gray-700">
                        <h1 class="text-2xl font-medium text-gray-900 dark:text-white mb-2">
                            Order #{{ $order->id }}: {{ $order->servicePackage->title ?? 'Service Package' }}
                        </h1>
                        <div class="flex flex-wrap gap-x-4 gap-y-1 text-sm text-gray-500 dark:text-gray-400">
                            <span>Status: <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $order->readable_status }}</span></span>
                            <span>Ordered: {{ $order->created_at->format('M d, Y') }}</span>
                            <span>Amount: <span class="font-semibold text-gray-700 dark:text-gray-300">{{ Number::currency($order->price, $order->currency) }}</span></span>
                            <span>Payment: <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $order->readable_payment_status }}</span></span>
                            @if (Auth::id() === $order->client_user_id)
                                <span>Producer: {{ $order->producer->name }}</span>
                            @else
                                <span>Client: {{ $order->client->name }} ({{ $order->client->email }})</span>
                            @endif
                        </div>
                    </div>

                    {{-- Placeholder for Order Workflow Components --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        {{-- Column 1: Requirements / Delivery / Actions --}}
                        <div class="md:col-span-2 space-y-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Order Workflow</h3>
                            
                            {{-- Requirements Submission Form --}}
                            @can('submitRequirements', $order)
                                <div class="p-4 border border-blue-200 dark:border-blue-800 rounded-md bg-blue-50 dark:bg-gray-900">
                                    <h4 class="text-md font-medium text-gray-800 dark:text-gray-200 mb-3">Submit Your Requirements</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                        Please provide the necessary details for the producer to start working on your order.
                                        @if($order->servicePackage->requirements_prompt)
                                            <span class="block mt-1 font-semibold">Producer Instructions:</span>
                                            <span class="block whitespace-pre-wrap">{{ $order->servicePackage->requirements_prompt }}</span>
                                        @endif
                                    </p>
                                    <form action="{{ route('orders.requirements.submit', $order) }}" method="POST">
                                        @csrf
                                        <div>
                                            <x-label for="requirements" value="{{ __('Your Requirements') }}" class="sr-only"/>
                                            <textarea id="requirements" name="requirements" rows="6" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm" required>{{ old('requirements') }}</textarea>
                                            <x-input-error for="requirements" class="mt-2" />
                                        </div>
                                        <div class="mt-4">
                                            <x-button>Submit Requirements</x-button>
                                        </div>
                                    </form>
                                </div>
                            @endcan

                            {{-- Display Submitted Requirements --}}
                            @if($order->requirements_submitted)
                                <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-md bg-gray-50 dark:bg-gray-900">
                                     <h4 class="text-md font-medium text-gray-800 dark:text-gray-200 mb-2">Submitted Requirements</h4>
                                     <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $order->requirements_submitted }}</p>
                                </div>
                            @elseif($order->status !== Order::STATUS_PENDING_REQUIREMENTS)
                                <div class="p-4 bg-gray-100 dark:bg-gray-900 rounded-md text-center text-gray-500 dark:text-gray-400">
                                    {{-- Placeholder or message if requirements not needed/submitted yet --}}
                                    @if($order->status === Order::STATUS_IN_PROGRESS)
                                        Requirements submitted. Awaiting delivery.
                                    @else
                                        {{-- Generic placeholder --}}
                                        Awaiting next step...
                                    @endif
                                </div>
                            @endif

                            {{-- Delivery Form --}}
                            @can('deliverOrder', $order)
                            <div class="p-4 border border-green-200 dark:border-green-800 rounded-md bg-green-50 dark:bg-gray-900">
                                <h4 class="text-md font-medium text-gray-800 dark:text-gray-200 mb-3">Deliver Order</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                    Upload the final file(s) for the client to review. You can add an optional message.
                                </p>
                                <form action="{{ route('orders.deliver', $order) }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <div class="mb-4">
                                        <x-label for="delivery_files" value="{{ __('Delivery Files (Max 20MB each)') }}" />
                                        <input type="file" id="delivery_files" name="delivery_files[]" multiple required class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-gray-700 dark:file:text-gray-300 dark:hover:file:bg-gray-600"/>
                                        <x-input-error for="delivery_files" class="mt-2" />
                                        <x-input-error for="delivery_files.*" class="mt-2" />
                                    </div>

                                    <div class="mb-4">
                                        <x-label for="delivery_message" value="{{ __('Delivery Message (Optional)') }}" />
                                        <textarea id="delivery_message" name="delivery_message" rows="4" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">{{ old('delivery_message') }}</textarea>
                                        <x-input-error for="delivery_message" class="mt-2" />
                                    </div>

                                    <div class="mt-4">
                                        <x-button>Deliver Order</x-button>
                                    </div>
                                </form>
                            </div>
                            @endcan

                            {{-- Display Delivery Info (Placeholder/Example) --}}
                            @if($order->status === Order::STATUS_READY_FOR_REVIEW || $order->status === Order::STATUS_COMPLETED || $order->status === Order::STATUS_REVISIONS_REQUESTED)
                                @php
                                    // Find the latest delivery event to potentially show the message
                                    $latestDeliveryEvent = $order->events()
                                                        ->where('event_type', App\Models\OrderEvent::EVENT_DELIVERY_SUBMITTED)
                                                        ->latest()
                                                        ->first();
                                    // Get delivery files
                                    $deliveryFiles = $order->files()->where('type', App\Models\OrderFile::TYPE_DELIVERY)->get();
                                @endphp
                                @if($latestDeliveryEvent || !$deliveryFiles->isEmpty())
                                <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-md bg-gray-50 dark:bg-gray-900">
                                    <h4 class="text-md font-medium text-gray-800 dark:text-gray-200 mb-2">Latest Delivery</h4>
                                    @if($latestDeliveryEvent && $latestDeliveryEvent->comment && str_contains($latestDeliveryEvent->comment, 'Delivery Message:'))
                                        @php
                                            // Extract message part
                                            $messageParts = explode("\n\nDelivery Message:\n", $latestDeliveryEvent->comment, 2);
                                            $deliveryMessage = $messageParts[1] ?? null;
                                        @endphp
                                        @if($deliveryMessage)
                                            <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap mb-3"><strong>Message:</strong> {{ $deliveryMessage }}</p>
                                        @endif
                                    @endif
                                    
                                    @if(!$deliveryFiles->isEmpty())
                                    <p class="text-sm text-gray-700 dark:text-gray-300 mb-1"><strong>Files:</strong></p>
                                    <ul class="list-disc list-inside space-y-1 text-sm ml-4">
                                         @foreach($deliveryFiles as $file)
                                            <li>
                                                <a href="{{ route('orders.files.download', ['order' => $order, 'file' => $file]) }}" class="text-indigo-600 hover:underline dark:text-indigo-400">{{ $file->file_name }}</a> 
                                                ({{ $file->formatted_size }}) 
                                                <span class="text-xs text-gray-500">({{ $file->created_at->format('M d, Y H:i') }})</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                    @else
                                     <p class="text-sm text-gray-500 dark:text-gray-400">No delivery files found for the latest delivery event.</p>
                                    @endif
                                </div>
                                @endif
                            @endif

                            {{-- Revision Request Form --}}
                            @can('requestRevision', $order)
                            <div class="p-4 border border-orange-200 dark:border-orange-700 rounded-md bg-orange-50 dark:bg-gray-900">
                                <h4 class="text-md font-medium text-gray-800 dark:text-gray-200 mb-3">Request Revisions</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                    If the delivery doesn't meet your requirements, you can request revisions.
                                    You have {{ $order->servicePackage->revisions_included - $order->revision_count }} revision(s) remaining. {{-- Using revisions_included from ServicePackage model --}}
                                </p>
                                <form action="{{ route('orders.requestRevision', $order) }}" method="POST">
                                    @csrf
                                    <div>
                                        <x-label for="revision_feedback" value="{{ __('Revision Feedback') }}" />
                                        <textarea id="revision_feedback" name="revision_feedback" rows="5" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm" required>{{ old('revision_feedback') }}</textarea>
                                        <x-input-error for="revision_feedback" class="mt-2" />
                                    </div>
                                    <div class="mt-4">
                                        <x-button color="secondary">Request Revisions</x-button> {{-- Assuming secondary color exists --}}
                                    </div>
                                </form>
                            </div>
                            @endcan

                            {{-- Accept Delivery Button --}}
                            @can('acceptDelivery', $order) 
                                <div class="p-4 border border-green-200 dark:border-green-700 rounded-md bg-green-50 dark:bg-gray-900 text-center">
                                     <h4 class="text-md font-medium text-gray-800 dark:text-gray-200 mb-3">Accept Delivery</h4>
                                     <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                         Happy with the delivery? Accept it to complete the order.
                                     </p>
                                     <form action="{{ route('orders.accept-delivery', $order) }}" method="POST"> 
                                         @csrf
                                         <x-button type="submit">Accept & Complete Order</x-button>
                                     </form>
                                 </div>
                            @endcan
                            
                             {{-- Display Files --}}
                            <div>
                                <h4 class="text-md font-medium text-gray-800 dark:text-gray-200 mb-2">Files</h4>
                                @if($order->files->isEmpty())
                                    <p class="text-sm text-gray-500 dark:text-gray-400">No files have been uploaded for this order yet.</p>
                                @else
                                    <ul class="list-disc list-inside space-y-1 text-sm">
                                        @foreach($order->files as $file)
                                            <li>
                                                <a href="{{ route('orders.files.download', ['order' => $order, 'file' => $file]) }}" class="text-indigo-600 hover:underline dark:text-indigo-400">{{ $file->file_name }}</a> 
                                                ({{ $file->formatted_size }}) - Type: {{ ucfirst($file->type) }} by {{ $file->uploader->name }}
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </div>

                        {{-- Column 2: Activity Log --}}
                        <div class="md:col-span-1 space-y-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Activity Log</h3>
                             @if($order->events->isEmpty())
                                <p class="text-sm text-gray-500 dark:text-gray-400">No activity recorded yet.</p>
                             @else
                                <div class="space-y-3 max-h-96 overflow-y-auto pr-2">
                                    @foreach ($order->events as $event)
                                        <div class="text-sm">
                                            <p class="text-gray-800 dark:text-gray-200">{{ $event->comment ?? ucfirst(str_replace('_', ' ', $event->event_type)) }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $event->user->name ?? 'System' }} - {{ $event->created_at->diffForHumans() }}
                                                @if($event->status_to)
                                                 (Status: {{ ucwords(str_replace('_', ' ', $event->status_to)) }})
                                                @endif
                                            </p>
                                        </div>
                                    @endforeach
                                </div>
                             @endif

                            {{-- Message Input Form --}}
                            @can('postMessage', $order)
                            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <form action="{{ route('orders.message.store', $order) }}" method="POST">
                                    @csrf
                                    <div>
                                        <x-label for="message" value="{{ __('Send a Message') }}" class="sr-only" />
                                        <textarea id="message" name="message" rows="3" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm" placeholder="Type your message here..." required>{{ old('message') }}</textarea>
                                        <x-input-error for="message" class="mt-2" />
                                    </div>
                                    <div class="mt-3 flex justify-end">
                                        <x-button>Send Message</x-button>
                                    </div>
                                </form>
                            </div>
                            @endcan
                        </div>
                    </div>

                    {{-- Cancellation Section --}}
                    @can('cancelOrder', $order)
                    <div class="mt-8 pt-6 border-t border-red-200 dark:border-red-700">
                        <h3 class="text-lg font-medium text-red-600 dark:text-red-400">Cancel Order</h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            If you need to cancel this order, please provide a reason below. Cancellation cannot be undone. Note that refunds are handled separately according to platform policy.
                        </p>
                        <form action="{{ route('orders.cancel', $order) }}" method="POST" class="mt-4">
                            @csrf
                            <div>
                                <x-label for="cancellation_reason" value="{{ __('Reason for Cancellation') }}" />
                                <textarea id="cancellation_reason" name="cancellation_reason" rows="3" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm" required>{{ old('cancellation_reason') }}</textarea>
                                <x-input-error for="cancellation_reason" class="mt-2" />
                            </div>
                            <div class="mt-4">
                                {{-- Use a danger button style if available --}}
                                <x-button type="submit" color="danger" onclick="return confirm('Are you sure you want to cancel this order? This action cannot be undone.');">
                                    Confirm Cancellation
                                </x-button>
                            </div>
                        </form>
                    </div>
                    @endcan

                    {{-- Show Cancellation Info if Cancelled --}}
                    @if($order->status === Order::STATUS_CANCELLED)
                        <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                             <h3 class="text-lg font-medium text-gray-500 dark:text-gray-400">Order Cancelled</h3>
                             @php
                                $cancelEvent = $order->events()->where('event_type', App\Models\OrderEvent::EVENT_ORDER_CANCELLED)->latest()->first();
                             @endphp
                             <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                 This order was cancelled on {{ $order->cancelled_at->format('M d, Y H:i') }}.
                                 @if($cancelEvent)
                                     <br>Cancelled by: {{ $cancelEvent->user->name ?? 'N/A' }}
                                     @if($cancelEvent->metadata['reason'] ?? null)
                                         <br>Reason: {{ $cancelEvent->metadata['reason'] }}
                                     @endif
                                 @endif
                             </p>
                         </div>
                     @endif

                </div>
            </div>
        </div>
    </div>
</x-app-layout> 