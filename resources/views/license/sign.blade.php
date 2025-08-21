<x-layouts.app-sidebar>
<div class="relative min-h-screen bg-gradient-to-br from-blue-50/30 via-white to-purple-50/30">
    <!-- Background Effects -->
    <div class="pointer-events-none fixed inset-0 overflow-hidden">
        <div class="absolute -right-40 -top-40 h-80 w-80 rounded-full bg-gradient-to-br from-blue-400/20 to-purple-600/20 blur-3xl"></div>
        <div class="absolute -bottom-40 -left-40 h-80 w-80 rounded-full bg-gradient-to-tr from-purple-400/20 to-pink-600/20 blur-3xl"></div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="w-20 h-20 bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-xl">
                    <i class="fas fa-file-contract text-3xl text-white"></i>
                </div>
                <h1 class="text-4xl font-bold bg-gradient-to-r from-gray-900 to-purple-800 bg-clip-text text-transparent mb-4">
                    License Agreement
                </h1>
                <p class="text-xl text-gray-600">
                    Please review and sign the license agreement for <strong>{{ $signature->project->name }}</strong>
                </p>
            </div>

            <!-- Project Information -->
            <div class="bg-white/95 backdrop-blur-sm border border-white/20 rounded-2xl shadow-xl p-6 mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
                    <div class="w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-info-circle text-white text-sm"></i>
                    </div>
                    Project Information
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="font-semibold text-gray-700 mb-2">Project Details</h3>
                        <div class="space-y-2 text-sm">
                            <div><span class="font-medium">Name:</span> {{ $signature->project->name }}</div>
                            <div><span class="font-medium">Owner:</span> {{ $signature->project->user->name }}</div>
                            <div><span class="font-medium">Genre:</span> {{ $signature->project->genre }}</div>
                            @if($signature->project->description)
                                <div><span class="font-medium">Description:</span> {{ Str::limit($signature->project->description, 100) }}</div>
                            @endif
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="font-semibold text-gray-700 mb-2">License Information</h3>
                        <div class="space-y-2 text-sm">
                            @if($signature->licenseTemplate)
                                <div><span class="font-medium">Template:</span> {{ $signature->licenseTemplate->name }}</div>
                                <div><span class="font-medium">Category:</span> {{ ucwords(str_replace('_', ' ', $signature->licenseTemplate->category ?? 'general')) }}</div>
                            @else
                                <div><span class="font-medium">Template:</span> Platform Default Terms</div>
                            @endif
                            <div><span class="font-medium">Agreement Date:</span> {{ now()->format('F j, Y') }}</div>
                        </div>
                    </div>
                </div>

                @if($signature->invitation_message)
                    <div class="mt-6 p-4 bg-blue-50 rounded-xl border border-blue-200">
                        <h3 class="font-semibold text-blue-900 mb-2">Message from {{ $signature->invitedBy->name ?? 'Project Owner' }}</h3>
                        <p class="text-blue-800">{{ $signature->invitation_message }}</p>
                    </div>
                @endif
            </div>

            <!-- License Content -->
            <div class="bg-white/95 backdrop-blur-sm border border-white/20 rounded-2xl shadow-xl p-6 mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                    <div class="w-8 h-8 bg-purple-500 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-document text-white text-sm"></i>
                    </div>
                    License Terms
                </h2>

                <div class="prose max-w-none">
                    @if($signature->licenseTemplate)
                        <div class="bg-gray-50 rounded-xl p-6 border border-gray-200 max-h-96 overflow-y-auto">
                            {!! nl2br(e($signature->licenseTemplate->getRenderedContent([
                                'project_name' => $signature->project->name,
                                'project_owner' => $signature->project->user->name,
                                'collaborator_name' => $signature->user->name,
                                'date' => now()->format('F j, Y'),
                            ]))) !!}
                        </div>
                    @else
                        <div class="bg-gray-50 rounded-xl p-6 border border-gray-200">
                            <h3 class="text-lg font-semibold mb-4">Platform Default Terms</h3>
                            <p class="mb-4">By participating in this project, you agree to the following terms:</p>
                            <ul class="list-disc list-inside space-y-2 text-sm">
                                <li>You retain ownership of your original contributions</li>
                                <li>You grant the project owner rights to use your contributions in the final work</li>
                                <li>You will be credited for your contributions unless otherwise agreed</li>
                                <li>All collaborators agree to work in good faith toward the project's completion</li>
                                <li>Disputes will be resolved through the platform's mediation process</li>
                            </ul>
                        </div>
                    @endif
                </div>

                @if($signature->project->license_notes)
                    <div class="mt-6 p-4 bg-amber-50 rounded-xl border border-amber-200">
                        <h3 class="font-semibold text-amber-900 mb-2">Additional Notes</h3>
                        <p class="text-amber-800">{{ $signature->project->license_notes }}</p>
                    </div>
                @endif
            </div>

            <!-- Signature Form -->
            <div class="bg-white/95 backdrop-blur-sm border border-white/20 rounded-2xl shadow-xl p-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                    <div class="w-8 h-8 bg-green-500 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-signature text-white text-sm"></i>
                    </div>
                    Digital Signature
                </h2>

                <form method="POST" action="{{ route('license.sign.submit', $signature) }}" class="space-y-6">
                    @csrf

                    <!-- Agreement Checkbox -->
                    <div class="bg-green-50 rounded-xl p-6 border border-green-200">
                        <label class="flex items-start space-x-3 cursor-pointer">
                            <input type="checkbox" name="agreement_accepted" value="1" required
                                   class="mt-1 h-5 w-5 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                            <div>
                                <div class="font-semibold text-green-900">I agree to the license terms</div>
                                <div class="text-sm text-green-700 mt-1">
                                    By checking this box, I confirm that I have read, understood, and agree to be bound by the license terms outlined above.
                                </div>
                            </div>
                        </label>
                        @error('agreement_accepted')
                            <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Digital Signature Input -->
                    <div>
                        <label for="digital_signature" class="block text-sm font-semibold text-gray-700 mb-2">
                            Digital Signature <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="digital_signature" id="digital_signature" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors"
                               placeholder="Type your full name as your digital signature"
                               value="{{ old('digital_signature', $signature->user->name) }}">
                        <p class="text-xs text-gray-500 mt-1">
                            Your digital signature has the same legal effect as a handwritten signature.
                        </p>
                        @error('digital_signature')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Legal Notice -->
                    <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                        <div class="flex items-start">
                            <i class="fas fa-info-circle text-gray-500 mt-1 mr-3"></i>
                            <div class="text-sm text-gray-700">
                                <strong>Legal Notice:</strong> By signing this agreement, you are entering into a legally binding contract. 
                                Your signature, IP address, and timestamp will be recorded for legal purposes. 
                                If you have questions about these terms, please contact the project owner before signing.
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-between pt-6">
                        <a href="{{ route('projects.show', $signature->project) }}" 
                           class="inline-flex items-center px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl transition-colors">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Back to Project
                        </a>
                        
                        <button type="submit" 
                                class="inline-flex items-center px-8 py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105">
                            <i class="fas fa-pen-fancy mr-2"></i>
                            Sign Agreement
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</x-layouts.app-sidebar> 