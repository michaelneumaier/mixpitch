<?php

namespace App\Livewire\Project;

use Livewire\Component;
use App\Models\Project;
use App\Models\LicenseSignature;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;

class LicenseSignatureManager extends Component
{
    public Project $project;
    public $showInviteModal = false;
    public $inviteEmail = '';
    public $inviteMessage = '';

    protected $rules = [
        'inviteEmail' => 'required|email',
        'inviteMessage' => 'nullable|string|max:500',
    ];

    protected $listeners = [
        'send-license-reminders' => 'sendReminders',
        'refresh-signatures' => '$refresh',
    ];

    public function mount(Project $project)
    {
        $this->project = $project;
    }

    public function getLicenseSignaturesProperty()
    {
        return $this->project->licenseSignatures()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getPendingSignaturesProperty()
    {
        return $this->licenseSignatures->where('status', 'pending');
    }

    public function getSignedSignaturesProperty()
    {
        return $this->licenseSignatures->where('status', 'signed');
    }

    public function sendReminders()
    {
        $this->authorize('update', $this->project);

        $pendingSignatures = $this->pendingSignatures;
        
        if ($pendingSignatures->isEmpty()) {
            session()->flash('error', 'No pending signatures to send reminders for.');
            return;
        }

        $reminderCount = 0;
        foreach ($pendingSignatures as $signature) {
            if ($signature->user && $signature->user->email) {
                try {
                    // Send reminder email
                    Mail::to($signature->user->email)->send(
                        new \App\Mail\LicenseAgreementReminder($signature)
                    );
                    
                    // Update last reminder sent
                    $signature->update([
                        'last_reminder_sent' => now(),
                        'reminder_count' => ($signature->reminder_count ?? 0) + 1
                    ]);
                    
                    $reminderCount++;
                } catch (\Exception $e) {
                    \Log::error('Failed to send license reminder: ' . $e->getMessage());
                }
            }
        }

        if ($reminderCount > 0) {
            session()->flash('success', "Sent {$reminderCount} reminder(s) successfully.");
        } else {
            session()->flash('error', 'Failed to send reminders. Please try again.');
        }

        $this->dispatch('refresh-signatures');
    }

    public function inviteCollaborator()
    {
        $this->validate();

        $this->authorize('update', $this->project);

        // Check if user exists
        $user = User::where('email', $this->inviteEmail)->first();
        
        if (!$user) {
            session()->flash('error', 'User with this email does not exist on the platform.');
            return;
        }

        // Check if signature already exists
        $existingSignature = LicenseSignature::where('project_id', $this->project->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingSignature) {
            session()->flash('error', 'This user already has a license agreement for this project.');
            return;
        }

        // Create license signature request
        $signature = LicenseSignature::create([
            'project_id' => $this->project->id,
            'user_id' => $user->id,
            'license_template_id' => $this->project->license_template_id,
            'status' => 'pending',
            'invited_by' => Auth::id(),
            'invitation_message' => $this->inviteMessage,
        ]);

        try {
            // Send invitation email
            Mail::to($user->email)->send(
                new \App\Mail\LicenseAgreementInvitation($signature)
            );

            session()->flash('success', "License agreement invitation sent to {$user->name}.");
            
            // Reset form
            $this->reset(['inviteEmail', 'inviteMessage', 'showInviteModal']);
            
        } catch (\Exception $e) {
            // Delete the signature if email failed
            $signature->delete();
            session()->flash('error', 'Failed to send invitation email. Please try again.');
            \Log::error('Failed to send license invitation: ' . $e->getMessage());
        }

        $this->dispatch('refresh-signatures');
    }

    public function resendInvitation($signatureId)
    {
        $this->authorize('update', $this->project);

        $signature = LicenseSignature::findOrFail($signatureId);
        
        if ($signature->project_id !== $this->project->id) {
            session()->flash('error', 'Invalid signature.');
            return;
        }

        if ($signature->status !== 'pending') {
            session()->flash('error', 'Can only resend invitations for pending signatures.');
            return;
        }

        try {
            Mail::to($signature->user->email)->send(
                new \App\Mail\LicenseAgreementInvitation($signature)
            );

            $signature->update([
                'last_reminder_sent' => now(),
                'reminder_count' => ($signature->reminder_count ?? 0) + 1
            ]);

            session()->flash('success', "Invitation resent to {$signature->user->name}.");
            
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to resend invitation.');
            \Log::error('Failed to resend license invitation: ' . $e->getMessage());
        }

        $this->dispatch('refresh-signatures');
    }

    public function revokeSignature($signatureId)
    {
        $this->authorize('update', $this->project);

        $signature = LicenseSignature::findOrFail($signatureId);
        
        if ($signature->project_id !== $this->project->id) {
            session()->flash('error', 'Invalid signature.');
            return;
        }

        $userName = $signature->user ? $signature->user->name : 'Unknown User';
        $signature->delete();

        session()->flash('success', "License agreement revoked for {$userName}.");
        $this->dispatch('refresh-signatures');
    }

    public function openInviteModal()
    {
        $this->showInviteModal = true;
    }

    public function closeInviteModal()
    {
        $this->showInviteModal = false;
        $this->reset(['inviteEmail', 'inviteMessage']);
    }

    public function render()
    {
        return view('livewire.project.license-signature-manager');
    }
} 