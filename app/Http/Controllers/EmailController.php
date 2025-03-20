<?php

namespace App\Http\Controllers;

use App\Mail\TestMail;
use App\Services\EmailService;
use Illuminate\Http\Request;

class EmailController extends Controller
{
    /**
     * Send a test email
     *
     * @param Request $request
     * @param EmailService $emailService
     * @return \Illuminate\Http\Response
     */
    public function sendTest(Request $request, EmailService $emailService)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        try {
            $result = $emailService->send(new TestMail(), $request->email, 'test');
            
            if ($result) {
                return back()->with('success', 'Test email has been sent successfully!');
            } else {
                return back()->with('warning', 'Email was not sent (possibly due to suppression list).')
                            ->withInput();
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Error sending test email: ' . $e->getMessage())
                         ->withInput();
        }
    }

    /**
     * Show the test email form
     *
     * @return \Illuminate\Http\Response
     */
    public function showTestForm()
    {
        return view('emails.test-form');
    }
}
