<?php

use App\Livewire\GlobalAudioPlayer;
use App\Livewire\ManageProject;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create(['role' => User::ROLE_CLIENT]);
    $this->actingAs($this->user);
});

it('can play audio project files in global player from manage project page', function () {
    // Create a project with an audio file
    $project = Project::factory()->create(['user_id' => $this->user->id]);
    $projectFile = ProjectFile::factory()->create([
        'project_id' => $project->id,
        'mime_type' => 'audio/mp3',
        'file_name' => 'test-audio.mp3',
    ]);

    // Test the ManageProject component
    Livewire::test(ManageProject::class, ['project' => $project])
        ->assertSee($projectFile->file_name)
        ->assertDispatchedTo(GlobalAudioPlayer::class, 'playProjectFile', [
            'projectFileId' => $projectFile->id,
        ])
        ->call('playProjectFile', $projectFile->id);
});

it('shows play button only for audio files', function () {
    $project = Project::factory()->create(['user_id' => $this->user->id]);

    // Create an audio file and a non-audio file
    $audioFile = ProjectFile::factory()->create([
        'project_id' => $project->id,
        'mime_type' => 'audio/mp3',
        'file_name' => 'audio.mp3',
    ]);

    $pdfFile = ProjectFile::factory()->create([
        'project_id' => $project->id,
        'mime_type' => 'application/pdf',
        'file_name' => 'document.pdf',
    ]);

    // Mount the component and check the rendered output
    $component = Livewire::test(ManageProject::class, ['project' => $project]);

    // Audio file should have play button
    $component->assertSee('playProjectFile('.$audioFile->id.')');

    // PDF file should not have play button
    $component->assertDontSee('playProjectFile('.$pdfFile->id.')');
});

it('checks audio mime types correctly', function () {
    $audioMimes = [
        'audio/mpeg' => true,
        'audio/mp3' => true,
        'audio/wav' => true,
        'audio/wave' => true,
        'audio/x-wav' => true,
        'audio/ogg' => true,
        'audio/aac' => true,
        'audio/m4a' => true,
        'audio/mp4' => true,
        'audio/flac' => true,
        'audio/x-flac' => true,
        'audio/webm' => true,
        'application/pdf' => false,
        'image/jpeg' => false,
        'video/mp4' => false,
    ];

    foreach ($audioMimes as $mimeType => $shouldBeAudio) {
        $file = ProjectFile::factory()->make(['mime_type' => $mimeType]);
        expect($file->isAudioFile())->toBe($shouldBeAudio);
    }
});

it('handles non-audio files gracefully', function () {
    $project = Project::factory()->create(['user_id' => $this->user->id]);
    $pdfFile = ProjectFile::factory()->create([
        'project_id' => $project->id,
        'mime_type' => 'application/pdf',
        'file_name' => 'document.pdf',
    ]);

    Livewire::test(ManageProject::class, ['project' => $project])
        ->call('playProjectFile', $pdfFile->id)
        ->assertNotDispatched('playProjectFile');
});
