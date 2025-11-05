<div>
    <flux:modal name="quick-project-modal" class="md:w-4/5 space-y-6">
        <div>
            <flux:heading size="lg">
                @if($workflow_type === 'standard')
                    Create Standard Project
                @elseif($workflow_type === 'contest')
                    Create Contest
                @elseif($workflow_type === 'client_management')
                    Create Client Project
                @else
                    Create New Project
                @endif
            </flux:heading>
            <flux:subheading>
                @if($workflow_type === 'standard')
                    Open your project to multiple producers for submissions
                @elseif($workflow_type === 'contest')
                    Competition-based project with prizes and deadlines
                @elseif($workflow_type === 'client_management')
                    Manage work for an external client
                @endif
            </flux:subheading>
        </div>

        <flux:separator />

        <form wire:submit="createProject" class="space-y-6">
            {{-- Hide project fields during client selection step --}}
            @if(!($workflow_type === 'client_management' && $showClientSelection))
                {{-- Project Name (Required for all workflows) --}}
                <flux:field>
                    <flux:label>Project Name <span class="text-red-500">*</span></flux:label>
                    <flux:input wire:model="name" placeholder="e.g., Summer Vibes Single" />
                    <flux:error name="name" />
                    <flux:description>Give your project a clear, descriptive name (5-80 characters)</flux:description>
                </flux:field>

                {{-- Basic Project Details (Optional for all workflows) --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>Artist Name</flux:label>
                        <flux:input wire:model="artist_name" placeholder="e.g., The Waves" />
                        <flux:error name="artist_name" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Project Type</flux:label>
                        <flux:select wire:model="project_type">
                            @foreach($projectTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </flux:select>
                        <flux:error name="project_type" />
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label>Genre</flux:label>
                    <flux:select wire:model="genre">
                        <option value="">Select a genre...</option>
                        @foreach($genres as $genreOption)
                            <option value="{{ $genreOption }}">{{ $genreOption }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="genre" />
                </flux:field>

                <flux:field>
                    <flux:label>Project Description</flux:label>
                    <flux:textarea wire:model="description" rows="4"
                        placeholder="Describe what you're looking for, your vision, and any specific requirements..." />
                    <flux:error name="description" />
                    <flux:description>You can add more details after creating the project</flux:description>
                </flux:field>

                <flux:field>
                    <flux:label>Collaboration Services</flux:label>
                    <div class="flex flex-wrap gap-2">
                        @foreach($collaborationServices as $value => $label)
                            <flux:checkbox wire:model="collaboration_types" value="{{ $value }}" variant="pills">
                                {{ $label }}
                            </flux:checkbox>
                        @endforeach
                    </div>
                    <flux:error name="collaboration_types" />
                    <flux:description>Select the services you need (Production is selected by default)</flux:description>
                </flux:field>
            @endif

            {{-- Contest-Specific Fields --}}
            @if($workflow_type === 'contest')
                <flux:separator />
                <flux:heading size="sm" class="text-orange-600 dark:text-orange-400">Contest Settings</flux:heading>

                <flux:field>
                    <flux:label>Submission Deadline</flux:label>
                    <flux:input type="datetime-local" wire:model="submission_deadline" />
                    <flux:error name="submission_deadline" />
                    <flux:description>Optional - set a deadline for when submissions close, or leave empty for open-ended contest</flux:description>
                </flux:field>

                <flux:callout icon="information-circle" color="blue">
                    After creating your contest, you'll configure prizes on the next page
                </flux:callout>
            @endif

            {{-- Client Management-Specific Fields --}}
            @if($workflow_type === 'client_management')
                <flux:separator />

                {{-- Step 1: Client Selection --}}
                @if($showClientSelection)
                    <div class="space-y-4">
                        <flux:heading size="sm" class="text-purple-600 dark:text-purple-400">Choose Client</flux:heading>

                        <flux:callout icon="information-circle" color="purple">
                            Select an existing client or create a new one for this project
                        </flux:callout>

                        {{-- Primary action: New Client --}}
                        <flux:button type="button" wire:click="chooseNewClient" variant="primary" icon="plus" class="w-full">
                            Create New Client
                        </flux:button>

                        {{-- Existing clients dropdown --}}
                        @if(count($clients) > 0)
                            <div class="relative">
                                <div class="flex items-center gap-2 mb-2">
                                    <div class="flex-1 border-t border-gray-300 dark:border-gray-600"></div>
                                    <flux:text class="text-sm text-gray-500 dark:text-gray-400">or choose existing</flux:text>
                                    <div class="flex-1 border-t border-gray-300 dark:border-gray-600"></div>
                                </div>

                                <flux:field>
                                    <flux:label>Select Existing Client</flux:label>
                                    <flux:select wire:model="selectedClientEmail" wire:change="selectExistingClient($event.target.value)">
                                        <option value="">Choose a client...</option>
                                        @foreach($clients as $index => $client)
                                            <option value="{{ $client['email'] }}">
                                                {{ $client['name'] }} ({{ $client['email'] }})
                                            </option>
                                        @endforeach
                                    </flux:select>
                                </flux:field>
                            </div>
                        @endif
                    </div>
                @else
                    {{-- Step 2: Project Details with Client Info --}}
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <flux:heading size="sm" class="text-purple-600 dark:text-purple-400">Client Information</flux:heading>
                            <flux:button type="button" wire:click="backToClientSelection" variant="ghost" icon="arrow-left" size="xs">
                                Change Client
                            </flux:button>
                        </div>

                        {{-- Show selected client info --}}
                        @if($isExistingClient && $client_email)
                            <flux:callout icon="check-circle" color="green">
                                <strong>Existing Client:</strong> {{ $client_name ?: $client_email }}
                            </flux:callout>
                        @else
                            <flux:callout icon="user-plus" color="blue">
                                <strong>New Client</strong> - Fill in client details below
                            </flux:callout>
                        @endif

                        {{-- Client fields --}}
                        <flux:field>
                            <flux:label>Client Email <span class="text-red-500">*</span></flux:label>
                            <flux:input type="email" wire:model="client_email" placeholder="client@example.com"
                                :disabled="$isExistingClient" />
                            <flux:error name="client_email" />
                            @if(!$isExistingClient)
                                <flux:description>Your client will receive a link to review and approve your work</flux:description>
                            @endif
                        </flux:field>

                        <flux:field>
                            <flux:label>Client Name</flux:label>
                            <flux:input wire:model="client_name" placeholder="e.g., Acme Records"
                                :disabled="$isExistingClient" />
                            <flux:error name="client_name" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Payment Amount</flux:label>
                            <flux:input type="number" wire:model="payment_amount" step="0.01" min="0" placeholder="0.00">
                                <x-slot name="iconLeading">
                                    <flux:icon.currency-dollar variant="mini" />
                                </x-slot>
                            </flux:input>
                            <flux:error name="payment_amount" />
                            <flux:description>Total project payment ($0 for pro bono work, you can set up milestones later)</flux:description>
                        </flux:field>
                    </div>
                @endif
            @endif

            {{-- Modal Actions --}}
            @if(!($workflow_type === 'client_management' && $showClientSelection))
                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" wire:click="closeModal" type="button">
                        Cancel
                    </flux:button>
                    <flux:button type="submit" variant="primary" icon="plus">
                        Create Project
                    </flux:button>
                </div>
            @else
                {{-- During client selection, only show cancel --}}
                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" wire:click="closeModal" type="button">
                        Cancel
                    </flux:button>
                </div>
            @endif
        </form>
    </flux:modal>
</div>
