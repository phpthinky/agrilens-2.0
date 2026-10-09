<?php

use App\Http\Controllers\Api\AssignmentController;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

// Public
Route::post('/login', [AuthController::class, 'login'])->name('api.login');

// Field technician app (Sanctum token auth)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');

    // A technician's work list: farms the admin has specifically scheduled for them.
    Route::get('/assignments', [AssignmentController::class, 'index'])->name('api.assignments.index');
    Route::get('/assignments/{assignment}', [AssignmentController::class, 'show'])->name('api.assignments.show');
    Route::post('/assignments/{assignment}/complete', [AssignmentController::class, 'complete'])->name('api.assignments.complete');
});
