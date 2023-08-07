<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MixController;


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

Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');


Route::middleware(['auth'])->group(function () {
    Route::post('/projects/store', [ProjectController::class, 'storeProject'])->name('projects.store');
    Route::get('/projects/upload', [ProjectController::class, 'createProject'])->name('projects.upload');
    Route::get('/projects/{project}/step2', [ProjectController::class, 'createStep2'])->name('projects.createStep2');
    Route::post('/projects/{project}/step2', [ProjectController::class, 'storeStep2'])->name('projects.storeStep2');
    Route::get('projects/{project}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
    Route::put('projects/{project}', [ProjectController::class, 'update'])->name('projects.update');

    Route::delete('projects/{project}/files/{file}', [ProjectController::class, 'deleteFile'])->name('projects.deleteFile');

    //Route::post('/projects/storeStep2', [ProjectController::class, 'storeStep2'])->name('projects.storeStep2');
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
    Route::get('/projects/{project}/download', [ProjectController::class, 'download'])->name('projects.download');

    Route::get('/projects/{slug}/mixes/create', [MixController::class, 'create'])->name('mixes.create');
    Route::post('/projects/{project}/mixes', [MixController::class, 'store'])->name('mixes.store');
    Route::patch('/mixes/{mix}/rate', [MixController::class, 'rate'])->name('mixes.rate');

});

// Route::get('/tracks', [TrackController::class, 'index'])->name('tracks.index');
// Route::get('/tracks/{id}', [TrackController::class, 'show'])->name('tracks.show');

//Route::get('/projects', [TrackController::class, 'projects'])->name('projects.index');
//Route::delete('/tracks/{id}', [TrackController::class, 'destroy'])->name('tracks.destroy');
//Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
Route::get('/projects/{slug}', [ProjectController::class, 'show'])->name('projects.show');


//Route::get('/projects/{id}/download', [ProjectController::class, 'download'])->name('projects.download');


// Route::middleware(['auth'])->group(function () {
//     Route::get('/projects/upload', [TrackController::class, 'createProject'])->name('projects.upload');
//     Route::post('/projects/store', [TrackController::class, 'storeProject'])->name('projects.store');
// });


Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::post('/tracks/upload', [\App\Http\Controllers\TrackController::class, 'upload'])->name('tracks.upload');


require __DIR__ . '/auth.php';
