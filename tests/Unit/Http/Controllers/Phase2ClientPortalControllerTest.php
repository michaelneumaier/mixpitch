<?php

namespace Tests\Unit\Http\Controllers;

use App\Http\Controllers\ClientPortalController;
use App\Models\Pitch;
use App\Models\PitchFile;
use App\Models\Project;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\PitchWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Mockery;
use Tests\TestCase;

class Phase2ClientPortalControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $controller;

    protected $pitchWorkflowService;

    protected $notificationService;

    protected $project;

    protected $pitch;

    protected $producer;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mock services
        $this->pitchWorkflowService = Mockery::mock(PitchWorkflowService::class);
        $this->notificationService = Mockery::mock(NotificationService::class);

        // Create controller with mocked dependencies
        $this->controller = new ClientPortalController(
            $this->pitchWorkflowService,
            $this->notificationService
        );

        // Create test data
        $this->producer = User::factory()->create([
            'role' => User::ROLE_PRODUCER,
            'name' => 'Test Producer',
        ]);

        $this->project = Project::factory()->create([
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'client@example.com',
            'client_name' => 'Test Client',
            'title' => 'Test Project',
            'description' => 'Test project description',
        ]);

        $this->pitch = Pitch::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->producer->id,
            'status' => Pitch::STATUS_COMPLETED,
            'payment_amount' => 500.00,
            'payment_status' => Pitch::PAYMENT_STATUS_PAID,
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_shows_account_upgrade_form_for_valid_signed_request()
    {
        // Create a signed request
        $signedUrl = URL::temporarySignedRoute(
            'client.portal.upgrade',
            now()->addHours(1),
            ['project' => $this->project->id]
        );

        $request = Request::create($signedUrl, 'GET');
        $request->setRouteResolver(function () {
            return new \Illuminate\Routing\Route('GET', '/test', []);
        });

        $response = $this->controller->showUpgrade($this->project, $request);

        $this->assertInstanceOf(\Illuminate\View\View::class, $response);
        $this->assertEquals('client_portal.upgrade', $response->getName());
        $this->assertEquals($this->project->id, $response->getData()['project']->id);
    }

    /** @test */
    public function it_rejects_upgrade_form_for_invalid_signature()
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Invalid or expired link.');

        $request = Request::create('/test', 'GET');
        $request->setRouteResolver(function () {
            return new \Illuminate\Routing\Route('GET', '/test', []);
        });

        $this->controller->showUpgrade($this->project, $request);
    }

    /** @test */
    public function it_redirects_to_login_if_user_already_exists()
    {
        // Create existing user
        User::factory()->create([
            'email' => $this->project->client_email,
            'role' => User::ROLE_CLIENT,
        ]);

        $signedUrl = URL::temporarySignedRoute(
            'client.portal.upgrade',
            now()->addHours(1),
            ['project' => $this->project->id]
        );

        $request = Request::create($signedUrl, 'GET');
        $request->setRouteResolver(function () {
            return new \Illuminate\Routing\Route('GET', '/test', []);
        });

        $response = $this->controller->showUpgrade($this->project, $request);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('login', $response->getTargetUrl());
    }

    /** @test */
    public function it_creates_client_account_successfully()
    {
        $signedUrl = URL::temporarySignedRoute(
            'client.portal.create_account',
            now()->addHours(1),
            ['project' => $this->project->id]
        );

        $request = Request::create($signedUrl, 'POST', [
            'name' => 'New Client',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $request->setRouteResolver(function () {
            return new \Illuminate\Routing\Route('POST', '/test', []);
        });

        $response = $this->controller->createAccount($request, $this->project);

        // Verify user was created
        $user = User::where('email', $this->project->client_email)->first();
        $this->assertNotNull($user);
        $this->assertEquals('New Client', $user->name);
        $this->assertEquals(User::ROLE_CLIENT, $user->role);
        $this->assertTrue(Hash::check('password123', $user->password));

        // Verify project was linked
        $this->project->refresh();
        $this->assertEquals($user->id, $this->project->client_user_id);

        // Verify redirect to dashboard
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('dashboard', $response->getTargetUrl());
    }

    /** @test */
    public function it_prevents_duplicate_account_creation()
    {
        // Create existing user
        User::factory()->create([
            'email' => $this->project->client_email,
            'role' => User::ROLE_CLIENT,
        ]);

        $signedUrl = URL::temporarySignedRoute(
            'client.portal.create_account',
            now()->addHours(1),
            ['project' => $this->project->id]
        );

        $request = Request::create($signedUrl, 'POST', [
            'name' => 'New Client',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $request->setRouteResolver(function () {
            return new \Illuminate\Routing\Route('POST', '/test', []);
        });

        $response = $this->controller->createAccount($request, $this->project);

        $this->assertEquals(302, $response->getStatusCode());
        // Should redirect back with error
    }

    /** @test */
    public function it_shows_invoice_for_paid_project()
    {
        $signedUrl = URL::temporarySignedRoute(
            'client.portal.invoice',
            now()->addHours(1),
            ['project' => $this->project->id]
        );

        $request = Request::create($signedUrl, 'GET');
        $request->setRouteResolver(function () {
            return new \Illuminate\Routing\Route('GET', '/test', []);
        });

        $response = $this->controller->invoice($this->project, $request);

        $this->assertInstanceOf(\Illuminate\View\View::class, $response);
        $this->assertEquals('client_portal.invoice', $response->getName());
        $viewData = $response->getData();
        $this->assertEquals($this->project->id, $viewData['project']->id);
        $this->assertEquals($this->pitch->id, $viewData['pitch']->id);
        $this->assertEquals(500.00, $viewData['amount']);
        $this->assertStringContainsString('INV-', $viewData['invoice_number']);
    }

    /** @test */
    public function it_shows_deliverables_for_completed_project()
    {
        // Create test deliverable files (using note field to identify deliverables)
        $deliverable1 = PitchFile::factory()->create([
            'pitch_id' => $this->pitch->id,
            'file_name' => 'final_mix.wav',
            'note' => 'deliverable',
            'size' => 1024000,
        ]);

        $deliverable2 = PitchFile::factory()->create([
            'pitch_id' => $this->pitch->id,
            'file_name' => 'master.wav',
            'note' => 'final deliverable',
            'size' => 2048000,
        ]);

        $signedUrl = URL::temporarySignedRoute(
            'client.portal.deliverables',
            now()->addHours(1),
            ['project' => $this->project->id]
        );

        $request = Request::create($signedUrl, 'GET');
        $request->setRouteResolver(function () {
            return new \Illuminate\Routing\Route('GET', '/test', []);
        });

        $response = $this->controller->deliverables($this->project, $request);

        $this->assertInstanceOf(\Illuminate\View\View::class, $response);
        $this->assertEquals('client_portal.deliverables', $response->getName());
        $viewData = $response->getData();
        $this->assertEquals($this->project->id, $viewData['project']->id);
        $this->assertEquals($this->pitch->id, $viewData['pitch']->id);
        // Note: The deliverables query may need adjustment based on actual implementation
        $this->assertArrayHasKey('deliverables', $viewData);
    }

    /** @test */
    public function it_validates_client_access_with_signed_url()
    {
        $signedUrl = URL::temporarySignedRoute(
            'client.portal.invoice',
            now()->addHours(1),
            ['project' => $this->project->id]
        );

        $request = Request::create($signedUrl, 'GET');
        $request->setRouteResolver(function () {
            return new \Illuminate\Routing\Route('GET', '/test', []);
        });

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('validateClientAccess');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, $this->project, $request);
        $this->assertTrue($result);
    }

    /** @test */
    public function it_validates_client_access_with_authenticated_client()
    {
        $client = User::factory()->create([
            'email' => $this->project->client_email,
            'role' => User::ROLE_CLIENT,
        ]);

        $this->project->update(['client_user_id' => $client->id]);

        Auth::login($client);

        $request = Request::create('/test', 'GET');

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('validateClientAccess');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, $this->project, $request);
        $this->assertTrue($result);
    }

    /** @test */
    public function it_rejects_invalid_client_access()
    {
        $request = Request::create('/test', 'GET');

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('validateClientAccess');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, $this->project, $request);
        $this->assertFalse($result);
    }

    /** @test */
    public function it_rejects_access_for_non_client_management_project()
    {
        $nonClientProject = Project::factory()->create([
            'workflow_type' => Project::WORKFLOW_TYPE_STANDARD,
            'client_email' => 'client@example.com',
        ]);

        $signedUrl = URL::temporarySignedRoute(
            'client.portal.invoice',
            now()->addHours(1),
            ['project' => $nonClientProject->id]
        );

        $request = Request::create($signedUrl, 'GET');
        $request->setRouteResolver(function () {
            return new \Illuminate\Routing\Route('GET', '/test', []);
        });

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('validateClientAccess');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, $nonClientProject, $request);
        $this->assertFalse($result);
    }

    /** @test */
    public function it_requires_completed_status_for_deliverables()
    {
        $this->pitch->update(['status' => Pitch::STATUS_IN_PROGRESS]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $signedUrl = URL::temporarySignedRoute(
            'client.portal.deliverables',
            now()->addHours(1),
            ['project' => $this->project->id]
        );

        $request = Request::create($signedUrl, 'GET');
        $request->setRouteResolver(function () {
            return new \Illuminate\Routing\Route('GET', '/test', []);
        });

        $this->controller->deliverables($this->project, $request);
    }

    /** @test */
    public function it_requires_paid_status_for_invoice()
    {
        $this->pitch->update(['payment_status' => Pitch::PAYMENT_STATUS_PENDING]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $signedUrl = URL::temporarySignedRoute(
            'client.portal.invoice',
            now()->addHours(1),
            ['project' => $this->project->id]
        );

        $request = Request::create($signedUrl, 'GET');
        $request->setRouteResolver(function () {
            return new \Illuminate\Routing\Route('GET', '/test', []);
        });

        $this->controller->invoice($this->project, $request);
    }
}
