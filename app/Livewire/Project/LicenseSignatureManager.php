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

    protected $listeners = [
        'refresh-signatures' => '$refresh',
    ];

    public function mount(Project $project)
    {
        $this->project = $project;
    }

    public function getLicenseSignaturesProperty()
    {
        // Get all active license signatures for backward compatibility
        return $this->project->licenseSignatures()
            ->with('user')
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getSignedSignaturesProperty()
    {
        // Return active signatures (same as licenseSignatures for new flow)
        return $this->licenseSignatures;
    }

    public function getPendingSignaturesProperty()
    {
        // Return empty collection since we no longer use pending signatures
        return collect();
    }

    public function getLicenseTemplateProperty()
    {
        return $this->project->licenseTemplate;
    }

    public function getRequiresAgreementProperty()
    {
        return $this->project->requiresLicenseAgreement();
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
        
        // Instead of deleting, set status to revoked for audit purposes
        $signature->update([
            'status' => 'revoked',
            'revoked_at' => now(),
            'revoked_by' => auth()->id(),
        ]);

        session()->flash('success', "License agreement revoked for {$userName}.");
        $this->dispatch('refresh-signatures');
    }

    public function render()
    {
        return view('livewire.project.license-signature-manager');
    }
} 