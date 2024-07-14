<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MixController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\PitchController;
use App\Http\Controllers\PitchFileController;
use App\Livewire\CreateProject;
use App\Livewire\ManageProject;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('home');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');
});

Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');


Route::middleware(['auth'])->group(function () {
    Route::post('/projects/store', [ProjectController::class, 'storeProject'])->name('projects.store');
    Route::get('/projects/upload', [ProjectController::class, 'createProject'])->name('projects.upload');
    Route::get('/projects/{project}/step2', [ProjectController::class, 'createStep2'])->name('projects.createStep2');
    Route::post('/projects/{project}/step2', [ProjectController::class, 'storeStep2'])->name('projects.storeStep2');
    //Route::get('projects/{project}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
    Route::put('projects/{project}', [ProjectController::class, 'update'])->name('projects.update');

    // Route::get('/create-project', function () {
    //     return view('livewire.create-project');
    // });

    Route::get('/create-project', CreateProject::class)->name('projects.create');
    Route::get('/edit-project/{project}', CreateProject::class)->name('projects.edit');
    Route::get('/manage-project/{project}', ManageProject::class)->name('projects.manage');


    Route::delete('projects/{project}/files/{file}', [ProjectController::class, 'deleteFile'])->name('projects.deleteFile');

    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
    Route::get('/projects/{project}/download', [ProjectController::class, 'download'])->name('projects.download');

    Route::get('/projects/{project}/mixes/create', [MixController::class, 'create'])->name('mixes.create');
    Route::post('/projects/{project}/mixes', [MixController::class, 'store'])->name('mixes.store');
    Route::patch('/mixes/{mix}/rate', [MixController::class, 'rate'])->name('mixes.rate');

    Route::resource('/pitches', PitchController::class);
    Route::get('/pitches/create/{project}', [PitchController::class, 'create'])->name('pitches.create');
    Route::post('/pitches/{pitch}/status', [PitchController::class, 'updateStatus'])->name('pitches.updateStatus');
    Route::get('/pitches/{pitch}/{pitchSnapshot}', [PitchController::class, 'showSnapshot'])->name('pitches.showSnapshot');

    Route::get('/pitch-files/{file}', [PitchFileController::class, 'show'])->name('pitch-files.show');
    Route::get('/pitch-files/download/{file}', [PitchFileController::class, 'download'])
        ->name('pitch-files.download')
        ->middleware('auth');

    Route::delete('/pitch-files/{file}', [PitchFileController::class, 'delete'])
        ->name('pitch-files.delete')
        ->middleware('auth');
});
