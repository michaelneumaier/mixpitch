<?php

use App\Models\User;
use App\Services\GoogleDriveService;

test('google drive service can be instantiated', function () {
    $service = app(GoogleDriveService::class);
    expect($service)->toBeInstanceOf(GoogleDriveService::class);
});

test('google drive connection status defaults to false for new user', function () {
    $user = User::factory()->create();
    $service = app(GoogleDriveService::class);

    expect($service->isConnected($user))->toBeFalse();
});

test('google drive service can generate s3 keys for different models', function () {
    $service = app(GoogleDriveService::class);

    // Use reflection to access protected method for testing
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('generateS3KeyForModel');
    $method->setAccessible(true);

    // Test with Project model
    $project = new \App\Models\Project(['id' => 123]);
    $key = $method->invoke($service, $project, 'test.mp3');
    expect($key)->toBe('projects/123/files/test.mp3');

    // Test with Pitch model
    $pitch = new \App\Models\Pitch(['id' => 456]);
    $key = $method->invoke($service, $pitch, 'pitch.mp3');
    expect($key)->toBe('pitches/456/files/pitch.mp3');
});
