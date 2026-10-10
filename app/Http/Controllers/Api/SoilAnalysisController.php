<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Crop;
use App\Models\Farm;
use App\Services\FertilizerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SoilAnalysisController extends Controller
{
    public function __construct(private readonly FertilizerService $fertilizer)
    {
    }

    /**
     * Create a soil analysis directly on an existing, registered farm — the
     * mobile app's free-form path (Barangay -> Farmer -> Farm -> New Analysis),
     * as opposed to AssignmentController::complete which closes out a farm the
     * admin specifically pre-scheduled.
     */
    public function store(Request $request, Farm $farm)
    {
        $user = Auth::user();
        abort_unless(
            $user->isAdmin() || in_array($farm->location_barangay_id, $user->getAccessibleBarangayIds()),
            403
        );

        $validated = $request->validate([
            'analysis_type' => 'required|in:manual,probe',
            'sample_name' => 'nullable|string|max:150',
            'probe_id' => 'required_if:analysis_type,probe|nullable|string|max:100',
            'probe_model' => 'nullable|string|max:100',
            'ph_level' => 'nullable|numeric|between:0,14',
            'nitrogen_level' => 'required|numeric|min:0',
            'phosphorus_level' => 'required|numeric|min:0',
            'potassium_level' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:2000',
        ]);

        $farm->loadMissing('farmer');

        $ph = array_key_exists('ph_level', $validated) && $validated['ph_level'] !== null
            ? (float) $validated['ph_level']
            : null;
        $n = (float) $validated['nitrogen_level'];
        $p = (float) $validated['phosphorus_level'];
        $k = (float) $validated['potassium_level'];

        $sample = $user->soilSamples()->create([
            'farm_id' => $farm->id,
            'farmer_id' => $farm->farmer_id,
            'analysis_type' => $validated['analysis_type'],
            'sample_name' => $validated['sample_name']
                ?? ('Field ' . ucfirst($validated['analysis_type']) . ' — ' . now()->format('M j, Y')),
            'farmer_name' => $farm->farmer->full_name,
            'address' => $farm->farmer->address,
            'sample_date' => now()->toDateString(),
            'date_tested' => now()->toDateString(),
            'color_hex' => '#8B4513',
            'probe_id' => $validated['probe_id'] ?? null,
            'probe_model' => $validated['probe_model'] ?? null,
            'ph_level' => $ph,
            'nitrogen_level' => $n,
            'phosphorus_level' => $p,
            'potassium_level' => $k,
            'notes' => $validated['notes'] ?? null,
            'fertility_score' => $this->fertilizer->computeFertilityScore($ph, $n, $p, $k),
            'recommended_crop' => Crop::topMatchName($ph, $n, $p, $k),
            'analyzed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Soil analysis saved.',
            'sample_id' => $sample->id,
            'fertility_score' => $sample->fertility_score,
            'recommended_crop' => $sample->recommended_crop,
        ], 201);
    }
}
