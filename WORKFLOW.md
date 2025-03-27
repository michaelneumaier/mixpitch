# MixPitch Workflow Documentation

This document provides a comprehensive overview of the project and pitch lifecycle within the MixPitch application, detailing all states, transitions, and interactions between different components.

## Table of Contents

1. [Overview](#overview)
2. [Project Workflow](#project-workflow)
   - [Project Statuses](#project-statuses)
   - [Project Lifecycle](#project-lifecycle)
3. [Pitch Workflow](#pitch-workflow)
   - [Pitch Statuses](#pitch-statuses)
   - [Pitch Lifecycle](#pitch-lifecycle)
   - [Status Transitions](#status-transitions)
   - [Validation Rules](#validation-rules)
4. [Snapshot System](#snapshot-system)
   - [Snapshot Statuses](#snapshot-statuses)
   - [Snapshot Lifecycle](#snapshot-lifecycle)
5. [Payment Process](#payment-process)
   - [Payment Statuses](#payment-statuses)
   - [Invoice System](#invoice-system)
6. [User Interactions](#user-interactions)
   - [Project Owner Actions](#project-owner-actions)
   - [Pitch Creator Actions](#pitch-creator-actions)
7. [Common Workflows](#common-workflows)
   - [Complete Project Flow](#complete-project-flow)
   - [Revision Flow](#revision-flow)
   - [Completion and Payment Flow](#completion-and-payment-flow)
8. [Codebase Structure and Key Files](#codebase-structure-and-key-files)
   - [Models](#models)
   - [Controllers](#controllers)
   - [Services](#services)
   - [Livewire Components](#livewire-components)
   - [Views](#views)

## Overview

MixPitch is a platform that connects music project owners with producers/engineers who can pitch their services. The platform follows a structured workflow where:

1. Project owners create projects
2. Producers submit pitches for projects
3. Project owners review pitches and request revisions or approve them
4. Producers submit revisions through snapshots
5. Once a pitch is approved and completed, payment is processed
6. Project is marked as completed

## Project Workflow

### Project Statuses

Projects in MixPitch have the following statuses:

| Status | Constant | Description |
|--------|----------|-------------|
| Unpublished | `STATUS_UNPUBLISHED` | The project has been created but is not yet visible to potential pitch creators |
| Open | `STATUS_OPEN` | The project is published and accepting new pitches |
| In Progress | `STATUS_IN_PROGRESS` | The project has active pitches being worked on |
| Completed | `STATUS_COMPLETED` | The project has been completed, with a successful pitch chosen |

### Project Lifecycle

1. **Creation**: A project owner creates a new project with a name, description, genre, artist name, project type, budget, and optionally a deadline, preview track, and image.
2. **Publishing**: The project owner publishes the project, changing its status from `unpublished` to `open`.
3. **Accepting Pitches**: While the project is `open`, producers can submit pitches.
4. **In Progress**: Once pitches are being actively worked on, the project status changes to `in_progress`.
5. **Completion**: When a pitch is selected and marked as completed, the project status changes to `completed`.

## Pitch Workflow

### Pitch Statuses

Pitches have a more complex state system with the following statuses:

| Status | Constant | Description |
|--------|----------|-------------|
| Pending | `STATUS_PENDING` | The pitch has been created but is pending initial approval from the project owner |
| In Progress | `STATUS_IN_PROGRESS` | The pitch is being actively worked on by the producer |
| Ready for Review | `STATUS_READY_FOR_REVIEW` | The producer has submitted the pitch for review |
| Pending Review | `STATUS_PENDING_REVIEW` | The pitch is awaiting review by the project owner |
| Approved | `STATUS_APPROVED` | The pitch has been approved by the project owner |
| Denied | `STATUS_DENIED` | The pitch has been denied by the project owner |
| Revisions Requested | `STATUS_REVISIONS_REQUESTED` | The project owner has requested revisions to the pitch |
| Completed | `STATUS_COMPLETED` | The pitch has been completed and is ready for payment |
| Closed | `STATUS_CLOSED` | The pitch has been closed (usually because another pitch for the same project was selected) |

### Pitch Lifecycle

1. **Creation**: A producer creates a pitch for a specific project.
2. **Initial Approval**: The project owner reviews the pitch and approves it to begin work (changes status from `pending` to `in_progress`).
3. **Development**: The producer works on the pitch, uploading files and making revisions.
4. **Review Submission**: When ready, the producer submits the pitch for review (changes status to `ready_for_review`).
5. **Review Process**: The project owner reviews the pitch and can:
   - Approve the pitch (changing status to `approved`)
   - Deny the pitch (changing status to `denied`)
   - Request revisions (changing status to `revisions_requested`)
6. **Revisions**: If revisions are requested, the producer makes changes and resubmits.
7. **Completion**: Once approved, the pitch can be marked as completed (changing status to `completed`).
8. **Payment**: After completion, payment is processed.

### Status Transitions

Pitch status transitions follow strict rules defined in the system. Forward transitions include:

```
pending → in_progress
in_progress → ready_for_review
ready_for_review → [approved, denied, revisions_requested]
approved → completed
revisions_requested → ready_for_review
```

Backward transitions include:

```
in_progress → pending
approved → ready_for_review
denied → ready_for_review
revisions_requested → in_progress
ready_for_review → [in_progress, denied, revisions_requested, pending_review]
completed → approved
```

### Validation Rules

The system enforces several validation rules for pitch status transitions:

1. **Submit for Review**: A pitch can only be submitted for review if:
   - It has at least one file attached
   - It is in the correct status (in_progress, denied, approved, or revisions_requested)

2. **Cancel Submission**: A submission can only be cancelled if:
   - The user is the pitch owner
   - The pitch is in the ready_for_review status
   - The current snapshot is in the pending status

3. **Approve Pitch**: A pitch can only be approved if:
   - It is in the ready_for_review status
   - The current snapshot is in the pending status
   - It has not been completed with payment already processed

4. **Deny Pitch**: A pitch can only be denied if:
   - It is in the ready_for_review status
   - The current snapshot is in the pending status

5. **Request Revisions**: Revisions can only be requested if:
   - The pitch is in the ready_for_review status
   - The current snapshot is in the pending status

6. **Complete Pitch**: A pitch can only be marked as completed if:
   - It is in the approved status

## Snapshot System

The snapshot system in MixPitch provides version control for pitches, allowing producers to submit revisions and project owners to review specific versions.

### Snapshot Statuses

Snapshots have the following statuses:

| Status | Description |
|--------|-------------|
| Pending | The snapshot has been submitted and is awaiting review |
| Accepted | The snapshot has been accepted by the project owner |
| Denied | The snapshot has been denied by the project owner |
| Revisions Requested | The project owner has requested revisions to this snapshot |
| Revision Addressed | This snapshot was revised in a newer snapshot |
| Completed | This snapshot is part of a completed pitch |

### Snapshot Lifecycle

1. **Creation**: When a producer submits a pitch for review, a snapshot is created to capture the current state.
2. **Review**: The project owner reviews the snapshot and can accept, deny, or request revisions.
3. **Revision**: If revisions are requested, the producer creates a new snapshot that references the previous one.
4. **Completion**: When a pitch is completed, its current snapshot is marked as completed.

Each snapshot stores:
- The pitch and project IDs
- The user ID who created it
- A version number
- File IDs associated with this version
- Status information
- Response to feedback (for revisions)

## Payment Process

### Payment Statuses

Pitch payments have the following statuses:

| Status | Constant | Description |
|--------|----------|-------------|
| Pending | `PAYMENT_STATUS_PENDING` | Payment is pending processing |
| Processing | `PAYMENT_STATUS_PROCESSING` | Payment is being processed |
| Paid | `PAYMENT_STATUS_PAID` | Payment has been successfully processed |
| Failed | `PAYMENT_STATUS_FAILED` | Payment processing failed |
| Not Required | `PAYMENT_STATUS_NOT_REQUIRED` | Payment is not required for this pitch |
| Refunded | `PAYMENT_STATUS_REFUNDED` | Payment has been refunded |

### Invoice System

The platform uses a unified InvoiceService to handle all invoice-related operations:

1. **Invoice Creation**: When a pitch is completed, an invoice is created with metadata linking it to the pitch and project.
2. **Payment Processing**: The project owner can process the payment using various payment methods.
3. **Receipt Generation**: After successful payment, a receipt is generated and can be viewed by both parties.

Invoices store:
- Pitch and project metadata
- Payment amount (based on project budget)
- Payment status and dates
- Transaction IDs and related payment information

## User Interactions

### Project Owner Actions

Project owners can:
1. Create and publish projects
2. Review pitch applications (accept or reject initial applications)
3. Review submitted pitches (approve, deny, or request revisions)
4. Complete approved pitches
5. Process payments for completed pitches
6. View pitch history and snapshots
7. Add comments and feedback

### Pitch Creator Actions

Pitch creators (producers) can:
1. Apply to projects by creating pitches
2. Upload files to their pitches
3. Submit pitches for review
4. Address revision requests
5. Cancel submitted pitches (if still pending review)
6. View pitch history and snapshots
7. Add comments and respond to feedback

## Common Workflows

### Complete Project Flow

1. Project owner creates and publishes a project
2. Producers submit pitches for the project
3. Project owner approves select pitches to begin work
4. Producers develop their pitches and submit for review
5. Project owner reviews pitches and may request revisions
6. Producers address revisions and resubmit
7. Project owner approves a pitch
8. Project owner marks an approved pitch as completed
9. All other pitches are automatically marked as closed
10. Project owner processes payment for the completed pitch
11. Project is marked as completed

### Revision Flow

1. Producer submits pitch for review
2. Project owner requests revisions with feedback
3. Pitch status changes to revisions_requested
4. Producer makes changes and submits a new snapshot with response to feedback
5. Pitch status changes back to ready_for_review
6. Project owner reviews the revised pitch
7. This cycle can continue until the pitch is approved or denied

### Completion and Payment Flow

1. Project owner approves a pitch
2. Project owner marks the pitch as completed
3. All other pitches for the project are automatically closed
4. Pending snapshots for closed pitches are declined
5. Project owner processes payment for the completed pitch
6. Payment status changes from pending to processing to paid
7. Both parties receive a receipt for the transaction
8. Project is marked as completed

## Codebase Structure and Key Files

This section outlines the key files and components involved in implementing the MixPitch workflow.

### Models

| Model | File | Purpose |
|-------|------|---------|
| Project | `app/Models/Project.php` | Defines project attributes, statuses, and relationships. Handles project lifecycle including publishing, unpublishing, and completion. |
| Pitch | `app/Models/Pitch.php` | Core model for pitch functionality. Defines pitch statuses, status transitions, validation rules, and methods for managing the pitch lifecycle. |
| PitchSnapshot | `app/Models/PitchSnapshot.php` | Manages pitch version control. Stores snapshot data, relationships, and snapshot-specific states. |
| PitchFile | `app/Models/PitchFile.php` | Handles files attached to pitches, including storage and retrieval. |
| PitchEvent | `app/Models/PitchEvent.php` | Records events in the pitch lifecycle, such as status changes, comments, and other actions. |

### Controllers

| Controller | File | Purpose |
|------------|------|---------|
| ProjectController | `app/Http/Controllers/ProjectController.php` | Handles project CRUD operations, publishing, unpublishing, and viewing. |
| PitchController | `app/Http/Controllers/PitchController.php` | Manages pitch creation, submission, review, and status changes. Implements redirection logic for different user types. |
| PitchFileController | `app/Http/Controllers/PitchFileController.php` | Manages file uploads, downloads, and deletion for pitches. |
| PitchPaymentController | `app/Http/Controllers/PitchPaymentController.php` | Processes payments for completed pitches and generates receipts. |
| BillingController | `app/Http/Controllers/Billing/BillingController.php` | Manages invoice display and payment history. |

### Services

| Service | File | Purpose |
|---------|------|---------|
| InvoiceService | `app/Services/InvoiceService.php` | Unified service for creating, processing, and formatting invoices for both pitch payments and general billing. |
| NotificationService | `app/Services/NotificationService.php` | Handles notifications for pitch status changes, comments, and other events. |

### Livewire Components

| Component | File | Purpose |
|-----------|------|---------|
| ManagePitch | `app/Livewire/Pitch/Component/ManagePitch.php` | Interactive component for pitch management by pitch creators. |
| UpdatePitchStatus | `app/Livewire/Pitch/Component/UpdatePitchStatus.php` | Handles status transitions for pitches. |
| CompletePitch | `app/Livewire/Pitch/Component/CompletePitch.php` | Manages the pitch completion process by project owners. |
| PitchFiles | `app/Livewire/Pitch/Component/PitchFiles.php` | Interactive file management for pitches. |
| PitchHistory | `app/Livewire/Pitch/Component/PitchHistory.php` | Displays the history of events and snapshots for a pitch. |

### Views

| View | File | Purpose |
|------|------|---------|
| Project Show | `resources/views/projects/show.blade.php` | Displays project details and allows pitching. |
| Project Management | `resources/views/livewire/project/page/manage-project.blade.php` | Interface for project owners to manage their projects and pitches. |
| Pitch Show | `resources/views/pitches/show.blade.php` | Main interface for pitch creators to work on their pitches. |
| Snapshot View | `resources/views/pitches/show-snapshot.blade.php` | Displays snapshot details for review. |
| Payment Overview | `resources/views/pitches/payment/overview.blade.php` | Payment processing interface for project owners. |
| Receipt | `resources/views/pitches/payment/receipt.blade.php` | Displays payment receipts after successful payment. |
| Invoice Details | `resources/views/components/invoice-details.blade.php` | Reusable component for displaying invoice information. |
| Invoices List | `resources/views/billing/invoices.blade.php` | Lists all invoices for a user. |
| Invoice Show | `resources/views/billing/invoice-show.blade.php` | Displays detailed invoice information. |

---

This document provides a comprehensive overview of the MixPitch workflow. For implementation details, please refer to the codebase and related technical documentation.
