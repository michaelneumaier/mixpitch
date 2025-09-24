<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Client Portal - {{ $project->title }}</title>

    <x-pwa-meta />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600&display=swap" rel="stylesheet" />

    @fluxAppearance

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- WaveSurfer.js for audio -->
    <script src="https://unpkg.com/wavesurfer.js"></script>

    @livewireStyles

    @php
        $allowedTypes = config('file-types.allowed_types', [
            'audio/*',
            'video/*',
            'application/pdf',
            'image/*',
            'application/zip',
        ]);
    @endphp
    <script>
        // Set default allowed file types from configuration
        window.defaultAllowedFileTypes = @json($allowedTypes);
    </script>

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="min-h-screen bg-gray-50 font-sans antialiased dark:bg-gray-900">

    @if (isset($isPreview) && $isPreview)
        <!-- Preview Banner -->
        <flux:callout variant="warning" class="rounded-none border-x-0 border-t-0">
            <flux:icon.eye class="mr-2" />
            <span class="font-semibold">Preview Mode</span>
            <span class="mx-2">•</span>
            <span>This is how your client sees their portal</span>
        </flux:callout>
    @endif

    <x-draggable-upload-page :model="$project" title="Client Portal">
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
            <div class="mx-auto p-2">
                <div class="mx-auto max-w-5xl">

                    {{-- Project Header Card --}}
                    <flux:card class="mb-6">
                        <div
                            class="mb-6 flex flex-col space-y-4 sm:flex-row sm:items-center sm:justify-between sm:space-y-0">
                            <div class="flex items-center gap-4">
                                @if (!empty($branding['logo_url']))
                                    <img src="{{ $branding['logo_url'] }}" alt="Brand Logo"
                                        class="h-12 w-12 rounded-xl bg-white object-contain p-1 shadow-lg sm:h-16 sm:w-16">
                                @else
                                    <div
                                        class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-purple-600 shadow-lg sm:h-16 sm:w-16">
                                        <flux:icon.briefcase class="text-lg text-white sm:text-2xl" />
                                    </div>
                                @endif
                                <div class="min-w-0 flex-1">
                                    <flux:heading size="xl" class="break-words">{{ $project->title }}
                                    </flux:heading>
                                    <flux:subheading>Managed by {{ $branding['brand_display'] ?? $pitch->user->name }}
                                    </flux:subheading>
                                </div>
                            </div>

                            @php
                                $statusVariant = 'ghost';
                                switch ($pitch->status) {
                                    case \App\Models\Pitch::STATUS_PENDING:
                                    case \App\Models\Pitch::STATUS_AWAITING_ACCEPTANCE:
                                        $statusVariant = 'ghost';
                                        break;
                                    case \App\Models\Pitch::STATUS_IN_PROGRESS:
                                    case \App\Models\Pitch::STATUS_CONTEST_ENTRY:
                                        $statusVariant = 'info';
                                        break;
                                    case \App\Models\Pitch::STATUS_READY_FOR_REVIEW:
                                        $statusVariant = 'warning';
                                        break;
                                    case \App\Models\Pitch::STATUS_REVISIONS_REQUESTED:
                                    case \App\Models\Pitch::STATUS_CLIENT_REVISIONS_REQUESTED:
                                        $statusVariant = 'warning';
                                        break;
                                    case \App\Models\Pitch::STATUS_APPROVED:
                                        $statusVariant = 'info';
                                        break;
                                    case \App\Models\Pitch::STATUS_COMPLETED:
                                    case \App\Models\Pitch::STATUS_CONTEST_WINNER:
                                        $statusVariant = 'success';
                                        break;
                                    case \App\Models\Pitch::STATUS_CONTEST_RUNNER_UP:
                                        $statusVariant = 'success';
                                        break;
                                    case \App\Models\Pitch::STATUS_DENIED:
                                    case \App\Models\Pitch::STATUS_CLOSED:
                                    case \App\Models\Pitch::STATUS_CONTEST_NOT_SELECTED:
                                        $statusVariant = 'danger';
                                        break;
                                }
                            @endphp
                            <flux:badge size="lg" :variant="$statusVariant" class="self-start sm:self-auto">
                                <div class="mr-2 h-2 w-2 animate-pulse rounded-full bg-current"></div>
                                {{ $pitch->readable_status }}
                            </flux:badge>
                        </div>

                        {{-- Project Progress --}}
                        <div class="mb-6 rounded-xl bg-gray-50 p-6 dark:bg-gray-800">
                            <div class="mb-4 flex items-center gap-3">
                                <flux:icon.map class="text-blue-500" />
                                <flux:heading size="sm">Project Progress</flux:heading>
                            </div>

                            <!-- Progress Steps -->
                            <div class="relative flex items-center justify-between overflow-x-auto">
                                <!-- Progress Line Background -->
                                <div
                                    class="absolute left-4 right-4 top-4 hidden h-0.5 rounded-full bg-gray-200 sm:block">
                                </div>

                                <!-- Dynamic Progress Line -->
                                @php
                                    $progressWidth = '25%'; // default
                                    switch ($pitch->status) {
                                        case \App\Models\Pitch::STATUS_PENDING:
                                            $progressWidth = '0%';
                                            break;
                                        case \App\Models\Pitch::STATUS_IN_PROGRESS:
                                            $progressWidth = '25%';
                                            break;
                                        case \App\Models\Pitch::STATUS_READY_FOR_REVIEW:
                                            $progressWidth = '50%';
                                            break;
                                        case \App\Models\Pitch::STATUS_APPROVED:
                                            $progressWidth = '75%';
                                            break;
                                        case \App\Models\Pitch::STATUS_COMPLETED:
                                            $progressWidth = '100%';
                                            break;
                                    }
                                @endphp
                                <div class="absolute left-4 top-4 hidden h-0.5 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 transition-all duration-1000 ease-out sm:block"
                                    style="width: {{ $progressWidth }};"></div>

                                <!-- Step 1: Project Started -->
                                <div class="relative flex min-w-0 flex-1 flex-col items-center">
                                    <div
                                        class="{{ in_array($pitch->status, [
                                            \App\Models\Pitch::STATUS_PENDING,
                                            \App\Models\Pitch::STATUS_IN_PROGRESS,
                                            \App\Models\Pitch::STATUS_READY_FOR_REVIEW,
                                            \App\Models\Pitch::STATUS_APPROVED,
                                            \App\Models\Pitch::STATUS_COMPLETED,
                                        ])
                                            ? 'bg-blue-500 border-blue-500 text-white'
                                            : 'bg-white border-gray-300 text-gray-400' }} flex h-6 w-6 items-center justify-center rounded-full border-2 sm:h-8 sm:w-8">
                                        <i class="fas fa-play text-xs"></i>
                                    </div>
                                    <span
                                        class="mt-1 w-full truncate text-center text-xs font-medium text-gray-600 sm:mt-2">Started</span>
                                </div>

                                <!-- Step 2: In Progress -->
                                <div class="relative flex min-w-0 flex-1 flex-col items-center">
                                    <div
                                        class="{{ in_array($pitch->status, [
                                            \App\Models\Pitch::STATUS_IN_PROGRESS,
                                            \App\Models\Pitch::STATUS_READY_FOR_REVIEW,
                                            \App\Models\Pitch::STATUS_APPROVED,
                                            \App\Models\Pitch::STATUS_COMPLETED,
                                        ])
                                            ? 'bg-purple-500 border-purple-500 text-white'
                                            : 'bg-white border-gray-300 text-gray-400' }} flex h-6 w-6 items-center justify-center rounded-full border-2 sm:h-8 sm:w-8">
                                        <i
                                            class="fas fa-cog {{ $pitch->status === \App\Models\Pitch::STATUS_IN_PROGRESS ? 'animate-spin' : '' }} text-xs"></i>
                                    </div>
                                    <span
                                        class="mt-1 w-full truncate text-center text-xs font-medium text-gray-600 sm:mt-2">In
                                        Progress</span>
                                </div>

                                <!-- Step 3: Ready for Review -->
                                <div class="relative flex min-w-0 flex-1 flex-col items-center">
                                    <div
                                        class="{{ in_array($pitch->status, [
                                            \App\Models\Pitch::STATUS_READY_FOR_REVIEW,
                                            \App\Models\Pitch::STATUS_APPROVED,
                                            \App\Models\Pitch::STATUS_COMPLETED,
                                        ])
                                            ? 'bg-amber-500 border-amber-500 text-white'
                                            : 'bg-white border-gray-300 text-gray-400' }} flex h-6 w-6 items-center justify-center rounded-full border-2 sm:h-8 sm:w-8">
                                        <i
                                            class="fas fa-eye {{ $pitch->status === \App\Models\Pitch::STATUS_READY_FOR_REVIEW ? 'animate-pulse' : '' }} text-xs"></i>
                                    </div>
                                    <span
                                        class="mt-1 w-full truncate text-center text-xs font-medium text-gray-600 sm:mt-2">Review</span>
                                </div>

                                <!-- Step 4: Approved -->
                                <div class="relative flex min-w-0 flex-1 flex-col items-center">
                                    <div
                                        class="{{ in_array($pitch->status, [\App\Models\Pitch::STATUS_APPROVED, \App\Models\Pitch::STATUS_COMPLETED])
                                            ? 'bg-green-500 border-green-500 text-white'
                                            : 'bg-white border-gray-300 text-gray-400' }} flex h-6 w-6 items-center justify-center rounded-full border-2 sm:h-8 sm:w-8">
                                        <i class="fas fa-check text-xs"></i>
                                    </div>
                                    <span
                                        class="mt-1 w-full truncate text-center text-xs font-medium text-gray-600 sm:mt-2">Approved</span>
                                </div>

                                <!-- Step 5: Completed -->
                                <div class="relative flex min-w-0 flex-1 flex-col items-center">
                                    <div
                                        class="{{ $pitch->status === \App\Models\Pitch::STATUS_COMPLETED
                                            ? 'bg-emerald-500 border-emerald-500 text-white'
                                            : 'bg-white border-gray-300 text-gray-400' }} flex h-6 w-6 items-center justify-center rounded-full border-2 sm:h-8 sm:w-8">
                                        <i class="fas fa-trophy text-xs"></i>
                                    </div>
                                    <span
                                        class="mt-1 w-full truncate text-center text-xs font-medium text-gray-600 sm:mt-2">Complete</span>
                                </div>
                            </div>

                            <!-- Current Status Description -->
                            <div class="mt-6 rounded-lg border border-white/40 bg-white/60 p-4 backdrop-blur-sm">
                                <p class="text-sm text-gray-700">
                                    @if ($pitch->status === \App\Models\Pitch::STATUS_PENDING)
                                        <i class="fas fa-info-circle mr-2 text-blue-500"></i>
                                        Your project has been created and the producer is preparing your deliverables.
                                    @elseif($pitch->status === \App\Models\Pitch::STATUS_IN_PROGRESS)
                                        <i class="fas fa-clock mr-2 text-purple-500"></i>
                                        The producer is actively working on your project. You'll be notified when it's
                                        ready for review.
                                    @elseif($pitch->status === \App\Models\Pitch::STATUS_READY_FOR_REVIEW)
                                        <i class="fas fa-bell mr-2 animate-pulse text-amber-500"></i>
                                        <strong>Action Required:</strong> Your project is ready for review! Please check
                                        the deliverables below and approve or request revisions.
                                    @elseif($pitch->status === \App\Models\Pitch::STATUS_APPROVED)
                                        <i class="fas fa-check-circle mr-2 text-green-500"></i>
                                        Great! You've approved the project. @if ($pitch->payment_amount > 0)
                                            Payment processing is in progress.
                                        @endif
                                    @elseif($pitch->status === \App\Models\Pitch::STATUS_COMPLETED)
                                        <i class="fas fa-star mr-2 text-emerald-500"></i>
                                        🎉 Project completed successfully! All deliverables are available below.
                                    @else
                                        <i class="fas fa-question-circle mr-2 text-gray-500"></i>
                                        Project status: {{ $pitch->readable_status }}
                                    @endif
                                </p>
                            </div>
                        </div>

                        {{-- Client Info & Payment Information --}}
                        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                            {{-- Client Info --}}
                            <div class="rounded-xl bg-gray-50 p-4 dark:bg-gray-800">
                                <div class="flex items-center gap-3">
                                    <flux:icon.user-circle class="flex-shrink-0 text-blue-500" />
                                    <div class="min-w-0 flex-1">
                                        @if ($project->client_name)
                                            <flux:heading size="sm" class="truncate">{{ $project->client_name }}
                                            </flux:heading>
                                            <flux:subheading class="truncate">{{ $project->client_email }}
                                            </flux:subheading>
                                        @else
                                            <flux:heading size="sm" class="truncate">{{ $project->client_email }}
                                            </flux:heading>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- Payment Information --}}
                            @if ($pitch->payment_amount > 0)
                                <div class="rounded-xl bg-green-50 p-4 dark:bg-green-900/20">
                                    <div class="flex items-center justify-between">
                                        <div class="flex min-w-0 flex-1 items-center gap-3">
                                            <flux:icon.currency-dollar class="flex-shrink-0 text-green-500" />
                                            <div class="min-w-0 flex-1">
                                                <flux:heading size="sm">
                                                    ${{ number_format($pitch->payment_amount, 2) }}
                                                </flux:heading>
                                                <flux:subheading>Project Value</flux:subheading>
                                            </div>
                                        </div>
                                        <div class="ml-2 flex-shrink-0 text-right">
                                            @if ($pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PAID)
                                                <flux:badge variant="success" size="sm">
                                                    <flux:icon.check-circle class="mr-1" />
                                                    <span class="hidden sm:inline">Paid</span>
                                                    <span class="sm:hidden">✓</span>
                                                </flux:badge>
                                            @else
                                                <flux:badge variant="warning" size="sm">
                                                    <flux:icon.clock class="mr-1" />
                                                    <span class="hidden sm:inline">Payment Due</span>
                                                    <span class="sm:hidden">Due</span>
                                                </flux:badge>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </flux:card>

                    {{-- Flash Messages --}}
                    @if (request()->query('checkout_status') === 'success')
                        <flux:callout variant="success" class="mb-6">
                            <flux:icon.check-circle class="mr-2" />
                            Payment successful! The project has been approved and the producer has been notified.
                            <script type="application/json" id="snapshot-data-json">@json($snapshotHistory)</script>
                        </flux:callout>
                    @elseif(request()->query('checkout_status') === 'cancel')
                        <flux:callout variant="warning" class="mb-6">
                            <flux:icon.exclamation-triangle class="mr-2" />
                            Payment was cancelled. You can try approving again when ready.
                        </flux:callout>
                    @endif

                    @if (session('success'))
                        <flux:callout variant="success" class="mb-6">
                            <flux:icon.check-circle class="mr-2" />
                            {{ session('success') }}
                        </flux:callout>
                    @endif

                    @if ($errors->any())
                        <flux:callout variant="danger" class="mb-6">
                            <flux:icon.exclamation-circle class="mr-2" />
                            <div>
                                <strong>Please fix the following errors:</strong>
                                <ul class="mt-2 list-inside list-disc space-y-1">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </flux:callout>
                    @endif

                    {{-- Project Description --}}
                    @if ($project->description)
                        <flux:card class="mb-6">
                            <div class="mb-4 flex items-center gap-3">
                                <flux:icon.document-text class="text-blue-500" />
                                <flux:heading size="lg">Project Brief</flux:heading>
                            </div>
                            <div class="rounded-xl bg-gray-50 p-6 dark:bg-gray-800">
                                <flux:text class="whitespace-pre-wrap">{{ $project->description }}</flux:text>
                            </div>
                        </flux:card>
                    @endif

                    {{-- Account Upgrade Section --}}
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
                                        variant="primary" size="lg" class="w-full">
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

                    {{-- Enhanced Files Section --}}
                    {{-- Project Files Section --}}
                    <flux:card class="mb-6">
                        <div class="mb-6 flex items-center gap-3">
                            <flux:icon.folder-open class="text-purple-500" />
                            <div>
                                <flux:heading size="lg">Project Files</flux:heading>
                                <flux:subheading>Manage your project files and deliverables</flux:subheading>
                            </div>
                        </div>
                        {{-- Client Reference Files Section --}}
                        <div class="mb-6 rounded-xl bg-blue-50 p-6 dark:bg-blue-900/20">
                            <div class="mb-4 flex items-center gap-3">
                                <flux:icon.cloud-arrow-up class="text-blue-500" />
                                <flux:heading size="lg">Your Reference Files</flux:heading>
                            </div>
                            <flux:text size="sm" class="mb-6">Upload briefs, references, or examples to help
                                the
                                producer understand your requirements perfectly.</flux:text>

                            {{-- File Upload Component - Using Global Uploader --}}
                            <x-file-management.upload-section :model="$project" context="client_portal"
                                accept="audio/*,video/*,.pdf,.doc,.docx,.jpg,.jpeg,.png" :max-files="10"
                                class="mb-4" />

                            {{-- Client Files List --}}
                            @if ($project->files->count() > 0)
                                <div class="space-y-3">
                                    @foreach ($project->files as $file)
                                        <div class="flex items-center justify-between rounded-xl border bg-white p-4 dark:bg-gray-700"
                                            data-file-id="{{ $file->id }}">
                                            <div class="flex min-w-0 flex-1 items-center gap-3">
                                                <flux:icon.document class="flex-shrink-0 text-blue-500" />
                                                <div class="min-w-0 flex-1">
                                                    <flux:heading size="sm" class="truncate">
                                                        {{ $file->file_name }}
                                                    </flux:heading>
                                                    <flux:subheading>{{ number_format($file->size / 1024, 1) }} KB
                                                    </flux:subheading>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <flux:button
                                                    href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('client.portal.download_project_file', now()->addHours(24), ['project' => $project->id, 'projectFile' => $file->id]) }}"
                                                    variant="outline" size="sm">
                                                    <flux:icon.arrow-down-tray class="mr-1" />
                                                    <span class="hidden sm:inline">Download</span>
                                                </flux:button>
                                                <flux:button variant="danger" size="sm" class="js-delete-file"
                                                    data-file-id="{{ $file->id }}"
                                                    data-file-name="{{ $file->file_name }}">
                                                    <flux:icon.trash class="mr-1" />
                                                    <span class="hidden sm:inline">Delete</span>
                                                </flux:button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="py-8 text-center">
                                    <flux:icon.folder-open class="mx-auto mb-3 text-blue-500" size="xl" />
                                    <flux:heading size="sm" class="mb-2">No reference files uploaded yet
                                    </flux:heading>
                                    <flux:subheading>Upload files above to get started</flux:subheading>
                                </div>
                            @endif
                        </div>

                        {{-- Producer Deliverables with Snapshot Navigation --}}
                        <div id="producer-deliverables" class="mb-6 rounded-xl bg-green-50 p-6 dark:bg-green-900/20">

                            {{-- Header with Version Info --}}
                            <div class="mb-6 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <flux:icon.clock class="text-green-500" />
                                    <div>
                                        <flux:heading size="lg">Producer Deliverables</flux:heading>
                                        <flux:subheading>
                                            @if ($currentSnapshot)
                                                Version {{ $currentSnapshot->version ?? 1 }} of
                                                {{ $snapshotHistory->count() }}
                                            @else
                                                No submissions yet
                                            @endif
                                        </flux:subheading>
                                    </div>
                                </div>

                                @if ($snapshotHistory->count() > 1)
                                    <flux:badge variant="success" size="sm">
                                        {{ $snapshotHistory->count() }} versions available
                                    </flux:badge>
                                @endif
                            </div>

                            {{-- Enhanced Snapshot Navigation with Version Comparison --}}
                            @if ($snapshotHistory->count() > 1)
                                <div class="mb-6">
                                    <div
                                        class="rounded-xl border border-blue-200/50 bg-gradient-to-r from-blue-50/80 to-green-50/80 p-4 backdrop-blur-sm">
                                        <div class="mb-3 flex items-center justify-between">
                                            <h5 class="font-semibold"
                                                style="color: {{ $branding['primary'] ?? '#1f2937' }};">Submission
                                                History
                                            </h5>
                                            @if ($snapshotHistory->count() >= 2)
                                                <button
                                                    class="js-toggle-comparison rounded-lg bg-blue-100 px-3 py-1 text-sm text-blue-800 transition-colors duration-200 hover:bg-blue-200">
                                                    <i class="fas fa-columns mr-1"></i>Compare Versions
                                                </button>
                                            @endif
                                        </div>

                                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3"
                                            id="snapshot-grid">
                                            @foreach ($snapshotHistory as $snapshot)
                                                <div class="snapshot-item {{ $currentSnapshot && $currentSnapshot->id === $snapshot['id']
                                                    ? 'bg-green-100 border-green-300 ring-2 ring-green-500'
                                                    : 'bg-white border-gray-200 hover:border-green-300' }} group cursor-pointer rounded-lg border p-3 transition-all duration-200 hover:shadow-md"
                                                    data-snapshot-id="{{ $snapshot['id'] }}"
                                                    @if ($snapshot['id'] !== 'current') data-snapshot-url="{{ URL::temporarySignedRoute('client.portal.snapshot', now()->addMinutes(60), ['project' => $project->id, 'snapshot' => $snapshot['id']]) }}#producer-deliverables" @endif>

                                                    <div class="flex items-center justify-between">
                                                        <div class="flex items-center">
                                                            <div
                                                                class="{{ $currentSnapshot && $currentSnapshot->id === $snapshot['id']
                                                                    ? 'bg-green-500 text-white'
                                                                    : 'bg-gray-100 text-gray-600' }} mr-3 flex h-8 w-8 items-center justify-center rounded-lg">
                                                                <i class="fas fa-camera text-xs"></i>
                                                            </div>
                                                            <div>
                                                                <div class="text-sm font-semibold">
                                                                    V{{ $snapshot['version'] }}</div>
                                                                <div class="text-xs text-gray-500">
                                                                    {{ $snapshot['submitted_at']->format('M j') }}
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div
                                                            class="{{ $snapshot['status'] === 'accepted'
                                                                ? 'bg-green-100 text-green-800'
                                                                : ($snapshot['status'] === 'pending'
                                                                    ? 'bg-yellow-100 text-yellow-800'
                                                                    : 'bg-gray-100 text-gray-600') }} rounded-lg px-2 py-1 text-xs">
                                                            {{ ucfirst($snapshot['status']) }}
                                                        </div>

                                                        {{-- Comparison Checkbox --}}
                                                        <input type="checkbox" class="comparison-checkbox ml-2 hidden"
                                                            data-snapshot-id="{{ $snapshot['id'] }}"
                                                            onchange="updateComparison()">
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>

                                        {{-- Version Comparison Interface --}}
                                        <div id="version-comparison"
                                            class="mt-4 hidden rounded-lg border border-blue-200/30 bg-white/60 p-4 backdrop-blur-sm">
                                            <div class="mb-3 flex items-center justify-between">
                                                <h6 class="font-semibold text-blue-800">Compare Versions</h6>
                                                <button class="text-blue-600 hover:text-blue-800"
                                                    id="js-hide-comparison">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                            <p class="mb-3 text-sm text-blue-700">Select two versions to compare side
                                                by
                                                side.</p>
                                            <div id="comparison-content">
                                                <!-- Comparison content will be loaded here -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- Enhanced Current Snapshot Files Display with Audio Player --}}
                            @if (
                                $currentSnapshot &&
                                    (method_exists($currentSnapshot, 'hasFiles')
                                        ? $currentSnapshot->hasFiles()
                                        : ($currentSnapshot->files ?? collect())->count() > 0))
                                <div class="mb-4">
                                    {{-- Response to Feedback (moved to top for better visibility) --}}
                                    @if ($currentSnapshot->response_to_feedback ?? false)
                                        <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 p-4">
                                            <h6 class="mb-2 font-semibold text-blue-800">Producer's Response to
                                                Feedback:
                                            </h6>
                                            <p class="text-sm text-blue-700">
                                                {{ $currentSnapshot->response_to_feedback }}
                                            </p>
                                        </div>
                                    @endif

                                    <div class="mb-3 flex items-center justify-between">
                                        <h5 class="font-semibold text-green-800">
                                            Files in Version {{ $currentSnapshot->version ?? 1 }}
                                        </h5>
                                        <span class="text-sm text-green-600">
                                            Submitted {{ $currentSnapshot->created_at->format('M j, Y g:i A') }}
                                        </span>
                                    </div>

                                    @if (!isset($isPreview) || !$isPreview)
                                        <div class="mb-4">
                                            <form x-data="approveAll({ url: '{{ URL::temporarySignedRoute('client.portal.files.approve_all', now()->addHours(24), ['project' => $project->id]) }}' })" @submit.prevent="submit" method="POST">
                                                @csrf
                                                <button type="submit"
                                                    class="inline-flex items-center rounded-lg bg-gradient-to-r from-green-600 to-emerald-600 px-4 py-2 text-sm text-white hover:from-green-700 hover:to-emerald-700">
                                                    <i class="fas fa-check-double mr-2"></i>
                                                    <span x-show="!loading">Approve All Files</span>
                                                    <span x-show="loading"><i class="fas fa-spinner fa-spin mr-1"></i>
                                                        Approving...</span>
                                                </button>
                                            </form>
                                        </div>
                                    @endif

                                    {{-- Enhanced File Display with Audio Players and Annotations --}}
                                    @if (request('checkout_status') === 'success')
                                        <div
                                            class="mb-3 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800">
                                            <i class="fas fa-check-circle mr-1"></i> Payment completed. Thank you!
                                        </div>
                                    @elseif(request('checkout_status') === 'cancel')
                                        <div
                                            class="mb-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                                            <i class="fas fa-info-circle mr-1"></i> Checkout canceled.
                                        </div>
                                    @endif

                                    {{-- Milestones Section --}}
                                    @if (isset($milestones) && $milestones->count() > 0)
                                        <div class="mb-6 rounded-xl bg-purple-50 p-6 dark:bg-purple-900/20">
                                            <div class="mb-4 flex items-center justify-between">
                                                <div class="flex items-center gap-2">
                                                    <flux:icon.flag class="text-purple-500" />
                                                    <flux:heading size="lg">Milestones</flux:heading>
                                                </div>
                                                @php($sumMilestones = $milestones->sum('amount'))
                                                <flux:badge variant="info">Total:
                                                    ${{ number_format($sumMilestones, 2) }}
                                                </flux:badge>
                                            </div>
                                            <div class="space-y-3">
                                                @foreach ($milestones as $m)
                                                    <div
                                                        class="flex items-center justify-between rounded-xl border bg-white p-4 dark:bg-gray-700">
                                                        <div class="min-w-0 flex-1">
                                                            <flux:heading size="sm" class="truncate">
                                                                {{ $m->name }}</flux:heading>
                                                            @if ($m->description)
                                                                <flux:subheading class="truncate">
                                                                    {{ $m->description }}
                                                                </flux:subheading>
                                                            @endif
                                                            <div class="mt-1 flex items-center gap-2">
                                                                <flux:text size="xs">Status:
                                                                    {{ ucfirst($m->status) }}</flux:text>
                                                                @if ($m->amount > 0)
                                                                    @if ($m->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PAID)
                                                                        <flux:badge variant="success" size="sm">
                                                                            Paid
                                                                        </flux:badge>
                                                                    @elseif($m->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PROCESSING)
                                                                        <flux:badge variant="warning" size="sm">
                                                                            Payment pending</flux:badge>
                                                                    @endif
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <div class="ml-4 flex items-center gap-3">
                                                            <flux:heading size="sm">
                                                                ${{ number_format($m->amount, 2) }}</flux:heading>
                                                            @if ($m->status !== 'approved' || ($m->amount > 0 && $m->payment_status !== \App\Models\Pitch::PAYMENT_STATUS_PAID))
                                                                <form method="POST"
                                                                    action="{{ URL::temporarySignedRoute('client.portal.milestones.approve', now()->addHours(24), ['project' => $project->id, 'milestone' => $m->id]) }}">
                                                                    @csrf
                                                                    <flux:button type="submit" variant="primary"
                                                                        size="sm">
                                                                        @if ($m->amount > 0)
                                                                            <flux:icon.credit-card
                                                                                class="mr-1" />Approve
                                                                            & Pay
                                                                        @else
                                                                            <flux:icon.check class="mr-1" />Approve
                                                                        @endif
                                                                    </flux:button>
                                                                </form>
                                                            @else
                                                                <flux:badge variant="success">
                                                                    <flux:icon.check-circle class="mr-1" /> Completed
                                                                </flux:badge>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                    <div class="space-y-4">
                                        @foreach ($currentSnapshot->files ?? collect() as $file)
                                            {{-- Check if file is audio for enhanced player --}}
                                            @if (in_array(pathinfo($file->file_name, PATHINFO_EXTENSION), ['mp3', 'wav', 'm4a', 'aac', 'flac']))
                                                {{-- Audio File with Enhanced Player --}}
                                                <flux:card id="file-{{ $file->id }}"
                                                    class="bg-green-50 dark:bg-green-900/20">
                                                    <div class="mb-3">
                                                        <div class="mb-2 flex items-center gap-2">
                                                            <flux:heading size="sm">{{ $file->file_name }}
                                                            </flux:heading>
                                                            @if ($file->client_approval_status === 'approved')
                                                                <flux:badge variant="success" size="sm">
                                                                    <flux:icon.check-circle class="mr-1" /> Approved
                                                                </flux:badge>
                                                            @endif
                                                        </div>
                                                        <div class="flex items-center justify-between">
                                                            <flux:text size="xs">
                                                                {{ number_format($file->size / 1024, 1) }} KB • Audio
                                                                File
                                                            </flux:text>
                                                            @if ($pitch->canClientDownloadFiles())
                                                                <flux:button
                                                                    href="{{ URL::temporarySignedRoute('client.portal.download_file', now()->addHours(24), ['project' => $project->id, 'pitchFile' => $file->id]) }}"
                                                                    variant="outline" size="sm">
                                                                    <flux:icon.arrow-down-tray class="mr-1" />
                                                                    <span class="hidden sm:inline">Download</span>
                                                                </flux:button>
                                                            @else
                                                                <div class="flex items-center gap-1">
                                                                    <flux:icon.lock-closed
                                                                        class="h-3 w-3 text-gray-500" />
                                                                    <flux:text size="xs" class="text-gray-500">
                                                                        Download Locked</flux:text>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>

                                                    {{-- Enhanced Audio Player with Client Comment Support --}}
                                                    @livewire('pitch-file-player', [
                                                        'file' => $file,
                                                        'isInCard' => true,
                                                        'clientMode' => true,
                                                        'clientEmail' => $project->client_email,
                                                    ])

                                                    @if (!isset($isPreview) || !$isPreview)
                                                        <div class="mt-3">
                                                            @if ($file->client_approval_status !== 'approved')
                                                                <form method="POST" x-data="approveFile({ url: '{{ URL::temporarySignedRoute('client.portal.files.approve', now()->addHours(24), ['project' => $project->id, 'pitchFile' => $file->id]) }}' })"
                                                                    @submit.prevent="submit">
                                                                    @csrf
                                                                    <flux:button type="submit" variant="primary"
                                                                        size="sm">
                                                                        <flux:icon.check class="mr-2" />
                                                                        <span x-show="!loading">Approve File</span>
                                                                        <span x-show="loading">
                                                                            <flux:icon.arrow-path
                                                                                class="mr-1 animate-spin" />
                                                                            Approving...
                                                                        </span>
                                                                    </flux:button>
                                                                </form>
                                                            @else
                                                                <flux:text size="xs" class="text-green-700"
                                                                    data-approved-text>
                                                                    Approved
                                                                    {{ optional($file->client_approved_at)->diffForHumans() }}
                                                                </flux:text>
                                                            @endif
                                                        </div>
                                                    @endif
                                                </flux:card>
                                            @else
                                                {{-- Non-Audio File - Standard Display --}}
                                                <flux:card id="file-{{ $file->id }}"
                                                    class="bg-green-50 dark:bg-green-900/20">
                                                    <div class="flex items-center justify-between">
                                                        <div class="flex min-w-0 flex-1 items-center gap-3">
                                                            <flux:icon.document
                                                                class="hidden flex-shrink-0 text-green-500 sm:block" />
                                                            <div class="min-w-0 flex-1">
                                                                <div class="mb-1 flex items-center gap-2">
                                                                    <flux:heading size="sm" class="truncate">
                                                                        {{ $file->file_name }}</flux:heading>
                                                                    @if ($file->client_approval_status === 'approved')
                                                                        <flux:badge variant="success" size="sm">
                                                                            <flux:icon.check-circle class="mr-1" />
                                                                            Approved
                                                                        </flux:badge>
                                                                    @endif
                                                                </div>
                                                                <div class="flex items-center justify-between">
                                                                    <flux:text size="xs">
                                                                        {{ number_format($file->size / 1024, 1) }} KB
                                                                    </flux:text>
                                                                    @if ($pitch->canClientDownloadFiles())
                                                                        <flux:button
                                                                            href="{{ URL::temporarySignedRoute('client.portal.download_file', now()->addHours(24), ['project' => $project->id, 'pitchFile' => $file->id]) }}"
                                                                            variant="outline" size="sm">
                                                                            <flux:icon.arrow-down-tray
                                                                                class="mr-1" />
                                                                            <span
                                                                                class="hidden sm:inline">Download</span>
                                                                        </flux:button>
                                                                    @else
                                                                        <flux:text size="xs"
                                                                            class="text-gray-500">
                                                                            @if ($pitch->status !== \App\Models\Pitch::STATUS_COMPLETED)
                                                                                Available when completed
                                                                            @elseif(
                                                                                $pitch->payment_amount > 0 &&
                                                                                    !in_array($pitch->payment_status, [
                                                                                        \App\Models\Pitch::PAYMENT_STATUS_PAID,
                                                                                        \App\Models\Pitch::PAYMENT_STATUS_NOT_REQUIRED,
                                                                                    ]))
                                                                                Available after payment
                                                                            @endif
                                                                        </flux:text>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                        @if (!isset($isPreview) || !$isPreview)
                                                            <div class="ml-3">
                                                                @if ($file->client_approval_status !== 'approved')
                                                                    <form method="POST" x-data="approveFile({ url: '{{ URL::temporarySignedRoute('client.portal.files.approve', now()->addHours(24), ['project' => $project->id, 'pitchFile' => $file->id]) }}' })"
                                                                        @submit.prevent="submit">
                                                                        @csrf
                                                                        <flux:button type="submit" variant="primary"
                                                                            size="sm">
                                                                            <flux:icon.check class="mr-2" />
                                                                            <span x-show="!loading">Approve File</span>
                                                                            <span x-show="loading">
                                                                                <flux:icon.arrow-path
                                                                                    class="mr-1 animate-spin" />
                                                                                Approving...
                                                                            </span>
                                                                        </flux:button>
                                                                    </form>
                                                                @else
                                                                    <flux:text size="xs" class="text-green-700"
                                                                        data-approved-text>
                                                                        Approved
                                                                        {{ optional($file->client_approved_at)->diffForHumans() }}
                                                                    </flux:text>
                                                                @endif
                                                            </div>
                                                        @endif
                                                    </div>
                                                </flux:card>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <div class="py-8 text-center">
                                    <div
                                        class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-green-100">
                                        <i class="fas fa-clock text-xl text-green-500"></i>
                                    </div>
                                    @if ($currentSnapshot)
                                        <p class="mb-2 font-medium text-green-700">No files in this version</p>
                                        <p class="text-sm text-green-600">The producer hasn't uploaded files for this
                                            submission yet.</p>
                                    @else
                                        <p class="mb-2 font-medium text-green-700">No deliverables uploaded yet</p>
                                        <p class="mx-auto max-w-md text-sm leading-relaxed text-green-600">The producer
                                            will upload files here as they work on your project. You'll be notified when
                                            new
                                            files are available.</p>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </flux:card>

                    {{-- Enhanced Action Forms with Payment Flow --}}
                    @if ($pitch->status === \App\Models\Pitch::STATUS_READY_FOR_REVIEW)
                        <flux:card class="mb-6">
                            <div class="mb-6 flex items-center gap-3">
                                <flux:icon.clipboard-document-check class="animate-pulse text-green-500" />
                                <div>
                                    <flux:heading size="lg">Review & Approval</flux:heading>
                                    <flux:subheading>The project is ready for your review. Please approve or request
                                        revisions.
                                    </flux:subheading>
                                </div>
                            </div>

                            {{-- Payment Information Banner --}}
                            @if ($pitch->payment_amount > 0)
                                <flux:callout variant="info" class="mb-6">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <flux:icon.credit-card class="text-blue-500" />
                                            <div>
                                                <flux:heading size="sm">Payment Required:
                                                    ${{ number_format($pitch->payment_amount, 2) }}</flux:heading>
                                                <flux:subheading>Secure payment processing via Stripe</flux:subheading>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-1 text-green-600">
                                            <flux:icon.shield-check class="h-4 w-4" />
                                            <flux:text size="sm">Secure</flux:text>
                                        </div>
                                    </div>
                                </flux:callout>
                            @endif

                            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                                {{-- Approve Form --}}
                                <div class="rounded-xl bg-green-50 p-6 dark:bg-green-900/20">
                                    <div class="mb-4 flex items-center gap-2">
                                        <flux:icon.check-circle class="text-green-500" />
                                        <flux:heading size="sm">Approve Project</flux:heading>
                                    </div>
                                    <flux:text size="sm" class="mb-4">
                                        @if ($pitch->payment_amount > 0)
                                            Clicking approve will redirect you to secure payment processing. You'll be
                                            charged
                                            ${{ number_format($pitch->payment_amount, 2) }} and the producer will be
                                            notified of
                                            completion.
                                        @else
                                            Clicking approve will notify the producer that the project is complete and
                                            satisfactory.
                                        @endif
                                    </flux:text>

                                    <form
                                        action="{{ URL::temporarySignedRoute('client.portal.approve', now()->addHours(24), ['project' => $project->id]) }}"
                                        method="POST">
                                        @csrf
                                        <flux:button type="submit" variant="primary" size="lg" class="w-full">
                                            @if ($pitch->payment_amount > 0)
                                                <flux:icon.credit-card class="mr-2" />
                                                Approve & Pay ${{ number_format($pitch->payment_amount, 2) }}
                                            @else
                                                <flux:icon.check-circle class="mr-2" />
                                                Approve Project
                                            @endif
                                        </flux:button>
                                    </form>

                                    @if ($pitch->payment_amount > 0)
                                        <div class="mt-3 flex items-center justify-center gap-1">
                                            <flux:icon.lock-closed class="h-3 w-3 text-green-600" />
                                            <flux:text size="xs" class="text-green-600">Powered by Stripe • SSL
                                                Encrypted
                                            </flux:text>
                                        </div>
                                    @endif
                                </div>

                                {{-- Request Revisions Form --}}
                                <div class="rounded-xl bg-amber-50 p-6 dark:bg-amber-900/20">
                                    <div class="mb-4 flex items-center gap-2">
                                        <flux:icon.pencil class="text-amber-500" />
                                        <flux:heading size="sm">Request Revisions</flux:heading>
                                    </div>
                                    <flux:text size="sm" class="mb-4">
                                        Use our structured feedback system to provide specific, organized feedback about
                                        what needs
                                        to be changed.
                                    </flux:text>

                                    {{-- Structured Feedback Form --}}
                                    <div class="mb-4 rounded-lg bg-white p-4 dark:bg-gray-700">
                                        @livewire('structured-feedback-form', [
                                            'pitch' => $pitch,
                                            'pitchFile' => ($currentSnapshot->files ?? collect())->first(),
                                            'clientEmail' => $project->client_email,
                                        ])
                                    </div>

                                    {{-- Traditional Text Feedback --}}
                                    <div class="border-t border-amber-200 pt-4 dark:border-amber-800">
                                        <flux:heading size="sm" class="mb-3">Or send traditional feedback:
                                        </flux:heading>
                                        <form
                                            action="{{ URL::temporarySignedRoute('client.portal.revisions', now()->addHours(24), ['project' => $project->id]) }}"
                                            method="POST">
                                            @csrf
                                            <flux:textarea name="feedback" rows="3"
                                                placeholder="Additional feedback or specific requests..."
                                                class="mb-3">
                                                {{ old('feedback') }}</flux:textarea>
                                            @error('feedback')
                                                <flux:text size="sm" class="mb-2 text-red-600">{{ $message }}
                                                </flux:text>
                                            @enderror
                                            <flux:button type="submit" variant="warning" size="sm"
                                                class="w-full">
                                                <flux:icon.paper-airplane class="mr-2" />
                                                Send Traditional Feedback
                                            </flux:button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </flux:card>
                    @endif

                    {{-- Post-Payment/Approval Success Section --}}
                    @if (in_array($pitch->status, [\App\Models\Pitch::STATUS_APPROVED, \App\Models\Pitch::STATUS_COMPLETED]))
                        <flux:callout variant="success" class="mb-6">
                            <div class="text-center">
                                <div
                                    class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-green-500">
                                    <flux:icon.check class="text-2xl text-white" />
                                </div>
                                <flux:heading size="xl" class="mb-2">
                                    @if ($pitch->status === \App\Models\Pitch::STATUS_COMPLETED)
                                        🎉 Project Completed!
                                    @else
                                        ✅ Project Approved!
                                    @endif
                                </flux:heading>
                                <flux:text>
                                    @if ($pitch->status === \App\Models\Pitch::STATUS_COMPLETED)
                                        Your project has been successfully completed. All deliverables are ready for
                                        download.
                                    @else
                                        Thank you for approving the project! @if ($pitch->payment_amount > 0)
                                            Payment has been processed successfully.
                                        @endif
                                    @endif
                                </flux:text>
                            </div>

                            @if ($pitch->payment_amount > 0 && $pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PAID)
                                <div
                                    class="mb-6 rounded-xl border border-green-200/50 bg-gradient-to-r from-green-50/80 to-emerald-50/80 p-6 backdrop-blur-sm">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <div
                                                class="mr-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-green-500 to-emerald-600">
                                                <i class="fas fa-receipt text-white"></i>
                                            </div>
                                            <div>
                                                <h4 class="font-semibold text-green-800">Payment Confirmed</h4>
                                                <p class="text-sm text-green-700">Amount:
                                                    ${{ number_format($pitch->payment_amount, 2) }} • Processed
                                                    securely via Stripe
                                                </p>
                                            </div>
                                        </div>
                                        <a href="{{ URL::temporarySignedRoute('client.portal.invoice', now()->addDays(7), ['project' => $project->id]) }}"
                                            class="rounded-lg bg-green-100 px-4 py-2 font-medium text-green-800 transition-colors duration-200 hover:bg-green-200">
                                            <i class="fas fa-download mr-2"></i>
                                            View Invoice
                                        </a>
                                    </div>
                                </div>
                            @endif

                            {{-- Milestones Section (duplicate check) --}}
                            @if (isset($milestones) && $milestones->count() > 0)
                                <div class="mb-6 rounded-xl bg-purple-50 p-6 dark:bg-purple-900/20">
                                    <div class="mb-4 flex items-center gap-2">
                                        <flux:icon.flag class="text-purple-500" />
                                        <flux:heading size="lg">Milestones</flux:heading>
                                    </div>
                                    <div class="space-y-3">
                                        @foreach ($milestones as $m)
                                            <div
                                                class="flex items-center justify-between rounded-xl border bg-white p-4 dark:bg-gray-700">
                                                <div class="min-w-0 flex-1">
                                                    <flux:heading size="sm" class="truncate">{{ $m->name }}
                                                    </flux:heading>
                                                    @if ($m->description)
                                                        <flux:subheading class="truncate">{{ $m->description }}
                                                        </flux:subheading>
                                                    @endif
                                                    <flux:text size="xs" class="mt-1">
                                                        Status: {{ ucfirst($m->status) }}
                                                        @if ($m->payment_status)
                                                            • Payment: {{ str_replace('_', ' ', $m->payment_status) }}
                                                        @endif
                                                    </flux:text>
                                                </div>
                                                <div class="ml-4 flex items-center gap-3">
                                                    <flux:heading size="sm">${{ number_format($m->amount, 2) }}
                                                    </flux:heading>
                                                    @if ($m->status !== 'approved' || ($m->amount > 0 && $m->payment_status !== \App\Models\Pitch::PAYMENT_STATUS_PAID))
                                                        <form method="POST"
                                                            action="{{ route('client.portal.milestones.approve', ['project' => $project->id, 'milestone' => $m->id]) }}">
                                                            @csrf
                                                            <flux:button type="submit" variant="primary">
                                                                @if ($m->amount > 0)
                                                                    <flux:icon.credit-card class="mr-2" />Approve &
                                                                    Pay
                                                                @else
                                                                    <flux:icon.check class="mr-2" />Approve
                                                                @endif
                                                            </flux:button>
                                                        </form>
                                                    @else
                                                        <flux:badge variant="success">
                                                            <flux:icon.check-circle class="mr-1" /> Completed
                                                        </flux:badge>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            {{-- Phase 2: Enhanced Completed Project Actions --}}
                            @if ($pitch->status === \App\Models\Pitch::STATUS_COMPLETED)
                                <div
                                    class="mb-6 rounded-xl border border-emerald-200/50 bg-gradient-to-r from-emerald-50/80 to-green-50/80 p-6 backdrop-blur-sm">
                                    <h4 class="mb-4 flex items-center font-semibold text-emerald-800">
                                        <i class="fas fa-gift mr-2"></i>
                                        Your Project Deliverables
                                    </h4>
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <a href="{{ URL::temporarySignedRoute('client.portal.deliverables', now()->addDays(7), ['project' => $project->id]) }}"
                                            class="flex items-center justify-center rounded-xl bg-gradient-to-r from-emerald-600 to-green-600 px-6 py-4 font-medium text-white shadow-lg transition-[transform,colors,shadow] duration-200 hover:scale-105 hover:from-emerald-700 hover:to-green-700 hover:shadow-xl">
                                            <i class="fas fa-download mr-3"></i>
                                            <div class="text-left">
                                                <div class="font-semibold">Download Files</div>
                                                <div class="text-xs opacity-90">Get your final deliverables</div>
                                            </div>
                                        </a>
                                        @if ($pitch->payment_amount > 0 && $pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PAID)
                                            <a href="{{ URL::temporarySignedRoute('client.portal.invoice', now()->addDays(7), ['project' => $project->id]) }}"
                                                class="flex items-center justify-center rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 px-6 py-4 font-medium text-white shadow-lg transition-[transform,colors,shadow] duration-200 hover:scale-105 hover:from-purple-700 hover:to-indigo-700 hover:shadow-xl">
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
                                <p class="mb-4 text-sm text-gray-600">
                                    Need to get in touch? Use the communication section below to send a message to your
                                    producer.
                                </p>

                                @if ($pitch->status === \App\Models\Pitch::STATUS_COMPLETED)
                                    <div
                                        class="inline-flex items-center rounded-lg border border-blue-200 bg-gradient-to-r from-blue-100 to-purple-100 px-4 py-2 text-blue-800">
                                        <i class="fas fa-star mr-2"></i>
                                        <span class="font-medium">We'd love your feedback on this project!</span>
                                    </div>
                                @endif
                            </div>
                        </flux:callout>
                    @endif

                    {{-- Communication Section --}}
                    <flux:card class="mb-6">
                        <div class="mb-6 flex items-center gap-3">
                            <flux:icon.chat-bubble-left-right class="text-purple-500" />
                            <div>
                                <flux:heading size="lg">Project Communication</flux:heading>
                                <flux:subheading>Stay in touch with your producer throughout the project
                                </flux:subheading>
                            </div>
                        </div>

                        {{-- Comment Form --}}
                        <div class="mb-6 rounded-xl bg-blue-50 p-6 dark:bg-blue-900/20">
                            <form
                                action="{{ URL::temporarySignedRoute('client.portal.comments.store', now()->addHours(24), ['project' => $project->id]) }}"
                                method="POST">
                                @csrf
                                <flux:field>
                                    <flux:label for="comment">Add a Comment</flux:label>
                                    <flux:textarea name="comment" id="comment" rows="4" required
                                        placeholder="Share your thoughts, ask questions, or provide additional feedback...">
                                        {{ old('comment') }}</flux:textarea>
                                    @error('comment')
                                        <flux:error name="comment" />
                                    @enderror
                                </flux:field>
                                <flux:button type="submit" variant="primary" class="mt-4">
                                    <flux:icon.paper-airplane class="mr-2" />
                                    Submit Comment
                                </flux:button>
                            </form>
                        </div>

                        {{-- Comment History --}}
                        <div class="space-y-4">
                            <div class="mb-4 flex items-center gap-2">
                                <flux:icon.clock class="text-gray-600" />
                                <flux:heading size="sm">Project Activity</flux:heading>
                            </div>

                            @forelse ($pitch->events->whereIn('event_type', ['client_comment', 'producer_comment', 'status_change', 'client_approved', 'client_revisions_requested']) as $event)
                                <div
                                    class="{{ $event->event_type === 'client_comment' ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-200' : 'bg-gray-50 dark:bg-gray-800 border-gray-200' }} rounded-xl border p-4">
                                    <div class="flex items-start gap-3">
                                        <div
                                            class="{{ $event->event_type === 'client_comment' ? 'bg-blue-500' : 'bg-gray-500' }} flex h-8 w-8 items-center justify-center rounded-lg">
                                            @if ($event->event_type === 'client_comment')
                                                <flux:icon.user class="h-4 w-4 text-white" />
                                            @else
                                                <flux:icon.user-circle class="h-4 w-4 text-white" />
                                            @endif
                                        </div>

                                        <div class="flex-1">
                                            <div class="mb-2 flex items-center justify-between">
                                                <flux:heading size="sm"
                                                    class="{{ $event->event_type === 'client_comment' ? 'text-blue-900' : 'text-gray-900' }}">
                                                    @if ($event->event_type === 'client_comment' && isset($event->metadata['client_email']))
                                                        You ({{ $event->metadata['client_email'] }})
                                                    @elseif($event->user)
                                                        {{ $event->user->name }} (Producer)
                                                    @else
                                                        System Event [{{ $event->event_type }}]
                                                    @endif
                                                </flux:heading>
                                                <flux:text size="xs" class="text-gray-500">
                                                    {{ $event->created_at->diffForHumans() }}</flux:text>
                                            </div>

                                            @if ($event->comment)
                                                <flux:text class="whitespace-pre-wrap">{{ $event->comment }}
                                                </flux:text>
                                            @endif

                                            @if ($event->status)
                                                <flux:badge variant="ghost" size="sm" class="mt-2">
                                                    Status: {{ Str::title(str_replace('_', ' ', $event->status)) }}
                                                </flux:badge>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="py-8 text-center">
                                    <flux:icon.chat-bubble-left-ellipsis class="mx-auto mb-3 text-gray-400"
                                        size="xl" />
                                    <flux:heading size="sm" class="mb-2">No activity yet</flux:heading>
                                    <flux:subheading>Comments and project updates will appear here</flux:subheading>
                                </div>
                            @endforelse
                        </div>
                    </flux:card>

                    {{-- Footer --}}
                    <footer class="py-8 text-center">
                        <flux:card class="bg-white/60">
                            <flux:text class="font-medium">
                                Powered by <span
                                    class="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text font-bold text-transparent">MixPitch</span>
                            </flux:text>
                        </flux:card>
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
                            const uploadUrl =
                                '{{ URL::signedRoute('client.portal.upload_file', ['project' => $project->id]) }}';
                            console.log('Upload URL:', uploadUrl);
                            console.log('Current timestamp:', new Date().toISOString());
                            console.log('CSRF Token:', token);

                            // Upload file with proper headers
                            fetch(uploadUrl, {
                                    method: 'POST',
                                    body: formData,
                                    headers: {
                                        'X-Requested-With': 'XMLHttpRequest',
                                        'X-CSRF-TOKEN': token // FIXED: Added proper CSRF token header
                                    }
                                })
                                .then(response => {
                                    console.log('Response status:', response.status);
                                    console.log('Response headers:', response.headers);

                                    if (!response.ok) {
                                        // Try to get error details from response
                                        return response.text().then(text => {
                                            console.log('Error response body:', text);
                                            throw new Error(
                                                `HTTP error! status: ${response.status} - ${text.substring(0, 200)}`
                                            );
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
                            div.className =
                                'bg-gradient-to-r from-blue-100/90 to-indigo-100/90 backdrop-blur-md border border-blue-200/50 rounded-xl p-4 mt-4 shadow-lg animate-fade-in-up';
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
                            fileDiv.className =
                                'flex items-center justify-between p-4 bg-gradient-to-r from-white/80 to-blue-50/60 backdrop-blur-sm border border-blue-200/40 rounded-xl shadow-sm hover:shadow-md transition-all duration-200 animate-fade-in-up';
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
                            const deleteUrl =
                                '{{ URL::signedRoute('client.portal.delete_project_file', ['project' => $project->id, 'projectFile' => 'PROJECT_FILE_ID']) }}'
                                .replace('PROJECT_FILE_ID', fileId);

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
                                            throw new Error(
                                                `HTTP error! status: ${response.status} - ${text.substring(0, 200)}`
                                            );
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
                                    deliverables.scrollIntoView({
                                        behavior: 'smooth',
                                        block: 'start'
                                    });
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
                        const isNewer = parseInt(snapshot.version) > parseInt(side === 'left' ? rightSnapshot.version : leftSnapshot
                            .version);
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
                        let added = 0,
                            removed = 0,
                            modified = 0,
                            unchanged = 0;

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
                            stats: {
                                added,
                                removed,
                                modified,
                                unchanged
                            }
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
                        const {
                            type,
                            fileName,
                            leftFile,
                            rightFile,
                            changes
                        } = fileDiff;

                        // Don't show unchanged files to reduce clutter
                        if (type === 'unchanged') {
                            return '';
                        }

                        // Card styling based on change type
                        const cardClass = {
                            'added': 'border-green-300 bg-green-50',
                            'removed': 'border-red-300 bg-red-50',
                            'modified': 'border-blue-300 bg-blue-50'
                        } [type];

                        const iconClass = {
                            'added': 'fas fa-plus-circle text-green-600',
                            'removed': 'fas fa-minus-circle text-red-600',
                            'modified': 'fas fa-edit text-blue-600'
                        } [type];

                        const statusLabel = {
                            'added': 'Added',
                            'removed': 'Removed',
                            'modified': 'Modified'
                        } [type];

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
                        } [type];
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

                        switch (fileType) {
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
                        notification.className =
                            'fixed top-4 right-4 bg-blue-500 text-white px-4 py-2 rounded-lg shadow-lg z-50 transition-all duration-300';
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
                        notification.className =
                            'fixed top-4 right-4 bg-orange-500 text-white px-4 py-2 rounded-lg shadow-lg z-50 transition-all duration-300';
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

                        switch (fileType) {
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
                        notification.className =
                            'fixed top-4 right-4 bg-indigo-500 text-white px-4 py-2 rounded-lg shadow-lg z-50 transition-all duration-300 max-w-sm';
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
                                    deliverables.scrollIntoView({
                                        behavior: 'smooth',
                                        block: 'start'
                                    });
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
                    function approveFile({
                        url
                    }) {
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
                                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                                'content'),
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
                                                chip.className =
                                                    'approved-chip inline-flex items-center ml-2 px-2 py-0.5 rounded bg-green-100 text-green-800 text-[10px]';
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

                    function approveAll({
                        url
                    }) {
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
                                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                                'content'),
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
                                                chip.className =
                                                    'approved-chip inline-flex items-center ml-2 px-2 py-0.5 rounded bg-green-100 text-green-800 text-[10px]';
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

            </div>
        </div>
    </x-draggable-upload-page>

    {{-- Global Components --}}
    @livewire('global-file-uploader')
    @livewire('global-audio-player')

    {{-- Scripts --}}
    @livewireScripts
    @fluxScripts
    @yield('scripts')
    @stack('scripts')
</body>

</html>
