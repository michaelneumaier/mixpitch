@props(['user'])

@if(!$user->hasCompletedProfile())
@php
    $completionStatus = $user->getProfileCompletionStatus();
    $missingFields = $user->getMissingProfileFields();
@endphp
<div class="mb-4 lg:mb-6">
    <div class="relative bg-gradient-to-r from-amber-50 via-orange-50 to-yellow-50 border border-amber-200 rounded-2xl shadow-lg overflow-hidden">
        <!-- Background Effects -->
        <div class="absolute inset-0 bg-gradient-to-br from-amber-400/5 via-orange-400/5 to-yellow-400/5"></div>
        <div class="absolute top-4 left-4 w-16 h-16 bg-amber-400/10 rounded-full blur-xl"></div>
        <div class="absolute bottom-4 right-4 w-12 h-12 bg-orange-400/10 rounded-full blur-lg"></div>
        
        <div class="relative p-4 lg:p-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <!-- Content Section -->
                <div class="flex-1">
                    <div class="flex items-center mb-3">
                        <div class="bg-gradient-to-r from-amber-500 to-orange-500 p-2 rounded-lg shadow-md mr-3">
                            <i class="fas fa-user-edit text-white text-lg"></i>
                        </div>
                        <div>
                            <h3 class="text-lg lg:text-xl font-bold text-amber-800">Complete Your Profile</h3>
                            <p class="text-sm text-amber-700">Set up your profile to get discovered and build your reputation</p>
                        </div>
                    </div>
                    
                    <!-- Progress Bar -->
                    <div class="mb-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-amber-800">Profile Completion</span>
                            <span class="text-sm font-bold text-amber-800">{{ $completionStatus['percentage'] }}%</span>
                        </div>
                        <div class="w-full bg-amber-200 rounded-full h-2">
                            <div class="bg-gradient-to-r from-amber-500 to-orange-500 h-2 rounded-full transition-all duration-300" 
                                 style="width: {{ $completionStatus['percentage'] }}%"></div>
                        </div>
                    </div>
                    
                    <!-- Progress Indicators -->
                    <div class="flex flex-wrap gap-3 mb-4">
                        @if(empty($user->username))
                        <div class="flex items-center bg-amber-100/80 px-3 py-2 rounded-lg border border-amber-200">
                            <i class="fas fa-at text-amber-600 mr-2 text-sm"></i>
                            <span class="text-sm font-medium text-amber-800">Username</span>
                            <span class="ml-2 text-xs text-amber-600 bg-amber-200 px-2 py-1 rounded-full">Missing</span>
                        </div>
                        @else
                        <div class="flex items-center bg-green-100/80 px-3 py-2 rounded-lg border border-green-200">
                            <i class="fas fa-check text-green-600 mr-2 text-sm"></i>
                            <span class="text-sm font-medium text-green-800">Username</span>
                            <span class="ml-2 text-xs text-green-600 bg-green-200 px-2 py-1 rounded-full">Complete</span>
                        </div>
                        @endif
                        
                        @if(empty($user->bio))
                        <div class="flex items-center bg-amber-100/80 px-3 py-2 rounded-lg border border-amber-200">
                            <i class="fas fa-file-alt text-amber-600 mr-2 text-sm"></i>
                            <span class="text-sm font-medium text-amber-800">Bio</span>
                            <span class="ml-2 text-xs text-amber-600 bg-amber-200 px-2 py-1 rounded-full">Missing</span>
                        </div>
                        @else
                        <div class="flex items-center bg-green-100/80 px-3 py-2 rounded-lg border border-green-200">
                            <i class="fas fa-check text-green-600 mr-2 text-sm"></i>
                            <span class="text-sm font-medium text-green-800">Bio</span>
                            <span class="ml-2 text-xs text-green-600 bg-green-200 px-2 py-1 rounded-full">Complete</span>
                        </div>
                        @endif
                        
                        @if(empty($user->location))
                        <div class="flex items-center bg-amber-100/80 px-3 py-2 rounded-lg border border-amber-200">
                            <i class="fas fa-map-marker-alt text-amber-600 mr-2 text-sm"></i>
                            <span class="text-sm font-medium text-amber-800">Location</span>
                            <span class="ml-2 text-xs text-amber-600 bg-amber-200 px-2 py-1 rounded-full">Missing</span>
                        </div>
                        @else
                        <div class="flex items-center bg-green-100/80 px-3 py-2 rounded-lg border border-green-200">
                            <i class="fas fa-check text-green-600 mr-2 text-sm"></i>
                            <span class="text-sm font-medium text-green-800">Location</span>
                            <span class="ml-2 text-xs text-green-600 bg-green-200 px-2 py-1 rounded-full">Complete</span>
                        </div>
                        @endif
                        
                        @if(empty($user->website))
                        <div class="flex items-center bg-amber-100/80 px-3 py-2 rounded-lg border border-amber-200">
                            <i class="fas fa-globe text-amber-600 mr-2 text-sm"></i>
                            <span class="text-sm font-medium text-amber-800">Website</span>
                            <span class="ml-2 text-xs text-amber-600 bg-amber-200 px-2 py-1 rounded-full">Optional</span>
                        </div>
                        @else
                        <div class="flex items-center bg-green-100/80 px-3 py-2 rounded-lg border border-green-200">
                            <i class="fas fa-check text-green-600 mr-2 text-sm"></i>
                            <span class="text-sm font-medium text-green-800">Website</span>
                            <span class="ml-2 text-xs text-green-600 bg-green-200 px-2 py-1 rounded-full">Complete</span>
                        </div>
                        @endif
                    </div>
                    
                    <!-- Benefits List -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                        <div class="flex items-center text-amber-700">
                            <i class="fas fa-search mr-2 text-amber-600"></i>
                            <span>Get discovered by clients</span>
                        </div>
                        <div class="flex items-center text-amber-700">
                            <i class="fas fa-star mr-2 text-amber-600"></i>
                            <span>Build professional reputation</span>
                        </div>
                        <div class="flex items-center text-amber-700">
                            <i class="fas fa-link mr-2 text-amber-600"></i>
                            <span>Share your public profile</span>
                        </div>
                        <div class="flex items-center text-amber-700">
                            <i class="fas fa-chart-line mr-2 text-amber-600"></i>
                            <span>Track your success</span>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-3 lg:flex-shrink-0">
                    <a href="{{ route('profile.edit') }}" 
                       class="group inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105">
                        <i class="fas fa-edit mr-2 group-hover:scale-110 transition-transform"></i>
                        Set Up Profile
                    </a>
                    
                    @if($user->username)
                    <a href="{{ route('profile.username', ['username' => '@' . $user->username]) }}" 
                       class="group inline-flex items-center justify-center px-4 py-3 border border-amber-300 text-amber-700 hover:text-amber-800 hover:bg-amber-50 font-medium rounded-xl transition-all duration-200">
                        <i class="fas fa-eye mr-2 group-hover:scale-110 transition-transform"></i>
                        Preview
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endif 