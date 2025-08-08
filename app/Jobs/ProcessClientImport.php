<?php

namespace App\Jobs;

use App\Models\Client;
use App\Models\ClientImportJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessClientImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $importJobId)
    {
    }

    public function handle(): void
    {
        $job = ClientImportJob::findOrFail($this->importJobId);
        $job->update(['status' => 'processing']);

        try {
            $filePath = storage_path('app/'.$job->storage_path);
            $spl = new \SplFileObject($filePath);
            $spl->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY | \SplFileObject::DROP_NEW_LINE);

            $headers = [];
            $rowIndex = -1;
            $imported = 0; $duplicates = 0; $errors = 0; $total = 0;
            $errorSamples = [];
            $mapping = (array) ($job->summary['mapping'] ?? []);
            foreach ($spl as $row) {
                if ($row === [null] || $row === false) { continue; }
                $rowIndex++;
                if ($rowIndex === 0) {
                    $headers = array_map(fn($h) => strtolower(trim((string)$h)), $row);
                    continue;
                }
                $total++;
                $assoc = [];
                foreach ($headers as $i => $key) {
                    $assoc[$key] = $row[$i] ?? null;
                }
                if (!empty($mapping)) {
                    $mapped = [];
                    foreach ($mapping as $target => $headerLabel) {
                        if (!$headerLabel) { continue; }
                        $keyLower = strtolower(trim((string)$headerLabel));
                        $mapped[$target] = $assoc[$keyLower] ?? null;
                    }
                    $assoc = array_merge($assoc, $mapped);
                }
                try {
                    $email = strtolower(trim((string)($assoc['email'] ?? '')));
                    if ($email === '') { $errors++; continue; }

                    $client = Client::firstOrCreate(
                        ['user_id' => $job->user_id, 'email' => $email],
                        [
                            'name' => trim((string)($assoc['name'] ?? '')) ?: null,
                            'company' => trim((string)($assoc['company'] ?? '')) ?: null,
                            'phone' => trim((string)($assoc['phone'] ?? '')) ?: null,
                            'timezone' => trim((string)($assoc['timezone'] ?? 'UTC')) ?: 'UTC',
                            'tags' => isset($assoc['tags']) && $assoc['tags'] !== '' ? array_map('trim', explode(',', (string)$assoc['tags'])) : null,
                            'status' => Client::STATUS_ACTIVE,
                        ]
                    );
                    if ($client->wasRecentlyCreated) { $imported++; } else { $duplicates++; }
                } catch (\Throwable $e) {
                    Log::warning('Client import row failed', ['job' => $job->id, 'error' => $e->getMessage()]);
                    $errors++;
                    if (count($errorSamples) < 25) {
                        $errorSamples[] = [
                            'row' => $rowIndex + 1,
                            'message' => $e->getMessage(),
                        ];
                    }
                }
            }

            $existing = $job->summary ?? [];
            $summaryPayload = array_merge($existing, [
                'totals' => compact('total','imported','duplicates','errors'),
                'errors' => $errorSamples,
            ]);
            $job->update([
                'status' => 'completed',
                'total_rows' => $total,
                'imported_rows' => $imported,
                'duplicate_rows' => $duplicates,
                'error_rows' => $errors,
                'summary' => $summaryPayload,
            ]);
        } catch (\Throwable $e) {
            $job->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
        }
    }
}


