<x-layouts.app-sidebar :title="$client->name ? $client->name . ' - Client Management' : 'Client - Client Management'">

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
@endpush
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50">
    <!-- Background decorative elements -->
    <div class="fixed top-20 left-10 w-20 h-20 bg-blue-200/30 rounded-full blur-xl"></div>
    <div class="fixed bottom-20 right-10 w-32 h-32 bg-indigo-200/30 rounded-full blur-xl"></div>
    <div class="fixed top-1/2 left-1/4 w-16 h-16 bg-purple-200/30 rounded-full blur-xl"></div>
    
    <div class="container mx-auto p-4 lg:p-8 relative z-10">
        <!-- Breadcrumb Navigation -->
        <nav class="flex items-center space-x-2 text-sm text-gray-600 mb-6">
            <a href="{{ route('producer.client-management') }}" 
               class="hover:text-blue-600 transition-colors duration-200 flex items-center">
                <i class="fas fa-chart-line mr-2"></i>
                Client Management
            </a>
            <i class="fas fa-chevron-right text-gray-400"></i>
            <span class="text-gray-900 font-medium">
                {{ $client->name ?: $client->email }}
            </span>
        </nav>

        <!-- Client Detail Dashboard Component -->
        <livewire:client-detail-dashboard :client="$client" />
    </div>
</div>
</x-layouts.app-sidebar>