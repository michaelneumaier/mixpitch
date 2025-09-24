<?php

namespace Tests\Unit;

use App\Services\LinkAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LinkAnalysisServiceGoogleDriveTest extends TestCase
{
    use RefreshDatabase;

    protected LinkAnalysisService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(LinkAnalysisService::class);

        // Set a test API key
        config(['linkimport.google_drive.api_key' => 'test_api_key']);
    }

    public function test_can_parse_google_drive_file_url(): void
    {
        $urls = [
            'https://drive.google.com/file/d/1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms/view?usp=sharing',
            'https://drive.google.com/file/d/1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms/view',
            'https://drive.google.com/open?id=1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms',
        ];

        foreach ($urls as $url) {
            $reflection = new \ReflectionClass($this->service);
            $method = $reflection->getMethod('parseGoogleDriveUrl');
            $method->setAccessible(true);

            $result = $method->invoke($this->service, $url);

            $this->assertNotNull($result);
            $this->assertEquals('1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms', $result['id']);
            $this->assertEquals('file', $result['type']);
        }
    }

    public function test_can_parse_google_drive_folder_url(): void
    {
        $urls = [
            'https://drive.google.com/drive/folders/1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms?usp=sharing',
            'https://drive.google.com/drive/u/0/folders/1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms',
        ];

        foreach ($urls as $url) {
            $reflection = new \ReflectionClass($this->service);
            $method = $reflection->getMethod('parseGoogleDriveUrl');
            $method->setAccessible(true);

            $result = $method->invoke($this->service, $url);

            $this->assertNotNull($result);
            $this->assertEquals('1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms', $result['id']);
            $this->assertEquals('folder', $result['type']);
        }
    }

    public function test_returns_null_for_invalid_google_drive_url(): void
    {
        $invalidUrls = [
            'https://docs.google.com/document/d/1ABC123/edit',
            'https://invalid-url.com',
            'not-a-url',
        ];

        foreach ($invalidUrls as $url) {
            $reflection = new \ReflectionClass($this->service);
            $method = $reflection->getMethod('parseGoogleDriveUrl');
            $method->setAccessible(true);

            $result = $method->invoke($this->service, $url);

            $this->assertNull($result);
        }
    }

    public function test_can_analyze_google_drive_file(): void
    {
        $fileId = '1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms';

        // Mock Google Drive API response for file metadata
        Http::fake([
            'https://www.googleapis.com/drive/v3/files/'.$fileId.'*' => Http::response([
                'id' => $fileId,
                'name' => 'test-audio-file.mp3',
                'mimeType' => 'audio/mpeg',
                'size' => '5242880', // 5MB
                'webContentLink' => 'https://drive.google.com/uc?id='.$fileId.'&export=download',
            ], 200),
        ]);

        $url = "https://drive.google.com/file/d/{$fileId}/view?usp=sharing";
        $result = $this->service->analyzeLink($url);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);

        $file = $result[0];
        $this->assertEquals('test-audio-file.mp3', $file['filename']);
        $this->assertEquals(5242880, $file['size']);
        $this->assertEquals('audio/mpeg', $file['mime_type']);
        $this->assertEquals($fileId, $file['file_id']);
        $this->assertStringContains('alt=media', $file['download_url']);
        $this->assertArrayHasKey('metadata', $file);
    }

    public function test_can_analyze_google_drive_folder(): void
    {
        $folderId = '1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms';

        // Mock Google Drive API response for folder contents
        Http::fake([
            'https://www.googleapis.com/drive/v3/files*' => Http::response([
                'files' => [
                    [
                        'id' => 'file1',
                        'name' => 'audio-track-1.mp3',
                        'mimeType' => 'audio/mpeg',
                        'size' => '3145728', // 3MB
                    ],
                    [
                        'id' => 'file2',
                        'name' => 'audio-track-2.wav',
                        'mimeType' => 'audio/wav',
                        'size' => '10485760', // 10MB
                    ],
                    [
                        'id' => 'subfolder',
                        'name' => 'Subfolder',
                        'mimeType' => 'application/vnd.google-apps.folder',
                    ],
                ],
                'nextPageToken' => null,
            ], 200),
        ]);

        $url = "https://drive.google.com/drive/folders/{$folderId}?usp=sharing";
        $result = $this->service->analyzeLink($url);

        $this->assertIsArray($result);
        $this->assertCount(2, $result); // Should exclude the folder

        $this->assertEquals('audio-track-1.mp3', $result[0]['filename']);
        $this->assertEquals(3145728, $result[0]['size']);
        $this->assertEquals('audio/mpeg', $result[0]['mime_type']);

        $this->assertEquals('audio-track-2.wav', $result[1]['filename']);
        $this->assertEquals(10485760, $result[1]['size']);
        $this->assertEquals('audio/wav', $result[1]['mime_type']);

        // Verify download URLs are generated correctly
        foreach ($result as $file) {
            $this->assertStringContains('alt=media', $file['download_url']);
            $this->assertStringContains('key=test_api_key', $file['download_url']);
        }
    }

    public function test_handles_google_drive_file_not_found(): void
    {
        $fileId = 'nonexistent-file-id';

        Http::fake([
            'https://www.googleapis.com/drive/v3/files/'.$fileId.'*' => Http::response([
                'error' => [
                    'code' => 404,
                    'message' => 'File not found',
                ],
            ], 404),
        ]);

        $url = "https://drive.google.com/file/d/{$fileId}/view";

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Google Drive file not found or not publicly accessible');

        $this->service->analyzeLink($url);
    }

    public function test_handles_google_drive_private_file(): void
    {
        $fileId = 'private-file-id';

        Http::fake([
            'https://www.googleapis.com/drive/v3/files/'.$fileId.'*' => Http::response([
                'error' => [
                    'code' => 403,
                    'message' => 'Forbidden',
                ],
            ], 403),
        ]);

        $url = "https://drive.google.com/file/d/{$fileId}/view";

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Google Drive file is private and requires authentication');

        $this->service->analyzeLink($url);
    }

    public function test_handles_empty_google_drive_folder(): void
    {
        $folderId = 'empty-folder-id';

        Http::fake([
            'https://www.googleapis.com/drive/v3/files*' => Http::response([
                'files' => [],
                'nextPageToken' => null,
            ], 200),
        ]);

        $url = "https://drive.google.com/drive/folders/{$folderId}";

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No accessible files found in Google Drive folder');

        $this->service->analyzeLink($url);
    }

    public function test_respects_max_files_limit_for_folder(): void
    {
        $folderId = 'large-folder-id';

        // Set a small limit for testing
        config(['linkimport.google_drive.max_files_per_folder' => 2]);

        // Mock response with 3 files
        Http::fake([
            'https://www.googleapis.com/drive/v3/files*' => Http::response([
                'files' => [
                    ['id' => 'file1', 'name' => 'file1.mp3', 'mimeType' => 'audio/mpeg', 'size' => '1000'],
                    ['id' => 'file2', 'name' => 'file2.mp3', 'mimeType' => 'audio/mpeg', 'size' => '2000'],
                    ['id' => 'file3', 'name' => 'file3.mp3', 'mimeType' => 'audio/mpeg', 'size' => '3000'],
                ],
                'nextPageToken' => null,
            ], 200),
        ]);

        $url = "https://drive.google.com/drive/folders/{$folderId}";
        $result = $this->service->analyzeLink($url);

        $this->assertCount(2, $result); // Should respect the limit
    }

    public function test_builds_correct_download_url(): void
    {
        $fileId = 'test-file-id';

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('buildGoogleDriveDownloadUrl');
        $method->setAccessible(true);

        $downloadUrl = $method->invoke($this->service, $fileId);

        $expectedUrl = 'https://www.googleapis.com/drive/v3/files/test-file-id?alt=media&key=test_api_key';
        $this->assertEquals($expectedUrl, $downloadUrl);
    }

    public function test_throws_exception_when_api_key_not_configured(): void
    {
        config(['linkimport.google_drive.api_key' => null]);

        $url = 'https://drive.google.com/file/d/test-file-id/view';

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Google Drive API key not configured');

        $this->service->analyzeLink($url);
    }

    public function test_handles_google_drive_api_timeout(): void
    {
        $fileId = 'timeout-file-id';

        Http::fake([
            'https://www.googleapis.com/drive/v3/files/'.$fileId.'*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection timeout');
            },
        ]);

        $url = "https://drive.google.com/file/d/{$fileId}/view";

        $this->expectException(\Exception::class);

        $this->service->analyzeLink($url);
    }
}
