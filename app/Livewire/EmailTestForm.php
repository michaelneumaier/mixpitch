<?php

namespace App\Livewire;

use App\Models\EmailTest;
use App\Services\EmailService;
use Livewire\Component;

class EmailTestForm extends Component
{
    public $email;

    public $subject = 'Test Email from MixPitch';

    public $template = 'emails.test';

    public $variables = [];

    public $variableKey = '';

    public $variableValue = '';

    public $status = null;

    public $message = '';

    protected $rules = [
        'email' => 'required|email',
        'subject' => 'required|string|max:255',
        'template' => 'required|string',
    ];

    public function addVariable()
    {
        if (empty($this->variableKey)) {
            return;
        }

        $this->variables[$this->variableKey] = $this->variableValue;
        $this->variableKey = '';
        $this->variableValue = '';
    }

    public function removeVariable($key)
    {
        if (isset($this->variables[$key])) {
            unset($this->variables[$key]);
        }
    }

    public function sendTest()
    {
        $this->validate();

        try {
            // Create a test record
            $test = EmailTest::create([
                'recipient_email' => $this->email,
                'subject' => $this->subject,
                'template' => $this->template,
                'content_variables' => $this->variables,
                'status' => 'pending',
            ]);

            // Send the test email
            $emailService = app(EmailService::class);
            $result = $emailService->sendTestEmail(
                $this->email,
                $this->subject,
                $this->template,
                $this->variables
            );

            // Update the test record
            $test->update([
                'status' => 'sent',
                'result' => $result,
                'sent_at' => now(),
            ]);

            $this->status = 'success';
            $this->message = 'Test email sent successfully!';

        } catch (\Exception $e) {
            // Update the test record with failure if it exists
            if (isset($test)) {
                $test->update([
                    'status' => 'failed',
                    'result' => ['error' => $e->getMessage()],
                ]);
            }

            $this->status = 'error';
            $this->message = 'Failed to send test email: '.$e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.email-test-form');
    }
}
