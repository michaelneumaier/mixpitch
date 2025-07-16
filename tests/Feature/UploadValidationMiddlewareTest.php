<?php

namespace Tests\Feature;

use App\Http\Middleware\ValidateUploadSettings;
use App\Models\FileUploadSetting;
use App\Models\User;
use App\Models\Project;
use App\Models\Pitch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class UploadValidationMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Project $project;
    protected Pitch $pitch;
    protected ValidateUploadSettings $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->project = Project::factory()->create(['user_id' => $this->user->id]);
        $this->pitch = Pitch::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'status' => Pitch::STATUS_IN_PROGRESS
        ]);
        
        $this->middleware = new ValidateUploadSettings();
        
        // Clear settings and cache
        FileUploadSetting::query()->delete();
        Cache::flush();
    }

    /** @test */
    public function it_skips_validation_for_non_upload_requests()
    {
        $request = Request::create('/some-other-endpoint', 'GET');
        
        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['success' => true], $response->getData(true));
    }

    /** @test */
    public function it_validates_file_uploads_against_settings()
    {
        // Set strict file size limit
        FileUploadSetting::updateSettings([
            FileUploadSetting::MAX_FILE_SIZE_MB => 50,
        ], FileUploadSetting::CONTEXT_GLOBAL);

        $largeFile = UploadedFile::fake()->create('large.mp3', 70 * 1024); // 70MB
        $request = Request::create('/upload', 'POST');
        $request->files->set('file', $largeFile);

        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertStringContainsString('Upload validation failed', $response->getContent());
    }

    /** @test */
    public function it_allows_valid_file_uploads()
    {
        // Set generous file size limit
        FileUploadSetting::updateSettings([
            FileUploadSetting::MAX_FILE_SIZE_MB => 200,
        ], FileUploadSetting::CONTEXT_GLOBAL);

        $validFile = UploadedFile::fake()->create('valid.mp3', 50 * 1024); // 50MB
        $request = Request::create('/upload', 'POST');
        $request->files->set('file', $validFile);

        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['success' => true], $response->getData(true));
    }

    /** @test */
    public function it_validates_chunk_uploads_against_settings()
    {
        // Set strict chunk size limit
        FileUploadSetting::updateSettings([
            FileUploadSetting::CHUNK_SIZE_MB => 5,
        ], FileUploadSetting::CONTEXT_GLOBAL);

        $largeChunk = UploadedFile::fake()->create('chunk.part', 8 * 1024); // 8MB
        $request = Request::create('/upload-chunk', 'POST');
        $request->files->set('chunk', $largeChunk);

        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertStringContainsString('Upload validation failed', $response->getContent());
    }

    /** @test */
    public function it_validates_total_size_for_session_creation()
    {
        // Set file size limit
        FileUploadSetting::updateSettings([
            FileUploadSetting::MAX_FILE_SIZE_MB => 100,
        ], FileUploadSetting::CONTEXT_GLOBAL);

        $request = Request::create('/create-session', 'POST');
        $request->merge(['total_size' => 150 * 1024 * 1024]); // 150MB

        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertStringContainsString('Upload validation failed', $response->getContent());
    }

    /** @test */
    public function it_determines_context_from_model_type_parameter()
    {
        // Set different limits for different contexts
        FileUploadSetting::updateSettings([
            FileUploadSetting::MAX_FILE_SIZE_MB => 50,
        ], FileUploadSetting::CONTEXT_GLOBAL);

        FileUploadSetting::updateSettings([
            FileUploadSetting::MAX_FILE_SIZE_MB => 200,
        ], FileUploadSetting::CONTEXT_PROJECTS);

        $validFile = UploadedFile::fake()->create('test.mp3', 100 * 1024); // 100MB
        $request = Request::create('/upload', 'POST');
        $request->files->set('file', $validFile);
        $request->merge(['model_type' => 'projects']);

        $response = $this->middleware->handle($request, function ($req) {
            // Check that settings were added to request
            $this->assertEquals('projects', $req->get('_upload_context'));
            $this->assertEquals(200, $req->get('_upload_settings')[FileUploadSetting::MAX_FILE_SIZE_MB]);
            return response()->json(['success' => true]);
        }, 'auto');

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_determines_context_from_route_parameters()
    {
        FileUploadSetting::updateSettings([
            FileUploadSetting::MAX_FILE_SIZE_MB => 300,
        ], FileUploadSetting::CONTEXT_PROJECTS);

        $validFile = UploadedFile::fake()->create('test.mp3', 100 * 1024); // 100MB
        $request = Request::create('/project/upload', 'POST');
        $request->files->set('file', $validFile);
        $request->setRouteResolver(function () {
            return new class {
                public function parameter($name) {
                    return $name === 'project' ? 'some-project' : null;
                }
            };
        });

        $response = $this->middleware->handle($request, function ($req) {
            $this->assertEquals('projects', $req->get('_upload_context'));
            return response()->json(['success' => true]);
        }, 'auto');

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_determines_context_from_path_patterns()
    {
        FileUploadSetting::updateSettings([
            FileUploadSetting::MAX_FILE_SIZE_MB => 150,
        ], FileUploadSetting::CONTEXT_PITCHES);

        $validFile = UploadedFile::fake()->create('pitch.mp3', 100 * 1024); // 100MB
        $request = Request::create('/pitch/upload-file', 'POST');
        $request->files->set('file', $validFile);

        $response = $this->middleware->handle($request, function ($req) {
            $this->assertEquals('pitches', $req->get('_upload_context'));
            return response()->json(['success' => true]);
        }, 'auto');

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_handles_client_portal_context()
    {
        FileUploadSetting::updateSettings([
            FileUploadSetting::MAX_FILE_SIZE_MB => 80,
        ], FileUploadSetting::CONTEXT_CLIENT_PORTALS);

        $validFile = UploadedFile::fake()->create('client.mp3', 50 * 1024); // 50MB
        $request = Request::create('/client-portal/upload', 'POST');
        $request->files->set('file', $validFile);

        $response = $this->middleware->handle($request, function ($req) {
            $this->assertEquals('client_portals', $req->get('_upload_context'));
            return response()->json(['success' => true]);
        }, 'auto');

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_rejects_invalid_context()
    {
        $request = Request::create('/upload', 'POST');
        $request->files->set('file', UploadedFile::fake()->create('test.mp3', 1024));

        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        }, 'invalid_context');

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString('Invalid upload context', $response->getContent());
    }

    /** @test */
    public function it_adds_settings_to_request_for_controllers()
    {
        FileUploadSetting::updateSettings([
            FileUploadSetting::MAX_FILE_SIZE_MB => 250,
            FileUploadSetting::CHUNK_SIZE_MB => 8,
        ], FileUploadSetting::CONTEXT_PROJECTS);

        $validFile = UploadedFile::fake()->create('test.mp3', 100 * 1024); // 100MB
        $request = Request::create('/upload', 'POST');
        $request->files->set('file', $validFile);

        $response = $this->middleware->handle($request, function ($req) {
            // Check that settings were added to request
            $settings = $req->get('_upload_settings');
            $this->assertNotNull($settings);
            $this->assertEquals(250, $settings[FileUploadSetting::MAX_FILE_SIZE_MB]);
            $this->assertEquals(8, $settings[FileUploadSetting::CHUNK_SIZE_MB]);
            $this->assertEquals('projects', $req->get('_upload_context'));
            
            return response()->json(['success' => true]);
        }, 'projects');

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_handles_missing_file_gracefully()
    {
        $request = Request::create('/upload', 'POST');
        // No file attached

        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_handles_validation_errors_gracefully()
    {
        // Create a scenario that will cause validation to fail
        $request = Request::create('/upload', 'POST');
        $largeFile = UploadedFile::fake()->create('large.mp3', 5 * 1024); // 5MB file
        $request->files->set('file', $largeFile);
        
        // Set very strict settings to ensure validation fails
        FileUploadSetting::updateSettings([
            FileUploadSetting::MAX_FILE_SIZE_MB => 1, // Very small limit (1MB) to trigger failure
        ], FileUploadSetting::CONTEXT_GLOBAL);

        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertStringContainsString('Upload validation failed', $response->getContent());
    }

    /** @test */
    public function it_provides_helpful_error_messages()
    {
        FileUploadSetting::updateSettings([
            FileUploadSetting::MAX_FILE_SIZE_MB => 30,
        ], FileUploadSetting::CONTEXT_PITCHES);

        $largeFile = UploadedFile::fake()->create('large.mp3', 50 * 1024); // 50MB
        $request = Request::create('/pitch/upload', 'POST');
        $request->files->set('file', $largeFile);

        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        }, 'auto');

        $this->assertEquals(422, $response->getStatusCode());
        $content = $response->getContent();
        $this->assertStringContainsString('30MB', $content);
        $this->assertStringContainsString('pitches context', $content);
    }

    /** @test */
    public function it_detects_upload_requests_correctly()
    {
        // Test with file parameter
        $request1 = Request::create('/test', 'POST');
        $request1->files->set('file', UploadedFile::fake()->create('test.mp3'));
        
        $response1 = $this->middleware->handle($request1, function ($req) {
            return response()->json(['processed' => true]);
        });
        
        // Should process the request (not skip it)
        $this->assertEquals(200, $response1->getStatusCode());

        // Test with chunk parameter
        $request2 = Request::create('/test', 'POST');
        $request2->files->set('chunk', UploadedFile::fake()->create('chunk.part'));
        
        $response2 = $this->middleware->handle($request2, function ($req) {
            return response()->json(['processed' => true]);
        });
        
        $this->assertEquals(200, $response2->getStatusCode());

        // Test with total_size parameter
        $request3 = Request::create('/test', 'POST');
        $request3->merge(['total_size' => 1024]);
        
        $response3 = $this->middleware->handle($request3, function ($req) {
            return response()->json(['processed' => true]);
        });
        
        $this->assertEquals(200, $response3->getStatusCode());

        // Test with upload in path
        $request4 = Request::create('/api/upload-something', 'POST');
        
        $response4 = $this->middleware->handle($request4, function ($req) {
            return response()->json(['processed' => true]);
        });
        
        $this->assertEquals(200, $response4->getStatusCode());
    }
}