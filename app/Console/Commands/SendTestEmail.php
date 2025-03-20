<?php

namespace App\Console\Commands;

use App\Mail\TestMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

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
    public function handle()
    {
        $email = $this->argument('email') ?: $this->ask('What email address should receive the test message?');

        $this->info("Sending test email to {$email}...");

        try {
            Mail::to($email)->send(new TestMail());
            $this->info('Test email sent successfully!');
        } catch (\Exception $e) {
            $this->error('Error sending test email: ' . $e->getMessage());
            $this->line('Trace:');
            $this->line($e->getTraceAsString());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
