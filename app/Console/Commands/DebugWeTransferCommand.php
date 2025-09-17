<?php

namespace App\Console\Commands;

use App\Services\LinkAnalysisService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class DebugWeTransferCommand extends Command
{
    protected $signature = 'debug:wetransfer {url}';

    protected $description = 'Debug WeTransfer link parsing';

    public function handle()
    {
        $url = $this->argument('url');
        $this->info("Debugging WeTransfer URL: {$url}");

        try {
            $linkAnalysisService = app(LinkAnalysisService::class);

            // Get the transfer ID
            $transferId = $this->extractWeTransferId($url);
            $this->info("Transfer ID: {$transferId}");

            // Follow redirects
            $userAgent = config('linkimport.processing.user_agent', 'MixPitch-LinkImporter/1.0');
            $timeout = config('linkimport.security.timeout_seconds', 60);

            $transferPageUrl = $this->resolveWeTransferRedirects($url, $userAgent, $timeout);
            $this->info("Final URL: {$transferPageUrl}");

            // Get the HTML
            $response = Http::timeout($timeout)
                ->withHeaders([
                    'User-Agent' => $userAgent,
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.5',
                    'Referer' => $url,
                ])
                ->get($transferPageUrl);

            $html = $response->body();
            $this->info('HTML length: '.strlen($html));

            // Extract __NEXT_DATA__
            if (preg_match('/<script id="__NEXT_DATA__" type="application\/json">(.+?)<\/script>/', $html, $matches)) {
                $data = json_decode($matches[1], true);
                $this->info('Found __NEXT_DATA__');
                $this->info('Keys: '.implode(', ', array_keys($data ?? [])));

                // Inspect props structure
                if (isset($data['props'])) {
                    $this->info('Props keys: '.implode(', ', array_keys($data['props'])));

                    if (isset($data['props']['pageProps'])) {
                        $this->info('PageProps keys: '.implode(', ', array_keys($data['props']['pageProps'])));

                        // Look for transfer data
                        if (isset($data['props']['pageProps']['transfer'])) {
                            $transfer = $data['props']['pageProps']['transfer'];
                            $this->info('Transfer data found!');
                            $this->info('Transfer keys: '.implode(', ', array_keys($transfer)));

                            // Show title if it exists
                            if (isset($transfer['title'])) {
                                $this->info('File title: '.$transfer['title']);
                            }

                            // Show files array if it exists
                            if (isset($transfer['files'])) {
                                $this->info('Files array found with '.count($transfer['files']).' files');
                                foreach ($transfer['files'] as $index => $file) {
                                    $this->info("File {$index}: ".json_encode($file, JSON_PRETTY_PRINT));
                                }
                            }
                        } else {
                            $this->error("No 'transfer' key in pageProps");
                            $this->info('PageProps content: '.json_encode($data['props']['pageProps'], JSON_PRETTY_PRINT));
                        }
                    } else {
                        $this->error("No 'pageProps' in props");
                        $this->info('Props content: '.json_encode($data['props'], JSON_PRETTY_PRINT));
                    }
                } else {
                    $this->error("No 'props' in __NEXT_DATA__");
                }
            } else {
                $this->error('No __NEXT_DATA__ found in HTML');

                // Look for other patterns
                $this->info('Searching for other patterns...');

                // Look for any JSON with "title" that might contain file data
                if (preg_match_all('/\{[^}]*"title"\s*:\s*"([^"]*mp3[^"]*)"[^}]*\}/', $html, $matches)) {
                    $this->info('Found JSON with mp3 titles:');
                    foreach ($matches[0] as $match) {
                        $this->info($match);
                    }
                }
            }

        } catch (\Exception $e) {
            $this->error('Error: '.$e->getMessage());
        }
    }

    protected function extractWeTransferId(string $url): ?string
    {
        if (preg_match('/we\.tl\/t-([a-zA-Z0-9]+)/', $url, $matches)) {
            return $matches[1];
        }

        if (preg_match('/wetransfer\.com\/downloads\/([a-zA-Z0-9]+)/', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }

    protected function resolveWeTransferRedirects(string $url, string $userAgent, int $timeout): string
    {
        $maxRedirects = 5;
        $currentUrl = $url;
        $redirectCount = 0;

        while ($redirectCount < $maxRedirects) {
            $response = Http::timeout($timeout)
                ->withHeaders([
                    'User-Agent' => $userAgent,
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                ])
                ->withoutRedirecting()
                ->get($currentUrl);

            if ($response->redirect()) {
                $location = $response->header('Location');
                if ($location) {
                    if (! parse_url($location, PHP_URL_HOST)) {
                        $parsed = parse_url($currentUrl);
                        $baseUrl = $parsed['scheme'].'://'.$parsed['host'];
                        $location = $baseUrl.(str_starts_with($location, '/') ? '' : '/').$location;
                    }

                    $currentUrl = $location;
                    $redirectCount++;

                    continue;
                }
            }

            break;
        }

        return $currentUrl;
    }
}
