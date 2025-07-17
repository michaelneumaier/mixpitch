<?php

namespace App\Console\Commands;

use App\Mail\TestMail;
use App\Services\EmailService;
use Illuminate\Console\Command;

class SendTestEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:test {email? : The email address to send the test to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test email using the configured mail driver';

    /**
     * Execute the console command.
     */
    public function handle(EmailService $emailService)
    {
        $email = $this->argument('email') ?: $this->ask('What email address should receive the test message?');

        $this->info("Sending test email to {$email}...");

        try {
            $result = $emailService->send(new TestMail, $email, 'test');

            if ($result) {
                $this->info('Test email sent successfully!');
            } else {
                $this->warn('Email was not sent (possibly due to suppression list)');
            }
        } catch (\Exception $e) {
            $this->error('Error sending test email: '.$e->getMessage());
            $this->line('Trace:');
            $this->line($e->getTraceAsString());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
