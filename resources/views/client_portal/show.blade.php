<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Client Portal - {{ $project->title }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <!-- Add Bootstrap JS and its dependencies -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.3/jquery.min.js"></script>
    <script src="https://unpkg.com/@popperjs/core@2"></script>
    <!-- Add WaveSurfer.js for audio player -->
    <script src="https://unpkg.com/wavesurfer.js"></script>
    <!-- Add Livewire for pitch-file-player component -->
    @livewireStyles
    <style>
        body { font-family: 'Inter', sans-serif; }
        

        
        /* Custom animations */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-fade-in-up { animation: fadeInUp 0.6s ease-out; }
        
        /* Upload area hover effects */
        .upload-area:hover {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(147, 51, 234, 0.1));
        }
        
        /* File progress animation */
        .progress-pulse {
            animation: pulse 1.5s infinite;
        }
    </style>
</head>
<body class="bg-blue-50 font-sans antialiased min-h-screen">

    @if(isset($isPreview) && $isPreview)
        <!-- Preview Banner -->
        <div class="bg-gradient-to-r from-orange-500 to-red-500 text-white py-3 px-4 relative z-50">
            <div class="container mx-auto max-w-5xl">
                <div class="flex items-center justify-center">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-eye text-orange-200"></i>
                        <span class="font-semibold">Preview Mode</span>
                        <span class="text-orange-100">•</span>
                        <span class="text-orange-100">This is how your client sees their portal</span>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="relative z-10 container mx-auto px-2 sm:px-4 lg:px-8 py-4 sm:py-8 max-w-5xl">

        {{-- Enhanced Header Card with Project Status Dashboard --}}
        <div class="mb-6 sm:mb-8 animate-fade-in-up">
            <div class="bg-white/95 backdrop-blur-md shadow-xl border border-white/20 rounded-2xl p-4 sm:p-6 lg:p-8">
                <!-- Background Effects -->
                <div class="absolute inset-0 bg-gradient-to-r from-blue-600/5 via-purple-600/5 to-pink-600/5 rounded-2xl"></div>
                
                <div class="relative z-10">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4 sm:mb-6 space-y-4 sm:space-y-0">
                        <div class="flex items-center">
                            @if(!empty($branding['logo_url']))
                                <img src="{{ $branding['logo_url'] }}" alt="Brand Logo" class="w-12 h-12 sm:w-16 sm:h-16 rounded-2xl mr-4 sm:mr-6 shadow-lg object-contain bg-white p-1">
                            @else
                                <div class="flex items-center justify-center w-12 h-12 sm:w-16 sm:h-16 bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl mr-4 sm:mr-6 shadow-lg flex-shrink-0">
                                    <i class="fas fa-briefcase text-white text-lg sm:text-2xl"></i>
                                </div>
                            @endif
                            <div class="min-w-0 flex-1">
                                <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-900 mb-1 sm:mb-2 break-words">{{ $project->title }}</h1>
                                <p class="text-sm sm:text-base lg:text-lg text-gray-600 truncate">Managed by {{ $branding['brand_display'] ?? $pitch->user->name }}</p>
                            </div>
                        </div>
                        
                        <!-- Enhanced Status Badge -->
                        <div class="bg-gradient-to-br from-white/80 to-purple-50/80 backdrop-blur-sm border border-purple-200/50 rounded-xl px-3 sm:px-6 py-2 sm:py-3 shadow-sm self-start sm:self-auto"
                             style="border-color: {{ $branding['secondary'] ?? '#4f46e5' }};">
                            <span class="inline-flex items-center px-2 sm:px-4 py-1 sm:py-2 rounded-xl text-xs sm:text-sm font-bold {{ $pitch->getStatusColorClass() }} border-2 border-white/50 shadow-lg backdrop-blur-sm">
                                <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 rounded-full mr-1 sm:mr-2 bg-current animate-pulse"></div>
                                <span class="truncate">{{ $pitch->readable_status }}</span>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Project Status Dashboard -->
                    <div class="mb-6">
                        <div class="bg-gradient-to-r from-gray-50/80 to-blue-50/80 backdrop-blur-sm border rounded-xl p-3 sm:p-4 lg:p-6"
                             style="border-color: {{ $branding['primary'] ?? '#1f2937' }}33;">
                            <h3 class="text-sm font-semibold text-gray-700 mb-4 flex items-center">
                                <i class="fas fa-route mr-2 text-blue-500"></i>
                                Project Progress
                            </h3>
                            
                            <!-- Progress Steps -->
                            <div class="flex items-center justify-between relative overflow-x-auto">
                                <!-- Progress Line Background -->
                                <div class="absolute top-4 left-4 right-4 h-0.5 bg-gray-200 rounded-full hidden sm:block"></div>
                                
                                <!-- Dynamic Progress Line -->
                                @php
                                    $progressWidth = match ($pitch->status) {
                                        \App\Models\Pitch::STATUS_PENDING => '0%',
                                        \App\Models\Pitch::STATUS_IN_PROGRESS => '25%',
                                        \App\Models\Pitch::STATUS_READY_FOR_REVIEW => '50%',
                                        \App\Models\Pitch::STATUS_APPROVED => '75%',
                                        \App\Models\Pitch::STATUS_COMPLETED => '100%',
                                        default => '25%',
                                    };
                                @endphp
                                <div class="absolute top-4 left-4 h-0.5 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full transition-all duration-1000 ease-out hidden sm:block" 
                                     style="width: {{ $progressWidth }};"></div>
                                
                                <!-- Step 1: Project Started -->
                                <div class="relative flex flex-col items-center min-w-0 flex-1">
                                    <div class="w-6 h-6 sm:w-8 sm:h-8 rounded-full border-2 flex items-center justify-center {{ 
                                        in_array($pitch->status, [\App\Models\Pitch::STATUS_PENDING, \App\Models\Pitch::STATUS_IN_PROGRESS, \App\Models\Pitch::STATUS_READY_FOR_REVIEW, \App\Models\Pitch::STATUS_APPROVED, \App\Models\Pitch::STATUS_COMPLETED]) 
                                        ? 'bg-blue-500 border-blue-500 text-white' 
                                        : 'bg-white border-gray-300 text-gray-400' 
                                    }}">
                                        <i class="fas fa-play text-xs"></i>
                                    </div>
                                    <span class="text-xs font-medium text-gray-600 mt-1 sm:mt-2 text-center truncate w-full">Started</span>
                                </div>
                                
                                <!-- Step 2: In Progress -->
                                <div class="relative flex flex-col items-center min-w-0 flex-1">
                                    <div class="w-6 h-6 sm:w-8 sm:h-8 rounded-full border-2 flex items-center justify-center {{ 
                                        in_array($pitch->status, [\App\Models\Pitch::STATUS_IN_PROGRESS, \App\Models\Pitch::STATUS_READY_FOR_REVIEW, \App\Models\Pitch::STATUS_APPROVED, \App\Models\Pitch::STATUS_COMPLETED]) 
                                        ? 'bg-purple-500 border-purple-500 text-white' 
                                        : 'bg-white border-gray-300 text-gray-400' 
                                    }}">
                                        <i class="fas fa-cog text-xs {{ $pitch->status === \App\Models\Pitch::STATUS_IN_PROGRESS ? 'animate-spin' : '' }}"></i>
                                    </div>
                                    <span class="text-xs font-medium text-gray-600 mt-1 sm:mt-2 text-center truncate w-full">In Progress</span>
                                </div>
                                
                                <!-- Step 3: Ready for Review -->
                                <div class="relative flex flex-col items-center min-w-0 flex-1">
                                    <div class="w-6 h-6 sm:w-8 sm:h-8 rounded-full border-2 flex items-center justify-center {{ 
                                        in_array($pitch->status, [\App\Models\Pitch::STATUS_READY_FOR_REVIEW, \App\Models\Pitch::STATUS_APPROVED, \App\Models\Pitch::STATUS_COMPLETED]) 
                                        ? 'bg-amber-500 border-amber-500 text-white' 
                                        : 'bg-white border-gray-300 text-gray-400' 
                                    }}">
                                        <i class="fas fa-eye text-xs {{ $pitch->status === \App\Models\Pitch::STATUS_READY_FOR_REVIEW ? 'animate-pulse' : '' }}"></i>
                                    </div>
                                    <span class="text-xs font-medium text-gray-600 mt-1 sm:mt-2 text-center truncate w-full">Review</span>
                                </div>
                                
                                <!-- Step 4: Approved -->
                                <div class="relative flex flex-col items-center min-w-0 flex-1">
                                    <div class="w-6 h-6 sm:w-8 sm:h-8 rounded-full border-2 flex items-center justify-center {{ 
                                        in_array($pitch->status, [\App\Models\Pitch::STATUS_APPROVED, \App\Models\Pitch::STATUS_COMPLETED]) 
                                        ? 'bg-green-500 border-green-500 text-white' 
                                        : 'bg-white border-gray-300 text-gray-400' 
                                    }}">
                                        <i class="fas fa-check text-xs"></i>
                                    </div>
                                    <span class="text-xs font-medium text-gray-600 mt-1 sm:mt-2 text-center truncate w-full">Approved</span>
                                </div>
                                
                                <!-- Step 5: Completed -->
                                <div class="relative flex flex-col items-center min-w-0 flex-1">
                                    <div class="w-6 h-6 sm:w-8 sm:h-8 rounded-full border-2 flex items-center justify-center {{ 
                                        $pitch->status === \App\Models\Pitch::STATUS_COMPLETED 
                                        ? 'bg-emerald-500 border-emerald-500 text-white' 
                                        : 'bg-white border-gray-300 text-gray-400' 
                                    }}">
                                        <i class="fas fa-trophy text-xs"></i>
                                    </div>
                                    <span class="text-xs font-medium text-gray-600 mt-1 sm:mt-2 text-center truncate w-full">Complete</span>
                                </div>
                            </div>
                            
                            <!-- Current Status Description -->
                            <div class="mt-6 p-4 bg-white/60 backdrop-blur-sm rounded-lg border border-white/40">
                                <p class="text-sm text-gray-700">
                                    @if($pitch->status === \App\Models\Pitch::STATUS_PENDING)
                                        <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                                        Your project has been created and the producer is preparing your deliverables.
                                    @elseif($pitch->status === \App\Models\Pitch::STATUS_IN_PROGRESS)
                                        <i class="fas fa-clock text-purple-500 mr-2"></i>
                                        The producer is actively working on your project. You'll be notified when it's ready for review.
                                    @elseif($pitch->status === \App\Models\Pitch::STATUS_READY_FOR_REVIEW)
                                        <i class="fas fa-bell text-amber-500 mr-2 animate-pulse"></i>
                                        <strong>Action Required:</strong> Your project is ready for review! Please check the deliverables below and approve or request revisions.
                                    @elseif($pitch->status === \App\Models\Pitch::STATUS_APPROVED)
                                        <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                        Great! You've approved the project. @if($pitch->payment_amount > 0)Payment processing is in progress.@endif
                                    @elseif($pitch->status === \App\Models\Pitch::STATUS_COMPLETED)
                                        <i class="fas fa-star text-emerald-500 mr-2"></i>
                                        🎉 Project completed successfully! All deliverables are available below.
                                    @else
                                        <i class="fas fa-question-circle text-gray-500 mr-2"></i>
                                        Project status: {{ $pitch->readable_status }}
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Client Info & Payment Information -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 sm:gap-4">
                        <!-- Client Info -->
                        <div class="bg-gradient-to-r from-gray-50/80 to-blue-50/80 backdrop-blur-sm border border-gray-200/50 rounded-xl p-3 sm:p-4">
                            <div class="flex items-center">
                                <i class="fas fa-user-circle text-blue-500 text-lg sm:text-xl mr-2 sm:mr-3 flex-shrink-0"></i>
                                <div class="min-w-0 flex-1">
            @if($project->client_name)
                                        <p class="font-semibold text-gray-800 text-sm sm:text-base truncate">{{ $project->client_name }}</p>
                                        <p class="text-xs sm:text-sm text-gray-600 truncate">{{ $project->client_email }}</p>
                                    @else
                                        <p class="font-semibold text-gray-800 text-sm sm:text-base truncate">{{ $project->client_email }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <!-- Payment Information -->
                        @if($pitch->payment_amount > 0)
                        <div class="bg-gradient-to-r from-green-50/80 to-emerald-50/80 backdrop-blur-sm border border-green-200/50 rounded-xl p-3 sm:p-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center min-w-0 flex-1">
                                    <i class="fas fa-dollar-sign text-green-500 text-lg sm:text-xl mr-2 sm:mr-3 flex-shrink-0"></i>
                                    <div class="min-w-0 flex-1">
                                        <p class="font-semibold text-gray-800 text-sm sm:text-base">${{ number_format($pitch->payment_amount, 2) }}</p>
                                        <p class="text-xs sm:text-sm text-gray-600">Project Value</p>
                                    </div>
                                </div>
                                <div class="text-right flex-shrink-0 ml-2">
                                    @if($pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PAID)
                                        <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-medium bg-green-100 text-green-800">
                                            <i class="fas fa-check-circle mr-1"></i>
                                            <span class="hidden sm:inline">Paid</span>
                                            <span class="sm:hidden">✓</span>
                                        </span>
            @else
                                        <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-medium bg-amber-100 text-amber-800">
                                            <i class="fas fa-clock mr-1"></i>
                                            <span class="hidden sm:inline">Payment Due</span>
                                            <span class="sm:hidden">Due</span>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
            @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Flash Messages with Modern Styling --}}
        @if(request()->query('checkout_status') === 'success')
            <div class="mb-6 animate-fade-in-up" style="animation-delay: 0.1s;">
                <div class="bg-gradient-to-r from-green-100/90 to-emerald-100/90 backdrop-blur-md border border-green-200/50 rounded-xl p-4 shadow-lg">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-600 text-xl mr-3"></i>
                        <span class="text-green-800 font-medium">Payment successful! The project has been approved and the producer has been notified.</span>
                    </div>
                    <script type="application/json" id="snapshot-data-json">@json($snapshotHistory)</script>
                </div>
            </div>
        @elseif(request()->query('checkout_status') === 'cancel')
            <div class="mb-6 animate-fade-in-up" style="animation-delay: 0.1s;">
                <div class="bg-gradient-to-r from-amber-100/90 to-orange-100/90 backdrop-blur-md border border-amber-200/50 rounded-xl p-4 shadow-lg">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle text-amber-600 text-xl mr-3"></i>
                        <span class="text-amber-800 font-medium">Payment was cancelled. You can try approving again when ready.</span>
                    </div>
                </div>
            </div>
        @endif

        @if (session('success'))
            <div class="mb-6 animate-fade-in-up" style="animation-delay: 0.1s;">
                <div class="bg-gradient-to-r from-green-100/90 to-emerald-100/90 backdrop-blur-md border border-green-200/50 rounded-xl p-4 shadow-lg">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-600 text-xl mr-3"></i>
                        <span class="text-green-800 font-medium">{{ session('success') }}</span>
                    </div>
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 animate-fade-in-up" style="animation-delay: 0.1s;">
                <div class="bg-gradient-to-r from-red-100/90 to-pink-100/90 backdrop-blur-md border border-red-200/50 rounded-xl p-4 shadow-lg">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-circle text-red-600 text-xl mr-3 mt-0.5"></i>
                        <div>
                            <span class="text-red-800 font-medium block mb-2">Please fix the following errors:</span>
                            <ul class="list-disc list-inside text-red-700 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                        </div>
                    </div>
                </div>
            </div>
        @endif

            {{-- Project Description --}}
            @if($project->description)
        <div class="mb-8 animate-fade-in-up" style="animation-delay: 0.2s;">
            <div class="bg-white/95 backdrop-blur-md shadow-xl border border-white/20 rounded-2xl p-8">
                <div class="absolute inset-0 bg-gradient-to-r from-blue-600/5 via-purple-600/5 to-pink-600/5 rounded-2xl"></div>
                <div class="relative z-10">
                    <div class="flex items-center mb-4">
                        <div class="flex items-center justify-center w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl mr-4 shadow-lg">
                            <i class="fas fa-file-alt text-white"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900">Project Brief</h3>
                    </div>
                    <div class="bg-gradient-to-br from-white/60 to-gray-50/40 backdrop-blur-sm border border-white/40 rounded-xl p-6">
                        <p class="text-gray-700 leading-relaxed whitespace-pre-wrap">{{ $project->description }}</p>
                    </div>
                </div>
            </div>
            </div>
            @endif

        {{-- Phase 2: Account Upgrade Section --}}
        @guest
        <div class="mb-8 animate-fade-in-up" style="animation-delay: 0.25s;">
            <div class="bg-gradient-to-r from-purple-100/95 to-pink-100/95 backdrop-blur-md shadow-xl border border-purple-200/50 rounded-2xl overflow-hidden">
                <div class="p-8">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center">
                            <div class="flex items-center justify-center w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl mr-4 shadow-lg">
                                <i class="fas fa-user-plus text-white"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-purple-900">Create Your MIXPITCH Account</h3>
                                <p class="text-purple-700 mt-1">Get full access to your projects and more</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="inline-flex items-center px-3 py-1 bg-purple-500/20 text-purple-700 rounded-full text-sm font-medium border border-purple-300/50">
                                <i class="fas fa-star mr-1"></i>
                                Recommended
                            </span>
                        </div>
                    </div>
                    
                    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Benefits -->
                        <div class="bg-white/60 backdrop-blur-sm border border-white/40 rounded-xl p-4">
                            <h4 class="font-semibold text-purple-900 mb-3">Account Benefits:</h4>
                            <ul class="space-y-2 text-sm text-purple-800">
                                <li class="flex items-center">
                                    <i class="fas fa-check text-green-500 mr-2"></i>
                                    Dashboard with all your projects
                                </li>
                                <li class="flex items-center">
                                    <i class="fas fa-check text-green-500 mr-2"></i>
                                    Download invoices and receipts
                                </li>
                                <li class="flex items-center">
                                    <i class="fas fa-check text-green-500 mr-2"></i>
                                    Project history and analytics
                                </li>
                                <li class="flex items-center">
                                    <i class="fas fa-check text-green-500 mr-2"></i>
                                    Enhanced file management
                                </li>
                            </ul>
                        </div>
                        
                        <!-- Action -->
                        <div class="bg-white/60 backdrop-blur-sm border border-white/40 rounded-xl p-4 flex flex-col justify-center">
                            <p class="text-sm text-purple-700 mb-4">
                                Creating an account is <strong>free</strong> and takes less than a minute. 
                                All your existing projects will be automatically linked to your new account.
                            </p>
                            <a href="{{ URL::temporarySignedRoute('client.portal.upgrade', now()->addHours(24), ['project' => $project->id]) }}" 
                               class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-xl hover:from-purple-700 hover:to-pink-700 transition-all duration-200 font-medium shadow-lg hover:shadow-xl transform hover:scale-105">
                                <i class="fas fa-user-plus mr-2"></i>
                                Create Free Account
                            </a>
                            <p class="text-xs text-purple-600 mt-2 text-center">
                                Using email: {{ $project->client_email }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endguest

        {{-- Enhanced Files Section --}}
        <div class="mb-8 animate-fade-in-up" style="animation-delay: 0.3s;">
            <div class="bg-white/95 backdrop-blur-md shadow-xl border border-white/20 rounded-2xl overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-r from-blue-600/5 via-purple-600/5 to-pink-600/5 rounded-2xl"></div>
                
                <div class="relative z-10">
                    <!-- Header -->
                    <div class="bg-gradient-to-r from-purple-50/80 to-blue-50/80 backdrop-blur-sm border-b border-purple-200/30 p-3 sm:p-4 lg:p-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="flex items-center justify-center w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-br from-purple-500 to-blue-600 rounded-xl mr-3 sm:mr-4 shadow-lg">
                                    <i class="fas fa-folder-open text-white text-sm sm:text-base"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg sm:text-xl font-bold text-purple-900">Project Files</h3>
                                    <p class="text-xs sm:text-sm text-purple-700 mt-1">Manage your project files and deliverables</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-3 sm:p-4 lg:p-6 space-y-4 sm:space-y-6">
                {{-- Client Reference Files Section --}}
                        <div class="bg-gradient-to-br from-blue-50/90 to-indigo-50/80 backdrop-blur-md border border-blue-200/50 rounded-2xl p-3 sm:p-4 lg:p-6 shadow-lg">
                            <h4 class="font-bold text-blue-900 mb-3 flex items-center text-lg">
                                <div class="flex items-center justify-center w-8 h-8 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg mr-3">
                                    <i class="fas fa-upload text-white text-sm"></i>
                                </div>
                        Your Reference Files
                    </h4>
                            <p class="text-sm text-blue-800 mb-6 leading-relaxed">Upload briefs, references, or examples to help the producer understand your requirements perfectly.</p>
                            
                            {{-- Enhanced File Upload Area --}}
                            <label for="client-file-input" class="upload-area border-2 border-dashed border-blue-300 hover:border-blue-400 rounded-2xl p-8 text-center mb-6 transition-all duration-300 cursor-pointer group block" id="client-upload-area">
                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 bg-gradient-to-br from-blue-100 to-indigo-100 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-105 transition-transform duration-300">
                                        <i class="fas fa-cloud-upload-alt text-2xl text-blue-500"></i>
                                    </div>
                                    <p class="text-blue-700 font-semibold mb-2">
                                        <span class="hover:text-blue-600 transition-colors duration-200">
                                            Click to upload files
                                        </span>
                                        <span class="text-blue-600"> or drag and drop</span>
                                    </p>
                                    <p class="text-xs text-blue-600 bg-blue-100/50 rounded-lg px-3 py-1">
                                        PDF, DOC, MP3, WAV, JPG, PNG (max 200MB each)
                                    </p>
                                </div>
                                <input id="client-file-input" type="file" class="hidden" multiple 
                                       accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.mp3,.wav,.m4a">
                            </label>
                    
                    {{-- Client Files List --}}
                    <div id="client-files-list">
                        @if($project->files->count() > 0)
                                    <div class="space-y-3">
                                @foreach($project->files as $file)
                                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between p-3 sm:p-4 bg-gradient-to-r from-white/80 to-blue-50/60 backdrop-blur-sm border border-blue-200/40 rounded-xl shadow-sm hover:shadow-md transition-all duration-200 space-y-3 sm:space-y-0" data-file-id="{{ $file->id }}">
                                        <div class="flex items-center min-w-0 flex-1">
                                                    <div class="flex items-center justify-center w-8 h-8 sm:w-10 sm:h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg mr-2 sm:mr-3 shadow-sm flex-shrink-0">
                                                        <i class="fas fa-file text-white text-xs sm:text-sm"></i>
                                                    </div>
                                                    <div class="min-w-0 flex-1">
                                                        <span class="text-xs sm:text-sm font-semibold text-blue-900 block truncate">{{ $file->file_name }}</span>
                                                        <div class="text-xs text-blue-600">{{ number_format($file->size / 1024, 1) }} KB</div>
                                                    </div>
                                        </div>
                                                <div class="flex items-center space-x-2 self-start sm:self-auto">
                                        <a href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('client.portal.download_project_file', now()->addHours(24), ['project' => $project->id, 'projectFile' => $file->id]) }}" 
                                                       class="inline-flex items-center px-2 sm:px-3 py-1.5 sm:py-2 bg-gradient-to-r from-blue-500 to-indigo-500 hover:from-blue-600 hover:to-indigo-600 text-white rounded-lg font-medium transition-all duration-200 hover:shadow-lg text-xs sm:text-sm">
                                                        <i class="fas fa-download mr-1 sm:mr-2"></i><span class="hidden sm:inline">Download</span>
                                        </a>
                                                    <button class="inline-flex items-center px-2 sm:px-3 py-1.5 sm:py-2 bg-gradient-to-r from-red-500 to-pink-500 hover:from-red-600 hover:to-pink-600 text-white rounded-lg font-medium transition-all duration-200 hover:shadow-lg text-xs sm:text-sm js-delete-file" data-file-id="{{ $file->id }}" data-file-name="{{ $file->file_name }}">
                                                        <i class="fas fa-trash mr-1 sm:mr-2"></i><span class="hidden sm:inline">Delete</span>
                                                    </button>
                                                </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                                    <div class="text-center py-6">
                                        <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center mx-auto mb-3">
                                            <i class="fas fa-folder-open text-blue-500"></i>
                                        </div>
                                        <p class="text-blue-700 font-medium mb-1">No reference files uploaded yet</p>
                                        <p class="text-blue-600 text-sm">Upload files above to get started</p>
                                    </div>
                        @endif
                    </div>
                </div>
                
                {{-- ENHANCED Producer Deliverables with Snapshot Navigation --}}
                <div id="producer-deliverables" class="bg-gradient-to-br from-green-50/90 to-emerald-50/80 backdrop-blur-md border border-green-200/50 rounded-2xl p-6 shadow-lg">
                    
                    {{-- Header with Version Info --}}
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center">
                            <div class="flex items-center justify-center w-8 h-8 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg mr-3">
                                <i class="fas fa-history text-white text-sm"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-green-900 text-lg">Producer Deliverables</h4>
                                <p class="text-sm text-green-700">
                                    @if($currentSnapshot)
                                        Version {{ $currentSnapshot->version ?? 1 }} of {{ $snapshotHistory->count() }}
                                    @else
                                        No submissions yet
                                    @endif
                                </p>
                            </div>
                        </div>
                        
                        @if($snapshotHistory->count() > 1)
                        <div class="flex items-center space-x-2">
                            <span class="text-xs text-green-600 bg-green-100 px-2 py-1 rounded-lg">
                                {{ $snapshotHistory->count() }} versions available
                            </span>
                        </div>
                        @endif
                    </div>

                    {{-- Enhanced Snapshot Navigation with Version Comparison --}}
                    @if($snapshotHistory->count() > 1)
                    <div class="mb-6">
                        <div class="bg-gradient-to-r from-blue-50/80 to-green-50/80 backdrop-blur-sm border border-blue-200/50 rounded-xl p-4">
                            <div class="flex items-center justify-between mb-3">
                                <h5 class="font-semibold" style="color: {{ $branding['primary'] ?? '#1f2937' }};">Submission History</h5>
                                @if($snapshotHistory->count() >= 2)
                                <button class="text-sm bg-blue-100 hover:bg-blue-200 text-blue-800 px-3 py-1 rounded-lg transition-colors duration-200 js-toggle-comparison">
                                    <i class="fas fa-columns mr-1"></i>Compare Versions
                                </button>
                                @endif
                            </div>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3" id="snapshot-grid">
                                @foreach($snapshotHistory as $snapshot)
                                <div class="snapshot-item group p-3 rounded-lg border transition-all duration-200 hover:shadow-md cursor-pointer
                                          {{ $currentSnapshot && $currentSnapshot->id === $snapshot['id'] 
                                             ? 'bg-green-100 border-green-300 ring-2 ring-green-500' 
                                             : 'bg-white border-gray-200 hover:border-green-300' }}"
                                     data-snapshot-id="{{ $snapshot['id'] }}"
                                     @if($snapshot['id'] !== 'current')
                                         data-snapshot-url="{{ URL::temporarySignedRoute('client.portal.snapshot', now()->addMinutes(60), ['project' => $project->id, 'snapshot' => $snapshot['id']]) }}#producer-deliverables"
                                     @endif
                                     >
                                    
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 rounded-lg flex items-center justify-center mr-3
                                                        {{ $currentSnapshot && $currentSnapshot->id === $snapshot['id']
                                                           ? 'bg-green-500 text-white' 
                                                           : 'bg-gray-100 text-gray-600' }}">
                                                <i class="fas fa-camera text-xs"></i>
                                            </div>
                                            <div>
                                                <div class="font-semibold text-sm">V{{ $snapshot['version'] }}</div>
                                                <div class="text-xs text-gray-500">{{ $snapshot['submitted_at']->format('M j') }}</div>
                                            </div>
                                        </div>
                                        
                                        <div class="text-xs px-2 py-1 rounded-lg
                                                    {{ $snapshot['status'] === 'accepted' ? 'bg-green-100 text-green-800' :
                                                       ($snapshot['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                                        'bg-gray-100 text-gray-600') }}">
                                            {{ ucfirst($snapshot['status']) }}
                                        </div>
                                        
                                        {{-- Comparison Checkbox --}}
                                        <input type="checkbox" class="comparison-checkbox hidden ml-2" 
                                               data-snapshot-id="{{ $snapshot['id'] }}"
                                               onchange="updateComparison()">
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            
                            {{-- Version Comparison Interface --}}
                            <div id="version-comparison" class="hidden mt-4 p-4 bg-white/60 backdrop-blur-sm border border-blue-200/30 rounded-lg">
                                <div class="flex items-center justify-between mb-3">
                                    <h6 class="font-semibold text-blue-800">Compare Versions</h6>
                                    <button class="text-blue-600 hover:text-blue-800" id="js-hide-comparison">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <p class="text-sm text-blue-700 mb-3">Select two versions to compare side by side.</p>
                                <div id="comparison-content">
                                    <!-- Comparison content will be loaded here -->
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- Enhanced Current Snapshot Files Display with Audio Player --}}
                    @if($currentSnapshot && (method_exists($currentSnapshot, 'hasFiles') ? $currentSnapshot->hasFiles() : ($currentSnapshot->files ?? collect())->count() > 0))
                    <div class="mb-4">
                        {{-- Response to Feedback (moved to top for better visibility) --}}
                        @if($currentSnapshot->response_to_feedback ?? false)
                        <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <h6 class="font-semibold text-blue-800 mb-2">Producer's Response to Feedback:</h6>
                            <p class="text-blue-700 text-sm">{{ $currentSnapshot->response_to_feedback }}</p>
                        </div>
                        @endif
                        
                        <div class="flex items-center justify-between mb-3">
                            <h5 class="font-semibold text-green-800">
                                Files in Version {{ $currentSnapshot->version ?? 1 }}
                            </h5>
                            <span class="text-sm text-green-600">
                                Submitted {{ $currentSnapshot->created_at->format('M j, Y g:i A') }}
                            </span>
                        </div>

                        @if(!isset($isPreview) || !$isPreview)
                        <div class="mb-4">
                            <form x-data="approveAll({ url: '{{ URL::temporarySignedRoute('client.portal.files.approve_all', now()->addHours(24), ['project' => $project->id]) }}' })" @submit.prevent="submit" method="POST">
                                @csrf
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-green-600 to-emerald-600 text-white rounded-lg hover:from-green-700 hover:to-emerald-700 text-sm">
                                    <i class="fas fa-check-double mr-2"></i>
                                    <span x-show="!loading">Approve All Files</span>
                                    <span x-show="loading"><i class="fas fa-spinner fa-spin mr-1"></i> Approving...</span>
                                </button>
                            </form>
                        </div>
                        @endif
                        
                        {{-- Enhanced File Display with Audio Players and Annotations --}}
                        @if(request('checkout_status') === 'success')
                            <div class="mb-3 rounded-lg border border-green-200 bg-green-50 p-3 text-green-800 text-sm">
                                <i class="fas fa-check-circle mr-1"></i> Payment completed. Thank you!
                            </div>
                        @elseif(request('checkout_status') === 'cancel')
                            <div class="mb-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-amber-800 text-sm">
                                <i class="fas fa-info-circle mr-1"></i> Checkout canceled.
                            </div>
                        @endif
                        
                        @if(isset($milestones) && $milestones->count() > 0)
                        <div class="mb-5 bg-white/90 backdrop-blur-sm border border-white/50 rounded-2xl p-4 sm:p-5">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-base sm:text-lg font-semibold text-gray-900 flex items-center">
                                    <i class="fas fa-flag-checkered text-purple-500 mr-2"></i>
                                    Milestones
                                </h3>
                                @php($sumMilestones = $milestones->sum('amount'))
                                <div class="text-xs sm:text-sm text-gray-700">Total: ${{ number_format($sumMilestones, 2) }}</div>
                            </div>
                            <div class="space-y-2">
                                @foreach($milestones as $m)
                                <div class="flex items-center justify-between p-3 rounded-xl border border-gray-200 bg-white">
                                    <div class="min-w-0">
                                        <div class="font-medium text-gray-900 truncate">{{ $m->name }}</div>
                                        @if($m->description)
                                        <div class="text-sm text-gray-600 truncate">{{ $m->description }}</div>
                                        @endif
                                        <div class="text-xs text-gray-600 mt-0.5 flex items-center gap-2">
                                            <span>Status: {{ ucfirst($m->status) }}</span>
                                            @if($m->amount > 0)
                                                @if($m->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PAID)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded bg-green-100 text-green-800">Paid</span>
                                                @elseif($m->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PROCESSING)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded bg-amber-100 text-amber-800">Payment pending</span>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 ml-4">
                                        <div class="text-right">
                                            <div class="text-sm font-semibold text-gray-900">${{ number_format($m->amount, 2) }}</div>
                                        </div>
                                        @if($m->status !== 'approved' || ($m->amount > 0 && $m->payment_status !== \App\Models\Pitch::PAYMENT_STATUS_PAID))
                                            <form method="POST" action="{{ URL::temporarySignedRoute('client.portal.milestones.approve', now()->addHours(24), ['project' => $project->id, 'milestone' => $m->id]) }}">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center px-3 sm:px-4 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-xl hover:from-purple-700 hover:to-indigo-700 transition-all duration-200 text-xs sm:text-sm">
                                                    @if($m->amount > 0)
                                                        <i class="fas fa-credit-card mr-2"></i>Approve & Pay
                                                    @else
                                                        <i class="fas fa-check mr-2"></i>Approve
                                                    @endif
                                                </button>
                                            </form>
                                        @else
                                            <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-medium bg-green-100 text-green-800">
                                                <i class="fas fa-check-circle mr-1"></i> Completed
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                        <div class="space-y-4">
                            @foreach(($currentSnapshot->files ?? collect()) as $file)
                                {{-- Check if file is audio for enhanced player --}}
                                @if(in_array(pathinfo($file->file_name, PATHINFO_EXTENSION), ['mp3', 'wav', 'm4a', 'aac', 'flac']))
                                    {{-- Audio File with Enhanced Player --}}
                                    <div id="file-{{ $file->id }}" class="bg-gradient-to-r from-white/90 to-green-50/70 backdrop-blur-sm border border-green-200/50 rounded-xl p-4 shadow-sm transition-all duration-300">
                                        <div class="mb-3">
                                            <h6 class="font-semibold text-green-900 mb-1 flex items-center gap-2">
                                                {{ $file->file_name }}
                                                @if($file->client_approval_status === 'approved')
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded bg-green-100 text-green-800 text-[10px]">
                                                        <i class="fas fa-check-circle mr-1"></i> Approved
                                                    </span>
                                                @endif
                                            </h6>
                                            <div class="flex justify-between items-center">
                                                <span class="text-xs text-green-600">{{ number_format($file->size / 1024, 1) }} KB • Audio File</span>
                                                @if($pitch->canClientDownloadFiles())
                                                    <a href="{{ URL::temporarySignedRoute('client.portal.download_file', now()->addHours(24), ['project' => $project->id, 'pitchFile' => $file->id]) }}" 
                                                       class="inline-flex items-center px-3 py-1.5 bg-gradient-to-r from-green-500 to-emerald-500 hover:from-green-600 hover:to-emerald-600 text-white rounded-lg font-medium transition-all duration-200 hover:shadow-lg text-xs">
                                                        <i class="fas fa-download mr-1"></i><span class="hidden sm:inline">Download</span>
                                                    </a>
                                                @else
                                                    <span class="text-xs text-gray-500 italic">
                                                        <i class="fas fa-lock"></i>
                                                        Download Locked
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                        
                                        {{-- Enhanced Audio Player with Client Comment Support --}}
                                        @livewire('pitch-file-player', [
                                            'file' => $file,
                                            'isInCard' => true,
                                            'clientMode' => true,
                                            'clientEmail' => $project->client_email
                                        ])

                                        @if(!isset($isPreview) || !$isPreview)
                                        <div class="mt-3">
                                            @if($file->client_approval_status !== 'approved')
                                                <form method="POST" x-data="approveFile({ url: '{{ URL::temporarySignedRoute('client.portal.files.approve', now()->addHours(24), ['project' => $project->id, 'pitchFile' => $file->id]) }}' })" @submit.prevent="submit">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center px-3 py-2 bg-gradient-to-r from-green-600 to-emerald-600 text-white rounded-lg hover:from-green-700 hover:to-emerald-700 text-xs">
                                                        <i class="fas fa-check mr-2"></i>
                                                        <span x-show="!loading">Approve File</span>
                                                        <span x-show="loading"><i class="fas fa-spinner fa-spin mr-1"></i> Approving...</span>
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-xs text-green-700" data-approved-text>Approved {{ optional($file->client_approved_at)->diffForHumans() }}</span>
                                            @endif
                                        </div>
                                        @endif
                                    </div>
                                @else
                                    {{-- Non-Audio File - Standard Display --}}
                                    <div id="file-{{ $file->id }}" class="flex items-center justify-between p-3 sm:p-4 bg-gradient-to-r from-white/80 to-green-50/60 backdrop-blur-sm border border-green-200/40 rounded-xl shadow-sm hover:shadow-md transition-all duration-300">
                                        <div class="flex items-center min-w-0 flex-1 mr-3">
                                            <div class="hidden sm:flex items-center justify-center w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg mr-3 shadow-sm flex-shrink-0">
                                                <i class="fas fa-file text-white text-sm"></i>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <span class="text-xs sm:text-sm font-semibold text-green-900 block truncate">
                                                    {{ $file->file_name }}
                                                    @if($file->client_approval_status === 'approved')
                                                        <span class="inline-flex items-center ml-2 px-2 py-0.5 rounded bg-green-100 text-green-800 text-[10px]">
                                                            <i class="fas fa-check-circle mr-1"></i> Approved
                                                        </span>
                                                    @endif
                                                </span>
                                                <div class="flex justify-between items-center">
                                                    <span class="text-xs text-green-600">{{ number_format($file->size / 1024, 1) }} KB</span>
                                                    @if($pitch->canClientDownloadFiles())
                                                        <a href="{{ URL::temporarySignedRoute('client.portal.download_file', now()->addHours(24), ['project' => $project->id, 'pitchFile' => $file->id]) }}" 
                                                           class="inline-flex items-center px-2 sm:px-3 py-1 sm:py-1.5 bg-gradient-to-r from-green-500 to-emerald-500 hover:from-green-600 hover:to-emerald-600 text-white rounded-lg font-medium transition-all duration-200 hover:shadow-lg text-xs flex-shrink-0">
                                                            <i class="fas fa-download mr-1"></i><span class="hidden sm:inline">Download</span>
                                                        </a>
                                                    @else
                                                        <span class="text-xs text-gray-500 italic">
                                                            @if($pitch->status !== \App\Models\Pitch::STATUS_COMPLETED)
                                                                Available when completed
                                                            @elseif($pitch->payment_amount > 0 && !in_array($pitch->payment_status, [\App\Models\Pitch::PAYMENT_STATUS_PAID, \App\Models\Pitch::PAYMENT_STATUS_NOT_REQUIRED]))
                                                                Available after payment
                                                            @endif
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        @if(!isset($isPreview) || !$isPreview)
                                        <div class="ml-3">
                                            @if($file->client_approval_status !== 'approved')
                                                <form method="POST" x-data="approveFile({ url: '{{ URL::temporarySignedRoute('client.portal.files.approve', now()->addHours(24), ['project' => $project->id, 'pitchFile' => $file->id]) }}' })" @submit.prevent="submit">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center px-3 py-2 bg-gradient-to-r from-green-600 to-emerald-600 text-white rounded-lg hover:from-green-700 hover:to-emerald-700 text-xs">
                                                        <i class="fas fa-check mr-2"></i>
                                                        <span x-show="!loading">Approve File</span>
                                                        <span x-show="loading"><i class="fas fa-spinner fa-spin mr-1"></i> Approving...</span>
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-xs text-green-700" data-approved-text>Approved {{ optional($file->client_approved_at)->diffForHumans() }}</span>
                                            @endif
                                        </div>
                                        @endif
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    @else
                    <div class="text-center py-8">
                        <div class="w-16 h-16 bg-green-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-clock text-green-500 text-xl"></i>
                        </div>
                        @if($currentSnapshot)
                            <p class="text-green-700 font-medium mb-2">No files in this version</p>
                            <p class="text-green-600 text-sm">The producer hasn't uploaded files for this submission yet.</p>
                        @else
                            <p class="text-green-700 font-medium mb-2">No deliverables uploaded yet</p>
                            <p class="text-green-600 text-sm leading-relaxed max-w-md mx-auto">The producer will upload files here as they work on your project. You'll be notified when new files are available.</p>
                        @endif
                    </div>
                    @endif
                </div>
                    </div>
                </div>
                </div>
            </div>

        {{-- Enhanced Action Forms with Payment Flow --}}
            @if ($pitch->status === \App\Models\Pitch::STATUS_READY_FOR_REVIEW)
            <div class="mb-6 sm:mb-8 animate-fade-in-up" style="animation-delay: 0.4s;">
                <div class="bg-white/95 backdrop-blur-md shadow-xl border border-white/20 rounded-2xl p-4 sm:p-6 lg:p-8">
                    <div class="absolute inset-0 bg-gradient-to-r from-green-600/5 via-blue-600/5 to-purple-600/5 rounded-2xl"></div>
                    
                    <div class="relative z-10">
                        <div class="flex items-center mb-4 sm:mb-6">
                            <div class="flex items-center justify-center w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-br from-green-500 to-blue-600 rounded-xl mr-3 sm:mr-4 shadow-lg flex-shrink-0">
                                <i class="fas fa-tasks text-white animate-pulse text-sm sm:text-base"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <h3 class="text-lg sm:text-xl font-bold text-gray-900">Review & Approval</h3>
                                <p class="text-gray-600 text-xs sm:text-sm">The project is ready for your review. Please approve or request revisions.</p>
                            </div>
                        </div>
                        
                        <!-- Payment Information Banner (if payment required) -->
                        @if($pitch->payment_amount > 0)
                        <div class="mb-4 sm:mb-6 p-3 sm:p-4 bg-gradient-to-r from-blue-50/80 to-green-50/80 backdrop-blur-sm border border-blue-200/50 rounded-xl">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-2 sm:space-y-0">
                                <div class="flex items-center min-w-0 flex-1">
                                    <div class="w-8 h-8 sm:w-10 sm:h-10 bg-gradient-to-br from-blue-500 to-green-500 rounded-lg flex items-center justify-center mr-2 sm:mr-3 flex-shrink-0">
                                        <i class="fas fa-credit-card text-white text-xs sm:text-sm"></i>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="font-semibold text-gray-800 text-sm sm:text-base">Payment Required: ${{ number_format($pitch->payment_amount, 2) }}</p>
                                        <p class="text-xs sm:text-sm text-gray-600">Secure payment processing via Stripe</p>
                                    </div>
                                </div>
                                <div class="flex items-center text-green-600 self-start sm:self-auto">
                                    <i class="fas fa-shield-alt mr-1 sm:mr-2"></i>
                                    <span class="text-xs sm:text-sm font-medium">Secure</span>
                                </div>
                            </div>
                        </div>
                        @endif
                        
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
                            {{-- Enhanced Approve Form --}}
                            <div class="bg-gradient-to-br from-green-50/80 to-emerald-50/80 backdrop-blur-sm border border-green-200/50 rounded-xl p-6">
                                <div class="mb-4">
                                    <h4 class="font-semibold text-green-800 mb-2 flex items-center">
                                        <i class="fas fa-check-circle mr-2"></i>
                                        Approve Project
                                    </h4>
                                    <p class="text-sm text-green-700 mb-4">
                                        @if($pitch->payment_amount > 0)
                                            Clicking approve will redirect you to secure payment processing. You'll be charged ${{ number_format($pitch->payment_amount, 2) }} and the producer will be notified of completion.
                                        @else
                                            Clicking approve will notify the producer that the project is complete and satisfactory.
                                        @endif
                                    </p>
                                </div>
                                
                                <form action="{{ URL::temporarySignedRoute('client.portal.approve', now()->addHours(24), ['project' => $project->id]) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="group w-full relative overflow-hidden bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-bold py-4 px-6 rounded-xl transition-all duration-300 hover:shadow-2xl">
                                        <div class="absolute inset-0 bg-white/20 transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
                                        <div class="relative flex items-center justify-center">
                                            @if($pitch->payment_amount > 0)
                                                <i class="fas fa-credit-card mr-3"></i>
                                                <span>Approve & Pay ${{ number_format($pitch->payment_amount, 2) }}</span>
                                            @else
                                                <i class="fas fa-check-circle mr-3"></i>
                                                <span>Approve Project</span>
                                            @endif
                                        </div>
                            </button>
                        </form>

                                @if($pitch->payment_amount > 0)
                                <div class="mt-3 flex items-center justify-center text-xs text-green-600">
                                    <i class="fas fa-lock mr-1"></i>
                                    <span>Powered by Stripe • SSL Encrypted</span>
                                </div>
                                @endif
                            </div>

                            {{-- Enhanced Request Revisions Form with Structured Feedback --}}
                            <div class="bg-gradient-to-br from-amber-50/80 to-orange-50/80 backdrop-blur-sm border border-amber-200/50 rounded-xl p-6">
                                <div class="mb-4">
                                    <h4 class="font-semibold text-amber-800 mb-2 flex items-center">
                                        <i class="fas fa-edit mr-2"></i>
                                        Request Revisions
                                    </h4>
                                    <p class="text-sm text-amber-700 mb-4">
                                        Use our structured feedback system to provide specific, organized feedback about what needs to be changed.
                                    </p>
                                </div>
                                
                                {{-- Enhanced Structured Feedback Form --}}
                                <div class="bg-white/60 backdrop-blur-sm border border-amber-200/30 rounded-lg p-4 mb-4">
                                    @livewire('structured-feedback-form', [
                                        'pitch' => $pitch,
                                        'pitchFile' => ($currentSnapshot->files ?? collect())->first(),
                                        'clientEmail' => $project->client_email
                                    ])
                                </div>
                                
                                {{-- Fallback: Traditional Text Feedback --}}
                                <div class="border-t border-amber-200/50 pt-4">
                                    <h5 class="text-sm font-semibold text-amber-800 mb-3">Or send traditional feedback:</h5>
                                    <form action="{{ URL::temporarySignedRoute('client.portal.revisions', now()->addHours(24), ['project' => $project->id]) }}" method="POST">
                                        @csrf
                                        <textarea name="feedback" rows="3" 
                                                  class="w-full rounded-lg border-amber-300 shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-500 focus:ring-opacity-20 transition-all duration-300 bg-white/80 backdrop-blur-sm text-sm" 
                                                  placeholder="Additional feedback or specific requests...">{{ old('feedback') }}</textarea>
                                        @error('feedback')
                                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                        <button type="submit" class="mt-3 w-full group relative overflow-hidden bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white font-bold py-2 px-4 rounded-lg transition-all duration-300 hover:shadow-lg text-sm">
                                            <div class="relative flex items-center justify-center">
                                                <i class="fas fa-paper-plane mr-2"></i>
                                                <span>Send Traditional Feedback</span>
                                            </div>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        
        {{-- Post-Payment/Approval Success Section --}}
        @if (in_array($pitch->status, [\App\Models\Pitch::STATUS_APPROVED, \App\Models\Pitch::STATUS_COMPLETED]))
            <div class="mb-8 animate-fade-in-up" style="animation-delay: 0.4s;">
                <div class="bg-white/95 backdrop-blur-md shadow-xl border border-white/20 rounded-2xl p-8">
                    <div class="absolute inset-0 bg-gradient-to-r from-green-600/5 via-emerald-600/5 to-blue-600/5 rounded-2xl"></div>
                    
                    <div class="relative z-10">
                        <div class="text-center mb-6">
                            <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
                                <i class="fas fa-check text-white text-2xl"></i>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900 mb-2">
                                @if($pitch->status === \App\Models\Pitch::STATUS_COMPLETED)
                                    🎉 Project Completed!
                                @else
                                    ✅ Project Approved!
                                @endif
                            </h3>
                            <p class="text-gray-600">
                                @if($pitch->status === \App\Models\Pitch::STATUS_COMPLETED)
                                    Your project has been successfully completed. All deliverables are ready for download.
                                @else
                                    Thank you for approving the project! @if($pitch->payment_amount > 0)Payment has been processed successfully.@endif
                                @endif
                            </p>
                        </div>
                        
                        @if($pitch->payment_amount > 0 && $pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PAID)
                        <div class="bg-gradient-to-r from-green-50/80 to-emerald-50/80 backdrop-blur-sm border border-green-200/50 rounded-xl p-6 mb-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center mr-4">
                                        <i class="fas fa-receipt text-white"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-green-800">Payment Confirmed</h4>
                                        <p class="text-sm text-green-700">Amount: ${{ number_format($pitch->payment_amount, 2) }} • Processed securely via Stripe</p>
                                    </div>
                                </div>
                                <a href="{{ URL::temporarySignedRoute('client.portal.invoice', now()->addDays(7), ['project' => $project->id]) }}" 
                                   class="px-4 py-2 bg-green-100 hover:bg-green-200 text-green-800 font-medium rounded-lg transition-colors duration-200">
                                    <i class="fas fa-download mr-2"></i>
                                    View Invoice
                                </a>
                            </div>
                        </div>
                        @endif

                        @if(isset($milestones) && $milestones->count() > 0)
                        <div class="bg-white/90 backdrop-blur-sm border border-white/50 rounded-2xl p-4 sm:p-6 mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-3 flex items-center">
                                <i class="fas fa-flag-checkered text-purple-500 mr-2"></i>
                                Milestones
                            </h3>
                            <div class="space-y-3">
                                @foreach($milestones as $m)
                                <div class="flex items-center justify-between p-3 rounded-xl border border-gray-200">
                                    <div class="min-w-0">
                                        <div class="font-medium text-gray-900 truncate">{{ $m->name }}</div>
                                        @if($m->description)
                                        <div class="text-sm text-gray-600 truncate">{{ $m->description }}</div>
                                        @endif
                                        <div class="text-xs text-gray-500 mt-1">Status: {{ ucfirst($m->status) }} @if($m->payment_status) • Payment: {{ str_replace('_',' ', $m->payment_status) }} @endif</div>
                                    </div>
                                    <div class="flex items-center gap-3 ml-4">
                                        <div class="text-right">
                                            <div class="text-sm font-semibold text-gray-900">${{ number_format($m->amount, 2) }}</div>
                                        </div>
                                        @if($m->status !== 'approved' || ($m->amount > 0 && $m->payment_status !== \App\Models\Pitch::PAYMENT_STATUS_PAID))
                                        <form method="POST" action="{{ route('client.portal.milestones.approve', ['project' => $project->id, 'milestone' => $m->id]) }}">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-xl hover:from-purple-700 hover:to-indigo-700 transition-all duration-200">
                                                @if($m->amount > 0)
                                                <i class="fas fa-credit-card mr-2"></i>Approve & Pay
                                                @else
                                                <i class="fas fa-check mr-2"></i>Approve
                                                @endif
                                            </button>
                                        </form>
                                        @else
                                        <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-medium bg-green-100 text-green-800">
                                            <i class="fas fa-check-circle mr-1"></i> Completed
                                        </span>
                                        @endif
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        {{-- Phase 2: Enhanced Completed Project Actions --}}
                        @if($pitch->status === \App\Models\Pitch::STATUS_COMPLETED)
                        <div class="bg-gradient-to-r from-emerald-50/80 to-green-50/80 backdrop-blur-sm border border-emerald-200/50 rounded-xl p-6 mb-6">
                            <h4 class="font-semibold text-emerald-800 mb-4 flex items-center">
                                <i class="fas fa-gift mr-2"></i>
                                Your Project Deliverables
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <a href="{{ URL::temporarySignedRoute('client.portal.deliverables', now()->addDays(7), ['project' => $project->id]) }}" 
                                   class="flex items-center justify-center px-6 py-4 bg-gradient-to-r from-emerald-600 to-green-600 text-white rounded-xl hover:from-emerald-700 hover:to-green-700 transition-all duration-200 font-medium shadow-lg hover:shadow-xl transform hover:scale-105">
                                    <i class="fas fa-download mr-3"></i>
                                    <div class="text-left">
                                        <div class="font-semibold">Download Files</div>
                                        <div class="text-xs opacity-90">Get your final deliverables</div>
                                    </div>
                                </a>
                                @if($pitch->payment_amount > 0 && $pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PAID)
                                <a href="{{ URL::temporarySignedRoute('client.portal.invoice', now()->addDays(7), ['project' => $project->id]) }}" 
                                   class="flex items-center justify-center px-6 py-4 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-xl hover:from-purple-700 hover:to-indigo-700 transition-all duration-200 font-medium shadow-lg hover:shadow-xl transform hover:scale-105">
                                    <i class="fas fa-receipt mr-3"></i>
                                    <div class="text-left">
                                        <div class="font-semibold">View Invoice</div>
                                        <div class="text-xs opacity-90">Download receipt & details</div>
                                    </div>
                                </a>
                                @endif
                    </div>
                </div>
            @endif

                        <div class="text-center">
                            <p class="text-sm text-gray-600 mb-4">
                                Need to get in touch? Use the communication section below to send a message to your producer.
                            </p>
                            
                            @if($pitch->status === \App\Models\Pitch::STATUS_COMPLETED)
                            <div class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-100 to-purple-100 text-blue-800 rounded-lg border border-blue-200">
                                <i class="fas fa-star mr-2"></i>
                                <span class="font-medium">We'd love your feedback on this project!</span>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Enhanced Communication Section --}}
        <div class="mb-6 sm:mb-8 animate-fade-in-up" style="animation-delay: 0.5s;">
            <div class="bg-white/95 backdrop-blur-md shadow-xl border border-white/20 rounded-2xl p-4 sm:p-6 lg:p-8">
                <div class="absolute inset-0 bg-gradient-to-r from-purple-600/5 via-blue-600/5 to-pink-600/5 rounded-2xl"></div>
                
                <div class="relative z-10">
                    <div class="flex items-center mb-4 sm:mb-6">
                        <div class="flex items-center justify-center w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl mr-3 sm:mr-4 shadow-lg flex-shrink-0">
                            <i class="fas fa-comments text-white text-sm sm:text-base"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="text-lg sm:text-xl font-bold text-gray-900">Project Communication</h3>
                            <p class="text-gray-600 text-xs sm:text-sm">Stay in touch with your producer throughout the project</p>
                        </div>
                    </div>

                    {{-- Comment Form --}}
                    <div class="bg-gradient-to-br from-blue-50/80 to-purple-50/80 backdrop-blur-sm border border-blue-200/50 rounded-xl p-3 sm:p-4 lg:p-6 mb-4 sm:mb-6">
                            <form action="{{ URL::temporarySignedRoute('client.portal.comments.store', now()->addHours(24), ['project' => $project->id]) }}" method="POST">
                                @csrf
                            <label for="comment" class="block text-sm font-semibold text-blue-900 mb-3">Add a Comment</label>
                            <textarea name="comment" id="comment" rows="4" required 
                                      class="w-full rounded-xl border-blue-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-20 transition-all duration-300 bg-white/80 backdrop-blur-sm" 
                                      placeholder="Share your thoughts, ask questions, or provide additional feedback...">{{ old('comment') }}</textarea>
                    @error('comment')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                            <button type="submit" class="mt-4 group inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-semibold rounded-xl transition-all duration-300 hover:shadow-lg">
                                <i class="fas fa-paper-plane mr-2"></i>
                        Submit Comment
                    </button>
                </form>
                    </div>

                    {{-- Enhanced Comment History --}}
                <div class="space-y-4">
                        <h4 class="font-semibold text-gray-800 flex items-center">
                            <i class="fas fa-history mr-2 text-gray-600"></i>
                            Project Activity
                        </h4>
                        
                    @forelse ($pitch->events->whereIn('event_type', ['client_comment', 'producer_comment', 'status_change', 'client_approved', 'client_revisions_requested']) as $event)
                            <div class="p-4 rounded-xl border transition-all duration-200 hover:shadow-md
                                {{ $event->event_type === 'client_comment' 
                                    ? 'bg-gradient-to-r from-blue-50/80 to-indigo-50/80 border-blue-200/50' 
                                    : 'bg-gradient-to-r from-gray-50/80 to-slate-50/80 border-gray-200/50' }}">
                                
                                <div class="flex items-start space-x-3">
                                    <div class="flex items-center justify-center w-8 h-8 rounded-lg {{ $event->event_type === 'client_comment' ? 'bg-blue-500' : 'bg-gray-500' }} shadow-sm">
                                        <i class="fas {{ $event->event_type === 'client_comment' ? 'fa-user' : 'fa-user-tie' }} text-white text-xs"></i>
                                    </div>
                                    
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-sm font-semibold {{ $event->event_type === 'client_comment' ? 'text-blue-900' : 'text-gray-900' }}">
                                @if($event->event_type === 'client_comment' && isset($event->metadata['client_email']))
                                                    You ({{ $event->metadata['client_email'] }})
                                @elseif($event->user)
                                    {{ $event->user->name }} (Producer)
                                @else
                                    System Event [{{ $event->event_type }}]
                                @endif
                                            </span>
                                            <span class="text-xs text-gray-500">{{ $event->created_at->diffForHumans() }}</span>
                                        </div>
                                        
                                        @if($event->comment)
                                            <p class="text-gray-800 whitespace-pre-wrap leading-relaxed">{{ $event->comment }}</p>
                                        @endif
                                        
                                @if($event->status)
                                            <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-medium bg-gray-100/80 text-gray-700 mt-2">
                                        Status: {{ Str::title(str_replace('_', ' ', $event->status)) }}
                                    </span>
                                @endif
                                    </div>
                                </div>
                        </div>
                    @empty
                            <div class="text-center py-8">
                                <div class="w-12 h-12 bg-gray-100 rounded-xl flex items-center justify-center mx-auto mb-3">
                                    <i class="fas fa-comment-dots text-gray-400"></i>
                                </div>
                                <p class="text-gray-500 font-medium mb-1">No activity yet</p>
                                <p class="text-gray-400 text-sm">Comments and project updates will appear here</p>
                            </div>
                    @endforelse
                    </div>
                </div>
            </div>
                </div>

        {{-- Modern Footer --}}
        <footer class="text-center py-8 animate-fade-in-up" style="animation-delay: 0.6s;">
            <div class="bg-white/60 backdrop-blur-md border border-white/30 rounded-xl p-4 shadow-lg">
                <p class="text-gray-600 font-medium">Powered by <span class="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent font-bold">MixPitch</span></p>
            </div>
        </footer>

    </div>

    {{-- Enhanced Client File Upload JavaScript --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const uploadArea = document.getElementById('client-upload-area');
            const fileInput = document.getElementById('client-file-input');
            const filesList = document.getElementById('client-files-list');
            
            // Handle file input change
            fileInput.addEventListener('change', function(e) {
                handleFiles(e.target.files);
            });
            
            // Enhanced drag and drop
            uploadArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                uploadArea.classList.add('border-blue-500', 'bg-blue-100', 'scale-105');
            });
            
            uploadArea.addEventListener('dragleave', function(e) {
                e.preventDefault();
                uploadArea.classList.remove('border-blue-500', 'bg-blue-100', 'scale-105');
            });
            
            uploadArea.addEventListener('drop', function(e) {
                e.preventDefault();
                uploadArea.classList.remove('border-blue-500', 'bg-blue-100', 'scale-105');
                handleFiles(e.dataTransfer.files);
            });
            
            // Ensure the entire upload area is clickable (redundant with label, but good for clarity)
            uploadArea.addEventListener('click', function(e) {
                // The label will handle the click, but we can add visual feedback here if needed
                if (!e.target.closest('input')) {
                    // Add a subtle click animation
                    uploadArea.style.transform = 'scale(0.98)';
                    setTimeout(() => {
                        uploadArea.style.transform = '';
                    }, 100);
                }
            });
            
            function handleFiles(files) {
                Array.from(files).forEach(uploadFile);
            }
            
            function uploadFile(file) {
                // Create enhanced progress indicator
                const progressDiv = createProgressIndicator(file.name);
                uploadArea.insertAdjacentElement('afterend', progressDiv);
                
                const formData = new FormData();
                formData.append('file', file);
                
                // Get CSRF token - FIXED
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                
                // Use properly signed URL for client portal uploads
                const uploadUrl = '{{ URL::signedRoute("client.portal.upload_file", ["project" => $project->id]) }}';
                console.log('Upload URL:', uploadUrl);
                console.log('Current timestamp:', new Date().toISOString());
                console.log('CSRF Token:', token);
                
                // Upload file with proper headers
                fetch(uploadUrl, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': token  // FIXED: Added proper CSRF token header
                    }
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    console.log('Response headers:', response.headers);
                    
                    if (!response.ok) {
                        // Try to get error details from response
                        return response.text().then(text => {
                            console.log('Error response body:', text);
                            throw new Error(`HTTP error! status: ${response.status} - ${text.substring(0, 200)}`);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Upload response:', data);
                    progressDiv.remove();
                    
                    if (data.success) {
                        addFileToList(data.file);
                        showSuccessMessage('File uploaded successfully!');
                    } else {
                        showErrorMessage(data.message || 'Upload failed');
                    }
                })
                .catch(error => {
                    progressDiv.remove();
                    console.error('Upload error:', error);
                    showErrorMessage('Upload failed: ' + error.message);
                });
            }
            
            function createProgressIndicator(fileName) {
                const div = document.createElement('div');
                div.className = 'bg-gradient-to-r from-blue-100/90 to-indigo-100/90 backdrop-blur-md border border-blue-200/50 rounded-xl p-4 mt-4 shadow-lg animate-fade-in-up';
                div.innerHTML = `
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-spinner fa-spin text-white text-sm"></i>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-blue-900">Uploading: ${fileName}</p>
                                <p class="text-xs text-blue-700">Please wait...</p>
                            </div>
                        </div>
                        <div class="w-8 h-8 progress-pulse">
                            <div class="w-full h-2 bg-blue-200 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full animate-pulse"></div>
                            </div>
                        </div>
                    </div>
                `;
                return div;
            }
            
            function addFileToList(file) {
                const existingFiles = filesList.querySelector('.space-y-3');
                const noFilesMsg = filesList.querySelector('.text-center');
                
                if (noFilesMsg) {
                    noFilesMsg.remove();
                }
                
                if (!existingFiles) {
                    const container = document.createElement('div');
                    container.className = 'space-y-3';
                    filesList.appendChild(container);
                }
                
                const fileDiv = document.createElement('div');
                fileDiv.className = 'flex items-center justify-between p-4 bg-gradient-to-r from-white/80 to-blue-50/60 backdrop-blur-sm border border-blue-200/40 rounded-xl shadow-sm hover:shadow-md transition-all duration-200 animate-fade-in-up';
                fileDiv.setAttribute('data-file-id', file.id);
                fileDiv.innerHTML = `
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg mr-3 shadow-sm">
                            <i class="fas fa-file text-white text-sm"></i>
                        </div>
                        <div>
                            <span class="text-sm font-semibold text-blue-900">${file.name}</span>
                            <div class="text-xs text-blue-600">${(file.size / 1024).toFixed(1)} KB</div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="inline-flex items-center px-3 py-1 bg-gradient-to-r from-green-500 to-emerald-500 text-white rounded-lg text-xs font-medium shadow-sm">
                            <i class="fas fa-check mr-1"></i>Just uploaded
                        </span>
                        <button onclick="deleteFile(${file.id}, '${file.name}')" 
                                class="inline-flex items-center px-2 py-1 bg-gradient-to-r from-red-500 to-pink-500 hover:from-red-600 hover:to-pink-600 text-white rounded-lg font-medium transition-all duration-200 hover:shadow-lg text-xs">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                `;
                
                filesList.querySelector('.space-y-3').appendChild(fileDiv);
            }
            
            function showSuccessMessage(message) {
                showMessage(message, 'success');
            }
            
            function showErrorMessage(message) {
                showMessage(message, 'error');
            }
            
            function showMessage(message, type) {
                const existing = document.querySelector('.flash-message');
                if (existing) existing.remove();
                
                const div = document.createElement('div');
                div.className = `flash-message fixed top-4 right-4 px-6 py-4 rounded-xl shadow-xl z-50 backdrop-blur-md border border-white/20 animate-fade-in-up ${
                    type === 'success' 
                        ? 'bg-gradient-to-r from-green-500/90 to-emerald-500/90 text-white' 
                        : 'bg-gradient-to-r from-red-500/90 to-pink-500/90 text-white'
                }`;
                
                div.innerHTML = `
                    <div class="flex items-center">
                        <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} mr-3"></i>
                        <span class="font-medium">${message}</span>
                    </div>
                `;
                
                document.body.appendChild(div);
                
                setTimeout(() => {
                    div.style.opacity = '0';
                    div.style.transform = 'translateY(-20px)';
                    setTimeout(() => div.remove(), 300);
                }, 5000);
            }

            // Delete file function
            window.deleteFile = function(fileId, fileName) {
                if (!confirm(`Are you sure you want to delete "${fileName}"? This action cannot be undone.`)) {
                    return;
                }

                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const deleteUrl = '{{ URL::signedRoute("client.portal.delete_project_file", ["project" => $project->id, "projectFile" => "PROJECT_FILE_ID"]) }}'.replace('PROJECT_FILE_ID', fileId);
                
                console.log('Delete URL:', deleteUrl);
                
                fetch(deleteUrl, {
                    method: 'DELETE',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': token,
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => {
                    console.log('Delete response status:', response.status);
                    
                    if (!response.ok) {
                        return response.text().then(text => {
                            console.log('Delete error response:', text);
                            throw new Error(`HTTP error! status: ${response.status} - ${text.substring(0, 200)}`);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Delete response:', data);
                    
                    if (data.success) {
                        // Remove the file from the UI
                        removeFileFromList(fileId);
                        showSuccessMessage('File deleted successfully!');
                    } else {
                        showErrorMessage(data.message || 'Delete failed');
                    }
                })
                .catch(error => {
                    console.error('Delete error:', error);
                    showErrorMessage('Delete failed: ' + error.message);
                });
            };

            // Remove file from UI
            function removeFileFromList(fileId) {
                // Find and remove the file element
                const fileElements = document.querySelectorAll('[data-file-id="' + fileId + '"]');
                fileElements.forEach(element => {
                    element.style.opacity = '0';
                    element.style.transform = 'translateX(-20px)';
                    setTimeout(() => element.remove(), 300);
                });

                // If no files left, show the "no files" message
                setTimeout(() => {
                    const filesList = document.getElementById('client-files-list');
                    const remainingFiles = filesList.querySelectorAll('.space-y-3 > div');
                    
                    if (remainingFiles.length === 0) {
                        filesList.innerHTML = `
                            <div class="text-center py-6">
                                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center mx-auto mb-3">
                                    <i class="fas fa-folder-open text-blue-500"></i>
                                </div>
                                <p class="text-blue-700 font-medium mb-1">No reference files uploaded yet</p>
                                <p class="text-blue-600 text-sm">Upload files above to get started</p>
                            </div>
                        `;
                    }
                }, 400);
            }
        });
        
        // Version Comparison JavaScript
        window.selectedSnapshots = [];
        window.snapshotData = JSON.parse(document.getElementById('snapshot-data-json').textContent);
        
        window.toggleVersionComparison = function() {
            const checkboxes = document.querySelectorAll('.comparison-checkbox');
            const comparisonDiv = document.getElementById('version-comparison');
            
            checkboxes.forEach(cb => cb.classList.toggle('hidden'));
            
            if (checkboxes[0].classList.contains('hidden')) {
                // Hide comparison mode
                comparisonDiv.classList.add('hidden');
                selectedSnapshots = [];
                checkboxes.forEach(cb => cb.checked = false);
            }
        };
        
        window.hideVersionComparison = function() {
            const checkboxes = document.querySelectorAll('.comparison-checkbox');
            const comparisonDiv = document.getElementById('version-comparison');
            
            checkboxes.forEach(cb => {
                cb.classList.add('hidden');
                cb.checked = false;
            });
            comparisonDiv.classList.add('hidden');
            selectedSnapshots = [];
        };
        
        window.selectSnapshot = function(snapshotId) {
            // Only navigate if not in comparison mode
            const checkboxes = document.querySelectorAll('.comparison-checkbox');
            if (checkboxes[0].classList.contains('hidden')) {
                // In preview mode, do not navigate (server may not have signed URLs)
                if (typeof window.isPortalPreview !== 'undefined' && window.isPortalPreview) {
                    console.log('Preview mode: Snapshot navigation disabled');
                    return;
                }
                    // Find the snapshot element and get its pre-generated signed URL
                    const snapshotElement = document.querySelector(`[data-snapshot-id="${snapshotId}"]`);
                    if (snapshotElement && snapshotElement.dataset.snapshotUrl) {
                        window.location.href = snapshotElement.dataset.snapshotUrl;
                    } else if (snapshotId === 'current') {
                        // For current snapshot, just scroll to the Producer Deliverables section
                        const deliverables = document.getElementById('producer-deliverables');
                        if (deliverables) {
                            deliverables.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    } else {
                        console.warn('No signed URL found for snapshot:', snapshotId);
                    }
            }
        };
        
        window.updateComparison = function() {
            const checkedBoxes = document.querySelectorAll('.comparison-checkbox:checked');
            const comparisonDiv = document.getElementById('version-comparison');
            const comparisonContent = document.getElementById('comparison-content');
            
            selectedSnapshots = Array.from(checkedBoxes).map(cb => cb.dataset.snapshotId);
            
            if (selectedSnapshots.length === 2) {
                // Show comparison
                comparisonDiv.classList.remove('hidden');
                comparisonContent.innerHTML = `
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin text-blue-500 text-xl mb-2"></i>
                        <p class="text-blue-700">Loading version comparison...</p>
                    </div>
                `;
                
                // Show comparison using the snapshots data already available on the page
                const snapshots = window.snapshotData;
                const leftSnapshot = snapshots.find(s => s.id == selectedSnapshots[0]);
                const rightSnapshot = snapshots.find(s => s.id == selectedSnapshots[1]);
                
                if (leftSnapshot && rightSnapshot) {
                    comparisonContent.innerHTML = buildComparisonView(leftSnapshot, rightSnapshot);
                } else {
                    comparisonContent.innerHTML = `
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-center">
                            <i class="fas fa-exclamation-triangle text-red-500 text-xl mb-2"></i>
                            <p class="text-red-700">Could not find the selected versions for comparison.</p>
                        </div>
                    `;
                }
            } else if (selectedSnapshots.length > 2) {
                // Limit to 2 selections
                checkedBoxes[checkedBoxes.length - 1].checked = false;
                selectedSnapshots.pop();
            } else {
                comparisonDiv.classList.add('hidden');
            }
        };
        
        function getVersionNumber(snapshotId) {
            const snapshots = window.snapshotData;
            const snapshot = snapshots.find(s => s.id == snapshotId);
            return snapshot ? snapshot.version : '?';
        }
        
        function buildComparisonView(leftSnapshot, rightSnapshot) {
            return `
                <div class="bg-white/95 backdrop-blur-sm rounded-xl shadow-lg">
                    <div class="bg-gradient-to-r from-blue-50 to-green-50 border-b border-blue-200/50 rounded-t-xl p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-bold text-blue-900">Version Comparison</h3>
                                <p class="text-sm text-blue-700 mt-1">
                                    Comparing Version ${leftSnapshot.version} 
                                    <span class="text-blue-500 mx-2">vs</span> 
                                    Version ${rightSnapshot.version}
                                </p>
                            </div>
                        </div>
                        
                        <div class="mt-3 flex items-center space-x-4 text-sm">
                            ${buildDifferencesSummary(leftSnapshot, rightSnapshot)}
                        </div>
                    </div>
                    
                    <div class="p-4 space-y-6">
                        {{-- File Differences Section --}}
                        <div class="bg-white/80 backdrop-blur-sm border border-gray-200 rounded-xl p-4">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-lg font-semibold text-gray-900 flex items-center">
                                    <i class="fas fa-file-contract text-blue-500 mr-2"></i>
                                    File Changes
                                </h4>
                                <p class="text-xs text-gray-500 flex items-center">
                                    <i class="fas fa-mouse-pointer mr-1"></i>
                                    Click any file to view it
                                </p>
                            </div>
                            ${buildFileDiffSection(leftSnapshot, rightSnapshot)}
                        </div>
                        
                        {{-- Version Details Side by Side --}}
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                            ${buildSnapshotColumn(leftSnapshot, 'left', leftSnapshot, rightSnapshot)}
                            ${buildSnapshotColumn(rightSnapshot, 'right', leftSnapshot, rightSnapshot)}
                        </div>
                    </div>
                </div>
            `;
        }
        
        function buildDifferencesSummary(leftSnapshot, rightSnapshot) {
            // Parse the submitted_at dates properly
            const leftDate = new Date(leftSnapshot.submitted_at.date || leftSnapshot.submitted_at);
            const rightDate = new Date(rightSnapshot.submitted_at.date || rightSnapshot.submitted_at);
            
            const timeDiff = Math.abs(rightDate - leftDate);
            const daysDiff = Math.round(timeDiff / (1000 * 60 * 60 * 24));
            const hoursDiff = Math.round(timeDiff / (1000 * 60 * 60));
            
            let timeDisplay;
            if (daysDiff > 0) {
                timeDisplay = `${daysDiff} day${daysDiff !== 1 ? 's' : ''}`;
            } else if (hoursDiff > 0) {
                timeDisplay = `${hoursDiff} hour${hoursDiff !== 1 ? 's' : ''}`;
            } else {
                timeDisplay = 'Less than an hour';
            }
            
            // Compare file counts
            const leftFiles = leftSnapshot.file_count || 0;
            const rightFiles = rightSnapshot.file_count || 0;
            const fileDiff = rightFiles - leftFiles;
            
            return `
                <span class="inline-flex items-center text-blue-700">
                    <i class="fas fa-clock mr-1"></i>
                    ${timeDisplay} between versions
                </span>
                ${fileDiff !== 0 ? `
                    <span class="inline-flex items-center ${fileDiff > 0 ? 'text-green-700' : 'text-red-700'}">
                        <i class="fas fa-file${fileDiff > 0 ? '-plus' : '-minus'} mr-1"></i>
                        ${Math.abs(fileDiff)} file${Math.abs(fileDiff) !== 1 ? 's' : ''} ${fileDiff > 0 ? 'added' : 'removed'}
                    </span>
                ` : `
                    <span class="inline-flex items-center text-gray-600">
                        <i class="fas fa-check-circle mr-1"></i>
                        Same number of files
                    </span>
                `}
            `;
        }
        
        function buildSnapshotColumn(snapshot, side, leftSnapshot, rightSnapshot) {
            const sideClass = side === 'left' ? 'border-gray-200' : 'border-blue-200';
            const headerClass = side === 'left' ? 'bg-gray-50' : 'bg-blue-50';
            
            // Determine which is newer based on version number
            const isNewer = parseInt(snapshot.version) > parseInt(side === 'left' ? rightSnapshot.version : leftSnapshot.version);
            const versionLabel = isNewer ? 'Newer version' : 'Older version';
            
            // Get file count
            const fileCount = snapshot.file_count || 0;
            
            return `
                <div class="border ${sideClass} rounded-lg">
                    <div class="${headerClass} px-4 py-2 border-b ${sideClass}">
                        <h4 class="font-semibold text-gray-800">
                            Version ${snapshot.version}
                            <span class="text-sm font-normal text-gray-600 ml-2">
                                ${formatDate(snapshot.submitted_at)}
                            </span>
                        </h4>
                    </div>
                    
                    <div class="p-4 space-y-3">
                        ${snapshot.response_to_feedback ? `
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                                <h6 class="font-semibold text-blue-800 text-sm mb-1">Producer's Response:</h6>
                                <p class="text-blue-700 text-sm">${snapshot.response_to_feedback}</p>
                            </div>
                        ` : ''}
                        
                        <div class="text-center py-6">
                            <div class="w-16 h-16 ${isNewer ? 'bg-green-100' : 'bg-gray-100'} rounded-2xl flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-file-audio ${isNewer ? 'text-green-500' : 'text-gray-500'} text-xl"></i>
                            </div>
                            <p class="text-gray-900 font-medium mb-2">
                                ${fileCount} file${fileCount !== 1 ? 's' : ''} in this version
                            </p>
                            <p class="text-gray-600 text-sm mb-3">
                                ${versionLabel} • ${formatDate(snapshot.submitted_at)}
                            </p>
                            <div class="space-y-2">
                                <div class="inline-flex items-center text-xs px-2 py-1 rounded-full ${snapshot.status === 'approved' ? 'bg-green-100 text-green-700' : snapshot.status === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-700'}">
                                    <i class="fas fa-circle mr-1 text-xs"></i>
                                    ${snapshot.status ? snapshot.status.charAt(0).toUpperCase() + snapshot.status.slice(1) : 'Unknown'}
                                </div>
                                <div class="mt-2">
                                    <a href="/projects/{{ $project->id }}/portal/snapshot/${snapshot.id}" 
                                       class="text-blue-600 hover:text-blue-800 underline text-xs">
                                        View full version details
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }
        
        function formatDate(dateInput) {
            // Handle both Carbon object format and direct date strings
            let date;
            if (typeof dateInput === 'object' && dateInput.date) {
                // Carbon datetime object from Laravel
                date = new Date(dateInput.date);
            } else {
                // Direct date string
                date = new Date(dateInput);
            }
            
            // Check if date is valid
            if (isNaN(date.getTime())) {
                return 'Date unavailable';
            }
            
            return date.toLocaleDateString('en-US', { 
                month: 'short', 
                day: 'numeric', 
                year: 'numeric',
                hour: 'numeric',
                minute: '2-digit'
            });
        }
        
        function buildFileDiffSection(leftSnapshot, rightSnapshot) {
            const leftFiles = leftSnapshot.files || [];
            const rightFiles = rightSnapshot.files || [];
            
            // Create file diff analysis
            const fileDiff = analyzeFileDifferences(leftFiles, rightFiles);
            
            if (fileDiff.all.length === 0) {
                return `
                    <div class="text-center py-8">
                        <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-file-circle-question text-gray-400 text-xl"></i>
                        </div>
                        <p class="text-gray-600">No files found in either version to compare</p>
                    </div>
                `;
            }
            
            return `
                <div class="space-y-4">
                    ${fileDiff.summary ? `
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                            <p class="text-blue-800 text-sm font-medium">${fileDiff.summary}</p>
                        </div>
                    ` : ''}
                    
                    <div class="space-y-3">
                        ${fileDiff.all.map(file => buildFileComparisonCard(file)).join('')}
                    </div>
                </div>
            `;
        }
        
        function analyzeFileDifferences(leftFiles, rightFiles) {
            const leftMap = new Map(leftFiles.map(f => [f.file_name, f]));
            const rightMap = new Map(rightFiles.map(f => [f.file_name, f]));
            
            const differences = [];
            let added = 0, removed = 0, modified = 0, unchanged = 0;
            
            // Check all unique file names
            const allFileNames = new Set([...leftMap.keys(), ...rightMap.keys()]);
            
            for (const fileName of allFileNames) {
                const leftFile = leftMap.get(fileName);
                const rightFile = rightMap.get(fileName);
                
                if (!leftFile && rightFile) {
                    // File exists in right (older version) but not left (newer version) = removed
                    differences.push({
                        type: 'removed',
                        fileName: fileName,
                        rightFile: rightFile,
                        leftFile: null
                    });
                    removed++;
                } else if (leftFile && !rightFile) {
                    // File exists in left (newer version) but not right (older version) = added
                    differences.push({
                        type: 'added',
                        fileName: fileName,
                        leftFile: leftFile,
                        rightFile: null
                    });
                    added++;
                } else if (leftFile && rightFile) {
                    // File exists in both - check for modifications
                    const changes = detectFileChanges(leftFile, rightFile);
                    if (changes.length > 0) {
                        differences.push({
                            type: 'modified',
                            fileName: fileName,
                            leftFile: leftFile,
                            rightFile: rightFile,
                            changes: changes
                        });
                        modified++;
                    } else {
                        differences.push({
                            type: 'unchanged',
                            fileName: fileName,
                            leftFile: leftFile,
                            rightFile: rightFile,
                            changes: []
                        });
                        unchanged++;
                    }
                }
            }
            
            // Create summary
            const parts = [];
            if (added > 0) parts.push(`${added} file${added !== 1 ? 's' : ''} added`);
            if (removed > 0) parts.push(`${removed} file${removed !== 1 ? 's' : ''} removed`);
            if (modified > 0) parts.push(`${modified} file${modified !== 1 ? 's' : ''} modified`);
            
            // Always show unchanged count for context
            const totalChanges = added + removed + modified;
            if (totalChanges === 0) {
                parts.push('No changes detected');
            } else if (unchanged > 0) {
                parts.push(`${unchanged} file${unchanged !== 1 ? 's' : ''} unchanged`);
            }
            
            const summary = parts.join(', ');
            
            return {
                all: differences,
                summary: summary,
                stats: { added, removed, modified, unchanged }
            };
        }
        
        function detectFileChanges(leftFile, rightFile) {
            const changes = [];
            
            // Size changes
            if (leftFile.size !== rightFile.size) {
                const sizeDiff = rightFile.size - leftFile.size;
                const percentChange = Math.round((sizeDiff / leftFile.size) * 100);
                changes.push({
                    type: 'size',
                    label: 'File size',
                    oldValue: formatFileSize(leftFile.size),
                    newValue: formatFileSize(rightFile.size),
                    difference: (sizeDiff > 0 ? '+' : '') + formatFileSize(Math.abs(sizeDiff)),
                    percentChange: percentChange,
                    improved: false // File size changes aren't necessarily improvements
                });
            }
            
            // Duration changes (for audio files)
            if (leftFile.duration && rightFile.duration && leftFile.duration !== rightFile.duration) {
                const durationDiff = rightFile.duration - leftFile.duration;
                changes.push({
                    type: 'duration',
                    label: 'Duration',
                    oldValue: formatDuration(leftFile.duration),
                    newValue: formatDuration(rightFile.duration),
                    difference: (durationDiff > 0 ? '+' : '') + formatDuration(Math.abs(durationDiff)),
                    improved: false // Duration changes aren't necessarily improvements
                });
            }
            
            // Note changes
            if ((leftFile.note || '') !== (rightFile.note || '')) {
                changes.push({
                    type: 'note',
                    label: 'Notes',
                    oldValue: leftFile.note || 'No notes',
                    newValue: rightFile.note || 'No notes',
                    improved: rightFile.note && !leftFile.note // Adding notes is generally good
                });
            }
            
            // File name changes (if original_file_name differs)
            if (leftFile.original_file_name !== rightFile.original_file_name) {
                changes.push({
                    type: 'filename',
                    label: 'Original filename',
                    oldValue: leftFile.original_file_name,
                    newValue: rightFile.original_file_name,
                    improved: false
                });
            }
            
            return changes;
        }
        
        function buildFileComparisonCard(fileDiff) {
            const { type, fileName, leftFile, rightFile, changes } = fileDiff;
            
            // Don't show unchanged files to reduce clutter
            if (type === 'unchanged') {
                return '';
            }
            
            // Card styling based on change type
            const cardClass = {
                'added': 'border-green-300 bg-green-50',
                'removed': 'border-red-300 bg-red-50',
                'modified': 'border-blue-300 bg-blue-50'
            }[type];
            
            const iconClass = {
                'added': 'fas fa-plus-circle text-green-600',
                'removed': 'fas fa-minus-circle text-red-600', 
                'modified': 'fas fa-edit text-blue-600'
            }[type];
            
            const statusLabel = {
                'added': 'Added',
                'removed': 'Removed',
                'modified': 'Modified'
            }[type];
            
            // Get file for quick info
            const displayFile = leftFile || rightFile;
            const fileSize = displayFile ? formatFileSize(displayFile.size) : '';
            const duration = displayFile && displayFile.duration ? formatDuration(displayFile.duration) : '';
            
            return `
                <div class="border ${cardClass} rounded-lg p-3 cursor-pointer hover:shadow-md transition-all duration-200 hover:border-opacity-80" 
                     onclick="navigateToFile('${type}', '${fileName}', ${leftFile ? leftFile.id : 'null'}, ${rightFile ? rightFile.id : 'null'})"
                     title="Click to find this file in the current view">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center min-w-0 flex-1">
                            <i class="${iconClass} mr-2 flex-shrink-0"></i>
                            <div class="min-w-0 flex-1">
                                <h5 class="font-semibold text-gray-900 truncate">${fileName}</h5>
                                <div class="flex items-center space-x-3 text-xs text-gray-600 mt-1">
                                    ${fileSize ? `<span>${fileSize}</span>` : ''}
                                    ${duration ? `<span>${duration}</span>` : ''}
                                    <span class="text-${type === 'added' ? 'green' : type === 'removed' ? 'red' : 'blue'}-600 font-medium">${statusLabel}</span>
                                    ${changes && changes.length > 0 ? `<span>${changes.length} change${changes.length !== 1 ? 's' : ''}</span>` : ''}
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex items-center space-x-2 ml-2 flex-shrink-0">
                            <span class="text-xs px-2 py-1 rounded-full font-medium ${getStatusBadgeClass(type)}">
                                ${statusLabel}
                            </span>
                            <i class="fas fa-external-link-alt text-gray-400 text-xs"></i>
                        </div>
                    </div>
                    
                    ${buildCompactFileDetails(type, leftFile, rightFile, changes)}
                </div>
            `;
        }
        
        function buildFileComparisonDetails(type, leftFile, rightFile, changes) {
            if (type === 'added') {
                return `
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-white/60 rounded-lg p-3">
                            ${buildFileDetails(leftFile, 'Added in newer version')}
                        </div>
                        <div class="text-center py-4 text-gray-400">
                            <i class="fas fa-times-circle text-2xl mb-2"></i>
                            <p class="text-sm">Not in older version</p>
                        </div>
                    </div>
                `;
            }
            
            if (type === 'removed') {
                return `
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="text-center py-4 text-gray-400">
                            <i class="fas fa-times-circle text-2xl mb-2"></i>
                            <p class="text-sm">Not in newer version</p>
                        </div>
                        <div class="bg-white/60 rounded-lg p-3">
                            ${buildFileDetails(rightFile, 'Removed from newer version')}
                        </div>
                    </div>
                `;
            }
            
            if (type === 'modified') {
                return `
                    <div class="space-y-3">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-white/60 rounded-lg p-3">
                                <h6 class="text-sm font-medium text-gray-700 mb-2">Previous Version</h6>
                                ${buildFileDetails(leftFile, 'Previous version')}
                            </div>
                            <div class="bg-white/60 rounded-lg p-3">
                                <h6 class="text-sm font-medium text-gray-700 mb-2">Current Version</h6>
                                ${buildFileDetails(rightFile, 'Current version')}
                            </div>
                        </div>
                        
                        ${changes.length > 0 ? `
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                                <h6 class="text-sm font-semibold text-yellow-800 mb-2">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                    Changes Detected
                                </h6>
                                <div class="space-y-2">
                                    ${changes.map(change => `
                                        <div class="text-sm">
                                            <span class="font-medium text-yellow-700">${change.label}:</span>
                                            <span class="text-gray-600">${change.oldValue}</span>
                                            <i class="fas fa-arrow-right mx-2 text-yellow-600"></i>
                                            <span class="text-gray-900 font-medium">${change.newValue}</span>
                                            ${change.difference ? `
                                                <span class="text-xs ml-2 px-1 py-0.5 rounded ${change.improved ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'}">
                                                    ${change.difference}
                                                </span>
                                            ` : ''}
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        ` : ''}
                    </div>
                `;
            }
            
            if (type === 'unchanged') {
                return `
                    <div class="bg-white/60 rounded-lg p-3">
                        ${buildFileDetails(rightFile, 'No changes detected')}
                    </div>
                `;
            }
        }
        
        function buildFileDetails(file, subtitle) {
            if (!file) return '<p class="text-gray-500 text-sm">File not available</p>';
            
            return `
                <div class="space-y-2">
                    <p class="text-xs text-gray-500">${subtitle}</p>
                    <div class="space-y-1 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Size:</span>
                            <span class="font-medium">${formatFileSize(file.size)}</span>
                        </div>
                        ${file.duration ? `
                            <div class="flex justify-between">
                                <span class="text-gray-600">Duration:</span>
                                <span class="font-medium">${formatDuration(file.duration)}</span>
                            </div>
                        ` : ''}
                        ${file.note ? `
                            <div class="pt-2 border-t border-gray-200">
                                <span class="text-gray-600 text-xs">Notes:</span>
                                <p class="text-gray-800 text-sm mt-1">${file.note}</p>
                            </div>
                        ` : ''}
                    </div>
                </div>
            `;
        }
        
        function buildCompactFileDetails(type, leftFile, rightFile, changes) {
            // For simple added/removed files, don't show additional details since info is already in the main card
            if (type === 'added' || type === 'removed') {
                return '';
            }
            
            // For modified files, show the key changes inline
            if (type === 'modified' && changes && changes.length > 0) {
                return `
                    <div class="mt-2 pt-2 border-t border-blue-200">
                        <div class="flex flex-wrap gap-2 text-xs">
                            ${changes.map(change => `
                                <span class="inline-flex items-center px-2 py-1 rounded-md bg-yellow-100 text-yellow-800">
                                    <i class="fas fa-arrow-right mr-1"></i>
                                    ${change.label}: ${change.difference || change.newValue}
                                </span>
                            `).join('')}
                        </div>
                    </div>
                `;
            }
            
            return '';
        }
        
        function getStatusBadgeClass(type) {
            return {
                'added': 'bg-green-100 text-green-700',
                'removed': 'bg-red-100 text-red-700',
                'modified': 'bg-blue-100 text-blue-700',
                'unchanged': 'bg-gray-100 text-gray-600'
            }[type];
        }
        
        function formatFileSize(bytes) {
            if (!bytes) return '0 B';
            const units = ['B', 'KB', 'MB', 'GB'];
            const factor = Math.floor(Math.log(bytes) / Math.log(1024));
            return (bytes / Math.pow(1024, factor)).toFixed(1) + ' ' + units[factor];
        }
        
        function formatDuration(seconds) {
            if (!seconds) return '0:00';
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = Math.floor(seconds % 60);
            return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
        }
        
        // File navigation from diff - simplified to scroll to existing files
        window.navigateToFile = function(fileType, fileName, leftFileId, rightFileId) {
            // Determine which file ID to look for based on type
            let targetFileId;
            let actionMessage;
            
            switch(fileType) {
                case 'added':
                    targetFileId = leftFileId;
                    actionMessage = `${fileName} was added in the newer version`;
                    break;
                case 'removed':
                    targetFileId = rightFileId;
                    actionMessage = `${fileName} was removed in the newer version`;
                    break;
                case 'modified':
                    targetFileId = leftFileId || rightFileId;
                    actionMessage = `${fileName} was modified`;
                    break;
                default:
                    console.error('Unknown file type:', fileType);
                    return;
            }
            
            if (!targetFileId) {
                showFileNotFoundNotification(fileName);
                return;
            }
            
            // Try to find and scroll to the file in the current view
            const targetElement = document.getElementById(`file-${targetFileId}`);
            if (targetElement) {
                // Hide the comparison modal first
                hideVersionComparison();
                
                // Highlight and scroll to the file
                setTimeout(() => {
                    highlightAndScrollToFile(targetElement, fileName, actionMessage);
                }, 300);
            } else {
                // File not in current view, show info message
                showFileNotInCurrentVersionNotification(fileName, fileType);
            }
        };
        
        // Highlight and scroll to file after page load
        window.highlightTargetFile = function() {
            const targetFileId = sessionStorage.getItem('highlightFileId');
            const targetFileName = sessionStorage.getItem('highlightFileName');
            
            if (targetFileId) {
                // Clear the stored values
                sessionStorage.removeItem('highlightFileId');
                sessionStorage.removeItem('highlightFileName');
                
                // Find and highlight the target file
                const targetElement = document.getElementById(`file-${targetFileId}`);
                if (targetElement) {
                    // Add highlight effect
                    targetElement.classList.add('ring-4', 'ring-blue-400', 'ring-opacity-60');
                    targetElement.style.backgroundColor = 'rgba(59, 130, 246, 0.1)';
                    
                    // Smooth scroll to the file
                    setTimeout(() => {
                        targetElement.scrollIntoView({ 
                            behavior: 'smooth', 
                            block: 'center',
                            inline: 'nearest' 
                        });
                    }, 100);
                    
                    // Remove highlight after a few seconds
                    setTimeout(() => {
                        targetElement.classList.remove('ring-4', 'ring-blue-400', 'ring-opacity-60');
                        targetElement.style.backgroundColor = '';
                    }, 4000);
                    
                    // Show a subtle notification
                    showFileFoundNotification(targetFileName);
                } else {
                    console.warn(`File with ID ${targetFileId} not found on this snapshot`);
                    showFileNotFoundNotification(targetFileName);
                }
            }
        };
        
        // Highlight and scroll to a specific file element
        function highlightAndScrollToFile(element, fileName, message) {
            // Add highlight effect
            element.classList.add('ring-4', 'ring-blue-400', 'ring-opacity-60');
            element.style.backgroundColor = 'rgba(59, 130, 246, 0.1)';
            
            // Smooth scroll to the file
            element.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'center',
                inline: 'nearest' 
            });
            
            // Show notification
            showFileFoundNotification(fileName, message);
            
            // Remove highlight after a few seconds
            setTimeout(() => {
                element.classList.remove('ring-4', 'ring-blue-400', 'ring-opacity-60');
                element.style.backgroundColor = '';
            }, 4000);
        }
        
        // Utility functions for user feedback
        function showFileFoundNotification(fileName, message = null) {
            const notification = document.createElement('div');
            notification.className = 'fixed top-4 right-4 bg-blue-500 text-white px-4 py-2 rounded-lg shadow-lg z-50 transition-all duration-300';
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <div class="text-sm">
                        <div class="font-medium">Found: ${fileName}</div>
                        ${message ? `<div class="text-xs opacity-90 mt-1">${message}</div>` : ''}
                    </div>
                </div>
            `;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
        
        function showFileNotFoundNotification(fileName) {
            const notification = document.createElement('div');
            notification.className = 'fixed top-4 right-4 bg-orange-500 text-white px-4 py-2 rounded-lg shadow-lg z-50 transition-all duration-300';
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <span class="text-sm">File "${fileName}" not found</span>
                </div>
            `;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
        
        function showFileNotInCurrentVersionNotification(fileName, fileType) {
            let message = '';
            let suggestion = '';
            
            switch(fileType) {
                case 'added':
                    message = `"${fileName}" was added in a newer version`;
                    suggestion = 'Switch to a newer snapshot to see this file';
                    break;
                case 'removed':
                    message = `"${fileName}" was removed in newer versions`;
                    suggestion = 'Switch to an older snapshot to see this file';
                    break;
                case 'modified':
                    message = `"${fileName}" exists but may be in a different version`;
                    suggestion = 'Try switching snapshots to find this file';
                    break;
            }
            
            const notification = document.createElement('div');
            notification.className = 'fixed top-4 right-4 bg-indigo-500 text-white px-4 py-2 rounded-lg shadow-lg z-50 transition-all duration-300 max-w-sm';
            notification.innerHTML = `
                <div class="flex items-start">
                    <i class="fas fa-info-circle mr-2 mt-0.5"></i>
                    <div class="text-sm">
                        <div class="font-medium">${message}</div>
                        <div class="text-xs opacity-90 mt-1">${suggestion}</div>
                    </div>
                </div>
            `;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => notification.remove(), 300);
            }, 4000);
        }
        
        // Auto-scroll to Producer Deliverables if hash is present
        function autoScrollToDeliverables() {
            if (window.location.hash === '#producer-deliverables') {
                setTimeout(() => {
                    const deliverables = document.getElementById('producer-deliverables');
                    if (deliverables) {
                        deliverables.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }, 100); // Small delay to ensure page is fully loaded
            }
        }
        
        // Run functions when page loads
        document.addEventListener('DOMContentLoaded', function() {
            highlightTargetFile();
            autoScrollToDeliverables();
        });
    </script>
    <script>
        // Unobtrusive listeners replacing inline onclick attributes
        document.addEventListener('DOMContentLoaded', function() {
            document.body.addEventListener('click', function(e) {
                const deleteBtn = e.target.closest('.js-delete-file');
                if (deleteBtn) {
                    const id = deleteBtn.dataset.fileId;
                    const name = deleteBtn.dataset.fileName;
                    if (id) {
                        deleteFile(id, name);
                    }
                }

                const toggleBtn = e.target.closest('.js-toggle-comparison');
                if (toggleBtn) {
                    if (typeof window.toggleVersionComparison === 'function') {
                        window.toggleVersionComparison();
                    }
                }

                const hideBtn = e.target.closest('#js-hide-comparison');
                if (hideBtn) {
                    if (typeof window.hideVersionComparison === 'function') {
                        window.hideVersionComparison();
                    }
                }

                const snapshotItem = e.target.closest('.snapshot-item');
                if (snapshotItem) {
                    const snapshotId = snapshotItem.dataset.snapshotId;
                    if (snapshotId && typeof window.selectSnapshot === 'function') {
                        window.selectSnapshot(snapshotId);
                    }
                }
            });
        });
    </script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
        function approveFile({ url }) {
            return {
                loading: false,
                async submit() {
                    if (this.loading) return;
                    this.loading = true;
                    try {
                        const res = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Accept': 'application/json'
                            },
                            body: new FormData(this.$el)
                        });
                        const data = await res.json();
                        this.loading = false;
                        if (data && data.success) {
                            const card = this.$el.closest('[id^="file-"]');
                            if (card) {
                                const title = card.querySelector('h6, span.font-semibold');
                                if (title && !title.querySelector('.approved-chip')) {
                                    const chip = document.createElement('span');
                                    chip.className = 'approved-chip inline-flex items-center ml-2 px-2 py-0.5 rounded bg-green-100 text-green-800 text-[10px]';
                                    chip.innerHTML = '<i class="fas fa-check-circle mr-1"></i> Approved';
                                    title.appendChild(chip);
                                }
                                const approvedText = card.querySelector('[data-approved-text]');
                                if (approvedText) {
                                    approvedText.textContent = 'Approved ' + (data.approved_at_human || 'just now');
                                    approvedText.classList.remove('hidden');
                                }
                                this.$el.classList.add('hidden');
                            }
                        }
                    } catch (e) {
                        this.loading = false;
                    }
                }
            }
        }

        function approveAll({ url }) {
            return {
                loading: false,
                async submit() {
                    if (this.loading) return;
                    this.loading = true;
                    try {
                        const res = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Accept': 'application/json'
                            },
                            body: new FormData(this.$el)
                        });
                        const data = await res.json();
                        this.loading = false;
                        if (data && data.success) {
                            // Mark all approve buttons hidden, and show chips/texts across all file cards
                            document.querySelectorAll('form[x-data^="approveFile"]').forEach(form => {
                                form.classList.add('hidden');
                            });
                            document.querySelectorAll('[id^="file-"]').forEach(card => {
                                const title = card.querySelector('h6, span.font-semibold');
                                if (title && !title.querySelector('.approved-chip')) {
                                    const chip = document.createElement('span');
                                    chip.className = 'approved-chip inline-flex items-center ml-2 px-2 py-0.5 rounded bg-green-100 text-green-800 text-[10px]';
                                    chip.innerHTML = '<i class="fas fa-check-circle mr-1"></i> Approved';
                                    title.appendChild(chip);
                                }
                                const approvedText = card.querySelector('[data-approved-text]');
                                if (approvedText) {
                                    approvedText.textContent = 'Approved just now';
                                    approvedText.classList.remove('hidden');
                                }
                            });
                        }
                    } catch (e) {
                        this.loading = false;
                    }
                }
            }
        }
    </script>
    

    <!-- Add Livewire scripts for pitch-file-player component -->
    @yield('scripts')
    @livewireScripts
    @stack('scripts')
</body>
</html> 