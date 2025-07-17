<?php

namespace App\Filament\Resources\EmailTestResource\Pages;

use App\Filament\Resources\EmailTestResource;
use App\Services\EmailService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateEmailTest extends CreateRecord
{
    protected static string $resource = EmailTestResource::class;

    protected function afterCreate(): void
    {
        $record = $this->record;

        // Automatically send the test email after creation
        if ($record->status === 'pending') {
            try {
                $emailService = app(EmailService::class);

                // Send the test email
                $result = $emailService->sendTestEmail(
                    $record->recipient_email,
                    $record->subject,
                    $record->template,
                    $record->content_variables ?? []
                );

                // Update the record
                $record->update([
                    'status' => 'sent',
                    'result' => $result,
                    'sent_at' => now(),
                ]);

                Notification::make()
                    ->title('Test email sent successfully')
                    ->success()
                    ->send();

            } catch (\Exception $e) {
                // Update the record with failure
                $record->update([
                    'status' => 'failed',
                    'result' => ['error' => $e->getMessage()],
                ]);

                Notification::make()
                    ->title('Failed to send test email')
                    ->body($e->getMessage())
                    ->danger()
                    ->send();
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
