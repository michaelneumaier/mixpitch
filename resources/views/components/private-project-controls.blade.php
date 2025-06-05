@props(['project' => null, 'user', 'wireModel' => 'visibility_level'])

@php
    $availableLevels = $project ? [] : \App\Models\Project::getAvailableVisibilityLevels($user);
    $remainingQuota = \App\Models\Project::getRemainingPrivateQuota($user);
    $currentLevel = $project ? $project->visibility_level : 'public';
@endphp

<div class="space-y-4" x-data="{ showAdvanced: {{ $currentLevel !== 'public' ? 'true' : 'false' }} }">
    <!-- Visibility Level Selection -->
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            Project Visibility
        </label>
        
        <div class="space-y-3">
            @foreach($availableLevels as $level => $description)
                <label class="flex items-start space-x-3 p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors duration-200
                    {{ $currentLevel === $level ? 'border-blue-500 bg-blue-50' : 'border-gray-200' }}">
                    <input type="radio" 
                           wire:model="{{ $wireModel }}" 
                           value="{{ $level }}"
                           class="mt-1 text-blue-600 focus:ring-blue-500"
                           @click="showAdvanced = ({{ ($level !== 'public') ? 'true' : 'false' }})">
                    <div class="flex-1">
                        <div class="flex items-center space-x-2">
                            <span class="font-medium text-gray-900 capitalize">{{ str_replace('_', ' ', $level) }}</span>
                            @if(in_array($level, ['private', 'invite_only']))
                                <span class="inline-flex items-center px-2 py-0.5 bg-yellow-100 text-yellow-800 text-xs font-medium rounded-full">
                                    <i class="fas fa-crown mr-1"></i>
                                    Pro Feature
                                </span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-600 mt-1">{{ $description }}</p>
                    </div>
                    
                    <!-- Icon -->
                    <div class="text-gray-400">
                        @switch($level)
                            @case('public')
                                <i class="fas fa-globe"></i>
                                @break
                            @case('unlisted')
                                <i class="fas fa-eye-slash"></i>
                                @break
                            @case('private')
                                <i class="fas fa-lock"></i>
                                @break
                            @case('invite_only')
                                <i class="fas fa-user-friends"></i>
                                @break
                        @endswitch
                    </div>
                </label>
            @endforeach
        </div>
    </div>

    <!-- Private Project Quota Warning -->
    @if($remainingQuota !== null)
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <div class="flex items-start space-x-3">
                <i class="fas fa-info-circle text-yellow-600 mt-0.5"></i>
                <div>
                    <h4 class="font-medium text-yellow-800">Private Project Quota</h4>
                    <p class="text-sm text-yellow-700 mt-1">
                        @if($remainingQuota > 0)
                            You have <strong>{{ $remainingQuota }}</strong> private project{{ $remainingQuota !== 1 ? 's' : '' }} remaining this month.
                        @else
                            You've reached your monthly limit for private projects. 
                            <a href="{{ route('subscription.index') }}" class="underline hover:no-underline">Upgrade to Pro Engineer</a> for unlimited private projects.
                        @endif
                    </p>
                </div>
            </div>
        </div>
    @endif

    <!-- Advanced Privacy Settings -->
    <div x-show="showAdvanced" x-transition class="space-y-4 bg-gray-50 rounded-lg p-4">
        <h4 class="font-medium text-gray-900 flex items-center">
            <i class="fas fa-cog text-gray-500 mr-2"></i>
            Privacy Settings
        </h4>
        
        <!-- Access Code Display (for existing projects) -->
        @if($project && $project->access_code)
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Access Code
                </label>
                <div class="flex items-center space-x-2">
                    <input type="text" 
                           value="{{ $project->access_code }}" 
                           readonly
                           class="flex-1 px-3 py-2 border border-gray-300 rounded-md bg-gray-100 font-mono text-sm">
                    <button type="button" 
                            onclick="navigator.clipboard.writeText('{{ $project->access_code }}')"
                            class="px-3 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
                <p class="text-xs text-gray-600 mt-1">
                    Share this code to give others access to your private project.
                </p>
            </div>
            
            <!-- Direct Access Link -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Direct Access Link
                </label>
                @php
                    $accessUrl = route('projects.show', ['project' => $project->slug, 'access_code' => $project->access_code]);
                @endphp
                <div class="flex items-center space-x-2">
                    <input type="text" 
                           value="{{ $accessUrl }}" 
                           readonly
                           class="flex-1 px-3 py-2 border border-gray-300 rounded-md bg-gray-100 text-sm">
                    <button type="button" 
                            onclick="navigator.clipboard.writeText('{{ $accessUrl }}')"
                            class="px-3 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors">
                        <i class="fas fa-link"></i>
                    </button>
                </div>
                <p class="text-xs text-gray-600 mt-1">
                    Anyone with this link can access your private project.
                </p>
            </div>
        @endif

        <!-- Privacy Options -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <label class="flex items-center space-x-3 p-3 border border-gray-200 rounded-lg bg-white">
                <input type="checkbox" 
                       wire:model="privacy_settings.allow_direct_access"
                       class="text-blue-600 focus:ring-blue-500">
                <div>
                    <span class="font-medium text-gray-900">Allow Direct Access</span>
                    <p class="text-sm text-gray-600">Allow access via direct link without login</p>
                </div>
            </label>

            <label class="flex items-center space-x-3 p-3 border border-gray-200 rounded-lg bg-white">
                <input type="checkbox" 
                       wire:model="privacy_settings.track_access"
                       class="text-blue-600 focus:ring-blue-500">
                <div>
                    <span class="font-medium text-gray-900">Track Access</span>
                    <p class="text-sm text-gray-600">Log who accesses this project</p>
                </div>
            </label>
        </div>

        <!-- Privacy Notes -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Privacy Notes (Optional)
            </label>
            <textarea wire:model="privacy_settings.notes"
                      rows="3"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                      placeholder="Add any notes about why this project is private or who should have access..."></textarea>
        </div>
    </div>

    <!-- Feature Explanation -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex items-start space-x-3">
            <i class="fas fa-lightbulb text-blue-600 mt-0.5"></i>
            <div>
                <h4 class="font-medium text-blue-800">Privacy Features</h4>
                <ul class="text-sm text-blue-700 mt-1 space-y-1">
                    <li><strong>Public:</strong> Anyone can find and view your project</li>
                    <li><strong>Unlisted:</strong> Hidden from search, but accessible via direct link</li>
                    <li><strong>Private:</strong> Only you can view (Pro feature)</li>
                    <li><strong>Invite Only:</strong> Only invited users can access (Pro feature)</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Toast notification for copy actions
function showCopyToast(message) {
    // You can integrate this with your toast system
    console.log(message);
}

// Add click handlers for copy buttons
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[onclick*="clipboard"]').forEach(button => {
        button.addEventListener('click', function() {
            setTimeout(() => showCopyToast('Copied to clipboard!'), 100);
        });
    });
});
</script>
@endpush 