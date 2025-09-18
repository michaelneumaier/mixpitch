<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BrandingSettingsController extends Controller
{
    public function edit()
    {
        $user = Auth::user();

        return view('settings/branding', compact('user'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'brand_logo_url' => ['nullable', 'url'],
            'brand_logo_file' => ['nullable', 'mimes:jpg,jpeg,png,webp,svg', 'max:4096'],
            'remove_logo' => ['nullable', 'boolean'],
            'brand_primary' => ['nullable', 'regex:/^#?[0-9a-fA-F]{3,6}$/'],
            'brand_secondary' => ['nullable', 'regex:/^#?[0-9a-fA-F]{3,6}$/'],
            'brand_text' => ['nullable', 'regex:/^#?[0-9a-fA-F]{3,6}$/'],
            'invite_email_subject' => ['nullable', 'string', 'max:255'],
            'invite_email_body' => ['nullable', 'string', 'max:5000'],
        ]);

        $user = User::query()->findOrFail(Auth::id());
        $payload = $request->only(['brand_logo_url', 'brand_primary', 'brand_secondary', 'brand_text', 'invite_email_subject', 'invite_email_body']);
        // Ensure colors have leading '#'
        foreach (['brand_primary', 'brand_secondary', 'brand_text'] as $key) {
            if (! empty($payload[$key]) && $payload[$key][0] !== '#') {
                $payload[$key] = '#'.$payload[$key];
            }
        }
        // Handle logo removal
        if ($request->boolean('remove_logo')) {
            $payload['brand_logo_url'] = null;
        }

        // Handle logo upload (stores public URL)
        if ($request->hasFile('brand_logo_file')) {
            $path = $request->file('brand_logo_file')->store('branding/logos', 'public');
            $payload['brand_logo_url'] = asset('storage/'.$path);
        }

        $user->update($payload);

        return back()->with('success', 'Branding settings saved.');
    }
}
