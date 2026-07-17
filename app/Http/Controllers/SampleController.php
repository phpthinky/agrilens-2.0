<?php

namespace App\Http\Controllers;

use App\Models\Crop;
use App\Models\CropFertilizerSchedule;
use App\Models\Farmer;
use App\Models\SoilSample;
use App\Services\ColorScienceService;
use App\Services\FertilizerService;
use App\Services\FertilizerScheduleService;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Helpers\CropFertilizerHelper;
use App\Helpers\CropPhHelper;
use Barryvdh\DomPDF\Facade\Pdf;

class SampleController extends Controller
{
    public function __construct(
        private readonly ColorScienceService $colorScience,
        private readonly FertilizerService   $fertilizer,
        private readonly FertilizerScheduleService $scheduleService
    ) {}

    // List all samples
    public function index()
    {
        $user = Auth::user();
        $samples = $user->isAdmin()
            ? SoilSample::with('user')->latest()->get()
            : $user->soilSamples()->latest()->get();

        return view('samples.index', compact('samples'));
    }

    // Show create form
    public function create()
    {
         if (!Auth::user()->isAdmin())
            {
                $limit = 5000;
                $samples = Auth::user()->soilSamples()->count();
                if($samples >= $limit) {
                    return redirect()->route('samples.index')
                ->with('error', 'Maximum limit of '.$limit.' samples reached. Please settle the unpaid modification fee to continue using the system.');
                }
            }
        // ── END SAMPLE LIMIT ──────────────────────────────────────────────────────────────────

        $user = Auth::user();
        $farmers = $user->isAdmin()
            ? Farmer::orderBy('name')->get()
            : $user->farmers()->orderBy('name')->get();

        return view('samples.create', compact('farmers'));
    }

    // Store new sample
    public function store(Request $request)
    {
        // ── SAMPLE LIMIT ── comment out the block below once the modification fee is settled ──
        if (!Auth::user()->isAdmin())
            {
                $limit = 5000;
                $samples = Auth::user()->soilSamples()->count();
                if($samples >= $limit) {
                    return redirect()->route('samples.index')
                ->with('error', 'Maximum limit of '.$limit.' samples reached. Please settle the unpaid modification fee to continue using the system.');
                }
            }
        // ── END SAMPLE LIMIT ──────────────────────────────────────────────────────────────────

        $request->validate([
            'sample_name' => 'required|string|max:150',
            'farmer_id'   => 'nullable|integer|exists:farmers,id',
            'farmer_name' => 'required|string|max:150',
            'address'     => 'required|string|max:255',
            'sample_date' => 'required|date|before_or_equal:today',
            'date_tested' => 'required|date|before_or_equal:today|after_or_equal:sample_date',
            'location'    => 'nullable|string|max:200',
        ]);

        $sample = Auth::user()->soilSamples()->create([
            'farmer_id'   => $request->farmer_id ?: null,
            'sample_name' => $request->sample_name,
            'farmer_name' => $request->farmer_name,
            'address'     => $request->address,
            'sample_date' => $request->sample_date,
            'date_tested' => $request->date_tested,
            'location'    => $request->location,
            'color_hex'   => '#8B4513',
        ]);

        return redirect()->route('samples.show', $sample)
            ->with('success', 'Soil sample added successfully! Ready for webcam analysis.');
    }
public function show(SoilSample $sample, 
    FertilizerScheduleService $scheduleService)
{
    $user = Auth::user();
    if (!$user->isAdmin() && $sample->user_id !== $user->id) {
        abort(403);
    }

    // Auto-compute when all 4 averaged colors are present and not yet analyzed
    if ($sample->allAveraged() && !$sample->isAnalyzed()) {
        $phTest = $sample->phTest;
        $ph = ($phTest && $phTest->status === 'complete' && $phTest->final_ph)
            ? (float) $phTest->final_ph
            : $this->colorScience->colorToPhLevel($sample->ph_color_hex);
        $n  = $this->colorScience->colorToNitrogenLevel($sample->nitrogen_color_hex);
        $p  = $this->colorScience->colorToPhosphorusLevel($sample->phosphorus_color_hex);
        $k  = $this->colorScience->colorToPotassiumLevel($sample->potassium_color_hex);
        $fs = $this->fertilizer->computeFertilityScore($ph, $n, $p, $k);

        $sample->update([
            'ph_level'         => $ph,
            'nitrogen_level'   => $n,
            'phosphorus_level' => $p,
            'potassium_level'  => $k,
            'fertility_score'  => $fs,
            'recommended_crop' => Crop::topMatchName($ph, $n, $p, $k),
            'analyzed_at'      => now(),
        ]);

        return redirect()->route('samples.show', $sample);
    }

    $readings = $sample->getReadingsByParameter();
    $fertRec  = [];
    $allCrops = Crop::active()->orderBy('name')->get(); // ← single fetch
    $cropFertData = [];
    $nStatus = $pStatus = $kStatus = null;

    if ($sample->isAnalyzed()) {
        $ph = (float) $sample->ph_level;
        $n  = (float) $sample->nitrogen_level;
        $p  = (float) $sample->phosphorus_level;
        $k  = (float) $sample->potassium_level;

        $fertRec = $this->fertilizer->recommend($ph, $n, $p, $k);

        // Use the same general classifier as the "Current Soil Status" table
        // so Low → n_low column, Medium → n_med, High → n_high (consistent display).
        $nStatus = $this->fertilizer->getNutrientStatus('nitrogen',   $n);
        $pStatus = $this->fertilizer->getNutrientStatus('phosphorus', $p);
        $kStatus = $this->fertilizer->getNutrientStatus('potassium',  $k);

        foreach ($allCrops as $crop) {
            $cropFertData[$crop->id] = CropFertilizerHelper::computeFertilizer($n, $p, $k, $crop, $nStatus, $pStatus, $kStatus);
        }
    }

    $aiEnabled     = !empty(env('ANTHROPIC_API_KEY'));
    $geminiEnabled = !empty(config('services.gemini.api_key'));
    // Crops that have a fertilizer application schedule defined

    $schedulableCrops = $sample->isAnalyzed()
        ? $scheduleService->supportedCrops()   // inject FertilizerScheduleService via constructor
        : collect();

    return view('samples.show', compact(
        'sample', 'readings',
        'fertRec', 'aiEnabled', 'geminiEnabled', 'allCrops', 'cropFertData',
        'schedulableCrops', 'nStatus', 'pStatus', 'kStatus'
    ));
}
    // Show individual test readings report
  
   public function report(SoilSample $sample)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && $sample->user_id !== $user->id) {
            abort(403);
        }

        $readings = $sample->getReadingsByParameter();
        $phTest   = $sample->phTest;
        $fertRec  = [];
        $nStatus  = $pStatus = $kStatus = null;

        if ($sample->isAnalyzed()) {
            $ph = (float)$sample->ph_level;
            $n  = (float)$sample->nitrogen_level;
            $p  = (float)$sample->phosphorus_level;
            $k  = (float)$sample->potassium_level;

            $fertRec = $this->fertilizer->recommend($ph, $n, $p, $k);
            $nStatus = $this->fertilizer->getNutrientStatus('nitrogen',   $n);
            $pStatus = $this->fertilizer->getNutrientStatus('phosphorus', $p);
            $kStatus = $this->fertilizer->getNutrientStatus('potassium',  $k);
        }

        return view('samples.report', compact('sample', 'readings', 'phTest', 'fertRec', 'nStatus', 'pStatus', 'kStatus'));
    }

    // Print-friendly PDF view (browser print-to-PDF)
    public function pdf(SoilSample $sample)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && $sample->user_id !== $user->id) {
            abort(403);
        }

        $phTest      = $sample->phTest;
        $fertRec     = [];
        $nStatus     = $pStatus = $kStatus = null;
        $scoredCrops = [];

        if ($sample->isAnalyzed()) {
            $ph = (float)$sample->ph_level;
            $n  = (float)$sample->nitrogen_level;
            $p  = (float)$sample->phosphorus_level;
            $k  = (float)$sample->potassium_level;

            $fertRec = $this->fertilizer->recommend($ph, $n, $p, $k);
            $nStatus = $this->fertilizer->getNutrientStatus('nitrogen',   $n);
            $pStatus = $this->fertilizer->getNutrientStatus('phosphorus', $p);
            $kStatus = $this->fertilizer->getNutrientStatus('potassium',  $k);

            // Build scored crop list (top 10, same logic as show.blade.php)
            $allCrops = Crop::active()->orderBy('name')->get();

            foreach ($allCrops->take(10) as $crop) {
                $fert = CropFertilizerHelper::computeFertilizer($n, $p, $k, $crop, $nStatus, $pStatus, $kStatus);

                $nSt = $fert['n']['status'] ?? 'N/A';
                $pSt = $fert['p']['status'] ?? 'N/A';
                $kSt = $fert['k']['status'] ?? 'N/A';

                $nTarget  = $fert['n']['target_ppm'] ?? null;
                $pTarget  = $fert['p']['target_ppm'] ?? null;
                $kTarget  = $fert['k']['target_ppm'] ?? null;

                $mopKgHa  = $kTarget !== null ? round($kTarget / 0.60, 2) : null;
                $dapKgHa  = $pTarget !== null ? round($pTarget / 0.46, 2) : null;
                $nFromDap = $dapKgHa !== null ? round($dapKgHa * 0.18, 2) : 0;
                $nRemain  = $nTarget !== null ? max(0, $nTarget - $nFromDap) : null;
                $ureaKgHa = $nRemain !== null ? round($nRemain / 0.46, 2) : null;

                // pH suitability
                $cropPhLow  = (float)($crop->ph_low  ?? 0);
                $cropPhHigh = (float)($crop->ph_high ?? 14);
                $phInRange  = ($ph >= $cropPhLow && $ph <= $cropPhHigh);

                $scoredCrops[] = [
                    'crop'      => $crop,
                    'nSt'       => $nSt,
                    'pSt'       => $pSt,
                    'kSt'       => $kSt,
                    'score'     => count(array_filter([$nSt, $pSt, $kSt], fn($s) => $s === 'Low')),
                    'urea'      => $ureaKgHa,
                    'dap'       => $dapKgHa,
                    'mop'       => $mopKgHa,
                    'phInRange' => $phInRange,
                    'phRange'   => "pH {$cropPhLow}–{$cropPhHigh}",
                ];
            }

            usort($scoredCrops, fn($a, $b) => $b['score'] <=> $a['score']);
        }

        $pdf = Pdf::loadView('samples.pdf', compact(
            'sample', 'phTest', 'fertRec', 'nStatus', 'pStatus', 'kStatus',
            'scoredCrops'
        ))->setPaper('a4', 'portrait');

        $filename = 'soil-report-' . $sample->id . '-' . now()->format('Ymd') . '.pdf';

        return $pdf->download($filename);
    }

// ══════════════════════════════════════════════════════════════════════════════
//  REPLACE ONLY the fertilizerSchedule() method in SampleController.php
//  (the rest of the controller stays the same)
// ══════════════════════════════════════════════════════════════════════════════

public function fertilizerSchedule(
    Request                  $request,
    SoilSample               $sample,
    FertilizerScheduleService $scheduleService
) {
    $user = Auth::user();
    if (!$user->isAdmin() && $sample->user_id !== $user->id) {
        abort(403);
    }

    // ── Resolve the selected crop ─────────────────────────────────────────────
    $cropId   = $request->input('crop_id');
    $crop     = $cropId ? Crop::active()->find($cropId) : null;

    // If no crop given (or invalid), redirect back to the sample page — the
    // modal on show.blade.php will have already validated the selection.
    if (!$crop || !$scheduleService->resolveKey($crop->name)) {
        return redirect()->route('samples.show', $sample)
            ->with('error', 'Please select a valid crop with a fertilizer schedule.');
    }

    // ── Basal fertilizer selection ────────────────────────────────────────────
    $basalFertKey = $request->input('basal_fert', 'urea');
    if (!array_key_exists($basalFertKey, \App\Services\FertilizerScheduleService::BASAL_FERTILIZERS)) {
        $basalFertKey = 'urea';
    }

    // ── Soil nutrient status ──────────────────────────────────────────────────
    $nStatus = $pStatus = $kStatus = null;
    $scheduleMatrix = [];
    $targets = $totals = null;
    $basalFertInfo = \App\Services\FertilizerScheduleService::BASAL_FERTILIZERS['urea'];

    if ($sample->isAnalyzed()) {
        $ph = (float) $sample->ph_level;
        $n  = (float) $sample->nitrogen_level;
        $p  = (float) $sample->phosphorus_level;
        $k  = (float) $sample->potassium_level;

        $nStatus = $this->fertilizer->getNutrientStatus('nitrogen',   $n);
        $pStatus = $this->fertilizer->getNutrientStatus('phosphorus', $p);
        $kStatus = $this->fertilizer->getNutrientStatus('potassium',  $k);

        // ── Build the schedule using actual crop LMH targets ──────────────────
        $rawSchedule = $scheduleService->scheduleForCrop($crop, $nStatus, $pStatus, $kStatus, $basalFertKey);

        if ($rawSchedule) {
            foreach ([1, 2, 3] as $index) {
                $scheduleMatrix[$index] = [
                    'label'      => $rawSchedule[$index]['stage'],
                    'basal_fert' => $rawSchedule[$index]['basal_fert'],
                    'urea'       => $rawSchedule[$index]['urea'],
                    'dap'        => $rawSchedule[$index]['dap'],
                    'mop'        => $rawSchedule[$index]['mop'],
                    'fertilizers' => $rawSchedule[$index]['fertilizers'],
                ];
            }

            $basalFertInfo = $rawSchedule['basal_fert_info']
                ?? \App\Services\FertilizerScheduleService::BASAL_FERTILIZERS['urea'];
        }

        $targets = $rawSchedule['targets'] ?? null;
        $totals  = $rawSchedule['totals']  ?? null;
    }

    $fertTypes = \App\Services\FertilizerScheduleService::FERT_TYPES;

    return view('samples.fertilizer-schedule', compact(
        'sample', 'nStatus', 'pStatus', 'kStatus',
        'scheduleMatrix', 'fertTypes', 'basalFertInfo',
        'crop', 'targets', 'totals'
    ));
}
    // Reset all readings for re-capture
    public function reset(SoilSample $sample)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && $sample->user_id !== $user->id) {
            abort(403);
        }

        // Delete NPK captured image files before clearing the DB rows.
        foreach ($sample->colorReadings()->whereIn('parameter', ['nitrogen', 'phosphorus', 'potassium'])->get() as $rd) {
            if ($rd->captured_image) {
                $full = public_path($rd->captured_image);
                if (is_file($full)) @unlink($full);
            }
        }

        $sample->colorReadings()->delete();
        $sample->update([
            'ph_color_hex'         => null,
            'nitrogen_color_hex'   => null,
            'phosphorus_color_hex' => null,
            'potassium_color_hex'  => null,
            'ph_level'             => null,
            'nitrogen_level'       => null,
            'phosphorus_level'     => null,
            'potassium_level'      => null,
            'fertility_score'      => null,
            'analyzed_at'          => null,
            'ai_recommendation'    => null,
            'recommended_crop'     => null,
            'tests_completed'      => 0,
        ]);

        return redirect()->route('samples.show', $sample)
            ->with('success', 'All readings have been reset. You can re-capture now.');
    }

    // Delete a sample permanently — admin only, requires password confirmation.
    public function destroy(Request $request, SoilSample $sample)
    {
        if (!Auth::user()->isAdmin()) {
            abort(403);
        }

        $request->validate([
            'admin_password' => 'required|string',
        ]);

        if (!Hash::check($request->admin_password, Auth::user()->password)) {
            return redirect()->route('samples.show', $sample)
                ->with('error', 'Incorrect password. Sample was NOT deleted.');
        }

        // Delete the sample; SoilSample::booted() deleting observer wipes public/captures/{id}/.
        $sample->delete();

        return redirect()->route('samples.index')
            ->with('success', "Sample \"{$sample->sample_name}\" and all its data have been permanently deleted.");
    }
}
