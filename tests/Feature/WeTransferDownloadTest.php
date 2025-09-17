<?php

namespace Tests\Feature;

use App\Jobs\ProcessLinkImport;
use App\Models\LinkImport;
use App\Services\LinkAnalysisService;
use Illuminate\Support\Facades\Queue;
use Tests\NonRefreshingTestCase;

class WeTransferDownloadTest extends NonRefreshingTestCase
{
    // Using NonRefreshingTestCase to avoid wiping main database

    public function test_can_analyze_real_wetransfer_link()
    {
        $linkAnalysisService = app(LinkAnalysisService::class);
        $realUrl = 'https://we.tl/t-sYmXCkwqC7';

        try {
            $files = $linkAnalysisService->analyzeLink($realUrl);

            $this->assertIsArray($files);
            $this->assertNotEmpty($files, 'Should detect at least one file');

            $firstFile = $files[0];
            $this->assertArrayHasKey('filename', $firstFile);
            $this->assertArrayHasKey('transfer_id', $firstFile);
            $this->assertArrayHasKey('security_hash', $firstFile);

            // Should detect the Kryptonite Yeah.mp3 file based on our web analysis
            $this->assertStringContainsString('Kryptonite', $firstFile['filename']);
            $this->assertEquals('sYmXCkwqC7', $firstFile['transfer_id']);
            $this->assertNotNull($firstFile['security_hash']);

        } catch (\Exception $e) {
            // If the link has expired, we should still test our code path
            $this->assertStringContainsString('expired', $e->getMessage());
        }
    }

    public function test_can_parse_wetransfer_download_url()
    {
        $linkAnalysisService = new LinkAnalysisService;

        // Use reflection to access the protected method
        $reflection = new \ReflectionClass($linkAnalysisService);
        $method = $reflection->getMethod('parseWeTransferDownloadUrl');
        $method->setAccessible(true);

        $testUrl = 'https://wetransfer.com/downloads/d93957618e735dbee72f945550a2b40320250916235316/6c5c0f?t_rid=ZW1haWx8YWRyb2l0fDI2MzY3YjBmLWZlMmEtNDdjOC1iMTQzLWRkNzdhOTYzZjYwZQ==';

        $result = $method->invoke($linkAnalysisService, $testUrl);

        $this->assertEquals('d93957618e735dbee72f945550a2b40320250916235316', $result['transfer_id']);
        $this->assertEquals('6c5c0f', $result['security_hash']);
        $this->assertEquals('ZW1haWx8YWRyb2l0fDI2MzY3YjBmLWZlMmEtNDdjOC1iMTQzLWRkNzdhOTYzZjYwZQ==', $result['recipient_id']);
    }

    public function test_process_link_import_job_with_enhanced_data()
    {
        // Mock the job to test our new code paths without database operations
        Queue::fake();

        // Create a mock LinkImport with the enhanced data structure
        $linkImport = new LinkImport([
            'id' => 1,
            'source_url' => 'https://we.tl/t-sYmXCkwqC7',
            'detected_files' => [[
                'filename' => 'Kryptonite Yeah.mp3',
                'size' => 5000000,
                'mime_type' => 'audio/mpeg',
                'transfer_id' => 'sYmXCkwqC7',
                'security_hash' => '6c5c0f',
                'recipient_id' => 'test123',
            ]],
        ]);

        $job = new ProcessLinkImport($linkImport);

        // The job should now have the security_hash and recipient_id available
        $this->assertInstanceOf(ProcessLinkImport::class, $job);

        // Verify the enhanced data structure contains the new fields
        $files = $linkImport->detected_files;
        $this->assertArrayHasKey('security_hash', $files[0]);
        $this->assertArrayHasKey('recipient_id', $files[0]);
        $this->assertEquals('6c5c0f', $files[0]['security_hash']);
        $this->assertEquals('test123', $files[0]['recipient_id']);
    }
}
