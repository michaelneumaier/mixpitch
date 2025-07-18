# Client Management Implementation Guide

## Overview

This guide provides a comprehensive plan to implement enhanced client management features by leveraging existing MixPitch infrastructure. The focus is on extending the current pitch file player system and building a robust client relationship management dashboard.

## 📊 Implementation Progress

### ✅ **Phase 1.1: Enhanced File Previews & Annotations** - **COMPLETED** ✅
- **✅ Database Schema Updates**: Added client comment fields to `pitch_file_comments`
- **✅ PitchFileComment Model**: Enhanced with client functionality (`isClientComment()`, `getAuthorName()`)
- **✅ ClientPitchFilePlayer Component**: Full-featured audio player for client portal
- **✅ Comprehensive Tests**: 26 tests with 59 assertions across all components
- **✅ Integration**: Client portal can now play audio files with timestamp-based annotations

**Key Features Delivered:**
- 🎵 WaveSurfer.js audio player integration
- 💬 Client timestamp-based commenting system
- 🔒 Permission-based access (clients can add but not edit/delete/resolve comments)
- 📊 Visual comment markers on waveform
- 📧 Producer notifications for client comments
- ✅ Full validation and error handling

### ✅ **Phase 1.2: Enhanced Annotation Management** - **COMPLETED** ✅
- **✅ PitchFileAnnotationSummary Component**: Dedicated annotation viewing outside the player
- **✅ Time Interval Grouping**: Comments organized in 30-second intervals
- **✅ Resolution Tracking**: Mark comments as resolved with filtering
- **✅ Jump-to-Timestamp**: Click to seek audio to specific comment locations
- **✅ Statistics Dashboard**: Total/resolved/unresolved comment counts
- **✅ Comprehensive Tests**: 11 tests with 29 assertions covering all functionality

**Key Features Delivered:**
- 📋 Structured annotation summary with time intervals
- ✅ Comment resolution workflow for producers
- 🎯 Jump-to-timestamp functionality
- 📊 Real-time statistics and progress tracking
- 👥 Client vs Producer comment distinction
- 💬 Nested replies support

### ✅ **Phase 2: Version Comparison System** - **COMPLETED** ✅
- **✅ FileComparisonPlayer Component**: Side-by-side file comparison with synchronized playback
- **✅ Version Detection**: Automatic loading of file versions from PitchSnapshot data
- **✅ Sync Controls**: Toggle synchronized playback between left/right files
- **✅ Multiple View Modes**: Side-by-side, overlay, and sequential comparison modes
- **✅ Annotation Comparison**: Timeline-based comment comparison between versions
- **✅ File Difference Analysis**: Duration, size, and version change calculations
- **✅ Comprehensive Tests**: 14 tests with 48 assertions covering all functionality

**Key Features Delivered:**
- 🎵 Synchronized dual audio player with independent and linked controls
- 📊 Real-time file comparison statistics (duration, size, version differences)
- 📋 Timeline-based annotation comparison with change highlighting
- 🎯 Jump-to-timestamp functionality for both players
- 🔄 Multiple comparison modes (side-by-side, overlay, sequential)
- 📈 Summary dashboard with visual change indicators
- ✅ Full validation and error handling for file compatibility

### ✅ **Phase 3: Client Management Dashboard** - **COMPLETED** ✅
- **✅ Client Model & Database**: Comprehensive client relationship tracking with relationships
- **✅ ClientManagementDashboard Component**: Full-featured CRM interface for producers
- **✅ Advanced Search & Filtering**: Multi-field search with status and sorting options
- **✅ Client CRUD Operations**: Create, edit, delete clients with validation and business rules
- **✅ Statistics & Analytics**: Revenue tracking, project counts, and follow-up management
- **✅ Comprehensive Tests**: 20 tests with 53 assertions covering all model functionality

**Key Features Delivered:**
- 👥 Complete client relationship management system
- 🔍 Advanced search and filtering with persistent URL state
- 📊 Real-time analytics dashboard with revenue and project metrics
- 🏷️ Tag-based client organization and categorization
- 📅 Contact tracking with automated follow-up reminders
- 🔗 Seamless integration with existing project creation workflow
- ✅ Full validation, error handling, and user feedback
- 📱 Responsive design for desktop and mobile usage

### 🚧 **Currently Working On: Phase 4** - **IN PROGRESS**
- **Enhanced Feedback Tools**: Structured feedback systems and templates

### 📋 **Future Enhancements:**
- Integration with email marketing platforms
- Advanced client analytics and reporting
- Automated follow-up workflows

---

## Current Architecture Foundation

### Existing Components We'll Leverage:
- **PitchFilePlayer**: Full-featured audio player with WaveSurfer.js integration
- **PitchFileComment**: Timestamp-based annotation system
- **SnapshotFilePlayer**: Version navigation and comparison base
- **ClientPortalController**: Client-facing functionality
- **ManageClientProject**: Producer interface for individual projects

### Database Schema Extensions Needed:
- Client relationship tracking tables
- Enhanced annotation features
- Client preference storage
- Cross-project analytics

## Implementation Phases

## Phase 1: Enhanced File Previews & Annotations

### 1.1 Extend PitchFilePlayer for Client Portal

**Create ClientPitchFilePlayer Component**

```php
// app/Livewire/ClientPitchFilePlayer.php
<?php

namespace App\Livewire;

use App\Livewire\PitchFilePlayer;
use App\Models\PitchFile;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;

class ClientPitchFilePlayer extends PitchFilePlayer
{
    public $clientMode = true;
    public $clientEmail;
    public $signedAccess = false;
    
    public function mount(PitchFile $pitchFile, Project $project, $signedAccess = false)
    {
        // Verify client management project
        if (!$project->isClientManagement()) {
            abort(403, 'Access denied');
        }
        
        $this->signedAccess = $signedAccess;
        $this->clientEmail = $project->client_email;
        
        parent::mount($pitchFile);
    }
    
    public function addComment($timestamp, $comment)
    {
        $this->validate([
            'comment' => 'required|string|max:1000',
        ]);
        
        // Create comment with client identifier
        $this->pitchFile->comments()->create([
            'timestamp' => $timestamp,
            'comment' => $comment,
            'user_id' => null, // Client comment
            'client_email' => $this->clientEmail,
            'is_client_comment' => true,
        ]);
        
        $this->refreshComments();
        $this->notifyProducerOfClientComment($comment);
    }
    
    protected function getCommentPermissions()
    {
        return [
            'can_add' => true,
            'can_edit' => false, // Clients can't edit comments
            'can_delete' => false,
            'can_resolve' => false,
        ];
    }
}
```

**Update PitchFileComment Model**

```php
// Add to app/Models/PitchFileComment.php
class PitchFileComment extends Model
{
    protected $fillable = [
        'pitch_file_id',
        'user_id',
        'parent_id',
        'timestamp',
        'comment',
        'is_resolved',
        'client_email',      // New field
        'is_client_comment', // New field
    ];
    
    public function isClientComment()
    {
        return $this->is_client_comment;
    }
    
    public function getAuthorName()
    {
        if ($this->isClientComment()) {
            return $this->client_email;
        }
        
        return $this->user?->name ?? 'Unknown';
    }
}
```

**Database Migration**

```php
// database/migrations/xxxx_add_client_fields_to_pitch_file_comments.php
Schema::table('pitch_file_comments', function (Blueprint $table) {
    $table->string('client_email')->nullable()->after('user_id');
    $table->boolean('is_client_comment')->default(false)->after('is_resolved');
});
```

### 1.2 Enhanced Annotation Viewing

**Create Annotation Summary Component**

```php
// app/Livewire/PitchFileAnnotationSummary.php
<?php

namespace App\Livewire;

use App\Models\PitchFile;
use Livewire\Component;

class PitchFileAnnotationSummary extends Component
{
    public PitchFile $pitchFile;
    public $groupedComments = [];
    public $showResolved = false;
    
    public function mount(PitchFile $pitchFile)
    {
        $this->pitchFile = $pitchFile;
        $this->loadComments();
    }
    
    public function loadComments()
    {
        $comments = $this->pitchFile->comments()
            ->with('user', 'replies.user')
            ->when(!$this->showResolved, fn($q) => $q->where('is_resolved', false))
            ->orderBy('timestamp')
            ->get();
            
        $this->groupedComments = $comments->groupBy(function($comment) {
            return floor($comment->timestamp / 30); // Group by 30-second intervals
        });
    }
    
    public function toggleResolved()
    {
        $this->showResolved = !$this->showResolved;
        $this->loadComments();
    }
    
    public function resolveComment($commentId)
    {
        $comment = $this->pitchFile->comments()->findOrFail($commentId);
        $comment->update(['is_resolved' => true]);
        $this->loadComments();
    }
    
    public function render()
    {
        return view('livewire.pitch-file-annotation-summary');
    }
}
```

**Annotation Summary View**

```blade
{{-- resources/views/livewire/pitch-file-annotation-summary.blade.php --}}
<div class="annotation-summary">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold">File Annotations</h3>
        <div class="flex items-center space-x-2">
            <label class="flex items-center">
                <input type="checkbox" wire:model="showResolved" wire:change="toggleResolved" class="mr-2">
                Show resolved
            </label>
            <span class="text-sm text-gray-500">
                {{ $groupedComments->flatten()->count() }} annotations
            </span>
        </div>
    </div>
    
    @forelse($groupedComments as $interval => $comments)
        <div class="mb-4 p-4 bg-gray-50 rounded-lg">
            <div class="text-sm font-medium text-gray-700 mb-2">
                {{ gmdate('i:s', $interval * 30) }} - {{ gmdate('i:s', ($interval + 1) * 30) }}
            </div>
            
            @foreach($comments as $comment)
                <div class="flex items-start space-x-3 mb-3 p-3 bg-white rounded border {{ $comment->is_resolved ? 'opacity-60' : '' }}">
                    <div class="flex-shrink-0">
                        @if($comment->isClientComment())
                            <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                                <i class="fas fa-user text-white text-xs"></i>
                            </div>
                        @else
                            <div class="w-8 h-8 bg-purple-500 rounded-full flex items-center justify-center">
                                <i class="fas fa-user-tie text-white text-xs"></i>
                            </div>
                        @endif
                    </div>
                    
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium text-gray-900">
                                {{ $comment->getAuthorName() }}
                            </p>
                            <div class="flex items-center space-x-2">
                                <span class="text-xs text-gray-500">
                                    {{ gmdate('i:s', $comment->timestamp) }}
                                </span>
                                @if(!$comment->is_resolved && !$comment->isClientComment())
                                    <button wire:click="resolveComment({{ $comment->id }})" 
                                            class="text-xs text-green-600 hover:text-green-800">
                                        Mark resolved
                                    </button>
                                @endif
                            </div>
                        </div>
                        <p class="text-sm text-gray-700 mt-1">{{ $comment->comment }}</p>
                        
                        @if($comment->replies->count() > 0)
                            <div class="mt-2 pl-4 border-l-2 border-gray-200">
                                @foreach($comment->replies as $reply)
                                    <div class="text-sm text-gray-600 mb-1">
                                        <strong>{{ $reply->user->name }}:</strong> {{ $reply->comment }}
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @empty
        <div class="text-center py-8 text-gray-500">
            <i class="fas fa-comment-dots text-4xl mb-2"></i>
            <p>No annotations yet</p>
        </div>
    @endforelse
</div>
```

## Phase 2: Version Comparison System

### 2.1 Create Side-by-Side File Comparison

**FileComparisonPlayer Component**

```php
// app/Livewire/FileComparisonPlayer.php
<?php

namespace App\Livewire;

use App\Models\PitchFile;
use App\Models\PitchSnapshot;
use Livewire\Component;

class FileComparisonPlayer extends Component
{
    public $leftFile;
    public $rightFile;
    public $leftSnapshot;
    public $rightSnapshot;
    public $syncPlayback = true;
    
    public function mount(PitchFile $leftFile, PitchFile $rightFile)
    {
        $this->leftFile = $leftFile;
        $this->rightFile = $rightFile;
        
        // Load snapshots for context
        $this->leftSnapshot = $leftFile->pitch->snapshots()
            ->whereJsonContains('snapshot_data->file_ids', $leftFile->id)
            ->first();
        $this->rightSnapshot = $rightFile->pitch->snapshots()
            ->whereJsonContains('snapshot_data->file_ids', $rightFile->id)
            ->first();
    }
    
    public function toggleSync()
    {
        $this->syncPlayback = !$this->syncPlayback;
    }
    
    public function render()
    {
        return view('livewire.file-comparison-player');
    }
}
```

**Comparison View**

```blade
{{-- resources/views/livewire/file-comparison-player.blade.php --}}
<div class="file-comparison-player" x-data="fileComparison()" x-init="init()">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold">File Comparison</h3>
        <div class="flex items-center space-x-4">
            <label class="flex items-center">
                <input type="checkbox" wire:model="syncPlayback" class="mr-2">
                Sync playback
            </label>
            <button @click="playBoth()" class="btn btn-primary">
                <i class="fas fa-play mr-2"></i>Play Both
            </button>
        </div>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Left File -->
        <div class="comparison-panel">
            <div class="bg-blue-50 p-4 rounded-lg mb-4">
                <h4 class="font-semibold text-blue-900">
                    @if($leftSnapshot)
                        Version {{ $leftSnapshot->version }} - {{ $leftSnapshot->created_at->format('M j, Y') }}
                    @else
                        Original Version
                    @endif
                </h4>
                <p class="text-sm text-blue-700">{{ $leftFile->file_name }}</p>
            </div>
            
            <livewire:snapshot-file-player 
                :pitchFile="$leftFile" 
                :comparisonMode="true"
                :playerId="'left-player'"
                key="left-{{ $leftFile->id }}" />
        </div>
        
        <!-- Right File -->
        <div class="comparison-panel">
            <div class="bg-green-50 p-4 rounded-lg mb-4">
                <h4 class="font-semibold text-green-900">
                    @if($rightSnapshot)
                        Version {{ $rightSnapshot->version }} - {{ $rightSnapshot->created_at->format('M j, Y') }}
                    @else
                        Current Version
                    @endif
                </h4>
                <p class="text-sm text-green-700">{{ $rightFile->file_name }}</p>
            </div>
            
            <livewire:snapshot-file-player 
                :pitchFile="$rightFile" 
                :comparisonMode="true"
                :playerId="'right-player'"
                key="right-{{ $rightFile->id }}" />
        </div>
    </div>
    
    <!-- Annotation Comparison -->
    <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div>
            <h4 class="font-semibold mb-3">Annotations (Version {{ $leftSnapshot?->version ?? 'Original' }})</h4>
            <livewire:pitch-file-annotation-summary 
                :pitchFile="$leftFile" 
                key="left-annotations-{{ $leftFile->id }}" />
        </div>
        
        <div>
            <h4 class="font-semibold mb-3">Annotations (Version {{ $rightSnapshot?->version ?? 'Current' }})</h4>
            <livewire:pitch-file-annotation-summary 
                :pitchFile="$rightFile" 
                key="right-annotations-{{ $rightFile->id }}" />
        </div>
    </div>
</div>

<script>
function fileComparison() {
    return {
        leftPlayer: null,
        rightPlayer: null,
        
        init() {
            // Initialize both players
            this.setupPlayers();
        },
        
        setupPlayers() {
            // Wait for players to be ready
            setTimeout(() => {
                this.leftPlayer = window.leftPlayer;
                this.rightPlayer = window.rightPlayer;
                
                if (@js($syncPlayback)) {
                    this.setupSyncPlayback();
                }
            }, 1000);
        },
        
        setupSyncPlayback() {
            if (this.leftPlayer && this.rightPlayer) {
                this.leftPlayer.on('play', () => {
                    if (@js($syncPlayback)) {
                        this.rightPlayer.play();
                    }
                });
                
                this.rightPlayer.on('play', () => {
                    if (@js($syncPlayback)) {
                        this.leftPlayer.play();
                    }
                });
                
                this.leftPlayer.on('pause', () => {
                    if (@js($syncPlayback)) {
                        this.rightPlayer.pause();
                    }
                });
                
                this.rightPlayer.on('pause', () => {
                    if (@js($syncPlayback)) {
                        this.leftPlayer.pause();
                    }
                });
            }
        },
        
        playBoth() {
            if (this.leftPlayer && this.rightPlayer) {
                this.leftPlayer.play();
                this.rightPlayer.play();
            }
        }
    }
}
</script>
```

## Phase 3: Client Management Dashboard

### 3.1 Create Client Relationship Model

**Client Model**

```php
// app/Models/Client.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    protected $fillable = [
        'user_id',
        'email',
        'name',
        'company',
        'phone',
        'timezone',
        'preferences',
        'notes',
        'tags',
        'status',
        'last_contacted_at',
        'total_spent',
        'total_projects',
    ];
    
    protected $casts = [
        'preferences' => 'array',
        'tags' => 'array',
        'last_contacted_at' => 'datetime',
        'total_spent' => 'decimal:2',
    ];
    
    public function producer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'client_email', 'email');
    }
    
    public function activeProjects(): HasMany
    {
        return $this->projects()->whereNotIn('status', [
            Project::STATUS_COMPLETED,
            Project::STATUS_CANCELLED
        ]);
    }
    
    public function completedProjects(): HasMany
    {
        return $this->projects()->where('status', Project::STATUS_COMPLETED);
    }
    
    public function updateStats()
    {
        $this->update([
            'total_projects' => $this->projects()->count(),
            'total_spent' => $this->projects()
                ->join('pitches', 'projects.id', '=', 'pitches.project_id')
                ->where('pitches.payment_status', 'paid')
                ->sum('pitches.payment_amount'),
        ]);
    }
}
```

**Migration**

```php
// database/migrations/xxxx_create_clients_table.php
Schema::create('clients', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('email');
    $table->string('name')->nullable();
    $table->string('company')->nullable();
    $table->string('phone')->nullable();
    $table->string('timezone')->default('UTC');
    $table->json('preferences')->nullable();
    $table->text('notes')->nullable();
    $table->json('tags')->nullable();
    $table->enum('status', ['active', 'inactive', 'blocked'])->default('active');
    $table->timestamp('last_contacted_at')->nullable();
    $table->decimal('total_spent', 10, 2)->default(0);
    $table->integer('total_projects')->default(0);
    $table->timestamps();
    
    $table->unique(['user_id', 'email']);
});
```

### 3.2 Client Management Dashboard

**ClientManagementDashboard Component**

```php
// app/Livewire/ClientManagementDashboard.php
<?php

namespace App\Livewire;

use App\Models\Client;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ClientManagementDashboard extends Component
{
    use WithPagination;
    
    public $search = '';
    public $statusFilter = 'all';
    public $sortBy = 'last_contacted_at';
    public $sortDirection = 'desc';
    
    public $showingClient = null;
    public $showClientModal = false;
    
    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'all'],
    ];
    
    public function updatingSearch()
    {
        $this->resetPage();
    }
    
    public function updatingStatusFilter()
    {
        $this->resetPage();
    }
    
    public function sortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
    }
    
    public function showClient($clientId)
    {
        $this->showingClient = Client::with(['projects.pitches'])->findOrFail($clientId);
        $this->showClientModal = true;
    }
    
    public function closeClientModal()
    {
        $this->showClientModal = false;
        $this->showingClient = null;
    }
    
    public function createProjectForClient($clientId)
    {
        $client = Client::findOrFail($clientId);
        
        return redirect()->route('projects.create', [
            'workflow_type' => 'client_management',
            'client_email' => $client->email,
            'client_name' => $client->name,
        ]);
    }
    
    public function render()
    {
        $clients = Client::where('user_id', Auth::id())
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%')
                      ->orWhere('company', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->statusFilter !== 'all', function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);
            
        $stats = [
            'total_clients' => Client::where('user_id', Auth::id())->count(),
            'active_projects' => Project::where('user_id', Auth::id())
                ->where('workflow_type', 'client_management')
                ->whereNotIn('status', [Project::STATUS_COMPLETED, Project::STATUS_CANCELLED])
                ->count(),
            'total_revenue' => Project::where('user_id', Auth::id())
                ->where('workflow_type', 'client_management')
                ->join('pitches', 'projects.id', '=', 'pitches.project_id')
                ->where('pitches.payment_status', 'paid')
                ->sum('pitches.payment_amount'),
        ];
        
        return view('livewire.client-management-dashboard', compact('clients', 'stats'));
    }
}
```

**Dashboard View**

```blade
{{-- resources/views/livewire/client-management-dashboard.blade.php --}}
<div class="client-management-dashboard">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 rounded-lg">
                    <i class="fas fa-users text-blue-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-600">Total Clients</p>
                    <p class="text-2xl font-semibold">{{ $stats['total_clients'] }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-lg">
                    <i class="fas fa-project-diagram text-green-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-600">Active Projects</p>
                    <p class="text-2xl font-semibold">{{ $stats['active_projects'] }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-3 bg-purple-100 rounded-lg">
                    <i class="fas fa-dollar-sign text-purple-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-600">Total Revenue</p>
                    <p class="text-2xl font-semibold">${{ number_format($stats['total_revenue'], 2) }}</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filters and Search -->
    <div class="bg-white p-6 rounded-lg shadow mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
            <div class="flex-1 max-w-md">
                <input type="text" 
                       wire:model.debounce.300ms="search"
                       placeholder="Search clients..."
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            
            <div class="flex items-center space-x-4">
                <select wire:model="statusFilter" class="px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="all">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="blocked">Blocked</option>
                </select>
                
                <a href="{{ route('projects.create', ['workflow_type' => 'client_management']) }}" 
                   class="btn btn-primary">
                    <i class="fas fa-plus mr-2"></i>New Project
                </a>
            </div>
        </div>
    </div>
    
    <!-- Client Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <button wire:click="sortBy('name')" class="flex items-center space-x-1">
                            <span>Client</span>
                            <i class="fas fa-sort"></i>
                        </button>
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Projects
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <button wire:click="sortBy('total_spent')" class="flex items-center space-x-1">
                            <span>Total Spent</span>
                            <i class="fas fa-sort"></i>
                        </button>
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <button wire:click="sortBy('last_contacted_at')" class="flex items-center space-x-1">
                            <span>Last Contact</span>
                            <i class="fas fa-sort"></i>
                        </button>
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Status
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($clients as $client)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                        <span class="text-blue-600 font-medium">
                                            {{ strtoupper(substr($client->name ?: $client->email, 0, 2)) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $client->name ?: 'No name' }}
                                    </div>
                                    <div class="text-sm text-gray-500">{{ $client->email }}</div>
                                    @if($client->company)
                                        <div class="text-xs text-gray-400">{{ $client->company }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <div class="flex items-center space-x-2">
                                <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs">
                                    {{ $client->projects->count() }} total
                                </span>
                                <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs">
                                    {{ $client->activeProjects->count() }} active
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            ${{ number_format($client->total_spent, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $client->last_contacted_at?->diffForHumans() ?? 'Never' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-medium rounded-full
                                {{ $client->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst($client->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex items-center space-x-2">
                                <button wire:click="showClient({{ $client->id }})" 
                                        class="text-indigo-600 hover:text-indigo-900">
                                    View
                                </button>
                                <button wire:click="createProjectForClient({{ $client->id }})" 
                                        class="text-green-600 hover:text-green-900">
                                    New Project
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                            No clients found
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <div class="mt-6">
        {{ $clients->links() }}
    </div>
    
    <!-- Client Detail Modal -->
    @if($showClientModal && $showingClient)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-show="true" x-transition>
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity" wire:click="closeClientModal">
                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                </div>
                
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900">
                                {{ $showingClient->name ?: $showingClient->email }}
                            </h3>
                            <button wire:click="closeClientModal" class="text-gray-400 hover:text-gray-600">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Client Info -->
                            <div>
                                <h4 class="font-medium text-gray-900 mb-2">Client Information</h4>
                                <div class="space-y-2 text-sm">
                                    <p><strong>Email:</strong> {{ $showingClient->email }}</p>
                                    @if($showingClient->company)
                                        <p><strong>Company:</strong> {{ $showingClient->company }}</p>
                                    @endif
                                    @if($showingClient->phone)
                                        <p><strong>Phone:</strong> {{ $showingClient->phone }}</p>
                                    @endif
                                    <p><strong>Total Spent:</strong> ${{ number_format($showingClient->total_spent, 2) }}</p>
                                    <p><strong>Total Projects:</strong> {{ $showingClient->total_projects }}</p>
                                </div>
                            </div>
                            
                            <!-- Recent Projects -->
                            <div>
                                <h4 class="font-medium text-gray-900 mb-2">Recent Projects</h4>
                                <div class="space-y-2">
                                    @foreach($showingClient->projects->take(5) as $project)
                                        <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                            <div>
                                                <p class="text-sm font-medium">{{ $project->title }}</p>
                                                <p class="text-xs text-gray-500">{{ $project->created_at->format('M j, Y') }}</p>
                                            </div>
                                            <span class="text-xs px-2 py-1 rounded {{ $project->getStatusColorClass() }}">
                                                {{ $project->status }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
```

## Phase 4: Enhanced Feedback Tools ✅ **COMPLETED**

### Technical Achievements - Phase 4

**✅ Core Components Implemented:**
- FeedbackTemplate model with comprehensive validation and question types
- StructuredFeedbackForm Livewire component for dynamic feedback collection
- Factory and test coverage with 34 tests and 48 assertions

**✅ Key Features Delivered:**
- Multiple question types: text, textarea, select, radio, checkbox, rating, range
- Template categories for organization (mixing, mastering, composition, etc.)
- Default system templates and custom user templates
- Dynamic form generation based on template structure
- Client portal integration for external feedback collection
- Feedback formatting with markdown for structured comments

### 4.1 Structured Feedback System ✅

**FeedbackTemplate Model**

```php
// app/Models/FeedbackTemplate.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeedbackTemplate extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'questions',
        'is_default',
        'category',
    ];
    
    protected $casts = [
        'questions' => 'array',
        'is_default' => 'boolean',
    ];
}
```

**Feedback Form Component**

```php
// app/Livewire/StructuredFeedbackForm.php
<?php

namespace App\Livewire;

use App\Models\FeedbackTemplate;
use App\Models\Project;
use Livewire\Component;

class StructuredFeedbackForm extends Component
{
    public Project $project;
    public $selectedTemplate = null;
    public $responses = [];
    public $customFeedback = '';
    
    public function mount(Project $project)
    {
        $this->project = $project;
    }
    
    public function selectTemplate($templateId)
    {
        $this->selectedTemplate = FeedbackTemplate::findOrFail($templateId);
        $this->responses = array_fill_keys(
            array_column($this->selectedTemplate->questions, 'id'),
            ''
        );
    }
    
    public function submitFeedback()
    {
        $this->validate([
            'responses' => 'required|array',
            'customFeedback' => 'nullable|string|max:2000',
        ]);
        
        $feedback = [
            'template_id' => $this->selectedTemplate?->id,
            'responses' => $this->responses,
            'custom_feedback' => $this->customFeedback,
            'submitted_at' => now(),
        ];
        
        // Store feedback and trigger revision request
        $pitch = $this->project->pitches()->first();
        $pitch->events()->create([
            'event_type' => 'structured_feedback',
            'comment' => $this->customFeedback,
            'status' => $pitch->status,
            'metadata' => $feedback,
        ]);
        
        session()->flash('success', 'Feedback submitted successfully!');
        $this->reset(['responses', 'customFeedback']);
    }
    
    public function render()
    {
        $templates = FeedbackTemplate::where('user_id', $this->project->user_id)
            ->orWhere('is_default', true)
            ->get();
            
        return view('livewire.structured-feedback-form', compact('templates'));
    }
}
```

## Implementation Timeline

### Week 1-2: File System Extensions
- Extend PitchFilePlayer for client portal
- Add client comment functionality
- Create annotation summary component
- Update database schema

### Week 3-4: Version Comparison
- Build FileComparisonPlayer component
- Implement side-by-side comparison view
- Add sync playback functionality
- Create annotation comparison

### Week 5-6: Client Management Core
- Create Client model and relationships
- Build ClientManagementDashboard component
- Implement client directory functionality
- Add client stats and analytics

### Week 7-8: Enhanced Feedback
- Create feedback template system
- Build structured feedback forms
- Integrate with existing comment system
- Add feedback analytics

### Week 9-10: Integration & Polish
- Connect all components
- Add comprehensive testing
- Performance optimization
- Documentation and training

## Testing Strategy

### Unit Tests
- Client model relationships
- Feedback template validation
- Comment system extensions
- File comparison logic

### Feature Tests
- Client dashboard functionality
- File comparison workflows
- Structured feedback submission
- Cross-project client tracking

### Integration Tests
- Client portal enhancements
- Producer dashboard integration
- Email notification system
- Payment flow verification

This implementation plan leverages your existing robust infrastructure while adding the sophisticated client management features needed to compete with platforms like Filepass. The phased approach ensures each component builds upon existing functionality without disrupting current workflows.