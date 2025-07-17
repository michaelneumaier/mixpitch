# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Essential Commands

### Development Setup
```bash
# Standard Laravel setup
composer install && npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run dev
php artisan serve
# Uses Laravel Valet; No need to serve
```


### Testing
```bash
# Run all tests
php artisan test

# Run specific test suites
php artisan test --filter=FileUploadSetting  # Upload settings tests
php artisan test tests/Unit/
php artisan test tests/Feature/

# Run single test
php artisan test --filter="test_method_name"
```

### Code Quality
```bash
# PHP code formatting
./vendor/bin/pint

# Clear caches when needed
php artisan config:clear && php artisan route:clear && php artisan view:clear
```

### File Upload Testing
```bash
# Test upload functionality specifically
php artisan test tests/Feature/FileUploadSettingsIntegrationTest.php
php artisan test tests/Feature/UploadValidationMiddlewareTest.php
```

## Architecture Overview

### Core Platform Concept
MixPitch is a collaborative music platform connecting musicians with audio professionals. Musicians upload projects, professionals submit pitches with audio files, and the platform manages the entire workflow from submission to payment.

### Key Architectural Patterns

**Service Layer Architecture**: Business logic is centralized in service classes rather than controllers or models:
- `FileManagementService`: Handles all file operations, validation, and storage
- `ProjectManagementService`: Project lifecycle and business rules
- `PitchWorkflowService`: Pitch state transitions and approvals
- `AudioProcessingService`: Audio file processing and waveform generation

**State Machine Workflows**: Critical business processes use sophisticated state machines with workflow-specific transitions and business rules

**Policy-Based Authorization**: Granular permissions using Laravel policies:
- `ProjectFilePolicy`: Controls file access based on user roles and project state
- `PitchFilePolicy`: Manages pitch file permissions with watermarking logic
- Resource-based authorization throughout

### Project Workflow Types & State Machine Architecture

MixPitch implements **four distinct workflow types**, each with unique pitch submission patterns, state transitions, and business rules:

#### **1. Standard Workflow** (`WORKFLOW_TYPE_STANDARD`)
**Purpose**: Open marketplace where multiple producers can submit pitches for project consideration.

**State Flow**:
```
PENDING → IN_PROGRESS → READY_FOR_REVIEW → APPROVED → COMPLETED
   ↓           ↓              ↓
DENIED    DENIED    REVISIONS_REQUESTED ↺
```

**Key Characteristics**:
- Open to all producers, multiple submissions allowed
- Requires project owner approval at each stage
- Supports revision cycles and feedback
- Payment occurs after completion
- Payout hold period: 1 day

#### **2. Contest Workflow** (`WORKFLOW_TYPE_CONTEST`)
**Purpose**: Competition-based projects with prizes and judging.

**State Flow**:
```
CONTEST_ENTRY → CONTEST_WINNER
              → CONTEST_RUNNER_UP  
              → CONTEST_NOT_SELECTED
```

**Key Characteristics**:
- Immediate submission as contest entry (no initial approval)
- Deadline enforcement with automatic closure
- Prize-based compensation system
- No revision cycles (one submission only)
- Winner selection process with automatic status updates
- Payout hold period: 0 days (immediate)

#### **3. Direct Hire Workflow** (`WORKFLOW_TYPE_DIRECT_HIRE`)
**Purpose**: Private collaboration with a pre-selected producer.

**State Flow**: Same as Standard but restricted to one producer

**Key Characteristics**:
- One-to-one collaboration via `target_producer_id`
- Automatic pitch creation when project is created
- Private workflow (not publicly visible)
- Established producer relationship required

#### **4. Client Management Workflow** (`WORKFLOW_TYPE_CLIENT_MANAGEMENT`)
**Purpose**: Professional client service with external client approval via portal.

**State Flow**:
```
READY_FOR_REVIEW → CLIENT_REVISIONS_REQUESTED ↺
                → COMPLETED (client approval, skips APPROVED)
```

**Key Characteristics**:
- External client involvement via signed URLs (7-day expiry)
- No client account required
- Client portal for approval/revision requests
- Direct completion after client approval
- Producer gets paid immediately after client approval
- Payout hold period: 0 days

### Database Architecture

**Core Entities and Relationships**:
```
User (musicians & producers)
├── Projects (musician uploads with workflow_type)
│   ├── ProjectFiles (original audio files)
│   ├── target_producer_id (for direct hire)
│   ├── client_email/client_name (for client management)
│   └── Pitches (producer submissions)
│       ├── PitchFiles (producer audio with waveforms)
│       ├── PitchSnapshots (version-controlled submissions)
│       └── PitchEvents (audit trail of status changes)
├── Transactions (payments & payouts)
└── Subscriptions (Stripe billing)
```

**State Management Tables**:
- `PitchSnapshot`: Version control for submissions with metadata
- `PitchEvent`: Audit trail with event types (status_change, client_comment, etc.)
- Workflow-specific state validations enforced by `PitchWorkflowService`

**File Upload System**: Multi-layered approach with context-aware validation:
- `FileUploadSetting`: Configurable limits per context (global/projects/pitches/client_portals)
- `ValidateUploadSettings` middleware: Centralized validation before controller logic
- S3/Cloudflare R2 integration with multipart uploads via Uppy.js

### Frontend Architecture

**Livewire-First Approach**: Most interactivity uses Livewire v3 rather than API calls:
- `CreateProject`: Multi-step project creation with file uploads
- `UppyFileUploader`: Large file upload with progress tracking and resume capability
- `AudioPlayer`: Custom audio playback with waveform visualization
- `ManageProject`: Real-time project management interface

**JavaScript Integration**: Alpine.js for lightweight interactions, custom modules for complex features:
- `uppy-config.js`: Dynamic upload configuration from backend settings
- `adaptive-chunk-manager.js`: Smart chunking based on connection quality

## Critical Implementation Details

### File Upload System
The platform has a sophisticated file upload system with context-aware validation:

**Settings Hierarchy**: `FileUploadSetting` model provides cascading configuration:
- Global defaults → Context-specific overrides (projects/pitches/client_portals)
- Real-time API endpoints at `/api/upload-settings/{context}` for frontend consumption
- Automatic cache invalidation when settings change

**Validation Flow**:
1. `ValidateUploadSettings` middleware intercepts upload requests
2. Context detected from route patterns, model types, or explicit parameters
3. File size, chunk size, and file type validation against dynamic settings
4. Settings injected into request for controller use

### Audio Processing Workflow
**File Processing Pipeline**:
1. Large files uploaded via Uppy.js multipart to S3/R2
2. Background jobs trigger audio processing (Lambda functions)
3. Waveform generation and audio optimization
4. Metadata extraction and storage in `PitchFile` model
5. Real-time progress updates via Livewire

### Payment Integration
**Stripe Connect Architecture**:
- Musicians (clients) pay for projects via standard Stripe
- Producers receive payouts via Stripe Connect
- Platform fee handling with configurable percentages
- Payout hold periods for security

### Testing Strategy

**Comprehensive Test Coverage**:
- **Unit Tests**: Service classes, models, and utilities
- **Feature Tests**: Full workflow testing including file uploads
- **Integration Tests**: Cross-service interactions and external APIs
- **Livewire Tests**: Component behavior and state management

**File Upload Testing Pattern**:
```php
// Test settings validation
FileUploadSetting::updateSettings([
    FileUploadSetting::MAX_FILE_SIZE_MB => 150,
], FileUploadSetting::CONTEXT_PROJECTS);

$largeFile = UploadedFile::fake()->create('large.mp3', 200 * 1024); // 200MB
$response = $this->postJson('/project/upload-file', ['file' => $largeFile]);
$response->assertStatus(422); // Validation should fail
```

## Development Guidelines

### File Upload Development
When working with file uploads:
- Always check `FileUploadSetting` for current limits rather than hardcoding
- Use the `ValidateUploadSettings` middleware for new upload endpoints
- Test across all contexts (global, projects, pitches, client_portals)
- Verify both validation and actual functionality work as expected

### Service Layer Usage
Business logic belongs in services, not controllers:
- Controllers should be thin, primarily handling HTTP concerns
- Services handle business rules, validation, and complex operations
- Use dependency injection for services in controllers
- Services should be testable independently of HTTP layer

### Policy Implementation
For authorization logic:
- Resource-based permissions use Policy classes
- Policies should handle complex authorization rules
- Controllers use `$this->authorize()` or `Gate::allows()`
- Consider different user roles and project states

### Livewire Component Development
- Components should handle their own state management
- Use Livewire events for cross-component communication
- File uploads in Livewire should integrate with the upload settings system
- Test components using Livewire's testing utilities

## Environment Configuration

**Required Services**:
- MySQL database for primary data
- AWS S3 or Cloudflare R2 for file storage
- Stripe for payment processing
- Google OAuth for social authentication
- Redis for caching and queues (production)

**Key Configuration**:
- File upload settings configurable via Filament admin panel
- Queue workers required for background audio processing
- Storage permissions must allow public access for audio streaming

## Common Patterns

### Adding New Upload Contexts
1. Add context constant to `FileUploadSetting` model
2. Update validation rules and defaults for the context
3. Add context detection logic to `ValidateUploadSettings` middleware
4. Update API endpoints to handle the new context
5. Create comprehensive tests for the new context

### Working with Project Workflows

#### **Understanding Workflow Context**
Always determine workflow type when working with projects/pitches:
```php
// Check workflow type
if ($project->isStandard()) { /* standard logic */ }
if ($project->isContest()) { /* contest logic */ }
if ($project->isDirectHire()) { /* direct hire logic */ }
if ($project->isClientManagement()) { /* client management logic */ }

// Workflow-specific guards in services
if (!$pitch->project->isStandard()) {
    throw new UnauthorizedActionException('Action not applicable for this workflow type');
}
```

#### **State Transition Patterns**
Use `PitchWorkflowService` for all state changes:
```php
// Standard workflow progression
$pitchWorkflowService->approveInitialPitch($pitch, $approvingUser);
$pitchWorkflowService->submitPitchForReview($pitch, $submittingUser, $files);
$pitchWorkflowService->approveSubmittedPitch($pitch, $approvingUser);

// Contest-specific methods
$pitchWorkflowService->selectContestWinner($pitch, $judgingUser);
$pitchWorkflowService->selectContestRunnerUp($pitch, $judgingUser);

// Client management methods
$pitchWorkflowService->clientApprovePitch($pitch, $clientIdentifier);
$pitchWorkflowService->clientRequestRevisions($pitch, $feedback, $clientIdentifier);
```

#### **Event and Notification Patterns**
Each workflow has specific notification patterns:
```php
// Standard workflow notifications
- notifyPitchSubmitted() / notifyPitchApproved()
- notifyPitchReadyForReview() / notifyPitchSubmissionApproved()

// Contest workflow notifications  
- notifyContestWinnerSelected() / notifyContestRunnerUpSelected()
- notifyContestEntryNotSelected()

// Client management notifications
- notifyClientReviewReady() / notifyProducerClientApprovedAndCompleted()
- notifyProducerClientRevisionsRequested()
```

#### **Workflow-Specific UI Considerations**
Frontend components must adapt to workflow types:
- Contest: Show deadline warnings, immediate file access
- Direct Hire: Display "specifically chosen" messaging  
- Client Management: Portal-specific interface elements
- Standard: Traditional approval workflow UI with progress indicators

### State Machine Integration
When adding workflow features:
- Always use `PitchWorkflowService` for state transitions
- Add workflow-specific guards to prevent invalid actions
- Update notification patterns for all affected workflows
- Create comprehensive tests covering all workflow types
- Use `PitchSnapshot` for version control and `PitchEvent` for audit trails

This codebase prioritizes maintainability through clear separation of concerns, comprehensive testing, and consistent architectural patterns. When making changes, follow the established patterns and ensure thorough test coverage.