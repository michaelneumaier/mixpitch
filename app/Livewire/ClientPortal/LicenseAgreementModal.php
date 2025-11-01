<?php

namespace App\Livewire\ClientPortal;

use App\Models\LicenseSignature;
use App\Models\Project;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class LicenseAgreementModal extends Component
{
    public Project $project;

    public bool $showModal = false;

    public bool $agreed = false;

    public bool $isProcessing = false;

    public ?string $errorMessage = null;

    protected $listeners = ['open-license-modal' => 'openModal'];

    public function mount()
    {
        // Check if project requires license agreement
        if (! $this->project->requires_license_agreement) {
            return;
        }

        // Check if project has a license template
        if (! $this->project->license_template_id) {
            Log::warning('Project requires license agreement but no license template set', [
                'project_id' => $this->project->id,
            ]);

            return;
        }

        // Determine client identifier
        $user = auth()->check() ? auth()->user() : null;
        $clientEmail = $user ? null : $this->project->client_email;

        // Check if client has already signed
        if (LicenseSignature::hasClientSigned($this->project, $user, $clientEmail)) {
            Log::info('Client has already signed license agreement', [
                'project_id' => $this->project->id,
                'user_id' => $user?->id,
                'client_email' => $clientEmail,
            ]);

            return;
        }

        // Show modal (unless postponed - handled by Alpine in the view)
        $this->showModal = true;

        Log::info('License agreement modal will be shown to client', [
            'project_id' => $this->project->id,
            'user_id' => $user?->id,
            'client_email' => $clientEmail,
            'requires_agreement' => $this->project->requires_license_agreement,
        ]);
    }

    public function signAgreement()
    {
        $this->errorMessage = null;
        $this->isProcessing = true;

        try {
            // Validate agreement is checked
            if (! $this->agreed) {
                $this->errorMessage = 'You must agree to the license terms to continue.';
                $this->isProcessing = false;

                return;
            }

            // Determine client identifier
            $user = auth()->check() ? auth()->user() : null;
            $clientEmail = $user ? null : $this->project->client_email;

            // Create license signature
            LicenseSignature::createForClient($this->project, $user, $clientEmail);

            Log::info('Client signed license agreement via portal', [
                'project_id' => $this->project->id,
                'user_id' => $user?->id,
                'client_email' => $clientEmail,
            ]);

            // Close modal, notify success, and reload page
            $this->showModal = false;
            $this->dispatch('license-signed');

            // Redirect to current URL to refresh the page and show updated status
            return redirect()->to(request()->url());

        } catch (\Exception $e) {
            Log::error('Failed to create client license signature', [
                'project_id' => $this->project->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->errorMessage = 'Unable to sign license agreement. Please try again.';

            // If already signed, just close the modal
            if (str_contains($e->getMessage(), 'already been signed')) {
                $this->showModal = false;
            }
        } finally {
            $this->isProcessing = false;
        }
    }

    public function postpone()
    {
        Log::info('Client postponed license agreement review', [
            'project_id' => $this->project->id,
        ]);

        // Dispatch event to Alpine to set sessionStorage
        $this->dispatch('postpone-license', projectId: $this->project->id);

        // Close modal
        $this->showModal = false;
    }

    public function openModal()
    {
        Log::info('Client manually opened license agreement modal', [
            'project_id' => $this->project->id,
        ]);

        // Clear any errors from previous attempt
        $this->errorMessage = null;
        $this->agreed = false;

        // Open modal
        $this->showModal = true;
    }

    public function render()
    {
        // Eager load license template for display
        $this->project->load('licenseTemplate');

        return view('livewire.client-portal.license-agreement-modal');
    }
}
