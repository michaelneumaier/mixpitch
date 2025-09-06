<?php

namespace App\Http\Controllers\Api\Zapier;

use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BulkUpdateClientsAction extends ZapierApiController
{
    /**
     * Bulk Update Clients Action
     *
     * This action allows updating multiple clients at once based on:
     * - Client IDs (specific list)
     * - Filters (status, tags, spending thresholds, etc.)
     * - Tag-based selection
     *
     * Useful for:
     * - Mass status updates (active/inactive)
     * - Bulk tag management
     * - Batch contact updates
     * - Client segmentation updates
     */
    public function update(Request $request): JsonResponse
    {
        if (! $this->checkZapierEnabled()) {
            return $this->errorResponse('Zapier integration is not enabled', 403);
        }

        if (! $this->checkZapierToken()) {
            return $this->errorResponse('Invalid Zapier token', 403);
        }

        $user = $request->user();

        // Validate input
        $validated = $request->validate([
            // Selection criteria (at least one required)
            'client_ids' => ['nullable', 'array', 'max:100'],
            'client_ids.*' => ['integer'],
            'client_emails' => ['nullable', 'array', 'max:100'],
            'client_emails.*' => ['email'],

            // Filter-based selection
            'filter_by_status' => ['nullable', 'string', Rule::in([Client::STATUS_ACTIVE, Client::STATUS_INACTIVE, Client::STATUS_PROSPECT])],
            'filter_by_tags' => ['nullable', 'array', 'max:20'],
            'filter_by_tags.*' => ['string', 'max:50'],
            'filter_minimum_spent' => ['nullable', 'numeric', 'min:0'],
            'filter_maximum_spent' => ['nullable', 'numeric', 'min:0'],
            'filter_minimum_projects' => ['nullable', 'integer', 'min:0'],
            'filter_created_after' => ['nullable', 'date'],
            'filter_created_before' => ['nullable', 'date'],
            'filter_last_contacted_before' => ['nullable', 'date'],

            // Updates to apply
            'update_status' => ['nullable', 'string', Rule::in([Client::STATUS_ACTIVE, Client::STATUS_INACTIVE, Client::STATUS_PROSPECT])],
            'update_tags' => ['nullable', 'array', 'max:20'],
            'update_tags.*' => ['string', 'max:50'],
            'tag_operation' => ['nullable', 'string', Rule::in(['replace', 'append', 'remove'])],
            'update_company' => ['nullable', 'string', 'max:255'],
            'update_phone' => ['nullable', 'string', 'max:50'],
            'mark_as_contacted' => ['nullable', 'boolean'],

            // Batch settings
            'dry_run' => ['nullable', 'boolean'],
            'max_updates' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        // Build the query to find clients to update
        $query = Client::where('user_id', $user->id);

        // Apply ID-based selection
        if (! empty($validated['client_ids']) || ! empty($validated['client_emails'])) {
            $query->where(function ($q) use ($validated) {
                if (! empty($validated['client_ids'])) {
                    $q->orWhereIn('id', $validated['client_ids']);
                }
                if (! empty($validated['client_emails'])) {
                    $q->orWhereIn('email', $validated['client_emails']);
                }
            });
        } else {
            // Apply filter-based selection
            if (isset($validated['filter_by_status'])) {
                $query->where('status', $validated['filter_by_status']);
            }

            if (! empty($validated['filter_by_tags'])) {
                $query->where(function ($q) use ($validated) {
                    foreach ($validated['filter_by_tags'] as $tag) {
                        $q->orWhereJsonContains('tags', $tag);
                    }
                });
            }

            if (isset($validated['filter_minimum_spent'])) {
                $query->where('total_spent', '>=', $validated['filter_minimum_spent']);
            }

            if (isset($validated['filter_maximum_spent'])) {
                $query->where('total_spent', '<=', $validated['filter_maximum_spent']);
            }

            if (isset($validated['filter_minimum_projects'])) {
                $query->where('total_projects', '>=', $validated['filter_minimum_projects']);
            }

            if (isset($validated['filter_created_after'])) {
                $query->where('created_at', '>=', Carbon::parse($validated['filter_created_after']));
            }

            if (isset($validated['filter_created_before'])) {
                $query->where('created_at', '<=', Carbon::parse($validated['filter_created_before']));
            }

            if (isset($validated['filter_last_contacted_before'])) {
                $query->where(function ($q) use ($validated) {
                    $q->where('last_contacted_at', '<=', Carbon::parse($validated['filter_last_contacted_before']))
                        ->orWhereNull('last_contacted_at');
                });
            }
        }

        // Apply limit
        $maxUpdates = $validated['max_updates'] ?? 50;
        $clients = $query->limit($maxUpdates)->get();

        if ($clients->isEmpty()) {
            return $this->errorResponse('No clients found matching the criteria', 404);
        }

        // Dry run - return what would be updated without making changes
        if ($request->boolean('dry_run', false)) {
            return $this->successResponse([
                'dry_run' => true,
                'clients_found' => $clients->count(),
                'clients_preview' => $clients->take(10)->map(function ($client) {
                    return [
                        'id' => $client->id,
                        'email' => $client->email,
                        'name' => $client->name,
                        'current_status' => $client->status,
                        'current_tags' => $client->tags ?? [],
                        'total_spent' => floatval($client->total_spent ?? 0),
                        'total_projects' => $client->total_projects ?? 0,
                    ];
                })->toArray(),
                'updates_to_apply' => $this->buildUpdateSummary($validated),
                'max_updates_reached' => $clients->count() >= $maxUpdates,
            ], 'Dry run completed - no changes made');
        }

        // Prepare update data
        $updateData = [];
        $updateSummary = [];

        if (isset($validated['update_status'])) {
            $updateData['status'] = $validated['update_status'];
            $updateSummary['status'] = $validated['update_status'];
        }

        if (isset($validated['update_company'])) {
            $updateData['company'] = $validated['update_company'];
            $updateSummary['company'] = $validated['update_company'];
        }

        if (isset($validated['update_phone'])) {
            $updateData['phone'] = $validated['update_phone'];
            $updateSummary['phone'] = $validated['update_phone'];
        }

        if ($request->boolean('mark_as_contacted', false)) {
            $updateData['last_contacted_at'] = now();
            $updateSummary['marked_as_contacted'] = true;
        }

        $updatedClients = [];

        // Process each client
        foreach ($clients as $client) {
            $clientUpdateData = $updateData;

            // Handle tag operations
            if (! empty($validated['update_tags'])) {
                $newTags = $validated['update_tags'];
                $currentTags = $client->tags ?? [];

                switch ($validated['tag_operation'] ?? 'replace') {
                    case 'append':
                        $clientUpdateData['tags'] = array_values(array_unique(array_merge($currentTags, $newTags)));
                        break;
                    case 'remove':
                        $clientUpdateData['tags'] = array_values(array_diff($currentTags, $newTags));
                        break;
                    case 'replace':
                    default:
                        $clientUpdateData['tags'] = $newTags;
                        break;
                }

                $updateSummary['tags_operation'] = $validated['tag_operation'] ?? 'replace';
                $updateSummary['tags_applied'] = $newTags;
            }

            // Apply updates
            $client->update($clientUpdateData);

            $updatedClients[] = [
                'id' => $client->id,
                'email' => $client->email,
                'name' => $client->name,
                'status' => $client->status,
                'company' => $client->company,
                'phone' => $client->phone,
                'tags' => $client->tags ?? [],
                'total_spent' => floatval($client->total_spent ?? 0),
                'total_projects' => $client->total_projects ?? 0,
                'last_contacted_at' => $client->last_contacted_at?->toIso8601String(),
                'updated_at' => $client->updated_at->toIso8601String(),
            ];
        }

        // Log the action
        if (config('zapier.log_usage')) {
            \App\Models\ZapierUsageLog::create([
                'user_id' => $user->id,
                'endpoint' => 'actions.clients.bulk_update',
                'request_data' => $this->sanitizeRequestData($request->all()),
                'response_count' => count($updatedClients),
                'status_code' => 200,
            ]);
        }

        return $this->successResponse([
            'updated_count' => count($updatedClients),
            'max_updates_reached' => $clients->count() >= $maxUpdates,
            'updates_applied' => $updateSummary,
            'clients' => $updatedClients,
            'batch_id' => 'bulk_'.now()->format('YmdHis').'_'.$user->id,
            'processed_at' => now()->toIso8601String(),
        ], "Successfully updated {$clients->count()} clients");
    }

    /**
     * Build a summary of what updates will be applied
     */
    private function buildUpdateSummary(array $validated): array
    {
        $summary = [];

        if (isset($validated['update_status'])) {
            $summary['status_change'] = $validated['update_status'];
        }

        if (! empty($validated['update_tags'])) {
            $summary['tags_operation'] = $validated['tag_operation'] ?? 'replace';
            $summary['tags'] = $validated['update_tags'];
        }

        if (isset($validated['update_company'])) {
            $summary['company_update'] = $validated['update_company'];
        }

        if (isset($validated['update_phone'])) {
            $summary['phone_update'] = $validated['update_phone'];
        }

        if (isset($validated['mark_as_contacted']) && $validated['mark_as_contacted']) {
            $summary['mark_as_contacted'] = true;
        }

        return $summary;
    }
}
