@extends('components.layouts.app')

@section('title', 'Client Management Dashboard')

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-2 py-4 lg:py-8">
        <div class="max-w-7xl mx-auto">
            <!-- Enhanced Dashboard Header -->
            <div class="relative mb-4 lg:mb-8">
                <!-- Unified Header Content -->
                <div class="relative bg-white/95 backdrop-blur-md border border-white/20 rounded-2xl shadow-xl p-4 lg:p-6 xl:p-8">
                    <!-- Main Header Row -->
                    <div class="mb-4 lg:mb-6">
                        <!-- Mobile: Compact Layout -->
                        <div class="flex flex-col lg:hidden gap-2 lg:gap-3">
                            <!-- Title + Button Row -->
                            <div class="flex items-center justify-between gap-4">
                                <h1 class="text-3xl font-bold bg-gradient-to-r from-gray-900 via-blue-800 to-purple-800 bg-clip-text text-transparent flex-1 min-w-0">
                                    Client Management
                                </h1>
                                <a href="{{ route('projects.create') }}?workflow_type=client_management" 
                                   class="group inline-flex items-center px-4 py-2 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105 flex-shrink-0">
                                    <i class="fas fa-plus mr-1.5 group-hover:scale-110 transition-transform text-sm"></i>
                                    <span class="text-sm">Create</span>
                                </a>
                            </div>
                            <!-- Subtitle -->
                            <p class="text-base text-gray-600 font-medium leading-snug">Analytics and insights for your client relationships</p>
                        </div>

                        <!-- Desktop: Original Layout -->
                        <div class="hidden lg:flex lg:items-start lg:justify-between gap-6">
                            <!-- Title Section -->
                            <div class="flex-1">
                                <h1 class="text-5xl font-bold bg-gradient-to-r from-gray-900 via-blue-800 to-purple-800 bg-clip-text text-transparent mb-2">
                                    Client Management Dashboard
                                </h1>
                                <p class="text-lg text-gray-600 font-medium">Comprehensive analytics and insights for your client relationships</p>
                            </div>
                                
                            <!-- Action Button -->
                            <div class="flex-shrink-0">
                                <a href="{{ route('projects.create') }}?workflow_type=client_management" 
                                   class="group inline-flex items-center px-6 py-3 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105">
                                    <i class="fas fa-plus mr-2 group-hover:scale-110 transition-transform"></i>
                                    New Client Project
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions and Back Button -->
                    <div class="border-t border-gray-200/60 pt-4 lg:pt-6">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                            <!-- Quick Actions -->
                            <div class="flex flex-wrap items-center gap-3">
                                <a href="{{ route('clients.import.index') }}" 
                                   class="inline-flex items-center px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-xl border border-gray-200 transition-all duration-200 hover:shadow-sm">
                                    <i class="fas fa-file-upload mr-2"></i>
                                    Import Clients
                                </a>
                                <a href="{{ route('settings.branding.edit') }}" 
                                   class="inline-flex items-center px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-xl border border-gray-200 transition-all duration-200 hover:shadow-sm">
                                    <i class="fas fa-palette mr-2"></i>
                                    Branding Settings
                                </a>
                            </div>

                            <!-- Navigation Link -->
                            <a href="{{ route('dashboard') }}" 
                               class="text-sm text-gray-500 hover:text-gray-700 font-medium transition-colors duration-200 px-2">
                                ← Back to Dashboard
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