@props(['pitch', 'title', 'description', 'uploadTitle', 'uploadDescription'])

<div class="overflow-hidden rounded-2xl border border-white/30 bg-gradient-to-br from-white/95 to-green-50/90 shadow-xl backdrop-blur-md">
    <div class="border-b border-white/20 bg-gradient-to-r from-green-500/10 via-emerald-500/10 to-green-500/10 p-4 lg:p-6 backdrop-blur-sm">
        <div class="flex items-center">
            <div class="mr-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-green-500 to-emerald-600">
                <i class="fas fa-sync-alt text-lg text-white"></i>
            </div>
            <div>
                <h4 class="text-lg font-bold text-green-800">{{ $title }}</h4>
                <p class="text-sm text-green-600">{{ $description }}</p>
            </div>
        </div>
    </div>
    <div class="p-4 lg:p-6">
        <!-- Upload Section for Producer -->
        <x-file-management.upload-section 
            :model="$pitch"
            :title="$uploadTitle"
            :description="$uploadDescription" />
    </div>
</div>