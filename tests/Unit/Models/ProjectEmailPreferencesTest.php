<?php

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    // Create a client management project for testing
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create([
        'user_id' => $this->user->id,
        'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
        'client_email' => 'client@example.com',
        'client_name' => 'Test Client',
    ]);
});

describe('getDefaultEmailPreferences', function () {
    it('returns correct default preference structure', function () {
        $defaults = $this->project->getDefaultEmailPreferences();

        expect($defaults)->toBeArray()
            ->toHaveKeys([
                'revision_confirmation',
                'producer_resubmitted',
                'producer_revisions_requested',
                'producer_client_commented',
                'payment_receipt',
                'payment_received',
            ])
            ->and($defaults['revision_confirmation'])->toBeTrue()
            ->and($defaults['producer_resubmitted'])->toBeTrue()
            ->and($defaults['producer_revisions_requested'])->toBeTrue()
            ->and($defaults['producer_client_commented'])->toBeTrue()
            ->and($defaults['payment_receipt'])->toBeTrue()
            ->and($defaults['payment_received'])->toBeTrue();
    });
});

describe('shouldSendProducerEmail', function () {
    it('returns false when global config disables email type', function () {
        Config::set('business.email_notifications.client_management.producer_revisions_requested', false);

        $result = $this->project->shouldSendProducerEmail('producer_revisions_requested');

        expect($result)->toBeFalse();
    });

    it('returns true when global config enables and no project preference set', function () {
        Config::set('business.email_notifications.client_management.producer_revisions_requested', true);

        $result = $this->project->shouldSendProducerEmail('producer_revisions_requested');

        expect($result)->toBeTrue();
    });

    it('respects project preference when global config enabled', function () {
        Config::set('business.email_notifications.client_management.producer_revisions_requested', true);
        $this->project->producer_email_preferences = [
            'producer_revisions_requested' => false,
            'producer_client_commented' => true,
            'payment_received' => true,
        ];
        $this->project->save();

        expect($this->project->shouldSendProducerEmail('producer_revisions_requested'))->toBeFalse()
            ->and($this->project->shouldSendProducerEmail('producer_client_commented'))->toBeTrue()
            ->and($this->project->shouldSendProducerEmail('payment_received'))->toBeTrue();
    });

    it('defaults to true for unknown preference types', function () {
        $result = $this->project->shouldSendProducerEmail('unknown_type');

        expect($result)->toBeTrue();
    });

    it('global config overrides project preference', function () {
        Config::set('business.email_notifications.client_management.producer_revisions_requested', false);
        $this->project->producer_email_preferences = [
            'producer_revisions_requested' => true, // Project wants it enabled
        ];
        $this->project->save();

        $result = $this->project->shouldSendProducerEmail('producer_revisions_requested');

        expect($result)->toBeFalse(); // But global config wins
    });
});

describe('shouldSendClientEmail', function () {
    it('returns false when global config disables email type', function () {
        Config::set('business.email_notifications.client_management.revision_confirmation', false);

        $result = $this->project->shouldSendClientEmail('revision_confirmation');

        expect($result)->toBeFalse();
    });

    it('returns true when global config enables and no project preference set', function () {
        Config::set('business.email_notifications.client_management.revision_confirmation', true);

        $result = $this->project->shouldSendClientEmail('revision_confirmation');

        expect($result)->toBeTrue();
    });

    it('respects project preference when global config enabled', function () {
        Config::set('business.email_notifications.client_management.revision_confirmation', true);
        $this->project->client_email_preferences = [
            'revision_confirmation' => false,
            'producer_resubmitted' => true,
            'payment_receipt' => true,
        ];
        $this->project->save();

        expect($this->project->shouldSendClientEmail('revision_confirmation'))->toBeFalse()
            ->and($this->project->shouldSendClientEmail('producer_resubmitted'))->toBeTrue()
            ->and($this->project->shouldSendClientEmail('payment_receipt'))->toBeTrue();
    });

    it('defaults to true for unknown preference types', function () {
        $result = $this->project->shouldSendClientEmail('unknown_type');

        expect($result)->toBeTrue();
    });

    it('global config overrides project preference', function () {
        Config::set('business.email_notifications.client_management.revision_confirmation', false);
        $this->project->client_email_preferences = [
            'revision_confirmation' => true, // Project wants it enabled
        ];
        $this->project->save();

        $result = $this->project->shouldSendClientEmail('revision_confirmation');

        expect($result)->toBeFalse(); // But global config wins
    });
});

describe('updateProducerEmailPreference', function () {
    it('updates preference and saves to database', function () {
        $this->project->updateProducerEmailPreference('producer_revisions_requested', false);

        $this->project->refresh();

        expect($this->project->producer_email_preferences)
            ->toBeArray()
            ->toHaveKey('producer_revisions_requested')
            ->and($this->project->producer_email_preferences['producer_revisions_requested'])->toBeFalse();
    });

    it('logs preference update', function () {
        Log::shouldReceive('info')
            ->once()
            ->with('Producer email preference updated', [
                'project_id' => $this->project->id,
                'type' => 'producer_revisions_requested',
                'enabled' => false,
            ]);

        $this->project->updateProducerEmailPreference('producer_revisions_requested', false);
    });

    it('initializes preferences array if null', function () {
        $this->project->producer_email_preferences = null;
        $this->project->save();

        $this->project->updateProducerEmailPreference('producer_revisions_requested', false);

        $this->project->refresh();

        expect($this->project->producer_email_preferences)->toBeArray()
            ->toHaveCount(6) // All default keys should be present
            ->and($this->project->producer_email_preferences['producer_revisions_requested'])->toBeFalse();
    });

    it('preserves existing preferences when updating one', function () {
        $this->project->producer_email_preferences = [
            'producer_revisions_requested' => true,
            'producer_client_commented' => false,
            'payment_received' => true,
        ];
        $this->project->save();

        $this->project->updateProducerEmailPreference('producer_client_commented', true);

        $this->project->refresh();

        expect($this->project->producer_email_preferences['producer_revisions_requested'])->toBeTrue()
            ->and($this->project->producer_email_preferences['producer_client_commented'])->toBeTrue()
            ->and($this->project->producer_email_preferences['payment_received'])->toBeTrue();
    });
});

describe('updateClientEmailPreference', function () {
    it('updates preference and saves to database', function () {
        $this->project->updateClientEmailPreference('revision_confirmation', false);

        $this->project->refresh();

        expect($this->project->client_email_preferences)
            ->toBeArray()
            ->toHaveKey('revision_confirmation')
            ->and($this->project->client_email_preferences['revision_confirmation'])->toBeFalse();
    });

    it('logs preference update', function () {
        Log::shouldReceive('info')
            ->once()
            ->with('Client email preference updated', [
                'project_id' => $this->project->id,
                'type' => 'revision_confirmation',
                'enabled' => false,
            ]);

        $this->project->updateClientEmailPreference('revision_confirmation', false);
    });

    it('initializes preferences array if null', function () {
        $this->project->client_email_preferences = null;
        $this->project->save();

        $this->project->updateClientEmailPreference('revision_confirmation', false);

        $this->project->refresh();

        expect($this->project->client_email_preferences)->toBeArray()
            ->toHaveCount(6) // All default keys should be present
            ->and($this->project->client_email_preferences['revision_confirmation'])->toBeFalse();
    });

    it('preserves existing preferences when updating one', function () {
        $this->project->client_email_preferences = [
            'revision_confirmation' => true,
            'producer_resubmitted' => false,
            'payment_receipt' => true,
        ];
        $this->project->save();

        $this->project->updateClientEmailPreference('producer_resubmitted', true);

        $this->project->refresh();

        expect($this->project->client_email_preferences['revision_confirmation'])->toBeTrue()
            ->and($this->project->client_email_preferences['producer_resubmitted'])->toBeTrue()
            ->and($this->project->client_email_preferences['payment_receipt'])->toBeTrue();
    });
});
