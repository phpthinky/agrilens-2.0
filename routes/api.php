<?php

use App\Http\Controllers\Api\AssignmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ColorimetricController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\MobileColorimetricController;
use App\Http\Controllers\Api\ScheduleAssignmentController;
use App\Http\Controllers\Api\SoilAnalysisController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TechnicianBarangayController;
use App\Http\Controllers\Api\TechnicianFarmerController;
use Illuminate\Support\Facades\Route;

// Public
Route::post('/login', [AuthController::class, 'login'])->name('api.login');

// Field technician app (Sanctum token auth)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');

    // A technician's work list: farms the admin has specifically scheduled for them.
    Route::get('/assignments', [AssignmentController::class, 'index'])->name('api.assignments.index');
    Route::get('/assignments/{assignment}', [AssignmentController::class, 'show'])->name('api.assignments.show');
    Route::post('/assignments/{assignment}/start', [AssignmentController::class, 'start'])->name('api.assignments.start');
    Route::post('/assignments/{assignment}/complete', [AssignmentController::class, 'complete'])->name('api.assignments.complete');

    // Mobile "Schedule Soil Test" path — technician books a future date on an already-registered farm.
    Route::post('/farms/{farm}/assignments', [ScheduleAssignmentController::class, 'store'])->name('api.farms.assignments.store');

    // Mobile homepage summary.
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('api.dashboard');

    // Barangay -> Farmer -> Farm browsing, scoped to the technician's assignments.
    Route::get('/barangays', [TechnicianBarangayController::class, 'index'])->name('api.barangays.index');
    Route::get('/barangays/{barangay}/farmers', [TechnicianBarangayController::class, 'farmers'])->name('api.barangays.farmers');
    Route::get('/farmers/{farmer}/farms', [TechnicianFarmerController::class, 'farms'])->name('api.farmers.farms');

    // Free-form analysis creation on an already-registered farm (not tied to a pre-scheduled assignment).
    Route::post('/farms/{farm}/analyses', [SoilAnalysisController::class, 'store'])->name('api.farms.analyses.store');

    // Colorimetric testing: the app uploads USB-webcam photos; color picking and analysis stay server-side.
    Route::post('/farms/{farm}/colorimetric-samples', [ColorimetricController::class, 'store'])->name('api.colorimetric.store');
    Route::get('/samples/{sample}/colorimetric', [ColorimetricController::class, 'show'])->name('api.colorimetric.show');
    Route::post('/samples/{sample}/colorimetric/captures', [ColorimetricController::class, 'capture'])->name('api.colorimetric.capture');
    Route::delete('/samples/{sample}/colorimetric/{parameter}', [ColorimetricController::class, 'reset'])
        ->where('parameter', 'ph|nitrogen|phosphorus|potassium')
        ->name('api.colorimetric.reset');
    // Mobile colorimetric: the app downloads the colour charts, reads the photos itself
    // (offline), and uploads the finished test with its photos in one request.
    Route::get('/colorimetric/config', [MobileColorimetricController::class, 'config'])->name('api.mobile-colorimetric.config');
    Route::post('/farms/{farm}/mobile-colorimetric', [MobileColorimetricController::class, 'store'])->name('api.mobile-colorimetric.store');
    Route::post('/samples/{sample}/result-link', [ColorimetricController::class, 'resultLink'])->name('api.colorimetric.result-link');

    // Consolidated pending/completed task list for the mobile homepage.
    Route::get('/tasks', [TaskController::class, 'index'])->name('api.tasks.index');
});
