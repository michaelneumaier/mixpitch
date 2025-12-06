<?php

namespace App\Services;

use App\Models\Pitch;
use App\Models\PitchEvent;
use Illuminate\Support\Collection;

class CommunicationExportService
{
    /**
     * Export conversation as JSON
     */
    public function exportAsJson(Pitch $pitch): array
    {
        $messages = $this->getExportableMessages($pitch);
        $project = $pitch->project;

        return [
            'export_date' => now()->toISOString(),
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'client_name' => $project->client_name,
                'client_email' => $project->client_email,
            ],
            'producer' => [
                'id' => $pitch->user?->id,
                'name' => $pitch->user?->name,
                'email' => $pitch->user?->email,
            ],
            'messages' => $messages->map(function ($message) {
                return [
                    'id' => $message->id,
                    'type' => $message->event_type,
                    'sender' => $this->getSenderInfo($message),
                    'content' => $message->comment,
                    'is_urgent' => $message->is_urgent,
                    'created_at' => $message->created_at->toISOString(),
                    'read_at' => $message->read_at?->toISOString(),
                ];
            })->values()->toArray(),
            'total_messages' => $messages->count(),
        ];
    }

    /**
     * Get data for printable HTML export
     */
    public function getExportData(Pitch $pitch): array
    {
        $messages = $this->getExportableMessages($pitch);
        $project = $pitch->project;

        return [
            'project' => $project,
            'pitch' => $pitch,
            'producer' => $pitch->user,
            'messages' => $messages,
            'exportDate' => now(),
        ];
    }

    /**
     * Get messages suitable for export
     */
    protected function getExportableMessages(Pitch $pitch): Collection
    {
        return $pitch->events()
            ->messages()
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Get sender information for a message
     */
    protected function getSenderInfo(PitchEvent $message): array
    {
        if ($message->event_type === PitchEvent::TYPE_PRODUCER_MESSAGE) {
            return [
                'type' => 'producer',
                'name' => $message->user?->name ?? 'Producer',
                'email' => $message->user?->email,
            ];
        }

        return [
            'type' => 'client',
            'name' => $message->metadata['client_name'] ?? 'Client',
            'email' => $message->metadata['client_email'] ?? null,
        ];
    }
}
