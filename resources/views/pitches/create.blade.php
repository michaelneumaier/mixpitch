<x-layouts.app-sidebar>
<div class="container mx-auto px-2 py-6">
    <div class="mx-auto w-full max-w-2xl space-y-6">

        <!-- Header -->
        <div>
            <flux:heading size="xl">Start Your Pitch</flux:heading>
            <flux:subheading>Submit a pitch for "{{ $project->name }}"</flux:subheading>
        </div>

        <!-- Project Context Summary -->
        <flux:card>
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <flux:heading size="lg" class="truncate">{{ $project->name }}</flux:heading>
                    @if ($project->artist_name)
                        <flux:text size="sm" class="text-gray-500 dark:text-gray-400">by {{ $project->artist_name }}</flux:text>
                    @endif
                </div>
                <flux:badge size="sm" color="{{ $project->isContest() ? 'amber' : 'blue' }}" class="flex-shrink-0">
                    {{ $project->readable_workflow_type }}
                </flux:badge>
            </div>
            <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-gray-700 dark:text-gray-300">
                @if ($project->genre)
                    <div class="flex items-center gap-1.5">
                        <flux:icon.musical-note class="h-4 w-4 text-gray-500 dark:text-gray-400" />
                        {{ $project->genre }}
                    </div>
                @endif
                <div class="flex items-center gap-1.5">
                    <flux:icon.currency-dollar class="h-4 w-4 text-gray-500 dark:text-gray-400" />
                    @if ($project->isContest() && $project->prize_amount > 0)
                        ${{ number_format($project->prize_amount, 0) }} prize
                    @elseif ($project->budget > 0)
                        ${{ number_format($project->budget, 0) }} budget
                    @else
                        Free collaboration
                    @endif
                </div>
                @if ($project->isContest() && $project->submission_deadline)
                    <div class="flex items-center gap-1.5">
                        <flux:icon.clock class="h-4 w-4 text-gray-500 dark:text-gray-400" />
                        Submit by {{ $project->submission_deadline->format('M j, Y') }}
                    </div>
                @endif
            </div>
        </flux:card>

        <form action="{{ route('projects.pitches.store', ['project' => $project->slug]) }}" method="POST">
            @csrf
            <input type="hidden" name="project_id" value="{{ $project->id }}">
            <div class="space-y-6">

                <!-- Cover Letter (optional) -->
                <flux:card x-data="{ coverLetter: {{ Js::from(old('cover_letter', '')) }} }">
                    <flux:field>
                        <flux:label for="cover_letter">
                            Cover Letter<span class="font-normal text-gray-500 dark:text-gray-400">&nbsp;(optional)</span>
                        </flux:label>
                        <flux:textarea id="cover_letter" name="cover_letter" rows="6" maxlength="2000"
                            x-model="coverLetter" class="resize-y"
                            placeholder="Introduce yourself and explain why you're a great fit for this project. Mention relevant experience, your approach, and what the {{ $project->isContest() ? 'contest' : 'project' }} owner can expect from working with you." />
                        <div class="mt-1 flex items-center justify-between gap-4">
                            <flux:description>
                                Help the project owner get to know you. You can edit this while your pitch is {{ $project->isContest() ? 'awaiting the deadline' : 'pending review' }}.
                            </flux:description>
                            <flux:text size="xs" class="flex-shrink-0 tabular-nums text-gray-500 dark:text-gray-400">
                                <span x-text="coverLetter.length">0</span>/2000
                            </flux:text>
                        </div>
                        @error('cover_letter')
                            <flux:error>{{ $message }}</flux:error>
                        @enderror
                    </flux:field>
                </flux:card>

                <!-- Agreements -->
                <flux:card class="space-y-4">
                    <flux:heading size="lg">Terms &amp; License</flux:heading>
                    <flux:checkbox name="agree_terms" id="agree_terms" value="1"
                        label="I agree to the Terms and Conditions"
                        :checked="(bool) old('agree_terms')" required />
                    @error('agree_terms')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
                    @if ($project->requiresLicenseAgreement())
                        <flux:checkbox name="agree_license" id="agree_license" value="1"
                            label="I agree to this project's license terms"
                            :checked="(bool) old('agree_license')" />
                        @error('agree_license')
                            <flux:error>{{ $message }}</flux:error>
                        @enderror
                    @endif
                </flux:card>

                <flux:button type="submit" variant="primary" icon="paper-airplane" class="w-full justify-center">
                    Start Your Pitch
                </flux:button>
            </div>
        </form>
    </div>
</div>
</x-layouts.app-sidebar>
