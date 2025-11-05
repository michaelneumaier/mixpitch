# Inline Editing Implementation Plan

**Goal:** Deprecate CreateProject component for editing by implementing comprehensive inline editing across manage-project and manage-client-project pages.

**Status:** In Progress
**Started:** 2025-11-03
**Last Updated:** 2025-11-03

---

## Overview

This plan replaces the current edit workflow (which redirects to CreateProject component) with inline editing directly on the management pages. This provides better UX, faster updates, and eliminates context switching.

### Key Principles
- **No component re-renders** - Use `skipRender()` to prevent breaking nested components
- **Alpine-only state** - No Livewire entanglement for display values
- **Direct database updates** - Bypass Livewire property tracking
- **Mobile-first** - Always-visible edit controls on touch devices
- **Workflow-aware** - Different fields for different project types

---

## Phase 1: Foundation ✅ COMPLETED

### 1.1 Inline Title Editing ✅
**Status:** Complete
**Files Modified:**
- `resources/views/components/project/header.blade.php` - Alpine inline editing UI
- `app/Livewire/ManageProject.php` - `updateProjectTitle()` method
- `app/Livewire/Project/ManageClientProject.php` - `updateProjectTitle()` method

**Implementation:**
- Hover-reveal pencil icon (always visible on mobile)
- Enter to save, Escape to cancel
- Visual save/cancel buttons (checkmark/X)
- Direct database update with `skipRender()`
- Alpine display (`x-text="originalTitle"`)

**Key Learning:** Using `@entangle().live` causes component re-renders. Use plain Alpine state instead.

---

### 1.2 Project Details Card ✅
**Status:** Complete
**Files Created:**
- `resources/views/components/project/details-card.blade.php` - Main component

**Files Modified:**
- `resources/views/livewire/project/page/manage-project.blade.php` - Sidebar integration
- `resources/views/livewire/project/manage-client-project.blade.php` - Sidebar integration
- `app/Livewire/ManageProject.php` - `updateProjectDetailsInline()` method
- `app/Livewire/Project/ManageClientProject.php` - `updateProjectDetailsInline()` method

**Features Implemented:**
- ✅ Artist Name - Inline editing
- ✅ Genre - Inline editing
- ✅ Description - Textarea editing
- ✅ Notes - Collapsible section with textarea
- ✅ Collaboration Types - Display only (edit button for future modal)
- ✅ Workflow-specific color schemes
- ✅ Dark mode support

**Key Learning:** Blade parser conflicts with Alpine `:class` shorthand. Use `x-bind:class` for explicit directives.

---

### 1.3 Collaboration Types Management ⏸️
**Status:** Deferred (Edit button exists, modal not implemented)

**Remaining Work:**
- Create modal component for editing collaboration types
- Wire up existing "Edit types" button
- Multi-select checkbox interface
- Validation (at least one type required except Client Management)

---

## Phase 2: Workflow-Specific Fields 🔄 IN PROGRESS

### 2.1 Client Information Card (Client Management) 🔄 NEXT
**Status:** Not Started
**Target Files:**
- Create: `resources/views/components/project/client-info-card.blade.php`
- Modify: `app/Livewire/Project/ManageClientProject.php`

**Features to Implement:**
- Display client name and email
- Inline editing for client info
- Dropdown to select existing clients (search/autocomplete)
- Manual entry for new clients
- Payment amount display/edit
- Link to client portal (when available)

**Method to Add:**
```php
public function updateClientInfo(array $updates): void
{
    // Validate client_email, client_name, payment_amount
    // Update project
    // Skip render
}
```

**Integration:** Add to sidebar in manage-client-project.blade.php

---

### 2.2 Budget Display/Edit (Standard Projects) ✅
**Status:** Complete
**Location:** Project Details Card

**Features Implemented:**
- ✅ Display current budget (free vs paid) with badge
- ✅ Toggle switch for Free/Paid (radio buttons)
- ✅ Number input for paid amount
- ✅ Validation (min: 0, max: 999999.99, required if paid)
- ✅ Currency formatting with 2 decimal places
- ✅ Collapsible section in Project Details Card
- ✅ Only shown for Standard workflow projects

**Method Added:**
```php
public function updateBudget(array $updates): void
{
    // Validates budget_type (free/paid) and budget amount
    // Ensures paid projects have budget > 0
    // Direct database update with skipRender()
}
```

**UI Approach:** Added to Project Details Card as collapsed section (matches Notes pattern)

---

### 2.3 Deadline Management (Standard & Client Management)
**Status:** Not Started
**Location:** Project Details Card

**Features to Implement:**
- Display current deadline (formatted with timezone)
- Inline datetime-local picker
- Timezone conversion (user timezone ↔ UTC)
- Clear deadline option
- Validation (must be future date)

**Method to Add:**
```php
public function updateDeadline(string $deadline): void
{
    // Convert from user timezone to UTC
    // Validate future date
    // Update project
    // Skip render
}
```

**Reuse Existing:** Leverage TimezoneService from CreateProject

**Note:** Contest projects use different deadline fields (submission_deadline, judging_deadline) - handled separately in existing Contest Timeline component

---

## Phase 3: Advanced Features

### 3.1 License Management Modal
**Status:** Not Started
**Current State:** Edit button redirects to CreateProject
**Target:** Open inline modal instead

**Files to Modify:**
- `resources/views/components/project/license-management.blade.php` - Convert edit button
- Create: Modal component for license editing

**Features:**
- Select license template dropdown
- Custom license terms textarea
- Require agreement checkbox
- License notes field

**Method to Add:**
```php
public function updateLicense(array $updates): void
{
    // Validate license_template_id, custom_license_terms, requires_license_agreement, license_notes
    // Update project
    // Skip render
}
```

**Pattern:** Use Flux modal triggered by existing "Edit" button

---

### 3.2 Contest Prize Configuration Modal
**Status:** Not Started
**Current State:** Edit Prizes button redirects to CreateProject
**Target:** Open ContestPrizeConfigurator as modal

**Files to Modify:**
- `resources/views/livewire/project/component/contest-prizes.blade.php` - Line 15 button
- `app/Livewire/ContestPrizeConfigurator.php` - Already exists, make modal-ready

**Implementation:**
- Remove redirect href from "Edit Prizes" button
- Add `flux:modal="edit-prizes"` trigger
- Embed existing ContestPrizeConfigurator component in modal
- Wire up save method

**Existing Component:** ContestPrizeConfigurator already handles all prize logic, just need to wrap in modal

---

## Phase 4: Polish & Testing

### 4.1 Comprehensive Validation
**Status:** Not Started

**Tasks:**
- Review all validation rules from CreateProject
- Ensure inline methods match CreateProject validation
- Add client-side validation hints
- Improve error messaging
- Test edge cases

**Files to Review:**
- `app/Livewire/CreateProject.php` lines 139-249 (validation rules)

---

### 4.2 Dark Mode & Responsive Verification
**Status:** Not Started

**Testing Checklist:**
- [ ] All cards display correctly in dark mode
- [ ] Inline editing works on mobile (touch targets)
- [ ] Modals are mobile-friendly
- [ ] Text is readable in both themes
- [ ] Color schemes match workflow types
- [ ] Buttons are accessible (44px min)

---

### 4.3 Feature Tests
**Status:** Not Started

**Tests to Write:**
```php
// tests/Feature/InlineProjectEditingTest.php
- test_can_update_project_title_inline()
- test_can_update_project_details_inline()
- test_can_update_client_info()
- test_can_update_budget()
- test_can_update_deadline()
- test_can_update_license()
- test_can_update_contest_prizes()
- test_validation_errors_display_correctly()
- test_unauthorized_users_cannot_edit()
```

**Pattern:** Use existing test patterns from CreateProject tests

---

## Technical Patterns & Learnings

### Pattern 1: Preventing Component Re-renders
```php
public function updateSomething(array $updates): void
{
    // Direct database update (bypasses Livewire property tracking)
    Project::where('id', $this->project->id)->update($validated);

    // Skip render (prevents nested components from breaking)
    $this->skipRender();
}
```

**Why:** Calling `$this->project->refresh()` or `$this->project->update()` triggers Livewire re-renders, which break nested components (like checklist badges).

---

### Pattern 2: Alpine State Management
```blade
<div x-data="{
    editing: false,
    value: '{{ addslashes($project->field) }}',
    original: '{{ addslashes($project->field) }}'
}">
    <!-- Display mode -->
    <span x-text="value"></span>

    <!-- Edit mode -->
    <input x-model="value"
           @keydown.enter="$wire.updateField(value).then(() => { editing = false; original = value; })">
</div>
```

**Why:** No `@entangle` - prevents live Livewire updates during typing. Updates only on explicit save.

---

### Pattern 3: Mobile-Friendly Edit Icons
```blade
<button class="opacity-100 lg:opacity-0 lg:group-hover:opacity-100 ...">
    <flux:icon.pencil />
</button>
```

**Why:** Always visible on mobile/tablet, hover-reveal on desktop.

---

### Pattern 4: Blade + Alpine Syntax
```blade
<!-- ❌ BAD - Blade parses as PHP -->
:class="{ 'rotate-180': showNotes }"

<!-- ✅ GOOD - Explicit Alpine directive -->
x-bind:class="showNotes ? 'rotate-180' : ''"
```

**Why:** Blade parser can conflict with Alpine shorthand when multiple `{{ }}` statements are nearby.

---

## Migration Strategy

### Current State (Before)
1. User clicks "Edit Project" button
2. Redirects to `/projects/{id}/edit`
3. CreateProject component loads with full form
4. User makes changes and submits
5. Redirects back to manage page

### Target State (After)
1. User clicks pencil icon or "Edit" button
2. Inline editing activates (or modal opens)
3. User makes changes and saves
4. Updates instantly without page reload
5. No context switching

### Transition Plan
1. ✅ **Phase 1-2:** Implement inline editing alongside existing CreateProject
2. **Phase 3:** Update all "Edit" buttons to use inline/modal editing
3. **Phase 4:** Test thoroughly, ensure feature parity
4. **Phase 5:** Remove CreateProject edit routes (keep create functionality)
5. **Phase 6:** Archive CreateProject edit methods

---

## Success Criteria

### Functionality
- [ ] All fields from CreateProject are editable inline
- [ ] Validation matches CreateProject exactly
- [ ] All workflow types supported (Standard, Contest, Direct Hire, Client Management)
- [ ] No component breaking on save
- [ ] Mobile UX is excellent

### Performance
- [ ] No full page reloads for edits
- [ ] Updates feel instant (<500ms)
- [ ] No console errors
- [ ] No N+1 queries

### Code Quality
- [ ] All methods have authorization checks
- [ ] Comprehensive validation
- [ ] Error handling with user-friendly messages
- [ ] Feature test coverage >80%
- [ ] Code follows existing patterns

---

## Rollback Plan

If issues arise:
1. CreateProject edit functionality remains intact during implementation
2. Can toggle users back to old edit flow via feature flag if needed
3. Database changes are additive only (no schema changes required)
4. All inline methods are new, not replacing existing functionality

---

## Questions & Decisions

### Q: Should we keep CreateProject for initial project creation?
**A:** YES - Only deprecating the edit functionality. Create flow stays the same.

### Q: What about collaboration types modal?
**A:** Deferred to later. Display-only for now with "Edit types" button placeholder.

### Q: How to handle contest deadlines?
**A:** Use existing Contest Timeline Management component. No changes needed.

### Q: Genre field - dropdown or text input?
**A:** Text input for now (matches CreateProject). Can enhance to autocomplete later.

---

## Notes

- All work maintains backward compatibility
- No breaking changes to existing functionality
- Progressive enhancement approach
- Can deploy phases incrementally
- Easy to rollback if needed

---

## Progress Tracking

**Phase 1:** ████████░░ 80% (4/5 complete)
**Phase 2:** ███████░░░ 67% (2/3 complete)
**Phase 3:** ░░░░░░░░░░ 0% (0/2 complete)
**Phase 4:** ░░░░░░░░░░ 0% (0/3 complete)

**Overall:** ██████░░░░ 55% (6/11 complete)
