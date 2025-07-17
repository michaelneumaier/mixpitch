<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestWaveformWorker extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'waveform:test-worker 
                            {file_url? : URL of audio file to test with}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the Cloudflare waveform worker';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $fileUrl = $this->argument('file_url');

        if (! $fileUrl) {
            $fileUrl = $this->ask('Enter a URL to an audio file to test with (or press enter for worker status check)');
        }

        $workerUrl = config('services.cloudflare.waveform_worker_url');

        if (empty($workerUrl)) {
            $this->error('Cloudflare waveform worker URL is not configured in .env file');
            $this->info('Add: CLOUDFLARE_WAVEFORM_WORKER_URL=https://your-worker.workers.dev');

            return 1;
        }

        $this->info("Testing worker at: {$workerUrl}");

        if (empty($fileUrl)) {
            // Just test worker status
            $this->testWorkerStatus($workerUrl);
        } else {
            // Test with actual file
            $this->testFileProcessing($workerUrl, $fileUrl);
        }

        return 0;
    }

    /**
     * Test worker status
     */
    protected function testWorkerStatus($workerUrl)
    {
        $this->info('Testing worker status...');

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->get($workerUrl);

            $this->info("Status: {$response->status()}");
            $this->info("Response: {$response->body()}");

            if ($response->successful()) {
                $this->info('✅ Worker is accessible');
            } else {
                $this->warn("⚠️  Worker responded with status {$response->status()}");
            }

        } catch (\Exception $e) {
            $this->error("❌ Error accessing worker: {$e->getMessage()}");
        }
    }

    /**
     * Test file processing
     */
    protected function testFileProcessing($workerUrl, $fileUrl)
    {
        $this->info("Testing file processing with: {$fileUrl}");

        try {
            $response = Http::timeout(60)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer '.config('services.cloudflare.worker_token', ''),
                ])
                ->post($workerUrl, [
                    'file_url' => $fileUrl,
                    'peaks_count' => 100,
                ]);

            $this->info("Status: {$response->status()}");

            if ($response->successful()) {
                $data = $response->json();

                $this->info('✅ Waveform generated successfully!');
                $this->table(['Property', 'Value'], [
                    ['Status', $data['status'] ?? 'unknown'],
                    ['Duration', ($data['duration'] ?? 0).' seconds'],
                    ['Peaks Count', count($data['waveform_peaks'] ?? [])],
                    ['Processing Time', ($data['processing_time'] ?? 'unknown').' ms'],
                ]);

                // Show first few peaks as sample
                if (! empty($data['waveform_peaks'])) {
                    $samplePeaks = array_slice($data['waveform_peaks'], 0, 5);
                    $this->info('Sample peaks: '.json_encode($samplePeaks));
                }

            } else {
                $this->error("❌ Worker error (Status: {$response->status()})");
                $this->error("Response: {$response->body()}");
            }

        } catch (\Exception $e) {
            $this->error("❌ Error processing file: {$e->getMessage()}");
        }
    }
}
