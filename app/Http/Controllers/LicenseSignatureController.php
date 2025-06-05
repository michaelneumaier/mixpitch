<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LicenseSignature;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class LicenseSignatureController extends Controller
{
    /**
     * Show the license agreement for signing
     */
    public function show(LicenseSignature $signature)
    {
        // Verify the user can access this signature
        if ($signature->user_id !== Auth::id()) {
            abort(403, 'You are not authorized to view this license agreement.');
        }

        // Check if already signed
        if ($signature->status === 'signed') {
            return redirect()->route('projects.show', $signature->project)
                ->with('info', 'You have already signed this license agreement.');
        }

        // Load relationships
        $signature->load(['project', 'licenseTemplate', 'user']);

        return view('license.sign', compact('signature'));
    }

    /**
     * Process the license signature
     */
    public function sign(Request $request, LicenseSignature $signature)
    {
        // Verify the user can sign this
        if ($signature->user_id !== Auth::id()) {
            abort(403, 'You are not authorized to sign this license agreement.');
        }

        // Check if already signed
        if ($signature->status === 'signed') {
            return redirect()->route('projects.show', $signature->project)
                ->with('info', 'You have already signed this license agreement.');
        }

        $request->validate([
            'agreement_accepted' => 'required|accepted',
            'digital_signature' => 'required|string|min:2|max:100',
        ]);

        // Update the signature
        $signature->update([
            'status' => 'signed',
            'signed_at' => now(),
            'digital_signature' => $request->digital_signature,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Redirect to project with success message
        return redirect()->route('projects.show', $signature->project)
            ->with('success', 'License agreement signed successfully! You can now participate in this project.');
    }
} 