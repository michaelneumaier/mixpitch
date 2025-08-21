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

**Development Environment utilizes SQLite; Prodution utilizes MySQL**: Always use php artisan for creating migration files

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

## UX/UI Guidelines

Please refer to the [UX Design Guidelines](./docs/ux-guidelines.md) for detailed information on:
- Core UX principles (clarity, minimal cognitive load, trust through work)
- Layout and structure guidelines (spacing, hierarchy, progressive disclosure)
- Visual design language (colors, typography, iconography)
- Interaction and motion patterns
- Content and microcopy tone
- Accessibility requirements
- Cross-platform behavior

When building UI components, follow these guidelines to maintain consistency and ensure a professional yet warm user experience.

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

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context
This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4.5
- filament/filament (FILAMENT) - v3
- laravel/framework (LARAVEL) - v10
- laravel/prompts (PROMPTS) - v0
- laravel/reverb (REVERB) - v1
- livewire/flux (FLUXUI_FREE) - v2
- livewire/flux-pro (FLUXUI_PRO) - v2
- livewire/livewire (LIVEWIRE) - v3
- laravel/pint (PINT) - v1
- pestphp/pest (PEST) - v2
- laravel-echo (ECHO) - v2
- alpinejs (ALPINEJS) - v3
- tailwindcss (TAILWINDCSS) - v4


## Conventions
- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture
- Stick to existing directory structure - don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files
- You must only create documentation files if explicitly requested by the user.


=== boost rules ===

## Laravel Boost
- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan
- Use the `list-artisan-commands` tool when you need to call an Artisan command to double check the available parameters.

## URLs
- Whenever you share a project URL with the user you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain / IP, and port.

## Tinker / Debugging
- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool
- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before any other approaches. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation specific for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- The 'search-docs' tool is perfect for all Laravel related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel-ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries - package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax
- You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit"
3. Quoted Phrases (Exact Position) - query="infinite scroll" - Words must be adjacent and in that order
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit"
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms


=== php rules ===

## PHP

- Always use curly braces for control structures, even if it has one line.

### Constructors
- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters.

### Type Declarations
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments
- Prefer PHPDoc blocks over comments. Never use comments within the code itself unless there is something _very_ complex going on.

## PHPDoc Blocks
- Add useful array shape type definitions for arrays when appropriate.

## Enums
- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.


=== filament/core rules ===

## Filament
- Filament is used by this application, check how and where to follow existing application conventions.
- Filament is a Server-Driven UI (SDUI) framework for Laravel. It allows developers to define user interfaces in PHP using structured configuration objects. It is built on top of Livewire, Alpine.js, and Tailwind CSS.
- You can use the `search-docs` tool to get information from the official Filament documentation when needed. This is very useful for Artisan command arguments, specific code examples, testing functionality, relationship management, and ensuring you're following idiomatic practices.
- Utilize static `make()` methods for consistent component initialization.

### Artisan
- You must use the Filament specific Artisan commands to create new files or components for Filament. You can find these with the `list-artisan-commands` tool, or with `php artisan` and the `--help` option.
- Inspect the required options, always pass `--no-interaction`, and valid arguments for other options when applicable.

### Filament's Core Features
- Actions: Handle doing something within the application, often with a button or link. Actions encapsulate the UI, the interactive modal window, and the logic that should be executed when the modal window is submitted. They can be used anywhere in the UI and are commonly used to perform one-time actions like deleting a record, sending an email, or updating data in the database based on modal form input.
- Forms: Dynamic forms rendered within other features, such as resources, action modals, table filters, and more.
- Infolists: Read-only lists of data.
- Notifications: Flash notifications displayed to users within the application.
- Panels: The top-level container in Filament that can include all other features like pages, resources, forms, tables, notifications, actions, infolists, and widgets.
- Resources: Static classes that are used to build CRUD interfaces for Eloquent models. Typically live in `app/Filament/Resources`.
- Schemas: Represent components that define the structure and behavior of the UI, such as forms, tables, or lists.
- Tables: Interactive tables with filtering, sorting, pagination, and more.
- Widgets: Small component included within dashboards, often used for displaying data in charts, tables, or as a stat.

### Relationships
- Determine if you can use the `relationship()` method on form components when you need `options` for a select, checkbox, repeater, or when building a `Fieldset`:

<code-snippet name="Relationship example for Form Select" lang="php">
Forms\Components\Select::make('user_id')
    ->label('Author')
    ->relationship('author')
    ->required(),
</code-snippet>


## Testing
- It's important to test Filament functionality for user satisfaction.
- Ensure that you are authenticated to access the application within the test.
- Filament uses Livewire, so start assertions with `livewire()` or `Livewire::test()`.

### Example Tests

<code-snippet name="Filament Table Test" lang="php">
    livewire(ListUsers::class)
        ->assertCanSeeTableRecords($users)
        ->searchTable($users->first()->name)
        ->assertCanSeeTableRecords($users->take(1))
        ->assertCanNotSeeTableRecords($users->skip(1))
        ->searchTable($users->last()->email)
        ->assertCanSeeTableRecords($users->take(-1))
        ->assertCanNotSeeTableRecords($users->take($users->count() - 1));
</code-snippet>

<code-snippet name="Filament Create Resource Test" lang="php">
    livewire(CreateUser::class)
        ->fillForm([
            'name' => 'Howdy',
            'email' => 'howdy@example.com',
        ])
        ->call('create')
        ->assertNotified()
        ->assertRedirect();

    assertDatabaseHas(User::class, [
        'name' => 'Howdy',
        'email' => 'howdy@example.com',
    ]);
</code-snippet>

<code-snippet name="Testing Multiple Panels (setup())" lang="php">
    use Filament\Facades\Filament;

    Filament::setCurrentPanel('app');
</code-snippet>

<code-snippet name="Calling an Action in a Test" lang="php">
    livewire(EditInvoice::class, [
        'invoice' => $invoice,
    ])->callAction('send');

    expect($invoice->refresh())->isSent()->toBeTrue();
</code-snippet>


=== filament/v3 rules ===

## Filament 3

## Version 3 Changes To Focus On
- Resources are located in `app/Filament/Resources/` directory.
- Resource pages (List, Create, Edit) are auto-generated within the resource's directory - e.g., `app/Filament/Resources/PostResource/Pages/`.
- Forms use the `Forms\Components` namespace for form fields.
- Tables use the `Tables\Columns` namespace for table columns.
- A new `Filament\Forms\Components\RichEditor` component is available.
- Form and table schemas now use fluent method chaining.
- Added `php artisan filament:optimize` command for production optimization.
- Requires implementing `FilamentUser` contract for production access control.


=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation
- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources
- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration
- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Testing
- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] <name>` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

### Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.


=== laravel/v10 rules ===

## Laravel 10

- Use the `search-docs` tool to get version specific documentation.
- Middleware typically live in `app/Http/Middleware/` and service providers in `app/Providers/`.
- There is no `bootstrap/app.php` application configuration in Laravel 10:
    - Middleware registration is in `app/Http/Kernel.php`
    - Exception handling is in `app/Exceptions/Handler.php`
    - Console commands and schedule registration is in `app/Console/Kernel.php`
    - Rate limits likely exist in `RouteServiceProvider` or `app/Http/Kernel.php`
- When using Eloquent model casts, you must use `protected $casts = [];` and not the `casts()` method. The `casts()` method isn't available on models in Laravel 10.


=== fluxui-free/core rules ===

## Flux UI Free

- This project is using the free edition of Flux UI. It has full access to the free components and variants, but does not have access to the Pro components.
- Flux UI is a component library for Livewire. Flux is a robust, hand-crafted, UI component library for your Livewire applications. It's built using Tailwind CSS and provides a set of components that are easy to use and customize.
- You should use Flux UI components when available.
- Fallback to standard Blade components if Flux is unavailable.
- If available, use Laravel Boost's `search-docs` tool to get the exact documentation and code snippets available for this project.
- Flux UI components look like this:

<code-snippet name="Flux UI Component Usage Example" lang="blade">
    <flux:button variant="primary"/>
</code-snippet>


### Available Components
This is correct as of Boost installation, but there may be additional components within the codebase.

<available-flux-components>
avatar, badge, brand, breadcrumbs, button, callout, checkbox, dropdown, field, heading, icon, input, modal, navbar, profile, radio, select, separator, switch, text, textarea, tooltip
</available-flux-components>


=== fluxui-pro/core rules ===

## Flux UI Pro

- This project is using the Pro version of Flux UI. It has full access to the free components and variants, as well as full access to the Pro components and variants.
- Flux UI is a component library for Livewire. Flux is a robust, hand-crafted, UI component library for your Livewire applications. It's built using Tailwind CSS and provides a set of components that are easy to use and customize.
- You should use Flux UI components when available.
- Fallback to standard Blade components if Flux is unavailable.
- If available, use Laravel Boost's `search-docs` tool to get the exact documentation and code snippets available for this project.
- Flux UI components look like this:

<code-snippet name="Flux UI component usage example" lang="blade">
    <flux:button variant="primary"/>
</code-snippet>


### Available Components
This is correct as of Boost installation, but there may be additional components within the codebase.

<available-flux-components>
accordion, autocomplete, avatar, badge, brand, breadcrumbs, button, calendar, callout, card, chart, checkbox, command, context, date-picker, dropdown, editor, field, heading, icon, input, modal, navbar, pagination, popover, profile, radio, select, separator, switch, table, tabs, text, textarea, toast, tooltip
</available-flux-components>


=== livewire/core rules ===

## Livewire Core
- Use the `search-docs` tool to find exact version specific documentation for how to write Livewire & Livewire tests.
- Use the `php artisan make:livewire [Posts\\CreatePost]` artisan command to create new components
- State should live on the server, with the UI reflecting it.
- All Livewire requests hit the Laravel backend, they're like regular HTTP requests. Always validate form data, and run authorization checks in Livewire actions.

## Livewire Best Practices
- Livewire components require a single root element.
- Use `wire:loading` and `wire:dirty` for delightful loading states.
- Add `wire:key` in loops:

    ```blade
    @foreach ($items as $item)
        <div wire:key="item-{{ $item->id }}">
            {{ $item->name }}
        </div>
    @endforeach
    ```

- Prefer lifecycle hooks like `mount()`, `updatedFoo()`) for initialization and reactive side effects:

<code-snippet name="Lifecycle hook examples" lang="php">
    public function mount(User $user) { $this->user = $user; }
    public function updatedSearch() { $this->resetPage(); }
</code-snippet>


## Testing Livewire

<code-snippet name="Example Livewire component test" lang="php">
    Livewire::test(Counter::class)
        ->assertSet('count', 0)
        ->call('increment')
        ->assertSet('count', 1)
        ->assertSee(1)
        ->assertStatus(200);
</code-snippet>


    <code-snippet name="Testing a Livewire component exists within a page" lang="php">
        $this->get('/posts/create')
        ->assertSeeLivewire(CreatePost::class);
    </code-snippet>


=== livewire/v3 rules ===

## Livewire 3

### Key Changes From Livewire 2
- These things changed in Livewire 2, but may not have been updated in this application. Verify this application's setup to ensure you conform with application conventions.
    - Use `wire:model.live` for real-time updates, `wire:model` is now deferred by default.
    - Components now use the `App\Livewire` namespace (not `App\Http\Livewire`).
    - Use `$this->dispatch()` to dispatch events (not `emit` or `dispatchBrowserEvent`).
    - Use the `components.layouts.app` view as the typical layout path (not `layouts.app`).

### New Directives
- `wire:show`, `wire:transition`, `wire:cloak`, `wire:offline`, `wire:target` are available for use. Use the documentation to find usage examples.

### Alpine
- Alpine is now included with Livewire, don't manually include Alpine.js.
- Plugins included with Alpine: persist, intersect, collapse, and focus.

### Lifecycle Hooks
- You can listen for `livewire:init` to hook into Livewire initialization, and `fail.status === 419` for the page expiring:

<code-snippet name="livewire:load example" lang="js">
document.addEventListener('livewire:init', function () {
    Livewire.hook('request', ({ fail }) => {
        if (fail && fail.status === 419) {
            alert('Your session expired');
        }
    });

    Livewire.hook('message.failed', (message, component) => {
        console.error(message);
    });
});
</code-snippet>


=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.


=== pest/core rules ===

## Pest

### Testing
- If you need to verify a feature is working, write or update a Unit / Feature test.

### Pest Tests
- All tests must be written using Pest. Use `php artisan make:test --pest <name>`.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files - these are core to the application.
- Tests should test all of the happy paths, failure paths, and weird paths.
- Tests live in the `tests/Feature` and `tests/Unit` directories.
- Pest tests look and behave like this:
<code-snippet name="Basic Pest Test Example" lang="php">
it('is true', function () {
    expect(true)->toBeTrue();
});
</code-snippet>

### Running Tests
- Run the minimal number of tests using an appropriate filter before finalizing code edits.
- To run all tests: `php artisan test`.
- To run all tests in a file: `php artisan test tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --filter=testName` (recommended after making a change to a related file).
- When the tests relating to your changes are passing, ask the user if they would like to run the entire test suite to ensure everything is still passing.

### Pest Assertions
- When asserting status codes on a response, use the specific method like `assertForbidden` and `assertNotFound` instead of using `assertStatus(403)` or similar, e.g.:
<code-snippet name="Pest Example Asserting postJson Response" lang="php">
it('returns all', function () {
    $response = $this->postJson('/api/docs', []);

    $response->assertSuccessful();
});
</code-snippet>

### Mocking
- Mocking can be very helpful when appropriate.
- When mocking, you can use the `Pest\Laravel\mock` Pest function, but always import it via `use function Pest\Laravel\mock;` before using it. Alternatively, you can use `$this->mock()` if existing tests do.
- You can also create partial mocks using the same import or self method.

### Datasets
- Use datasets in Pest to simplify tests which have a lot of duplicated data. This is often the case when testing validation rules, so consider going with this solution when writing tests for validation rules.

<code-snippet name="Pest Dataset Example" lang="php">
it('has emails', function (string $email) {
    expect($email)->not->toBeEmpty();
})->with([
    'james' => 'james@laravel.com',
    'taylor' => 'taylor@laravel.com',
]);
</code-snippet>


=== tailwindcss/core rules ===

## Tailwind Core

- Use Tailwind CSS classes to style HTML, check and use existing tailwind conventions within the project before writing your own.
- Offer to extract repeated patterns into components that match the project's conventions (i.e. Blade, JSX, Vue, etc..)
- Think through class placement, order, priority, and defaults - remove redundant classes, add classes to parent or child carefully to limit repetition, group elements logically
- You can use the `search-docs` tool to get exact examples from the official documentation when needed.

### Spacing
- When listing items, use gap utilities for spacing, don't use margins.

    <code-snippet name="Valid Flex Gap Spacing Example" lang="html">
        <div class="flex gap-8">
            <div>Superior</div>
            <div>Michigan</div>
            <div>Erie</div>
        </div>
    </code-snippet>


### Dark Mode
- If existing pages and components support dark mode, new pages and components must support dark mode in a similar way, typically using `dark:`.


=== tailwindcss/v4 rules ===

## Tailwind 4

- Always use Tailwind CSS v4 - do not use the deprecated utilities.
- `corePlugins` is not supported in Tailwind v4.
- In Tailwind v4, you import Tailwind using a regular CSS `@import` statement, not using the `@tailwind` directives used in v3:

<code-snippet name="Tailwind v4 Import Tailwind Diff" lang="diff"
   - @tailwind base;
   - @tailwind components;
   - @tailwind utilities;
   + @import "tailwindcss";
</code-snippet>


### Replaced Utilities
- Tailwind v4 removed deprecated utilities. Do not use the deprecated option - use the replacement.
- Opacity values are still numeric.

| Deprecated |	Replacement |
|------------+--------------|
| bg-opacity-* | bg-black/* |
| text-opacity-* | text-black/* |
| border-opacity-* | border-black/* |
| divide-opacity-* | divide-black/* |
| ring-opacity-* | ring-black/* |
| placeholder-opacity-* | placeholder-black/* |
| flex-shrink-* | shrink-* |
| flex-grow-* | grow-* |
| overflow-ellipsis | text-ellipsis |
| decoration-slice | box-decoration-slice |
| decoration-clone | box-decoration-clone |


=== tests rules ===

## Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test` with a specific filename or filter.
</laravel-boost-guidelines>