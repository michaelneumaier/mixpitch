<?php

namespace App\Http\Controllers;

use App\Mail\TestMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class EmailController extends Controller
{
    /**
     * Send a test email
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function sendTest(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        try {
            Mail::to($request->email)->send(new TestMail());
            return back()->with('success', 'Test email has been sent successfully!');
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
