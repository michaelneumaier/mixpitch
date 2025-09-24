<?php

namespace App\Livewire;

use App\Services\PayoutAccountManagementService;
use Livewire\Component;

class PayoutProviderSelector extends Component
{
    public $selectedProvider = null;

    public $providers = [];

    public $showSetupModal = false;

    public $setupProvider = null;

    public $setupData = [];

    public $loading = false;

    protected PayoutAccountManagementService $accountService;

    public function boot(PayoutAccountManagementService $accountService)
    {
        $this->accountService = $accountService;
    }

    public function mount()
    {
        $this->loadProviders();
        $this->selectedProvider = auth()->user()->preferred_payout_method ?? 'stripe';
    }

    public function loadProviders()
    {
        $this->providers = $this->accountService->getAvailableProviders(auth()->user());
    }

    public function selectProvider(string $providerName)
    {
        $provider = collect($this->providers)->firstWhere('name', $providerName);

        if (! $provider) {
            $this->addError('provider', 'Invalid provider selected.');

            return;
        }

        if (! $provider['is_ready']) {
            // Need to set up this provider
            $this->setupProvider($providerName);

            return;
        }

        // Switch to this provider
        $this->loading = true;

        $result = $this->accountService->switchPreferredProvider(auth()->user(), $providerName);

        if ($result['success']) {
            $this->selectedProvider = $providerName;
            $this->loadProviders(); // Refresh provider data
            session()->flash('success', "Switched to {$provider['display_name']} successfully!");
            $this->dispatch('provider-switched', $providerName);
        } else {
            $this->addError('provider', $result['error']);
        }

        $this->loading = false;
    }

    public function setupProvider(string $providerName)
    {
        $this->setupProvider = $providerName;
        $this->setupData = [];
        $this->showSetupModal = true;
    }

    public function completeSetup()
    {
        $this->loading = true;

        $result = $this->accountService->setupPayoutAccount(
            auth()->user(),
            $this->setupProvider,
            $this->setupData
        );

        if ($result['success']) {
            $this->showSetupModal = false;
            $this->setupProvider = null;
            $this->setupData = [];
            $this->loadProviders();

            $provider = collect($this->providers)->firstWhere('name', $this->setupProvider);
            session()->flash('success', "Successfully set up {$provider['display_name']}!");

            // Auto-select the newly set up provider if it's ready
            if ($provider['is_ready']) {
                $this->selectedProvider = $this->setupProvider;
                $this->dispatch('provider-switched', $this->setupProvider);
            }
        } else {
            $this->addError('setup', $result['error']);
        }

        $this->loading = false;
    }

    public function cancelSetup()
    {
        $this->showSetupModal = false;
        $this->setupProvider = null;
        $this->setupData = [];
        $this->resetErrorBag();
    }

    public function refreshProviderStatus(string $providerName)
    {
        $this->loading = true;

        $result = $this->accountService->refreshAccountStatus(auth()->user(), $providerName);

        if ($result['success']) {
            $this->loadProviders();
            session()->flash('success', 'Provider status refreshed successfully!');
        } else {
            $this->addError('refresh', $result['error']);
        }

        $this->loading = false;
    }

    public function removeProvider(string $providerName)
    {
        $this->loading = true;

        $result = $this->accountService->removePayoutAccount(auth()->user(), $providerName);

        if ($result['success']) {
            $this->loadProviders();

            // Update selected provider if the removed one was selected
            if ($result['was_preferred']) {
                $this->selectedProvider = $result['new_preferred'];
                $this->dispatch('provider-switched', $this->selectedProvider);
            }

            session()->flash('success', 'Provider removed successfully!');
        } else {
            $this->addError('remove', $result['error']);
        }

        $this->loading = false;
    }

    public function getOnboardingLink(string $providerName)
    {
        $result = $this->accountService->getOnboardingLink(auth()->user(), $providerName);

        if ($result['success'] && $result['url']) {
            return redirect()->away($result['url']);
        }

        // If no URL (like PayPal), just trigger setup
        $this->setupProvider($providerName);
    }

    public function render()
    {
        return view('livewire.payout-provider-selector');
    }
}
