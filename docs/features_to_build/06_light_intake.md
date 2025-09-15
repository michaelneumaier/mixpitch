# Light Intake Implementation Plan

## Overview

Streamline project creation with an intelligent intake form that prioritizes essential information while gracefully handling optional details. This creates a smooth onboarding experience that gets projects started quickly while capturing the information needed for successful collaboration.

## UX/UI Implementation

### Progressive Project Creation Wizard

**Location**: Replace existing project creation flow  
**Current**: Single-page form with all fields at once  
**New**: Multi-step wizard with smart prioritization and optional sections

```blade
{{-- Step-based project creation wizard --}}
<div class="max-w-4xl mx-auto" x-data="projectWizard()">
    {{-- Progress indicator --}}
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                @foreach(['basics', 'details', 'requirements', 'settings'] as $index => $step)
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-8 h-8 rounded-full text-sm font-medium
                            {{ $loop->first ? 'bg-indigo-600 text-white' : 'bg-slate-200 text-slate-600' }}"
                            :class="{
                                'bg-indigo-600 text-white': currentStep >= {{ $index + 1 }},
                                'bg-slate-200 text-slate-600': currentStep < {{ $index + 1 }}
                            }"
                        >
                            {{ $index + 1 }}
                        </div>
                        @if(!$loop->last)
                            <div class="w-12 h-0.5 bg-slate-200 ml-2"
                                :class="{ 'bg-indigo-600': currentStep > {{ $index + 1 }} }">
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
            <div class="text-sm text-slate-600">
                Step <span x-text="currentStep"></span> of 4
            </div>
        </div>
        
        <div class="mt-2">
            <div class="text-sm font-medium text-slate-900" x-text="stepTitles[currentStep - 1]"></div>
            <div class="text-sm text-slate-600" x-text="stepDescriptions[currentStep - 1]"></div>
        </div>
    </div>
    
    {{-- Step 1: Project Basics (High Priority) --}}
    <div x-show="currentStep === 1" x-transition class="space-y-6">
        <flux:card class="p-6">
            <h3 class="text-lg font-semibold mb-4">Essential Project Information</h3>
            
            <div class="space-y-6">
                {{-- Project title --}}
                <flux:field>
                    <flux:label>Project Title</flux:label>
                    <flux:input 
                        wire:model.live="project.title"
                        placeholder="e.g., Summer Anthem Mix & Master"
                        class="text-lg"
                        required
                    />
                    <flux:error name="project.title" />
                    <flux:text size="sm" class="text-slate-500">
                        This is how your project will appear to producers
                    </flux:text>
                </flux:field>
                
                {{-- Project type --}}
                <flux:field>
                    <flux:label>What do you need?</flux:label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($projectTypes as $type)
                            <label class="relative flex items-center p-4 border border-slate-200 rounded-lg cursor-pointer hover:bg-slate-50 transition-colors">
                                <input 
                                    type="radio" 
                                    wire:model.live="project.type_id" 
                                    value="{{ $type->id }}"
                                    class="sr-only"
                                >
                                <div class="flex items-start space-x-3 w-full">
                                    <div class="flex-shrink-0 mt-1">
                                        <div class="w-4 h-4 border-2 border-slate-300 rounded-full flex items-center justify-center">
                                            <div class="w-2 h-2 bg-indigo-600 rounded-full opacity-0 transition-opacity"
                                                 :class="{ 'opacity-100': $wire.project.type_id == {{ $type->id }} }">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <div class="font-medium">{{ $type->name }}</div>
                                        <div class="text-sm text-slate-600 mt-1">{{ $type->description }}</div>
                                        <div class="text-xs text-slate-500 mt-2">
                                            Typical price: {{ $type->price_range }}
                                        </div>
                                    </div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                    <flux:error name="project.type_id" />
                </flux:field>
                
                {{-- Budget range --}}
                <flux:field>
                    <flux:label>Budget Range</flux:label>
                    <flux:select wire:model.live="project.budget_range" required>
                        <option value="">Select your budget</option>
                        <option value="under_500">Under $500</option>
                        <option value="500_1000">$500 - $1,000</option>
                        <option value="1000_2500">$1,000 - $2,500</option>
                        <option value="2500_5000">$2,500 - $5,000</option>
                        <option value="5000_plus">$5,000+</option>
                        <option value="to_be_discussed">To be discussed</option>
                    </flux:select>
                    <flux:error name="project.budget_range" />
                    <flux:text size="sm" class="text-slate-500">
                        This helps producers understand your project scope
                    </flux:text>
                </flux:field>
                
                {{-- Deadline --}}
                <flux:field>
                    <flux:label>When do you need this completed?</flux:label>
                    <div class="grid grid-cols-2 gap-4">
                        <flux:input 
                            type="date"
                            wire:model.live="project.deadline"
                            min="{{ now()->addDays(3)->format('Y-m-d') }}"
                            required
                        />
                        <flux:select wire:model.live="project.urgency">
                            <option value="normal">Normal timeline</option>
                            <option value="rush">Rush job (+25% fee)</option>
                            <option value="emergency">Emergency (+50% fee)</option>
                        </flux:select>
                    </div>
                    <flux:error name="project.deadline" />
                    <flux:text size="sm" class="text-slate-500">
                        Minimum 3 days required for quality work
                    </flux:text>
                </flux:field>
            </div>
        </flux:card>
    </div>
    
    {{-- Step 2: Project Details (Medium Priority) --}}
    <div x-show="currentStep === 2" x-transition class="space-y-6">
        <flux:card class="p-6">
            <h3 class="text-lg font-semibold mb-4">Project Details</h3>
            
            <div class="space-y-6">
                {{-- Contact information --}}
                <div class="bg-slate-50 rounded-lg p-4">
                    <h4 class="font-medium mb-3">Client Contact Information</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <flux:field>
                            <flux:label>Client Name</flux:label>
                            <flux:input 
                                wire:model.defer="project.client_name"
                                placeholder="Band name or your name"
                            />
                        </flux:field>
                        
                        <flux:field>
                            <flux:label>Client Email</flux:label>
                            <flux:input 
                                type="email"
                                wire:model.defer="project.client_email"
                                placeholder="for final delivery and communication"
                            />
                            <flux:text size="sm" class="text-slate-500">
                                We'll include them in project updates
                            </flux:text>
                        </flux:field>
                    </div>
                </div>
                
                {{-- Project description --}}
                <flux:field>
                    <flux:label>Project Description</flux:label>
                    <flux:textarea 
                        wire:model.defer="project.description"
                        rows="4"
                        placeholder="Describe your project, genre, vision, and any specific requirements..."
                    />
                    <flux:text size="sm" class="text-slate-500">
                        Help producers understand your vision and style
                    </flux:text>
                </flux:field>
                
                {{-- Reference tracks --}}
                <flux:field>
                    <flux:label>Reference Tracks (Optional)</flux:label>
                    <div class="space-y-3">
                        <flux:textarea 
                            wire:model.defer="project.reference_tracks"
                            rows="3"
                            placeholder="List Spotify/YouTube links or artist names that inspire this project..."
                        />
                        <div class="p-3 bg-blue-50 rounded-lg">
                            <div class="flex items-start space-x-2">
                                <flux:icon name="lightbulb" class="h-5 w-5 text-blue-600 mt-0.5" />
                                <div class="text-sm text-blue-700">
                                    <strong>Pro tip:</strong> Include 2-3 reference tracks that capture the sound, energy, or production style you're aiming for.
                                </div>
                            </div>
                        </div>
                    </div>
                </flux:field>
                
                {{-- Genre and style --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>Primary Genre</flux:label>
                        <flux:select wire:model.defer="project.primary_genre">
                            <option value="">Select genre</option>
                            @foreach($genres as $genre)
                                <option value="{{ $genre }}">{{ $genre }}</option>
                            @endforeach
                        </flux:select>
                    </flux:field>
                    
                    <flux:field>
                        <flux:label>Subgenre/Style</flux:label>
                        <flux:input 
                            wire:model.defer="project.subgenre"
                            placeholder="e.g., Dark Pop, Indie Folk, Trap"
                        />
                    </flux:field>
                </div>
            </div>
        </flux:card>
    </div>
    
    {{-- Step 3: Requirements & Deliverables (Medium Priority) --}}
    <div x-show="currentStep === 3" x-transition class="space-y-6">
        <flux:card class="p-6">
            <h3 class="text-lg font-semibold mb-4">Requirements & Deliverables</h3>
            
            <div class="space-y-6">
                {{-- Deliverable formats --}}
                <flux:field>
                    <flux:label>Required Deliverable Formats</flux:label>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        @foreach($deliverableFormats as $format)
                            <label class="flex items-center space-x-2 p-3 border border-slate-200 rounded-lg cursor-pointer hover:bg-slate-50">
                                <flux:checkbox 
                                    wire:model.defer="project.deliverable_formats"
                                    value="{{ $format->id }}"
                                />
                                <div>
                                    <div class="font-medium text-sm">{{ $format->name }}</div>
                                    <div class="text-xs text-slate-600">{{ $format->description }}</div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </flux:field>
                
                {{-- Revision policy --}}
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                    <h4 class="font-medium text-amber-900 mb-3">Revision Policy</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <flux:field>
                            <flux:label size="sm">Included Revisions</flux:label>
                            <flux:select wire:model.defer="project.included_revisions">
                                <option value="2">2 rounds (Standard)</option>
                                <option value="3">3 rounds (Recommended)</option>
                                <option value="5">5 rounds (Premium)</option>
                                <option value="unlimited">Unlimited (Budget permitting)</option>
                            </flux:select>
                        </flux:field>
                        
                        <flux:field>
                            <flux:label size="sm">Additional Revision Cost</flux:label>
                            <flux:input 
                                type="number"
                                wire:model.defer="project.additional_revision_cost"
                                placeholder="150"
                                step="25"
                            />
                        </flux:field>
                    </div>
                    <flux:text size="sm" class="text-amber-700 mt-2">
                        Clear revision boundaries help both parties manage expectations
                    </flux:text>
                </div>
                
                {{-- Special requirements --}}
                <flux:field>
                    <flux:label>Special Requirements or Notes</flux:label>
                    <flux:textarea 
                        wire:model.defer="project.special_requirements"
                        rows="3"
                        placeholder="Any specific technical requirements, creative constraints, or important details..."
                    />
                    <flux:text size="sm" class="text-slate-500">
                        Mention any specific plugins, techniques, or limitations
                    </flux:text>
                </flux:field>
            </div>
        </flux:card>
    </div>
    
    {{-- Step 4: Project Settings (Low Priority) --}}
    <div x-show="currentStep === 4" x-transition class="space-y-6">
        <flux:card class="p-6">
            <h3 class="text-lg font-semibold mb-4">Project Settings</h3>
            
            <div class="space-y-6">
                {{-- Workflow type --}}
                <flux:field>
                    <flux:label>Collaboration Style</flux:label>
                    <div class="space-y-3">
                        @foreach($workflowTypes as $workflow)
                            <label class="flex items-start space-x-3 p-4 border border-slate-200 rounded-lg cursor-pointer hover:bg-slate-50">
                                <input 
                                    type="radio" 
                                    wire:model.defer="project.workflow_type" 
                                    value="{{ $workflow->value }}"
                                    class="mt-1"
                                >
                                <div>
                                    <div class="font-medium">{{ $workflow->label }}</div>
                                    <div class="text-sm text-slate-600 mt-1">{{ $workflow->description }}</div>
                                    @if($workflow->badge)
                                        <flux:badge variant="{{ $workflow->badge_variant }}" size="sm" class="mt-2">
                                            {{ $workflow->badge }}
                                        </flux:badge>
                                    @endif
                                </div>
                            </label>
                        @endforeach
                    </div>
                </flux:field>
                
                {{-- File naming conventions --}}
                <div class="border border-slate-200 rounded-lg p-4">
                    <h4 class="font-medium mb-3">File Organization (Optional)</h4>
                    <div class="space-y-4">
                        <flux:field>
                            <flux:label size="sm">Preferred File Naming</flux:label>
                            <flux:select wire:model.defer="project.file_naming_convention">
                                <option value="auto">Automatic (recommended)</option>
                                <option value="project_version">ProjectName_V01</option>
                                <option value="date_version">ProjectName_2024-01-15_V01</option>
                                <option value="custom">Custom format</option>
                            </flux:select>
                        </flux:field>
                        
                        <flux:field>
                            <flux:label size="sm">Sample Rate Preference</flux:label>
                            <flux:select wire:model.defer="project.sample_rate">
                                <option value="match_source">Match source material</option>
                                <option value="44100">44.1 kHz (Standard)</option>
                                <option value="48000">48 kHz (Professional)</option>
                                <option value="96000">96 kHz (High-res)</option>
                            </flux:select>
                        </flux:field>
                        
                        <flux:field>
                            <flux:label size="sm">Bit Depth</flux:label>
                            <flux:select wire:model.defer="project.bit_depth">
                                <option value="24">24-bit (Recommended)</option>
                                <option value="16">16-bit (CD Quality)</option>
                                <option value="32">32-bit Float</option>
                            </flux:select>
                        </flux:field>
                    </div>
                </div>
                
                {{-- Additional settings --}}
                <div class="space-y-4">
                    <div class="flex items-center space-x-2">
                        <flux:checkbox wire:model.defer="project.auto_setup_maildrop" />
                        <flux:label>Set up project email for easy file sharing</flux:label>
                    </div>
                    
                    <div class="flex items-center space-x-2">
                        <flux:checkbox wire:model.defer="project.enable_auto_versioning" />
                        <flux:label>Enable automatic file versioning</flux:label>
                    </div>
                    
                    <div class="flex items-center space-x-2">
                        <flux:checkbox wire:model.defer="project.require_license_agreement" />
                        <flux:label>Require producer license agreement</flux:label>
                    </div>
                    
                    <div class="flex items-center space-x-2">
                        <flux:checkbox wire:model.defer="project.auto_invite_collaborators" />
                        <flux:label>Auto-invite client to project updates</flux:label>
                    </div>
                </div>
            </div>
        </flux:card>
    </div>
    
    {{-- Navigation buttons --}}
    <div class="flex items-center justify-between pt-6 border-t">
        <flux:button 
            x-show="currentStep > 1"
            @click="previousStep()"
            variant="outline"
        >
            <flux:icon name="arrow-left" size="sm" />
            Previous
        </flux:button>
        
        <div class="flex items-center space-x-3">
            <flux:button 
                x-show="currentStep < 4"
                @click="skipToEnd()"
                variant="ghost"
                class="text-slate-600"
            >
                Skip optional steps
            </flux:button>
            
            <flux:button 
                x-show="currentStep < 4"
                @click="nextStep()"
                variant="primary"
                :disabled="!canProceed()"
            >
                Next
                <flux:icon name="arrow-right" size="sm" />
            </flux:button>
            
            <flux:button 
                x-show="currentStep === 4"
                wire:click="createProject"
                variant="primary"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove>Create Project</span>
                <span wire:loading>Creating...</span>
            </flux:button>
        </div>
    </div>
</div>

<script>
function projectWizard() {
    return {
        currentStep: 1,
        stepTitles: [
            'Project Basics',
            'Project Details', 
            'Requirements',
            'Settings'
        ],
        stepDescriptions: [
            'Essential information to get started',
            'Help producers understand your vision',
            'Define deliverables and revision policy',
            'Optional project configuration'
        ],
        
        nextStep() {
            if (this.canProceed() && this.currentStep < 4) {
                this.currentStep++;
            }
        },
        
        previousStep() {
            if (this.currentStep > 1) {
                this.currentStep--;
            }
        },
        
        skipToEnd() {
            this.currentStep = 4;
        },
        
        canProceed() {
            // Basic validation for each step
            switch (this.currentStep) {
                case 1:
                    return this.$wire.project.title && 
                           this.$wire.project.type_id && 
                           this.$wire.project.budget_range && 
                           this.$wire.project.deadline;
                case 2:
                case 3:
                case 4:
                    return true; // Optional steps
                default:
                    return false;
            }
        }
    }
}
</script>
```

### Smart Form Field Prioritization

```blade
{{-- Dynamic field priority indicator --}}
<div class="space-y-6">
    @foreach($formFields as $field)
        <flux:field>
            <div class="flex items-center justify-between">
                <flux:label>{{ $field->label }}</flux:label>
                <div class="flex items-center space-x-2">
                    @if($field->priority === 'high')
                        <flux:badge variant="danger" size="sm">Required</flux:badge>
                    @elseif($field->priority === 'medium')
                        <flux:badge variant="warning" size="sm">Recommended</flux:badge>
                    @else
                        <flux:badge variant="outline" size="sm">Optional</flux:badge>
                    @endif
                    
                    @if($field->has_help)
                        <flux:button 
                            size="sm" 
                            variant="ghost"
                            @click="showHelp = !showHelp"
                        >
                            <flux:icon name="question-mark-circle" size="sm" />
                        </flux:button>
                    @endif
                </div>
            </div>
            
            {{-- Dynamic field component based on type --}}
            <x-dynamic-component 
                :component="'form-field-' . $field->type"
                :field="$field"
                wire:model.defer="project.{{ $field->name }}"
            />
            
            @if($field->has_help)
                <div x-show="showHelp" x-transition class="mt-2 p-3 bg-blue-50 rounded-lg">
                    <flux:text size="sm" class="text-blue-700">
                        {{ $field->help_text }}
                    </flux:text>
                </div>
            @endif
        </flux:field>
    @endforeach
</div>
```

### Automated Project Setup

```blade
{{-- Post-creation automation status --}}
<div class="bg-green-50 border border-green-200 rounded-lg p-6">
    <div class="flex items-center mb-4">
        <div class="flex-shrink-0">
            <flux:icon name="check-circle" class="h-6 w-6 text-green-600" />
        </div>
        <h3 class="ml-3 text-lg font-medium text-green-900">
            Project Created Successfully!
        </h3>
    </div>
    
    <div class="space-y-3">
        <div class="flex items-center text-sm">
            <flux:icon name="check" class="h-4 w-4 text-green-600 mr-2" />
            <span class="text-green-700">Project folder created</span>
        </div>
        
        @if($project->auto_setup_maildrop)
            <div class="flex items-center text-sm">
                <flux:icon name="check" class="h-4 w-4 text-green-600 mr-2" />
                <span class="text-green-700">
                    Project email set up: 
                    <code class="bg-green-100 px-2 py-1 rounded">{{ $project->maildrop_address }}</code>
                </span>
            </div>
        @endif
        
        @if($project->auto_invite_collaborators && $project->client_email)
            <div class="flex items-center text-sm">
                <flux:icon name="check" class="h-4 w-4 text-green-600 mr-2" />
                <span class="text-green-700">Client invited to project updates</span>
            </div>
        @endif
        
        @if($project->enable_auto_versioning)
            <div class="flex items-center text-sm">
                <flux:icon name="check" class="h-4 w-4 text-green-600 mr-2" />
                <span class="text-green-700">Auto-versioning enabled for file uploads</span>
            </div>
        @endif
        
        @if($project->revisionPolicy)
            <div class="flex items-center text-sm">
                <flux:icon name="check" class="h-4 w-4 text-green-600 mr-2" />
                <span class="text-green-700">
                    Revision policy configured: {{ $project->revisionPolicy->included_rounds }} included rounds
                </span>
            </div>
        @endif
    </div>
    
    <div class="mt-6 flex items-center space-x-3">
        <flux:button 
            wire:click="viewProject"
            variant="primary"
        >
            View Project
        </flux:button>
        
        <flux:button 
            wire:click="uploadFiles"
            variant="outline"
        >
            Upload Files
        </flux:button>
        
        <flux:button 
            wire:click="inviteProducers"
            variant="outline"
        >
            Invite Producers
        </flux:button>
    </div>
</div>
```

## Database Schema

### New Table: `intake_form_fields`

```php
Schema::create('intake_form_fields', function (Blueprint $table) {
    $table->id();
    $table->string('name'); // Field identifier
    $table->string('label'); // Display label
    $table->string('type'); // input, textarea, select, checkbox, etc.
    $table->enum('priority', ['high', 'medium', 'low']); // Field priority
    $table->integer('step'); // Which wizard step (1-4)
    $table->integer('order')->default(0); // Order within step
    $table->boolean('required')->default(false);
    $table->json('validation_rules')->nullable(); // Laravel validation rules
    $table->json('options')->nullable(); // For select fields, etc.
    $table->text('help_text')->nullable();
    $table->text('placeholder')->nullable();
    $table->boolean('is_active')->default(true);
    $table->json('conditional_logic')->nullable(); // Show/hide based on other fields
    $table->timestamps();
    
    $table->index(['step', 'order']);
    $table->index(['priority', 'step']);
});
```

### New Table: `project_templates`

```php
Schema::create('project_templates', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->text('description');
    $table->foreignId('project_type_id')->constrained()->onDelete('cascade');
    $table->json('default_values'); // Pre-filled form values
    $table->json('required_fields'); // Override field requirements
    $table->json('hidden_fields')->nullable(); // Fields to hide for this template
    $table->json('automation_settings'); // What to auto-setup
    $table->boolean('is_active')->default(true);
    $table->integer('usage_count')->default(0);
    $table->timestamps();
    
    $table->index('project_type_id');
    $table->index(['is_active', 'usage_count']);
});
```

### New Table: `intake_sessions`

```php
Schema::create('intake_sessions', function (Blueprint $table) {
    $table->id();
    $table->string('session_id')->unique();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->integer('current_step')->default(1);
    $table->json('form_data'); // Collected form data
    $table->json('field_interactions')->nullable(); // Track field usage
    $table->timestamp('started_at');
    $table->timestamp('completed_at')->nullable();
    $table->timestamp('abandoned_at')->nullable();
    $table->foreignId('created_project_id')->nullable()->constrained('projects');
    $table->timestamps();
    
    $table->index(['user_id', 'completed_at']);
    $table->index(['session_id']);
});
```

### Extend `projects` table

```php
Schema::table('projects', function (Blueprint $table) {
    $table->string('intake_session_id')->nullable()->after('metadata');
    $table->json('automation_applied')->nullable()->after('intake_session_id'); // Track what was auto-setup
    $table->enum('creation_method', ['wizard', 'quick', 'template', 'api'])->default('wizard');
    $table->integer('completion_percentage')->default(0); // How complete is the project info
    $table->json('missing_info_prompts')->nullable(); // What info should be collected later
    
    $table->index(['creation_method', 'created_at']);
    $table->index('completion_percentage');
});
```

## Service Layer Architecture

### New Service: `IntakeWizardService`

```php
<?php

namespace App\Services;

use App\Models\Project;
use App\Models\User;
use App\Models\IntakeSession;
use App\Models\IntakeFormField;
use App\Models\ProjectTemplate;
use App\Services\ProjectManagementService;
use App\Services\MaildropService;
use App\Services\RevisionRoundService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IntakeWizardService
{
    protected ProjectManagementService $projectService;
    protected MaildropService $maildropService;
    protected RevisionRoundService $revisionService;
    
    public function __construct(
        ProjectManagementService $projectService,
        MaildropService $maildropService,
        RevisionRoundService $revisionService
    ) {
        $this->projectService = $projectService;
        $this->maildropService = $maildropService;
        $this->revisionService = $revisionService;
    }
    
    public function startIntakeSession(User $user, ?string $templateId = null): IntakeSession
    {
        $sessionId = Str::uuid()->toString();
        
        // Load template defaults if specified
        $defaultData = [];
        if ($templateId) {
            $template = ProjectTemplate::findOrFail($templateId);
            $defaultData = $template->default_values;
        }
        
        $session = IntakeSession::create([
            'session_id' => $sessionId,
            'user_id' => $user->id,
            'current_step' => 1,
            'form_data' => $defaultData,
            'started_at' => now(),
        ]);
        
        Log::info('Intake session started', [
            'session_id' => $sessionId,
            'user_id' => $user->id,
            'template_id' => $templateId,
        ]);
        
        return $session;
    }
    
    public function updateSessionData(
        string $sessionId, 
        array $formData, 
        int $currentStep = null
    ): IntakeSession {
        
        $session = IntakeSession::where('session_id', $sessionId)->firstOrFail();
        
        // Merge new data with existing
        $existingData = $session->form_data;
        $updatedData = array_merge($existingData, $formData);
        
        $updateFields = ['form_data' => $updatedData];
        
        if ($currentStep !== null) {
            $updateFields['current_step'] = $currentStep;
        }
        
        $session->update($updateFields);
        
        return $session->fresh();
    }
    
    public function completeIntakeAndCreateProject(string $sessionId): Project
    {
        $session = IntakeSession::where('session_id', $sessionId)->firstOrFail();
        
        if ($session->completed_at) {
            throw new \Exception('Intake session already completed');
        }
        
        DB::beginTransaction();
        
        try {
            // Validate required fields
            $this->validateRequiredFields($session->form_data);
            
            // Create project from form data
            $project = $this->createProjectFromIntakeData($session);
            
            // Apply automation based on settings
            $automationApplied = $this->applyAutomation($project, $session->form_data);
            
            // Update session as completed
            $session->update([
                'completed_at' => now(),
                'created_project_id' => $project->id,
            ]);
            
            // Update project with automation info
            $project->update([
                'intake_session_id' => $session->session_id,
                'automation_applied' => $automationApplied,
                'creation_method' => 'wizard',
                'completion_percentage' => $this->calculateCompletionPercentage($session->form_data),
            ]);
            
            DB::commit();
            
            Log::info('Project created from intake', [
                'session_id' => $sessionId,
                'project_id' => $project->id,
                'automation_applied' => $automationApplied,
            ]);
            
            return $project;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create project from intake', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
    
    protected function createProjectFromIntakeData(IntakeSession $session): Project
    {
        $data = $session->form_data;
        
        return $this->projectService->createProject($session->user, [
            'title' => $data['title'],
            'description' => $data['description'],
            'project_type_id' => $data['type_id'],
            'budget_range' => $data['budget_range'],
            'deadline' => $data['deadline'],
            'urgency' => $data['urgency'] ?? 'normal',
            'client_name' => $data['client_name'] ?? null,
            'client_email' => $data['client_email'] ?? null,
            'primary_genre' => $data['primary_genre'] ?? null,
            'subgenre' => $data['subgenre'] ?? null,
            'reference_tracks' => $data['reference_tracks'] ?? null,
            'special_requirements' => $data['special_requirements'] ?? null,
            'workflow_type' => $data['workflow_type'] ?? 'standard',
            'deliverable_formats' => $data['deliverable_formats'] ?? [],
            'file_naming_convention' => $data['file_naming_convention'] ?? 'auto',
            'sample_rate' => $data['sample_rate'] ?? 'match_source',
            'bit_depth' => $data['bit_depth'] ?? '24',
        ]);
    }
    
    protected function applyAutomation(Project $project, array $formData): array
    {
        $applied = [];
        
        // Set up project maildrop
        if ($formData['auto_setup_maildrop'] ?? false) {
            $this->maildropService->createMaildropForProject($project);
            $applied[] = 'maildrop_created';
        }
        
        // Configure revision policy
        if (isset($formData['included_revisions']) || isset($formData['additional_revision_cost'])) {
            $this->revisionService->createRevisionPolicy($project, [
                'included_rounds' => (int) ($formData['included_revisions'] ?? 2),
                'additional_round_price' => (float) ($formData['additional_revision_cost'] ?? 150),
                'lock_on_approval' => true,
                'require_payment_for_additional' => true,
            ]);
            $applied[] = 'revision_policy_created';
        }
        
        // Set up auto-versioning
        if ($formData['enable_auto_versioning'] ?? false) {
            $project->update(['enable_auto_versioning' => true]);
            $applied[] = 'auto_versioning_enabled';
        }
        
        // Invite client to project updates
        if ($formData['auto_invite_collaborators'] ?? false && !empty($formData['client_email'])) {
            $this->projectService->inviteClientToProject($project, $formData['client_email']);
            $applied[] = 'client_invited';
        }
        
        // Set up license agreement requirement
        if ($formData['require_license_agreement'] ?? false) {
            $project->update(['requires_license_agreement' => true]);
            $applied[] = 'license_requirement_set';
        }
        
        return $applied;
    }
    
    protected function validateRequiredFields(array $formData): void
    {
        $requiredFields = IntakeFormField::where('required', true)
            ->where('is_active', true)
            ->pluck('name')
            ->toArray();
            
        $missing = [];
        
        foreach ($requiredFields as $field) {
            if (empty($formData[$field])) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            throw new \InvalidArgumentException(
                'Missing required fields: ' . implode(', ', $missing)
            );
        }
    }
    
    protected function calculateCompletionPercentage(array $formData): int
    {
        $allFields = IntakeFormField::where('is_active', true)->get();
        $totalFields = $allFields->count();
        $completedFields = 0;
        
        foreach ($allFields as $field) {
            if (!empty($formData[$field->name])) {
                // Weight fields by priority
                $weight = match($field->priority) {
                    'high' => 3,
                    'medium' => 2,
                    'low' => 1,
                };
                
                $completedFields += $weight;
            }
        }
        
        // Calculate weighted total possible
        $totalPossible = $allFields->sum(function ($field) {
            return match($field->priority) {
                'high' => 3,
                'medium' => 2,
                'low' => 1,
            };
        });
        
        return $totalPossible > 0 ? (int) (($completedFields / $totalPossible) * 100) : 0;
    }
    
    public function getFormFieldsForStep(int $step): Collection
    {
        return IntakeFormField::where('step', $step)
            ->where('is_active', true)
            ->orderBy('order')
            ->orderBy('priority', 'desc') // High priority first
            ->get();
    }
    
    public function suggestTemplate(User $user, array $projectHints = []): ?ProjectTemplate
    {
        // Simple template suggestion based on project type or user history
        if (isset($projectHints['project_type_id'])) {
            return ProjectTemplate::where('project_type_id', $projectHints['project_type_id'])
                ->where('is_active', true)
                ->orderBy('usage_count', 'desc')
                ->first();
        }
        
        // Could be enhanced with ML-based suggestions
        return null;
    }
    
    public function abandonSession(string $sessionId): void
    {
        $session = IntakeSession::where('session_id', $sessionId)->first();
        
        if ($session && !$session->completed_at) {
            $session->update(['abandoned_at' => now()]);
            
            Log::info('Intake session abandoned', [
                'session_id' => $sessionId,
                'current_step' => $session->current_step,
                'form_data_keys' => array_keys($session->form_data),
            ]);
        }
    }
}
```

### Enhanced ProjectManagementService Integration

```php
// Add to existing ProjectManagementService

public function createProject(User $user, array $data): Project
{
    // ... existing validation ...
    
    $project = Project::create([
        // ... existing fields ...
        'completion_percentage' => $data['completion_percentage'] ?? 0,
        'creation_method' => $data['creation_method'] ?? 'manual',
        'missing_info_prompts' => $this->identifyMissingInformation($data),
    ]);
    
    // ... rest of existing logic ...
    
    return $project;
}

protected function identifyMissingInformation(array $data): array
{
    $suggestions = [];
    
    if (empty($data['client_email'])) {
        $suggestions[] = [
            'field' => 'client_email',
            'priority' => 'medium',
            'message' => 'Add client email to include them in project updates',
            'action' => 'prompt_for_client_email'
        ];
    }
    
    if (empty($data['reference_tracks'])) {
        $suggestions[] = [
            'field' => 'reference_tracks',
            'priority' => 'medium',
            'message' => 'Reference tracks help producers understand your vision',
            'action' => 'prompt_for_references'
        ];
    }
    
    if (empty($data['deliverable_formats'])) {
        $suggestions[] = [
            'field' => 'deliverable_formats',
            'priority' => 'high',
            'message' => 'Specify required file formats for final delivery',
            'action' => 'prompt_for_deliverables'
        ];
    }
    
    return $suggestions;
}
```

## Livewire Components

### Main Intake Wizard Component

```php
<?php

namespace App\Livewire\Project;

use App\Models\User;
use App\Models\IntakeSession;
use App\Models\ProjectType;
use App\Models\ProjectTemplate;
use App\Services\IntakeWizardService;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class IntakeWizard extends Component
{
    public $sessionId;
    public $currentStep = 1;
    public $project = [];
    public $selectedTemplate = null;
    
    // Form data arrays for each step
    public $basics = [];
    public $details = [];
    public $requirements = [];
    public $settings = [];
    
    protected $rules = [
        'project.title' => 'required|string|max:255',
        'project.type_id' => 'required|exists:project_types,id',
        'project.budget_range' => 'required|string',
        'project.deadline' => 'required|date|after:' . '+3 days',
        'project.description' => 'nullable|string|max:2000',
        'project.client_email' => 'nullable|email',
        'project.primary_genre' => 'nullable|string',
        'project.reference_tracks' => 'nullable|string|max:1000',
    ];
    
    public function mount(?string $templateId = null)
    {
        $service = app(IntakeWizardService::class);
        $session = $service->startIntakeSession(auth()->user(), $templateId);
        
        $this->sessionId = $session->session_id;
        $this->project = $session->form_data;
        
        if ($templateId) {
            $this->selectedTemplate = ProjectTemplate::find($templateId);
        }
    }
    
    public function nextStep()
    {
        $this->validateCurrentStep();
        
        if ($this->currentStep < 4) {
            $this->saveCurrentStepData();
            $this->currentStep++;
        }
    }
    
    public function previousStep()
    {
        if ($this->currentStep > 1) {
            $this->saveCurrentStepData();
            $this->currentStep--;
        }
    }
    
    public function skipToEnd()
    {
        $this->saveCurrentStepData();
        $this->currentStep = 4;
    }
    
    protected function validateCurrentStep()
    {
        switch ($this->currentStep) {
            case 1:
                $this->validate([
                    'project.title' => 'required|string|max:255',
                    'project.type_id' => 'required|exists:project_types,id',
                    'project.budget_range' => 'required|string',
                    'project.deadline' => 'required|date|after:+3 days',
                ]);
                break;
            case 2:
                $this->validate([
                    'project.description' => 'nullable|string|max:2000',
                    'project.client_email' => 'nullable|email',
                ]);
                break;
            case 3:
            case 4:
                // Optional steps - no validation required
                break;
        }
    }
    
    protected function saveCurrentStepData()
    {
        $service = app(IntakeWizardService::class);
        $service->updateSessionData(
            $this->sessionId,
            $this->project,
            $this->currentStep
        );
    }
    
    public function createProject(IntakeWizardService $service)
    {
        $this->validate();
        
        try {
            $this->saveCurrentStepData();
            
            $project = $service->completeIntakeAndCreateProject($this->sessionId);
            
            Toaster::success('Project created successfully!');
            
            return redirect()->route('projects.show', $project);
            
        } catch (\Exception $e) {
            Toaster::error('Failed to create project: ' . $e->getMessage());
        }
    }
    
    public function loadTemplate(string $templateId)
    {
        $template = ProjectTemplate::findOrFail($templateId);
        
        // Merge template defaults with current data
        $this->project = array_merge($this->project, $template->default_values);
        $this->selectedTemplate = $template;
        
        // Save updated data
        $this->saveCurrentStepData();
        
        Toaster::success('Template loaded successfully!');
    }
    
    public function getProjectTypesProperty()
    {
        return ProjectType::where('is_active', true)
            ->orderBy('order')
            ->get();
    }
    
    public function getGenresProperty()
    {
        return [
            'Pop', 'Rock', 'Hip-Hop', 'Electronic', 'Country', 'Jazz',
            'Classical', 'R&B', 'Reggae', 'Folk', 'Metal', 'Punk',
            'Indie', 'Alternative', 'Funk', 'Blues', 'Gospel', 'Other'
        ];
    }
    
    public function getDeliverableFormatsProperty()
    {
        return collect([
            ['id' => 'wav_24_48', 'name' => 'WAV 24/48', 'description' => 'Professional master'],
            ['id' => 'wav_16_44', 'name' => 'WAV 16/44.1', 'description' => 'CD quality'],
            ['id' => 'mp3_320', 'name' => 'MP3 320kbps', 'description' => 'High quality streaming'],
            ['id' => 'stems', 'name' => 'Individual Stems', 'description' => 'Separate instrument tracks'],
            ['id' => 'instrumental', 'name' => 'Instrumental Version', 'description' => 'No vocals'],
            ['id' => 'acapella', 'name' => 'Acapella Version', 'description' => 'Vocals only'],
        ]);
    }
    
    public function getWorkflowTypesProperty()
    {
        return collect([
            [
                'value' => 'standard',
                'label' => 'Open Competition',
                'description' => 'Multiple producers can submit proposals and compete for your project',
                'badge' => 'Most Popular',
                'badge_variant' => 'success'
            ],
            [
                'value' => 'direct_hire',
                'label' => 'Direct Hire',
                'description' => 'Work directly with a specific producer you choose',
                'badge' => null,
                'badge_variant' => null
            ],
            [
                'value' => 'contest',
                'label' => 'Contest Mode',
                'description' => 'Set a prize and let producers compete with finished work',
                'badge' => 'High Quality',
                'badge_variant' => 'warning'
            ],
            [
                'value' => 'client_management',
                'label' => 'Client Project',
                'description' => 'Professional client workflow with external approvals',
                'badge' => 'Professional',
                'badge_variant' => 'info'
            ],
        ]);
    }
    
    public function render()
    {
        return view('livewire.project.intake-wizard');
    }
}
```

### Field Priority Component

```php
<?php

namespace App\Livewire\Admin;

use App\Models\IntakeFormField;
use Livewire\Component;
use Livewire\WithPagination;
use Masmerise\Toaster\Toaster;

class IntakeFieldManager extends Component
{
    use WithPagination;
    
    public $editing = null;
    public $field = [
        'name' => '',
        'label' => '',
        'type' => 'input',
        'priority' => 'medium',
        'step' => 1,
        'required' => false,
        'help_text' => '',
        'placeholder' => '',
    ];
    
    protected $rules = [
        'field.name' => 'required|string|unique:intake_form_fields,name',
        'field.label' => 'required|string|max:255',
        'field.type' => 'required|in:input,textarea,select,checkbox,radio',
        'field.priority' => 'required|in:high,medium,low',
        'field.step' => 'required|integer|min:1|max:4',
        'field.required' => 'boolean',
        'field.help_text' => 'nullable|string|max:500',
        'field.placeholder' => 'nullable|string|max:255',
    ];
    
    public function createField()
    {
        $this->validate();
        
        IntakeFormField::create($this->field);
        
        $this->reset('field');
        Toaster::success('Field created successfully!');
    }
    
    public function editField(IntakeFormField $field)
    {
        $this->editing = $field->id;
        $this->field = $field->toArray();
    }
    
    public function updateField()
    {
        $this->validate();
        
        $field = IntakeFormField::findOrFail($this->editing);
        $field->update($this->field);
        
        $this->reset(['editing', 'field']);
        Toaster::success('Field updated successfully!');
    }
    
    public function deleteField(IntakeFormField $field)
    {
        $field->delete();
        Toaster::success('Field deleted successfully!');
    }
    
    public function updateOrder(array $orderedIds)
    {
        foreach ($orderedIds as $index => $id) {
            IntakeFormField::where('id', $id)->update(['order' => $index + 1]);
        }
        
        Toaster::success('Field order updated!');
    }
    
    public function render()
    {
        $fields = IntakeFormField::orderBy('step')
            ->orderBy('order')
            ->orderBy('priority', 'desc')
            ->paginate(20);
            
        return view('livewire.admin.intake-field-manager', compact('fields'));
    }
}
```

## Analytics & Optimization

### Intake Analytics Service

```php
<?php

namespace App\Services;

use App\Models\IntakeSession;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

class IntakeAnalyticsService
{
    public function getIntakeMetrics(int $days = 30): array
    {
        $startDate = now()->subDays($days);
        
        return [
            'sessions_started' => $this->getSessionsStarted($startDate),
            'sessions_completed' => $this->getSessionsCompleted($startDate),
            'completion_rate' => $this->getCompletionRate($startDate),
            'average_time_to_complete' => $this->getAverageCompletionTime($startDate),
            'abandonment_by_step' => $this->getAbandonmentByStep($startDate),
            'field_completion_rates' => $this->getFieldCompletionRates($startDate),
            'template_usage' => $this->getTemplateUsage($startDate),
        ];
    }
    
    protected function getSessionsStarted(\Carbon\Carbon $startDate): int
    {
        return IntakeSession::where('started_at', '>=', $startDate)->count();
    }
    
    protected function getSessionsCompleted(\Carbon\Carbon $startDate): int
    {
        return IntakeSession::where('started_at', '>=', $startDate)
            ->whereNotNull('completed_at')
            ->count();
    }
    
    protected function getCompletionRate(\Carbon\Carbon $startDate): float
    {
        $started = $this->getSessionsStarted($startDate);
        $completed = $this->getSessionsCompleted($startDate);
        
        return $started > 0 ? ($completed / $started) * 100 : 0;
    }
    
    protected function getAverageCompletionTime(\Carbon\Carbon $startDate): float
    {
        $averageSeconds = IntakeSession::where('started_at', '>=', $startDate)
            ->whereNotNull('completed_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, started_at, completed_at)) as avg_seconds')
            ->value('avg_seconds');
            
        return $averageSeconds ? round($averageSeconds / 60, 1) : 0; // Convert to minutes
    }
    
    protected function getAbandonmentByStep(\Carbon\Carbon $startDate): array
    {
        return IntakeSession::where('started_at', '>=', $startDate)
            ->whereNull('completed_at')
            ->whereNotNull('abandoned_at')
            ->groupBy('current_step')
            ->selectRaw('current_step, COUNT(*) as abandoned_count')
            ->pluck('abandoned_count', 'current_step')
            ->toArray();
    }
    
    protected function getFieldCompletionRates(\Carbon\Carbon $startDate): array
    {
        $sessions = IntakeSession::where('started_at', '>=', $startDate)
            ->whereNotNull('completed_at')
            ->get();
            
        $fieldStats = [];
        
        foreach ($sessions as $session) {
            foreach ($session->form_data as $field => $value) {
                if (!isset($fieldStats[$field])) {
                    $fieldStats[$field] = ['completed' => 0, 'total' => 0];
                }
                
                $fieldStats[$field]['total']++;
                
                if (!empty($value)) {
                    $fieldStats[$field]['completed']++;
                }
            }
        }
        
        // Calculate completion rates
        foreach ($fieldStats as $field => &$stats) {
            $stats['completion_rate'] = $stats['total'] > 0 
                ? ($stats['completed'] / $stats['total']) * 100 
                : 0;
        }
        
        return $fieldStats;
    }
    
    protected function getTemplateUsage(\Carbon\Carbon $startDate): array
    {
        return Project::where('created_at', '>=', $startDate)
            ->where('creation_method', 'wizard')
            ->join('project_templates', 'projects.template_id', '=', 'project_templates.id')
            ->groupBy('project_templates.id', 'project_templates.name')
            ->selectRaw('project_templates.name, COUNT(*) as usage_count')
            ->pluck('usage_count', 'name')
            ->toArray();
    }
    
    public function identifyOptimizationOpportunities(): array
    {
        $metrics = $this->getIntakeMetrics();
        $opportunities = [];
        
        // Low completion rate
        if ($metrics['completion_rate'] < 60) {
            $opportunities[] = [
                'type' => 'completion_rate',
                'priority' => 'high',
                'issue' => 'Low intake completion rate',
                'suggestion' => 'Review required fields and consider reducing friction in early steps',
                'current_value' => $metrics['completion_rate'],
                'target_value' => 75,
            ];
        }
        
        // High abandonment on specific step
        foreach ($metrics['abandonment_by_step'] as $step => $count) {
            if ($count > 10) { // Arbitrary threshold
                $opportunities[] = [
                    'type' => 'step_abandonment',
                    'priority' => 'medium',
                    'issue' => "High abandonment on step {$step}",
                    'suggestion' => "Review step {$step} complexity and field requirements",
                    'current_value' => $count,
                    'step' => $step,
                ];
            }
        }
        
        // Low field completion rates
        foreach ($metrics['field_completion_rates'] as $field => $stats) {
            if ($stats['completion_rate'] < 30 && $stats['total'] > 5) {
                $opportunities[] = [
                    'type' => 'field_completion',
                    'priority' => 'low',
                    'issue' => "Low completion rate for field '{$field}'",
                    'suggestion' => "Consider making field optional or improving help text",
                    'current_value' => $stats['completion_rate'],
                    'field' => $field,
                ];
            }
        }
        
        return $opportunities;
    }
}
```

## Testing Strategy

### Feature Tests

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ProjectType;
use App\Services\IntakeWizardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntakeWizardTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_starts_intake_session()
    {
        $user = User::factory()->create();
        $service = app(IntakeWizardService::class);
        
        $session = $service->startIntakeSession($user);
        
        $this->assertDatabaseHas('intake_sessions', [
            'user_id' => $user->id,
            'current_step' => 1,
            'session_id' => $session->session_id,
        ]);
    }
    
    public function test_updates_session_data()
    {
        $user = User::factory()->create();
        $service = app(IntakeWizardService::class);
        
        $session = $service->startIntakeSession($user);
        
        $formData = [
            'title' => 'Test Project',
            'type_id' => ProjectType::factory()->create()->id,
            'budget_range' => '500_1000',
        ];
        
        $updatedSession = $service->updateSessionData(
            $session->session_id,
            $formData,
            2
        );
        
        $this->assertEquals(2, $updatedSession->current_step);
        $this->assertEquals('Test Project', $updatedSession->form_data['title']);
    }
    
    public function test_creates_project_from_complete_intake()
    {
        $user = User::factory()->create();
        $projectType = ProjectType::factory()->create();
        $service = app(IntakeWizardService::class);
        
        $session = $service->startIntakeSession($user);
        
        $completeData = [
            'title' => 'Complete Project',
            'type_id' => $projectType->id,
            'budget_range' => '1000_2500',
            'deadline' => now()->addWeeks(2)->format('Y-m-d'),
            'description' => 'Test project description',
            'auto_setup_maildrop' => true,
            'enable_auto_versioning' => true,
        ];
        
        $service->updateSessionData($session->session_id, $completeData);
        
        $project = $service->completeIntakeAndCreateProject($session->session_id);
        
        $this->assertDatabaseHas('projects', [
            'title' => 'Complete Project',
            'user_id' => $user->id,
            'project_type_id' => $projectType->id,
            'creation_method' => 'wizard',
        ]);
        
        $this->assertNotNull($session->fresh()->completed_at);
        $this->assertEquals($project->id, $session->fresh()->created_project_id);
    }
    
    public function test_validates_required_fields()
    {
        $user = User::factory()->create();
        $service = app(IntakeWizardService::class);
        
        $session = $service->startIntakeSession($user);
        
        // Missing required fields
        $incompleteData = [
            'title' => 'Test Project',
            // Missing type_id, budget_range, deadline
        ];
        
        $service->updateSessionData($session->session_id, $incompleteData);
        
        $this->expectException(\InvalidArgumentException::class);
        $service->completeIntakeAndCreateProject($session->session_id);
    }
}
```

### Livewire Component Tests

```php
<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Project\IntakeWizard;
use App\Models\User;
use App\Models\ProjectType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class IntakeWizardTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_renders_intake_wizard()
    {
        $user = User::factory()->create();
        ProjectType::factory()->create(['name' => 'Mixing & Mastering']);
        
        $this->actingAs($user);
        
        Livewire::test(IntakeWizard::class)
            ->assertStatus(200)
            ->assertSee('Project Basics')
            ->assertSee('Essential Project Information');
    }
    
    public function test_progresses_through_steps()
    {
        $user = User::factory()->create();
        $projectType = ProjectType::factory()->create();
        
        $this->actingAs($user);
        
        Livewire::test(IntakeWizard::class)
            ->set('project.title', 'Test Project')
            ->set('project.type_id', $projectType->id)
            ->set('project.budget_range', '500_1000')
            ->set('project.deadline', now()->addWeeks(2)->format('Y-m-d'))
            ->call('nextStep')
            ->assertSet('currentStep', 2)
            ->assertSee('Project Details');
    }
    
    public function test_validates_required_fields()
    {
        $user = User::factory()->create();
        
        $this->actingAs($user);
        
        Livewire::test(IntakeWizard::class)
            ->call('nextStep')
            ->assertHasErrors(['project.title', 'project.type_id']);
    }
    
    public function test_creates_project_successfully()
    {
        $user = User::factory()->create();
        $projectType = ProjectType::factory()->create();
        
        $this->actingAs($user);
        
        Livewire::test(IntakeWizard::class)
            ->set('project', [
                'title' => 'Test Project',
                'type_id' => $projectType->id,
                'budget_range' => '500_1000',
                'deadline' => now()->addWeeks(2)->format('Y-m-d'),
                'description' => 'Test description',
            ])
            ->call('createProject')
            ->assertRedirect();
            
        $this->assertDatabaseHas('projects', [
            'title' => 'Test Project',
            'user_id' => $user->id,
        ]);
    }
}
```

## Implementation Steps

### Phase 1: Core Wizard Infrastructure (Week 1)
1. Create database migrations for intake forms, sessions, and templates
2. Implement basic `IntakeWizardService` with session management
3. Create intake form field management system
4. Set up wizard step progression logic

### Phase 2: Form Builder & Field Management (Week 2)
1. Build dynamic form field rendering system
2. Implement field priority and step assignment
3. Create admin interface for form field management
4. Add field validation and conditional logic

### Phase 3: Wizard UI Implementation (Week 3)
1. Create progressive intake wizard Livewire component
2. Implement step-by-step navigation with validation
3. Add template loading and suggestion system
4. Style with Flux UI following UX guidelines

### Phase 4: Automation & Integration (Week 4)
1. Implement project automation based on intake data
2. Integrate with existing maildrop and versioning services
3. Add client invitation and notification systems
4. Create revision policy automation

### Phase 5: Analytics & Optimization (Week 5)
1. Build intake analytics and monitoring dashboard
2. Implement abandonment tracking and optimization suggestions
3. Add A/B testing capability for different form configurations
4. Create intake performance reporting

## Business Benefits

### For Users
- Faster project creation with guided assistance
- Reduced cognitive load with progressive disclosure
- Better project outcomes through comprehensive information gathering
- Professional setup automation saves time

### For Platform
- Higher project completion rates through better initial setup
- Improved data quality for better matching
- Reduced support burden through clearer expectations
- Analytics for continuous form optimization

This implementation creates a smooth, intelligent intake process that gets users to successful project creation quickly while ensuring all necessary information is captured for optimal collaboration outcomes.