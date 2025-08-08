@extends('components.layouts.app')

@section('title', 'Client Management Dashboard')

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-2 py-4 lg:py-8">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="relative mb-4 lg:mb-8">
                <div class="relative bg-white/95 backdrop-blur-md border border-white/20 rounded-2xl shadow-xl p-4 lg:p-6 xl:p-8">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 lg:gap-6 mb-6">
                        <div class="flex items-center">
                            <div class="flex items-center justify-center w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl mr-4 shadow-lg">
                                <i class="fas fa-users text-white text-xl"></i>
                            </div>
                            <div>
                                <h1 class="text-3xl lg:text-4xl font-bold bg-gradient-to-r from-blue-900 via-indigo-800 to-purple-800 bg-clip-text text-transparent">
                                    Client Management Dashboard
                                </h1>
                                <p class="text-gray-600 text-lg">Comprehensive analytics and insights for your client relationships</p>
                            </div>
                        </div>
                        
                        <div class="flex flex-col sm:flex-row gap-3">
                            <a href="{{ route('projects.create') }}?workflow_type=client_management" 
                               class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105">
                                <i class="fas fa-plus mr-2"></i>New Client Project
                            </a>
                            <a href="{{ route('clients.import.index') }}" 
                               class="inline-flex items-center px-6 py-3 bg-white hover:bg-gray-50 text-gray-700 font-semibold rounded-xl border-2 border-gray-200 hover:border-gray-300 shadow-md hover:shadow-lg transition-all duration-200 hover:scale-105">
                                <i class="fas fa-file-upload mr-2"></i>Import Clients (CSV)
                            </a>
                            <a href="{{ route('settings.branding.edit') }}" 
                            class="inline-flex items-center px-4 py-2.5 bg-white hover:bg-gray-50 text-gray-700 font-semibold rounded-xl border-2 border-gray-200 hover:border-gray-300 shadow-md hover:shadow-lg transition-all duration-200 hover:scale-105">
                                <i class="fas fa-palette mr-2"></i>
                                Branding
                            </a>
                            <a href="{{ route('dashboard') }}" 
                               class="inline-flex items-center px-6 py-3 bg-white hover:bg-gray-50 text-gray-700 font-semibold rounded-xl border-2 border-gray-200 hover:border-gray-300 shadow-md hover:shadow-lg transition-all duration-200 hover:scale-105">
                                <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                            </a>
                            
                        </div>
                    </div>
                </div>
            </div>

            <!-- Delivery Kanban + Client Management Dashboard -->
            <div class="relative space-y-6">
                <div class="relative bg-white/95 backdrop-blur-sm border border-white/20 rounded-2xl shadow-xl overflow-hidden">
                    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
                    @livewire('delivery-pipeline-board')
                </div>

                <div class="relative bg-white/95 backdrop-blur-sm border border-white/20 rounded-2xl shadow-xl overflow-hidden">
                    @livewire('client-management-dashboard', [
                        'userId' => auth()->id(),
                        'expanded' => true
                    ])
                </div>
            </div>
        </div>
    </div>
</div>
@endsection