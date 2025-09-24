@guest
    <flux:card class="mb-6 bg-purple-50 dark:bg-purple-900/20">
        <div class="mb-6 flex items-start justify-between">
            <div class="flex items-center gap-3">
                <flux:icon.user-plus class="text-purple-500" />
                <div>
                    <flux:heading size="lg">Create Your MIXPITCH Account</flux:heading>
                    <flux:subheading>Get full access to your projects and more</flux:subheading>
                </div>
            </div>
            <flux:badge variant="info">
                <flux:icon.star class="mr-1" />
                Recommended
            </flux:badge>
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            {{-- Benefits --}}
            <div class="rounded-xl bg-white p-4 dark:bg-gray-800">
                <flux:heading size="sm" class="mb-3">Account Benefits:</flux:heading>
                <ul class="space-y-2">
                    <li class="flex items-center gap-2">
                        <flux:icon.check class="h-4 w-4 text-green-500" />
                        <flux:text size="sm">Dashboard with all your projects</flux:text>
                    </li>
                    <li class="flex items-center gap-2">
                        <flux:icon.check class="h-4 w-4 text-green-500" />
                        <flux:text size="sm">Download invoices and receipts</flux:text>
                    </li>
                    <li class="flex items-center gap-2">
                        <flux:icon.check class="h-4 w-4 text-green-500" />
                        <flux:text size="sm">Project history and analytics</flux:text>
                    </li>
                    <li class="flex items-center gap-2">
                        <flux:icon.check class="h-4 w-4 text-green-500" />
                        <flux:text size="sm">Enhanced file management</flux:text>
                    </li>
                </ul>
            </div>

            {{-- Action --}}
            <div class="flex flex-col justify-center rounded-xl bg-white p-4 dark:bg-gray-800">
                <flux:text size="sm" class="mb-4">
                    Creating an account is <strong>free</strong> and takes less than a minute.
                    All your existing projects will be automatically linked to your new account.
                </flux:text>
                <flux:button
                    href="{{ URL::temporarySignedRoute('client.portal.upgrade', now()->addHours(24), ['project' => $project->id]) }}"
                    variant="primary" class="w-full">
                    <flux:icon.user-plus class="mr-2" />
                    Create Free Account
                </flux:button>
                <p class="mt-2 text-center text-xs text-purple-600">
                    Using email: {{ $project->client_email }}
                </p>
            </div>
        </div>
    </flux:card>
@endguest

