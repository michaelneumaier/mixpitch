# Areas for Further Research

*   Investigate the usage of `Spatie\Permission\Traits\HasRoles` in the `User` model versus the overridden `hasRole()` method checking the `role` column. Determine the primary mechanism for role/permission checking.
*   Determine the current role and usage of the `Track` model (`app/Models/Track.php`), as it appears less functional than other file-related models.
*   Review the file storage mechanism in `MixController` (`Storage::disk('public')`). Is this intentional and secure for mix files, compared to the S3/signed URL approach used elsewhere?
*   Implement proper AWS SNS signature verification in `SesWebhookController::verifyRequest()` for production security.
*   Verify if the `email_valid` column exists on the `users` table and if the logic in `SesWebhookController::handleBounce()` to update it is desired/needed.
*   Review `PitchService`. The `changePitchStatus` method seems incorrect/redundant, and `deletePitch` is incomplete (lacks file cleanup). Determine its intended role vs. `PitchWorkflowService` and `FileManagementService`.
*   Investigate the dual notification approach in `NotificationService`. Why does `notifyPitchSubmitted` use both `createNotification` (DB record + event) and `$user->notify()` (Laravel's system)? Is this intentional? Are other Laravel Notifications used elsewhere (`app/Notifications/`)?
*   *(List specific areas requiring deeper investigation here)* 