<?php

use App\Http\Controllers\Api\AssignmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
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

    // Consolidated pending/completed task list for the mobile homepage.
    Route::get('/tasks', [TaskController::class, 'index'])->name('api.tasks.index');
});
