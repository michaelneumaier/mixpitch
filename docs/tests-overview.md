# MixPitch Test Suite Overview

This document provides a comprehensive, directory-grouped overview of all test files and their respective test methods in the MixPitch application. Use this as a quick reference for test coverage and navigation.

---

## Feature

### ApiTokenPermissionsTest.php
- `test_api_token_permissions_can_be_updated`

### AuthenticationTest.php
- `test_login_screen_can_be_rendered`
- `test_users_can_authenticate_using_the_login_screen`
- `test_users_can_not_authenticate_with_invalid_password`

### BrowserSessionsTest.php
- `test_other_browser_sessions_can_be_logged_out`

### ClientPaymentFlowTest.php
- `client_approval_initiates_checkout_when_payment_required`
- `client_approval_completes_immediately_when_no_payment_required`
- `stripe_webhook_processes_checkout_completion_for_client_payment`
- `webhook_controller_ignores_non_client_pitch_checkouts`
- `webhook_is_idempotent_for_already_paid_pitches`

### ClientPortalTest.php
- `valid_signed_url_grants_access_to_client_portal`
- `invalid_signed_url_is_rejected`
- `expired_signed_url_is_rejected`
- `non_client_management_project_cannot_access_portal`
- `client_management_project_without_pitch_cannot_access_portal`
- `client_can_store_comment_via_portal`
- `client_cannot_store_empty_comment`
- `client_can_approve_pitch_via_portal`
- `client_cannot_approve_pitch_in_invalid_status`
- `client_can_request_revisions_via_portal`
- `client_cannot_request_revisions_with_empty_feedback`
- `client_cannot_request_revisions_in_invalid_status`
- `producer_submit_triggers_client_review_notification`
- `producer_can_complete_client_management_pitch_after_client_approval`
- `producer_cannot_complete_client_management_pitch_without_approval`
- `producer_can_resend_client_invite`

### ContestWorkflowTest.php
- `test_contest_workflow_with_prize_and_multiple_entries`
- `test_contest_workflow_without_prize`
- `test_contest_runner_up_selection`
- `test_producer_cannot_select_winner`

### CreateApiTokenTest.php
- `test_api_tokens_can_be_created`

### DeleteAccountTest.php
- `test_user_accounts_can_be_deleted`
- `test_correct_password_must_be_provided_before_account_can_be_deleted`

### DeleteApiTokenTest.php
- `test_api_tokens_can_be_deleted`

### DirectHireWorkflowTest.php
- `direct_hire_project_creation_assigns_pitch_and_notifies_producer`
- `producer_can_submit_direct_hire_pitch_for_review`
- `owner_can_approve_direct_hire_submission`
- `owner_can_request_revisions_for_direct_hire_submission`
- `producer_can_resubmit_after_revisions_requested`
- `owner_can_complete_direct_hire_pitch`
- `unauthorized_users_cannot_access_direct_hire`

### EmailVerificationTest.php
- `test_email_verification_screen_can_be_rendered`
- `test_email_can_be_verified`
- `test_email_can_not_verified_with_invalid_hash`

### ExampleTest.php
- `test_the_application_returns_a_successful_response`

### FileManagementTest.php
- `service_can_upload_project_file`
- `service_can_delete_project_file`
- `service_can_get_project_file_download_url`
- `service_can_set_preview_track`
- `service_throws_exception_setting_preview_track_with_file_from_different_project`
- `service_can_clear_preview_track`
- `service_can_upload_pitch_file`
- `service_can_delete_pitch_file`
- `service_can_get_pitch_file_download_url`
- `project_storage_capacity_check_throws_exception_when_limit_exceeded`
- `pitch_storage_capacity_check_throws_exception_when_limit_exceeded`
- `project_file_size_check_throws_exception_when_limit_exceeded`
- `pitch_file_size_check_throws_exception_when_limit_exceeded`

---

## Feature/Livewire

### AudioPlayerTest.php
- `renders_successfully`

### AuthDropdownTest.php
- `renders_successfully_for_authenticated_user`
- `renders_successfully_for_guest`

### CreateProjectTest.php
- `renders_successfully`

### EmailTestFormTest.php
- `renders_successfully`

### FileUploaderTest.php
- `component_renders_correctly_for_project`
- `component_renders_correctly_for_pitch`
- `file_is_required`
- `invalid_mime_type_is_rejected`
- `file_size_too_large_is_rejected`
- `can_upload_file_for_project`
- `can_upload_file_for_pitch`

### FiltersProjectsComponentTest.php
- `renders_successfully`

### ManageProjectStubTest.php
- `component_renders_with_manually_created_stub`

### ManageProjectTest.php
- `renders_successfully_for_project_owner`
- `fails_to_render_for_unauthorized_user`
- `can_update_project_details`
- `can_publish_project`
- `can_unpublish_project`
- `debug_update_project_details`

### NotificationCountTest.php
- `renders_successfully_for_logged_in_user`
- `renders_zero_count_for_guest`
- `loads_correct_initial_unread_count`
- `loads_zero_count_when_no_unread_notifications`
- `it_refreshes_count_when_notification_read_event_is_dispatched`
- `it_refreshes_count_when_notification_created_event_is_broadcast`

### NotificationListTest.php
- `renders_successfully_for_logged_in_user`
- `renders_empty_for_guest`
- `loads_initial_notifications_for_user`
- `limits_initial_notifications_to_default_limit`
- `mark_as_read_marks_single_notification_read`
- `mark_all_as_read_marks_all_user_notifications_read`
- `loads_more_notifications_when_requested`
- `it_refreshes_when_notification_created_event_is_broadcast`
- `user_can_delete_their_own_notification`
- `user_cannot_delete_another_users_notification`

### NotificationPreferencesTest.php
- `component_renders_successfully`
- `it_loads_manageable_notification_types`
- `it_loads_existing_preferences_correctly`
- `updating_a_preference_saves_it_to_database`

### PitchFilePlayerTest.php
- `renders_successfully`

### ProfileEditFormTest.php
- `renders_successfully`

### ProjectCardTest.php
- `renders_successfully`

### ProjectListItemTest.php
- `renders_successfully`

### ProjectMixesTest.php
- `renders_successfully`

### ProjectTracksTest.php
- `renders_successfully`

## Feature/Livewire/Pitch

### CompletePitchTest.php
- `renders_successfully`

### PaymentDetailsTest.php
- `renders_successfully`

## Feature/Livewire/Pitch/Component

### PitchHistoryTest.php
- `renders_successfully`

### UpdatePitchStatusTest.php
- `renders_successfully`

## Feature/Livewire/Pitch/Snapshot

### ShowSnapshotTest.php
- `renders_successfully`

## Feature/Livewire/Forms

### ProjectFormTest.php
- `form_object_can_be_instantiated_in_parent_component`
- `can_initialize_project_form`
- `can_fill_form_from_project_model`
- `can_map_collaboration_types_to_booleans`

---

## Laravel Dusk Implementation Plan

This plan outlines the steps to integrate Laravel Dusk for browser-level testing into the MixPitch application.

### Phase 1: Setup and Configuration

1.  **Install Laravel Dusk:**
    *   Add `laravel/dusk` to the `require-dev` section in `composer.json`.
    *   Run `composer update laravel/dusk`.
    *   Run `php artisan dusk:install` to install Dusk scaffolding.
2.  **Configure Environment:**
    *   Ensure `.env.dusk.local` (or `.env.testing`) has the correct `APP_URL` set to the local development server URL (e.g., `http://localhost:8000` or `http://mixpitch.test`).
    *   Verify database configuration for Dusk tests (can use the same `testing` connection or a separate one).
    *   Install the appropriate ChromeDriver for the local Chrome/Chromium version (`php artisan dusk:chrome-driver --detect`).
3.  **Create Base Dusk Test Case:**
    *   Review the generated `tests/DuskTestCase.php`.
    *   Customize if necessary (e.g., base URL, screen size).

### Phase 2: Initial Test Implementation

1.  **Create First Test:**
    *   Generate a simple test case: `php artisan dusk:make LoginTest`.
    *   Implement a basic test to verify the login page loads and elements are present.
    *   Implement a test for successful user login.
    *   Implement a test for failed user login.
2.  **Run Dusk Tests:**
    *   Start the development server (if not already running): `php artisan serve` or Sail/Valet.
    *   Run the Dusk tests: `php artisan dusk`.
    *   Debug any initial failures related to environment, selectors, or ChromeDriver.

### Phase 3: Expanding Test Coverage

1.  **Identify Key User Flows:**
    *   List critical user interactions to test (e.g., registration, project creation, file upload, pitch submission, commenting, status changes, pitch completion).
2.  **Develop Core Feature Tests:**
    *   Create Dusk tests for the identified key flows.
    *   Focus on testing core functionality and happy paths first.
    *   Utilize Dusk's assertions (`assertSee`, `assertPathIs`, `assertAuthenticated`, etc.) and interaction methods (`type`, `click`, `select`, etc.).
    *   Use Page Objects or Components for reusable UI elements and actions.
3.  **Refine Selectors:**
    *   Add `dusk` attributes to HTML elements for more robust and maintainable selectors (e.g., `<button dusk="login-button">Login</button>`).

### Phase 4: Integration and CI

1.  **Integrate with Existing Test Suite:**
    *   Consider how Dusk tests fit into the overall testing workflow (e.g., run separately or as part of a full suite).
2.  **Continuous Integration (CI):**
    *   Configure the CI environment (e.g., GitHub Actions, GitLab CI) to run Dusk tests.
    *   This often involves setting up a headless browser environment and managing ChromeDriver within the CI pipeline.

### Phase 5: Maintenance and Optimization

1.  **Regular Updates:** Keep Dusk and ChromeDriver updated.
2.  **Refactoring:** Refactor tests for clarity and maintainability as the application evolves.
3.  **Performance:** Optimize tests for speed where possible (though Dusk tests are inherently slower than Feature/Unit tests).

---

*This document is a work in progress and will be expanded to include all remaining test files and directories in the MixPitch codebase.*
