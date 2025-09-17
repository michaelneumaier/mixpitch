<?php

use App\Livewire\Components\FileList;
use Livewire\Livewire;

uses(Tests\TestCase::class);

test('component renders with empty file collection', function () {
    $component = Livewire::test(FileList::class, [
        'files' => collect(),
    ]);

    $component->assertSee('No files uploaded yet')
        ->assertSee('Upload files to share with collaborators');
});

test('component renders with file collection', function () {
    $files = collect([
        (object) [
            'id' => 1,
            'file_name' => 'test-audio.mp3',
            'size' => 1024000,
            'mime_type' => 'audio/mpeg',
            'created_at' => now(),
            'formatted_size' => '1 MB',
        ],
        (object) [
            'id' => 2,
            'file_name' => 'test-document.pdf',
            'size' => 512000,
            'mime_type' => 'application/pdf',
            'created_at' => now(),
            'formatted_size' => '512 KB',
        ],
    ]);

    $component = Livewire::test(FileList::class, [
        'files' => $files,
        'showFileCount' => true,
        'showTotalSize' => true,
    ]);

    $component->assertSee('Files (2)')
        ->assertSee('test-audio.mp3')
        ->assertSee('test-document.pdf')
        ->assertSee('Total: 1.46 MB');
});

test('component handles workflow color schemes correctly', function () {
    $workflowColors = [
        'text_primary' => 'text-blue-900 dark:text-blue-100',
        'text_muted' => 'text-blue-600 dark:text-blue-400',
        'icon' => 'text-blue-600 dark:text-blue-400',
        'accent_bg' => 'bg-blue-100 dark:bg-blue-900',
    ];

    $component = Livewire::test(FileList::class, [
        'files' => collect(),
        'colorScheme' => $workflowColors,
    ]);

    $component->assertStatus(200);
    expect($component->get('colorScheme'))->toBe($workflowColors);
});

test('component handles predefined theme schemes', function () {
    $component = Livewire::test(FileList::class, [
        'files' => collect(),
        'colorScheme' => 'client-portal',
    ]);

    $resolvedColors = $component->get('resolvedColorScheme');
    expect($resolvedColors['text_primary'])->toBe('text-indigo-900 dark:text-indigo-100');
    expect($resolvedColors['icon'])->toBe('text-indigo-600 dark:text-indigo-400');
});

test('component dispatches play file action for audio files', function () {
    $files = collect([
        (object) [
            'id' => 1,
            'file_name' => 'test-audio.mp3',
            'size' => 1024000,
            'mime_type' => 'audio/mpeg',
            'created_at' => now(),
        ],
    ]);

    $component = Livewire::test(FileList::class, [
        'files' => $files,
        'canPlay' => true,
        'playMethod' => 'playProjectFile',
        'modelType' => 'project',
        'modelId' => 123,
    ]);

    $component->call('playFile', 1)
        ->assertDispatched('fileAction', [
            'action' => 'playProjectFile',
            'fileId' => 1,
            'modelType' => 'project',
            'modelId' => 123,
        ]);
});

test('component dispatches download file action', function () {
    $files = collect([
        (object) [
            'id' => 1,
            'file_name' => 'test-document.pdf',
            'size' => 512000,
            'mime_type' => 'application/pdf',
            'created_at' => now(),
        ],
    ]);

    $component = Livewire::test(FileList::class, [
        'files' => $files,
        'canDownload' => true,
        'downloadMethod' => 'getDownloadUrl',
        'modelType' => 'project',
        'modelId' => 123,
    ]);

    $component->call('downloadFile', 1)
        ->assertDispatched('fileAction', [
            'action' => 'getDownloadUrl',
            'fileId' => 1,
            'modelType' => 'project',
            'modelId' => 123,
        ]);
});

test('component dispatches delete file action', function () {
    $files = collect([
        (object) [
            'id' => 1,
            'file_name' => 'test-document.pdf',
            'size' => 512000,
            'mime_type' => 'application/pdf',
            'created_at' => now(),
        ],
    ]);

    $component = Livewire::test(FileList::class, [
        'files' => $files,
        'canDelete' => true,
        'deleteMethod' => 'confirmDeleteFile',
        'modelType' => 'project',
        'modelId' => 123,
    ]);

    $component->call('confirmDeleteFile', 1)
        ->assertDispatched('fileAction', [
            'action' => 'confirmDeleteFile',
            'fileId' => 1,
            'modelType' => 'project',
            'modelId' => 123,
        ]);
});

test('component correctly identifies audio files', function () {
    $component = Livewire::test(FileList::class);
    $instance = $component->instance();

    // Test with audio mime type
    $audioFile = (object) [
        'mime_type' => 'audio/mpeg',
        'file_name' => 'test.mp3',
    ];
    expect($instance->isAudioFile($audioFile))->toBeTrue();

    // Test with audio extension
    $audioFileByExtension = (object) [
        'file_name' => 'test.wav',
    ];
    expect($instance->isAudioFile($audioFileByExtension))->toBeTrue();

    // Test with non-audio file
    $nonAudioFile = (object) [
        'mime_type' => 'application/pdf',
        'file_name' => 'test.pdf',
    ];
    expect($instance->isAudioFile($nonAudioFile))->toBeFalse();
});

test('component formats file sizes correctly', function () {
    $component = Livewire::test(FileList::class);
    $instance = $component->instance();

    expect($instance->formatFileSize(1024))->toBe('1 KB');
    expect($instance->formatFileSize(1024 * 1024))->toBe('1 MB');
    expect($instance->formatFileSize(1024 * 1024 * 1024))->toBe('1 GB');
    expect($instance->formatFileSize(0))->toBe('0 bytes');
    expect($instance->formatFileSize(512))->toBe('512 bytes');
});

test('component calculates total file size correctly', function () {
    $files = collect([
        (object) ['id' => 1, 'size' => 1024, 'file_name' => 'test1.txt', 'created_at' => now()],
        (object) ['id' => 2, 'size' => 2048, 'file_name' => 'test2.txt', 'created_at' => now()],
        (object) ['id' => 3, 'size' => 512, 'file_name' => 'test3.txt', 'created_at' => now()],
    ]);

    $component = Livewire::test(FileList::class, [
        'files' => $files,
    ]);

    expect($component->get('totalFileSize'))->toBe(3584);
});

test('component respects capability flags', function () {
    $files = collect([
        (object) [
            'id' => 1,
            'file_name' => 'test-audio.mp3',
            'size' => 1024000,
            'mime_type' => 'audio/mpeg',
            'created_at' => now(),
        ],
    ]);

    // Test with all capabilities disabled
    $component = Livewire::test(FileList::class, [
        'files' => $files,
        'canPlay' => false,
        'canDownload' => false,
        'canDelete' => false,
    ]);

    $component->assertStatus(200);
    expect($component->get('canPlay'))->toBeFalse();
    expect($component->get('canDownload'))->toBeFalse();
    expect($component->get('canDelete'))->toBeFalse();
});

test('component handles empty state customization', function () {
    $component = Livewire::test(FileList::class, [
        'files' => collect(),
        'emptyStateMessage' => 'Custom empty message',
        'emptyStateSubMessage' => 'Custom sub message',
    ]);

    $component->assertSee('Custom empty message')
        ->assertSee('Custom sub message');
});

test('component handles newly uploaded file animation', function () {
    $files = collect([
        (object) [
            'id' => 1,
            'file_name' => 'test-file.pdf',
            'size' => 1024,
            'created_at' => now(),
        ],
    ]);

    $component = Livewire::test(FileList::class, [
        'files' => $files,
        'newlyUploadedFileIds' => [1],
        'showAnimations' => true,
    ]);

    $component->assertSeeHtml('animate-fade-in');
});

test('component works with different variants', function () {
    $component = Livewire::test(FileList::class, [
        'files' => collect(),
        'variant' => 'compact',
    ]);

    expect($component->get('variant'))->toBe('compact');
});

// Bulk Actions Tests

test('bulk actions can be enabled and configured', function () {
    $component = Livewire::test(FileList::class, [
        'files' => collect(),
        'enableBulkActions' => true,
        'bulkActions' => ['delete', 'download'],
    ]);

    expect($component->get('enableBulkActions'))->toBeTrue();
    expect($component->get('bulkActions'))->toBe(['delete', 'download']);
});

test('file selection works correctly', function () {
    $files = collect([
        (object) ['id' => 1, 'file_name' => 'test1.txt', 'created_at' => now()],
        (object) ['id' => 2, 'file_name' => 'test2.txt', 'created_at' => now()],
    ]);

    $component = Livewire::test(FileList::class, [
        'files' => $files,
        'enableBulkActions' => true,
    ]);

    // Initially no files selected
    expect($component->get('selectedFileIds'))->toBe([]);
    expect($component->get('hasSelectedFiles'))->toBeFalse();

    // Select first file
    $component->call('toggleFileSelection', 1);
    expect($component->get('selectedFileIds'))->toBe([1]);
    expect($component->get('hasSelectedFiles'))->toBeTrue();
    expect($component->get('isSelectMode'))->toBeTrue();

    // Select second file
    $component->call('toggleFileSelection', 2);
    expect($component->get('selectedFileIds'))->toBe([1, 2]);

    // Deselect first file
    $component->call('toggleFileSelection', 1);
    expect($component->get('selectedFileIds'))->toBe([2]);
});

test('select all functionality works', function () {
    $files = collect([
        (object) ['id' => 1, 'file_name' => 'test1.txt', 'created_at' => now()],
        (object) ['id' => 2, 'file_name' => 'test2.txt', 'created_at' => now()],
        (object) ['id' => 3, 'file_name' => 'test3.txt', 'created_at' => now()],
    ]);

    $component = Livewire::test(FileList::class, [
        'files' => $files,
        'enableBulkActions' => true,
    ]);

    // Select all files
    $component->call('selectAllFiles');
    expect($component->get('selectedFileIds'))->toBe([1, 2, 3]);
    expect($component->get('allFilesSelected'))->toBeTrue();
    expect($component->get('isSelectMode'))->toBeTrue();

    // Clear selection
    $component->call('clearSelection');
    expect($component->get('selectedFileIds'))->toBe([]);
    expect($component->get('allFilesSelected'))->toBeFalse();
    expect($component->get('isSelectMode'))->toBeFalse();
});

test('toggle select all works correctly', function () {
    $files = collect([
        (object) ['id' => 1, 'file_name' => 'test1.txt', 'created_at' => now()],
        (object) ['id' => 2, 'file_name' => 'test2.txt', 'created_at' => now()],
    ]);

    $component = Livewire::test(FileList::class, [
        'files' => $files,
        'enableBulkActions' => true,
    ]);

    // First toggle should select all
    $component->call('toggleSelectAll');
    expect($component->get('allFilesSelected'))->toBeTrue();

    // Second toggle should clear all
    $component->call('toggleSelectAll');
    expect($component->get('selectedFileIds'))->toBe([]);
    expect($component->get('allFilesSelected'))->toBeFalse();
});

test('selected file size calculation works', function () {
    $files = collect([
        (object) ['id' => 1, 'file_name' => 'test1.txt', 'size' => 1024, 'created_at' => now()],
        (object) ['id' => 2, 'file_name' => 'test2.txt', 'size' => 2048, 'created_at' => now()],
        (object) ['id' => 3, 'file_name' => 'test3.txt', 'size' => 512, 'created_at' => now()],
    ]);

    $component = Livewire::test(FileList::class, [
        'files' => $files,
        'enableBulkActions' => true,
    ]);

    // Select first two files
    $component->call('toggleFileSelection', 1);
    $component->call('toggleFileSelection', 2);

    expect($component->get('selectedFileCount'))->toBe(2);
    expect($component->get('selectedFileSize'))->toBe(3072); // 1024 + 2048
});

test('bulk delete dispatches confirmation event', function () {
    $files = collect([
        (object) ['id' => 1, 'file_name' => 'test1.txt', 'created_at' => now()],
        (object) ['id' => 2, 'file_name' => 'test2.txt', 'created_at' => now()],
    ]);

    $component = Livewire::test(FileList::class, [
        'files' => $files,
        'enableBulkActions' => true,
        'bulkActions' => ['delete'],
        'canDelete' => true,
        'modelType' => 'project',
        'modelId' => 123,
    ]);

    // Select files and delete
    $component->call('toggleFileSelection', 1);
    $component->call('toggleFileSelection', 2);
    $component->call('bulkDeleteSelected');

    $component->assertDispatched('bulkFileAction', [
        'action' => 'confirmBulkDeleteFiles',
        'fileIds' => [1, 2],
        'modelType' => 'project',
        'modelId' => 123,
    ]);
});

test('bulk download dispatches correct event', function () {
    $files = collect([
        (object) ['id' => 1, 'file_name' => 'test1.txt', 'created_at' => now()],
        (object) ['id' => 2, 'file_name' => 'test2.txt', 'created_at' => now()],
    ]);

    $component = Livewire::test(FileList::class, [
        'files' => $files,
        'enableBulkActions' => true,
        'bulkActions' => ['download'],
        'canDownload' => true,
        'modelType' => 'project',
        'modelId' => 123,
    ]);

    // Select files and download
    $component->call('toggleFileSelection', 1);
    $component->call('toggleFileSelection', 2);
    $component->call('bulkDownloadSelected');

    $component->assertDispatched('bulkFileAction', [
        'action' => 'bulkDownloadFiles',
        'fileIds' => [1, 2],
        'modelType' => 'project',
        'modelId' => 123,
    ]);
});

test('bulk actions respect permission flags', function () {
    $files = collect([
        (object) ['id' => 1, 'file_name' => 'test1.txt', 'created_at' => now()],
    ]);

    $component = Livewire::test(FileList::class, [
        'files' => $files,
        'enableBulkActions' => true,
        'bulkActions' => ['delete', 'download'],
        'canDelete' => false,
        'canDownload' => false,
    ]);

    // Select file
    $component->call('toggleFileSelection', 1);

    // Try bulk actions - should not dispatch events
    $component->call('bulkDeleteSelected');
    $component->call('bulkDownloadSelected');

    $component->assertNotDispatched('bulkFileAction');
});

test('bulk actions require enableBulkActions flag', function () {
    $files = collect([
        (object) ['id' => 1, 'file_name' => 'test1.txt', 'created_at' => now()],
    ]);

    $component = Livewire::test(FileList::class, [
        'files' => $files,
        'enableBulkActions' => false,
    ]);

    // Selection methods should do nothing when bulk actions disabled
    $component->call('toggleFileSelection', 1);
    expect($component->get('selectedFileIds'))->toBe([]);

    $component->call('selectAllFiles');
    expect($component->get('selectedFileIds'))->toBe([]);
});

test('isFileSelected method works correctly', function () {
    $files = collect([
        (object) ['id' => 1, 'file_name' => 'test1.txt', 'created_at' => now()],
        (object) ['id' => 2, 'file_name' => 'test2.txt', 'created_at' => now()],
    ]);

    $component = Livewire::test(FileList::class, [
        'files' => $files,
        'enableBulkActions' => true,
    ]);

    // Initially no files selected
    expect($component->get('selectedFileIds'))->toBe([]);

    // Select first file
    $component->call('toggleFileSelection', 1);
    expect($component->get('selectedFileIds'))->toBe([1]);

    // Get fresh instance after state change
    $instance = $component->instance();
    expect($instance->isFileSelected(1))->toBeTrue();
    expect($instance->isFileSelected(2))->toBeFalse();
});

// File Upload Event Listener Tests

test('component listens to filesUploaded event and dispatches refresh', function () {
    $files = collect([
        (object) ['id' => 1, 'file_name' => 'test1.txt', 'created_at' => now()],
    ]);

    $component = Livewire::test(FileList::class, [
        'files' => $files,
        'modelType' => 'project',
        'modelId' => 123,
        'enableBulkActions' => true,
    ]);

    // Select a file first
    $component->call('toggleFileSelection', 1);
    expect($component->get('selectedFileIds'))->toBe([1]);

    // Dispatch filesUploaded event with matching model
    $component->dispatch('filesUploaded', [
        'model_type' => \App\Models\Project::class,
        'model_id' => 123,
        'count' => 1,
        'source' => 'uppy',
    ]);

    // Should clear selection and dispatch refresh request
    expect($component->get('selectedFileIds'))->toBe([]);
    $component->assertDispatched('fileListRefreshRequested', [
        'modelType' => 'project',
        'modelId' => 123,
        'source' => 'uppy',
    ]);
});

test('component ignores filesUploaded event for different model', function () {
    $files = collect([
        (object) ['id' => 1, 'file_name' => 'test1.txt', 'created_at' => now()],
    ]);

    $component = Livewire::test(FileList::class, [
        'files' => $files,
        'modelType' => 'project',
        'modelId' => 123,
        'enableBulkActions' => true,
    ]);

    // Select a file first
    $component->call('toggleFileSelection', 1);
    expect($component->get('selectedFileIds'))->toBe([1]);

    // Dispatch filesUploaded event with different model ID
    $component->dispatch('filesUploaded', [
        'model_type' => \App\Models\Project::class,
        'model_id' => 456, // Different ID
        'count' => 1,
        'source' => 'uppy',
    ]);

    // Should NOT clear selection or dispatch refresh
    expect($component->get('selectedFileIds'))->toBe([1]);
    $component->assertNotDispatched('fileListRefreshRequested');
});

test('component handles refreshFiles event', function () {
    $files = collect([
        (object) ['id' => 1, 'file_name' => 'test1.txt', 'created_at' => now()],
    ]);

    $component = Livewire::test(FileList::class, [
        'files' => $files,
        'modelType' => 'project',
        'modelId' => 123,
        'enableBulkActions' => true,
    ]);

    // Select a file first
    $component->call('toggleFileSelection', 1);
    expect($component->get('selectedFileIds'))->toBe([1]);

    // Dispatch refreshFiles event
    $component->dispatch('refreshFiles');

    // Should clear selection and dispatch refresh request
    expect($component->get('selectedFileIds'))->toBe([]);
    $component->assertDispatched('fileListRefreshRequested', [
        'modelType' => 'project',
        'modelId' => 123,
        'source' => 'manual_refresh',
    ]);
});

test('component handles storageChanged event', function () {
    $files = collect([
        (object) ['id' => 1, 'file_name' => 'test1.txt', 'created_at' => now()],
    ]);

    $component = Livewire::test(FileList::class, [
        'files' => $files,
        'modelType' => 'project',
        'modelId' => 123,
        'enableBulkActions' => true,
    ]);

    // Select a file first
    $component->call('toggleFileSelection', 1);
    expect($component->get('selectedFileIds'))->toBe([1]);

    // Dispatch storageChanged event
    $component->dispatch('storageChanged');

    // Should clear selection and dispatch refresh request
    expect($component->get('selectedFileIds'))->toBe([]);
    $component->assertDispatched('fileListRefreshRequested', [
        'modelType' => 'project',
        'modelId' => 123,
        'source' => 'storage_change',
    ]);
});

test('component handles file-deleted event', function () {
    $files = collect([
        (object) ['id' => 1, 'file_name' => 'test1.txt', 'created_at' => now()],
        (object) ['id' => 2, 'file_name' => 'test2.txt', 'created_at' => now()],
    ]);

    $component = Livewire::test(FileList::class, [
        'files' => $files,
        'modelType' => 'project',
        'modelId' => 123,
        'enableBulkActions' => true,
    ]);

    // Select files first
    $component->call('toggleFileSelection', 1);
    $component->call('toggleFileSelection', 2);
    expect($component->get('selectedFileIds'))->toBe([1, 2]);

    // Dispatch file-deleted event (simulating successful deletion)
    $component->dispatch('file-deleted');

    // Should clear selection and dispatch refresh request
    expect($component->get('selectedFileIds'))->toBe([]);
    $component->assertDispatched('fileListRefreshRequested', [
        'modelType' => 'project',
        'modelId' => 123,
        'source' => 'file_deleted',
    ]);
});
