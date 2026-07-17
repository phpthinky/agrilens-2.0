<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\AnalysisController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SampleController;
use App\Http\Controllers\ColorReadingController;
use App\Http\Controllers\AiRecommendationController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\NpkColorChartController;
use App\Http\Controllers\Admin\PhColorChartController;
use App\Http\Controllers\Admin\CropFertilizerScheduleController;
use App\Http\Controllers\BarangayController;
use App\Http\Controllers\FarmerController;
use App\Http\Controllers\FarmController;
use App\Http\Controllers\PublicMapController;
use App\Http\Controllers\PhTestController;
use App\Http\Controllers\ParameterTestController;
use App\Http\Controllers\GeminiCropRecommendationController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\CropRequirementsController;
use App\Http\Controllers\CropController;
use Illuminate\Support\Facades\Route;

// Root: authenticated users go to their dashboard, guests see the public map.
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route(auth()->user()->isAdmin() ? 'admin.dashboard' : 'dashboard')
        : redirect()->route('public.map');
});

// ── Guest routes ─────────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login',    [LoginController::class,    'show'])->name('login');
    Route::post('/login',   [LoginController::class,    'login']);
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// ── Public map ──────────────────────────────────────────────────────────────
Route::get('/map', [PublicMapController::class, 'index'])->name('public.map');
Route::get('/map/farm/{farm}', [PublicMapController::class, 'farmDetails'])->name('public.farm.details');
Route::get('/api/farms-data', [PublicMapController::class, 'getFarmsData'])->name('api.farms-data');

// ── Authenticated routes ──────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {

    // User dashboard
    Route::get('/dashboard', [DashboardController::class, 'user'])->name('dashboard');

    // Soil samples
    Route::get('/samples',              [SampleController::class, 'index'])->name('samples.index');
    Route::get('/samples/{sample}',        [SampleController::class, 'show'])->name('samples.show');
    Route::get('/samples/{sample}/report', [SampleController::class, 'report'])->name('samples.report');
    Route::get('/samples/{sample}/pdf',    [SampleController::class, 'pdf'])->name('samples.pdf');
    Route::get('/samples/{sample}/fertilizer-schedule', [SampleController::class, 'fertilizerSchedule'])->name('samples.fertilizer-schedule');
    Route::post('/samples/{sample}/reset',   [SampleController::class, 'reset'])->name('samples.reset');
    Route::delete('/samples/{sample}',       [SampleController::class, 'destroy'])->name('samples.destroy');

    // pH test workflow (separate 2-step page)
    Route::get('/samples/{sample}/ph-test',       [PhTestController::class, 'show'])->name('ph-test.show');
    Route::post('/samples/{sample}/ph-test/reset',[PhTestController::class, 'reset'])->name('ph-test.reset');

    // N / P / K individual capture pages
    Route::get('/samples/{sample}/test/{parameter}', [ParameterTestController::class, 'show'])
        ->name('parameter-test.show')
        ->where('parameter', 'nitrogen|phosphorus|potassium');

    // API endpoints (called by JavaScript)
    Route::post('/api/color-readings',      [ColorReadingController::class,    'store'])->name('color-readings.store');
    Route::post('/api/ph-test/capture',     [PhTestController::class,          'capture'])->name('ph-test.capture');
    Route::post('/api/ph-test/recapture',   [PhTestController::class,          'recapture'])->name('ph-test.recapture');
    Route::post('/api/ai-recommendation',         [AiRecommendationController::class,       'generate'])->name('ai-recommendation.generate');
    Route::post('/api/gemini-crop-recommendations',[GeminiCropRecommendationController::class, 'generate'])->name('gemini-crop-recommendations.generate');

    // Barangays
    Route::resource('barangays', BarangayController::class);

    // Farmers (ported from Version 1 — replaces the old simple Add-Farmer flow)
    Route::get('/farmers',                [FarmerController::class, 'index'])->name('farmers.index');
    Route::get('/farmers/create',         [FarmerController::class, 'create'])->name('farmers.create');
    Route::post('/farmers',               [FarmerController::class, 'store'])->name('farmers.store');
    Route::get('/farmers/{farmer}',       [FarmerController::class, 'show'])->name('farmers.show');
    Route::get('/farmers/{farmer}/edit',  [FarmerController::class, 'edit'])->name('farmers.edit');
    Route::put('/farmers/{farmer}',       [FarmerController::class, 'update'])->name('farmers.update');
    Route::delete('/farmers/{farmer}',    [FarmerController::class, 'destroy'])->name('farmers.destroy');

    // Farms + GIS mapping
    Route::get('/farms',                  [FarmController::class, 'index'])->name('farms.index');
    Route::get('/farms/create',           [FarmController::class, 'create'])->name('farms.create');
    Route::post('/farms',                 [FarmController::class, 'store'])->name('farms.store');
    Route::get('/farms/{farm}',           [FarmController::class, 'show'])->name('farms.show');
    Route::get('/farms/{farm}/edit',      [FarmController::class, 'edit'])->name('farms.edit');
    Route::put('/farms/{farm}',           [FarmController::class, 'update'])->name('farms.update');
    Route::delete('/farms/{farm}',        [FarmController::class, 'destroy'])->name('farms.destroy');

    Route::get('/api/farmers-by-barangay', [FarmController::class, 'getFarmersByBarangay'])->name('api.farmers-by-barangay');
    Route::get('/api/farms-for-map',       [FarmController::class, 'getFarmsForMap'])->name('api.farms-for-map');
    Route::get('/api/farms-statistics',    [FarmController::class, 'getStatistics'])->name('api.farms-statistics');
    Route::get('/api/farms/all-polygons',  [FarmController::class, 'getAllFarmPolygons'])->name('api.farms.all-polygons');
    Route::post('/api/farms/validate-polygon', [FarmController::class, 'validatePolygonOverlap'])->name('api.farms.validate-polygon');

    // Create Analysis (Manual / Colorimetric / Digital Probe), started from a Farm
    Route::get('/farms/{farm}/analyses/create',        [AnalysisController::class, 'chooseType'])->name('analyses.choose');
    Route::get('/farms/{farm}/analyses/create/manual',  [AnalysisController::class, 'createManual'])->name('analyses.create.manual');
    Route::get('/farms/{farm}/analyses/create/probe',   [AnalysisController::class, 'createProbe'])->name('analyses.create.probe');
    Route::post('/farms/{farm}/analyses',               [AnalysisController::class, 'store'])->name('analyses.store');
    Route::post('/farms/{farm}/analyses/probe/confirm', [AnalysisController::class, 'storeProbe'])->name('analyses.store.probe');

    // Export
    Route::get('/export',                          [ExportController::class, 'export'])->name('export');
    Route::get('/export/phase2',                   [ExportController::class, 'exportPhase2'])->name('export.phase2');
    Route::get('/samples/{sample}/export-excel',   [ExportController::class, 'exportSample'])->name('samples.export-excel');

    // Help & Guidelines
    Route::get('/help', [HelpController::class, 'index'])->name('help.index');

    // Crop pH & NPK Requirements reference (read-only public view)
    Route::get('/crop-requirements',         [CropRequirementsController::class, 'index'])->name('crops.requirements');
    Route::get('/crop-requirements/export',  [CropRequirementsController::class, 'export'])->name('crops.requirements.export');

    // Crops CRUD (admin + technician management)
    Route::get('/crops',              [CropController::class, 'index'])->name('crops.index');
    Route::get('/crops/create',       [CropController::class, 'create'])->name('crops.create');
    Route::post('/crops',             [CropController::class, 'store'])->name('crops.store');
    Route::get('/crops/{crop}/edit',  [CropController::class, 'edit'])->name('crops.edit');
    Route::put('/crops/{crop}',       [CropController::class, 'update'])->name('crops.update');
    Route::delete('/crops/{crop}',    [CropController::class, 'destroy'])->name('crops.destroy');

    // ── Admin-only ────────────────────────────────────────────────────────────
    Route::middleware('can:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard',        [DashboardController::class, 'admin'])->name('dashboard');
        Route::get('/users',            [UserController::class, 'index'])->name('users');
        Route::post('/users',           [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}',     [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}',  [UserController::class, 'destroy'])->name('users.destroy');

        // pH Color Chart management
        Route::get('/ph-color-charts',                      [PhColorChartController::class, 'index'])->name('ph-color-charts');
        Route::post('/ph-color-charts',                     [PhColorChartController::class, 'store'])->name('ph-color-charts.store');
        Route::patch('/ph-color-charts/{phColorChart}',    [PhColorChartController::class, 'toggle'])->name('ph-color-charts.toggle');
        Route::delete('/ph-color-charts/{phColorChart}',   [PhColorChartController::class, 'destroy'])->name('ph-color-charts.destroy');

        // NPK Color Chart management
        Route::get('/npk-color-charts',                     [NpkColorChartController::class, 'index'])->name('npk-color-charts');
        Route::post('/npk-color-charts',                    [NpkColorChartController::class, 'store'])->name('npk-color-charts.store');
        Route::patch('/npk-color-charts/{npkColorChart}',  [NpkColorChartController::class, 'toggle'])->name('npk-color-charts.toggle');
        Route::delete('/npk-color-charts/{npkColorChart}', [NpkColorChartController::class, 'destroy'])->name('npk-color-charts.destroy');

        // Fertilizer Application Schedule management
        Route::get('/crop-fertilizer-schedules',             [CropFertilizerScheduleController::class, 'index'])->name('crop-fertilizer-schedules.index');
        Route::get('/crop-fertilizer-schedules/{crop}/edit',[CropFertilizerScheduleController::class, 'edit'])->name('crop-fertilizer-schedules.edit');
        Route::put('/crop-fertilizer-schedules/{crop}',     [CropFertilizerScheduleController::class, 'update'])->name('crop-fertilizer-schedules.update');
    });
});
