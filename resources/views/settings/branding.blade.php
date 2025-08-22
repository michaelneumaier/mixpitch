<x-layouts.app-sidebar>

<div class="bg-gradient-to-br from-blue-50 via-purple-50 to-pink-50 min-h-screen">
    <div class="mx-auto px-2 md:py-2">
        <div class="mx-auto">
            <!-- Compact Dashboard Header -->
            <flux:card class="mb-2 bg-white/50">
                <div class="flex items-center justify-between gap-3 mb-3">
                    <flux:heading size="lg" class="bg-gradient-to-r from-gray-900 via-purple-800 to-indigo-800 dark:from-gray-100 dark:via-purple-300 dark:to-indigo-300 bg-clip-text text-transparent">
                        Branding Settings
                    </flux:heading>
                    
                    <div class="flex items-center gap-2">
                        <flux:button href="{{ route('producer.client-management') }}" icon="arrow-left" variant="ghost" size="xs">
                            Client Management
                        </flux:button>
                    </div>
                </div>
                
                <flux:subheading class="text-slate-600 dark:text-slate-400">
                    Customize your brand logo and client communication settings
                </flux:subheading>
            </flux:card>

            <!-- Status Messages -->
            @if(session('success'))
                <flux:callout icon="check-circle" color="green" class="mb-4">
                    {{ session('success') }}
                </flux:callout>
            @endif
            @if($errors->any())
                <flux:callout icon="exclamation-circle" color="red" class="mb-4">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </flux:callout>
            @endif

            <!-- Form -->
            <flux:card>
                <form method="post" action="{{ route('settings.branding.update') }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <!-- Logo Section -->
                    <div class="mb-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="p-2 bg-gradient-to-r from-purple-500 to-indigo-600 rounded-lg shadow-sm">
                                <flux:icon name="photo" class="text-white" size="lg" />
                            </div>
                            <div>
                                <flux:heading size="lg" class="text-slate-800 dark:text-slate-200">Logo</flux:heading>
                                <flux:subheading class="text-slate-600 dark:text-slate-400">Upload an image or provide a URL. PNG, JPG, SVG, or WEBP up to 4MB.</flux:subheading>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <flux:field>
                                    <flux:label>Upload Image</flux:label>
                                    <input type="file" name="brand_logo_file" accept="image/png,image/jpeg,image/webp,image/svg+xml" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg file:mr-4 file:py-2 file:px-4 file:rounded-l-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 dark:file:bg-indigo-900/30 file:text-indigo-700 dark:file:text-indigo-300 hover:file:bg-indigo-100 dark:hover:file:bg-indigo-900/50 bg-white dark:bg-gray-800">
                                </flux:field>
                                @if($user->brand_logo_url)
                                <div class="mt-3">
                                    <flux:text size="sm" class="mb-2">Current logo:</flux:text>
                                    <img src="{{ $user->brand_logo_url }}" alt="Current logo" class="h-16 object-contain rounded border border-gray-200 dark:border-gray-700 p-2 bg-white dark:bg-gray-800" />
                                </div>
                                @endif
                            </div>
                            <div>
                                <flux:field>
                                    <flux:label>Or use a URL</flux:label>
                                    <flux:input type="url" name="brand_logo_url" value="{{ old('brand_logo_url', $user->brand_logo_url) }}" placeholder="https://..." />
                                </flux:field>
                                @if($user->brand_logo_url)
                                <div class="mt-3">
                                    <label class="inline-flex items-center gap-2">
                                        <flux:checkbox name="remove_logo" value="1" />
                                        <flux:text size="sm">Remove existing logo</flux:text>
                                    </label>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <flux:separator class="my-6" />

                    <!-- Invite Email Section -->
                    <div class="mb-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="p-2 bg-gradient-to-r from-blue-500 to-cyan-600 rounded-lg shadow-sm">
                                <flux:icon name="envelope" class="text-white" size="lg" />
                            </div>
                            <div>
                                <flux:heading size="lg" class="text-slate-800 dark:text-slate-200">Client Invite Email</flux:heading>
                                <flux:subheading class="text-slate-600 dark:text-slate-400">Customize the subject and message sent when inviting clients to their portal.</flux:subheading>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <flux:field>
                                <flux:label>Subject</flux:label>
                                <flux:input type="text" name="invite_email_subject" value="{{ old('invite_email_subject', $user->invite_email_subject) }}" placeholder="You're invited to your MixPitch client portal" />
                            </flux:field>
                            
                            <flux:field>
                                <flux:label>Message</flux:label>
                                <flux:textarea name="invite_email_body" rows="6" placeholder="Hi {{ $user->name }},&#10;&#10;Here's your secure link to view and approve your project.&#10;&#10;Best,&#10;{{ $user->name }}">{{ old('invite_email_body', $user->invite_email_body) }}</flux:textarea>
                            </flux:field>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-200 dark:border-slate-700">
                        <flux:button href="{{ route('producer.client-management') }}" variant="ghost">
                            Cancel
                        </flux:button>
                        <flux:button type="submit" icon="check" variant="primary">
                            Save Branding
                        </flux:button>
                    </div>
                </form>
            </flux:card>
        </div>
    </div>
</div>

</x-layouts.app-sidebar>


