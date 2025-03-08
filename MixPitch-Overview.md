# MixPitch - Comprehensive System Overview

## Introduction

MixPitch is a collaborative platform built using Laravel, Livewire v3, and Jetstream that connects musicians with audio professionals. The platform enables musicians to upload their projects and receive contributions from mixers, producers, and audio engineers, while allowing audio professionals to showcase their skills and build their portfolios.

## Technology Stack

- **Backend Framework**: Laravel 10.x
- **Frontend Interactivity**: Livewire v3
- **Authentication & UI**: Laravel Jetstream
- **CSS Framework**: Tailwind CSS
- **Database**: MySQL (based on Laravel migrations)
- **Additional Libraries**:
  - Eloquent Sluggable (for URL-friendly slugs)
  - Laravel State Machine (for managing state transitions)
  - Spatie Laravel Permission (for role-based access control)
  - Laravel Comments (for commenting functionality)
  - Masmerise Livewire Toaster (for toast notifications)

## Core System Architecture

MixPitch follows the MVC (Model-View-Controller) architecture pattern with additional Livewire components for reactive UI elements. The system is organized into several key modules:

### 1. User Management

The system utilizes Laravel Jetstream for user authentication, registration, and profile management. Users can:
- Register and login
- Manage their profile information
- Update profile photos
- Enable two-factor authentication

### 2. Project Management

Projects are the central entity in MixPitch, representing music that artists upload for collaboration.

#### Key Components:
- **Model**: `Project.php`
- **Controller**: `ProjectController.php`
- **Livewire Components**: 
  - `CreateProject.php` (for creating and editing projects)
  - `ManageProject.php` (for managing existing projects)
  - `ProjectsComponent.php` (for displaying projects)

#### Project Workflow:
1. Artists create projects with details like name, description, genre, etc.
2. They can upload audio files to the project
3. They can set collaboration types (mixing, mastering, production, etc.)
4. They can set a budget and deadline
5. They can publish the project to make it available for pitches

### 3. Pitch System

Pitches represent proposals from audio professionals to work on a project.

#### Key Components:
- **Model**: `Pitch.php`
- **Controller**: `PitchController.php`
- **Livewire Components**:
  - `ManagePitch.php` (for managing pitch details and files)
  - `UpdatePitchStatus.php` (for changing pitch status)

#### Pitch Workflow:
1. Audio professionals view available projects
2. They create a pitch for a project they want to work on
3. The pitch goes through various status stages:
   - `pending` → Initial state
   - `in_progress` → Work is being done
   - `ready_for_review` → Submitted for project owner to review
   - `pending_review` → Sent back for revisions
   - `approved` → Pitch is accepted
   - `denied` → Pitch is rejected
   - `completed` → Work is completed

#### Pitch Snapshots:
The system includes a snapshot feature that allows audio professionals to create versions of their pitch:
- Each snapshot captures the state of the pitch files at a point in time
- Project owners can review snapshots and provide feedback
- Snapshots can be accepted, revised, or declined

### 4. Mix Management

Mixes represent the final audio files submitted by audio professionals.

#### Key Components:
- **Model**: `Mix.php`
- **Controller**: `MixController.php`

#### Mix Workflow:
1. Audio professionals upload their mix files
2. Project owners can rate the mixes
3. Project owners can select the mix they want to use

### 5. File Management

The system handles various types of files:

#### Project Files:
- Uploaded by project owners
- Stored in `public/storage/projects/{project_id}`
- Managed through the `ProjectFile` model

#### Pitch Files:
- Uploaded by audio professionals as part of their pitch
- Stored in `public/storage/pitch_files`
- Managed through the `PitchFile` model

#### Mix Files:
- Uploaded as final deliverables
- Stored in `public/storage/mixes`
- Managed through the `Mix` model

## Database Structure

The database consists of several interconnected tables:

### Core Tables:
1. **users** - User accounts and profile information
2. **projects** - Music projects uploaded by artists
3. **project_files** - Files associated with projects
4. **pitches** - Proposals from audio professionals
5. **pitch_files** - Files associated with pitches
6. **pitch_snapshots** - Versions of pitches at specific points in time
7. **pitch_events** - Activity log for pitches (status changes, comments, ratings)
8. **mixes** - Final mixes submitted by audio professionals

### Relationships:
- A user can have many projects, pitches, and mixes
- A project can have many files, pitches, and mixes
- A pitch belongs to a user and a project, and has many files, events, and snapshots
- A pitch snapshot belongs to a pitch and contains file references

## Key Features

### 1. Project Creation and Management
- Multi-step project creation process
- File upload with preview functionality
- Project status management (unpublished, open, review, completed, closed)
- Project image and audio preview capabilities

### 2. Pitch Workflow
- Status-based workflow with state transitions
- File upload and management
- Commenting system for feedback
- Rating system for evaluation
- Snapshot creation for version control

### 3. Collaboration Types
The system supports multiple collaboration types:
- Mixing
- Mastering
- Production
- Songwriting
- Vocal tuning

### 4. User Interface
- Responsive design using Tailwind CSS
- Interactive components with Livewire
- Audio player for previewing tracks
- File management interfaces

## Routes and Navigation

The application's routes are organized into several groups:

### Public Routes:
- Home page (`/`)
- Project listing (`/projects`)
- Project details (`/projects/{project}`)
- About page (`/about`)
- Pricing page (`/pricing`)

### Authenticated Routes:
- Dashboard (`/dashboard`)
- Project management:
  - Create project (`/create-project`)
  - Edit project (`/edit-project/{project}`)
  - Manage project (`/manage-project/{project}`)
- Pitch management:
  - Create pitch (`/pitches/create/{project}`)
  - View pitch (`/pitches/{pitch}`)
  - View pitch snapshot (`/pitches/{pitch}/{pitchSnapshot}`)
- Mix management:
  - Create mix (`/projects/{project}/mixes/create`)
  - Rate mix (`/mixes/{mix}/rate`)

## Security and Access Control

The system implements several security measures:

1. **Authentication** via Laravel Sanctum and Jetstream
2. **Authorization** checks to ensure users can only:
   - Edit their own projects
   - View pitches they created or are for their projects
   - Delete their own comments
   - Access files they have permission to view

3. **Status-based access control** for pitches:
   - Project files are only accessible once a pitch is approved
   - Certain actions are only available at specific pitch statuses

## User Roles and Permissions

While not explicitly defined in the code, the system has implicit roles:

1. **Project Owners** (Musicians/Artists):
   - Create and manage projects
   - Review and approve/deny pitches
   - Rate mixes

2. **Audio Professionals**:
   - Browse available projects
   - Create pitches for projects
   - Upload files as part of pitches
   - Submit mixes

## Frontend Components

The frontend is built using Blade templates with Livewire components for reactivity:

### Key Blade Templates:
- `home.blade.php` - Landing page
- `projects/project.blade.php` - Project details
- `pitches/show.blade.php` - Pitch details

### Key Livewire Components:
- `AudioPlayer.php` - For playing audio files
- `CreateProject.php` - Project creation form
- `ManageProject.php` - Project management interface
- `ManagePitch.php` - Pitch management interface
- `ProjectsComponent.php` - Project listing

## Conclusion

MixPitch is a comprehensive platform that facilitates collaboration between musicians and audio professionals. Its modular architecture, state-based workflow, and interactive UI create a seamless experience for users on both sides of the collaboration process. The system effectively manages the entire lifecycle from project creation to pitch submission, review, and final mix delivery.
