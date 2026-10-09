<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Crop;
use App\Models\SoilTestAssignment;
use App\Services\FertilizerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AssignmentController extends Controller
{
    public function __construct(private readonly FertilizerService $fertilizer)
    {
    }

    /**
     * A technician's work list: farms the admin has specifically scheduled for
     * them to test (SoilTestAssignment.assigned_technician_id), not everything
     * in their barangay. Barangay coverage is a separate, broader access gate.
     */
    public function index(Request $request)
    {
        $assignments = SoilTestAssignment::forTechnician(Auth::user())
            ->pending()
            ->with(['farm.farmer', 'farm.locationBarangay'])
            ->orderBy('scheduled_date')
            ->get()
            ->map(fn (SoilTestAssignment $a) => $this->transform($a));

        return response()->json(['assignments' => $assignments]);
    }

    public function show(SoilTestAssignment $assignment)
    {
        $this->authoriseAssignment($assignment);
        $assignment->load(['farm.farmer', 'farm.locationBarangay']);

        return response()->json(['assignment' => $this->transform($assignment, detailed: true)]);
    }

    public function complete(Request $request, SoilTestAssignment $assignment)
    {
        $this->authoriseAssignment($assignment);

        if ($assignment->status !== 'pending') {
            return response()->json(['message' => 'This assignment is no longer pending.'], 422);
        }

        $validated = $request->validate([
            'probe_id' => 'required|string|max:100',
            'probe_model' => 'nullable|string|max:100',
            'ph_level' => 'nullable|numeric|between:0,14',
            'nitrogen_level' => 'required|numeric|min:0',
            'phosphorus_level' => 'required|numeric|min:0',
            'potassium_level' => 'required|numeric|min:0',
            'sample_name' => 'nullable|string|max:150',
        ]);

        $farm = $assignment->farm()->with('farmer')->first();
        $user = $request->user();

        $ph = array_key_exists('ph_level', $validated) && $validated['ph_level'] !== null ? (float) $validated['ph_level'] : null;
        $n = (float) $validated['nitrogen_level'];
        $p = (float) $validated['phosphorus_level'];
        $k = (float) $validated['potassium_level'];

        $sample = $user->soilSamples()->create([
            'farm_id' => $farm->id,
            'farmer_id' => $farm->farmer_id,
            'analysis_type' => 'probe',
            'sample_name' => $validated['sample_name'] ?? ('Field Probe — ' . now()->format('M j, Y')),
            'farmer_name' => $farm->farmer->full_name,
            'address' => $farm->farmer->address,
            'sample_date' => now()->toDateString(),
            'date_tested' => now()->toDateString(),
            'color_hex' => '#8B4513',
            'probe_id' => $validated['probe_id'],
            'probe_model' => $validated['probe_model'] ?? null,
            'ph_level' => $ph,
            'nitrogen_level' => $n,
            'phosphorus_level' => $p,
            'potassium_level' => $k,
            'fertility_score' => $this->fertilizer->computeFertilityScore($ph, $n, $p, $k),
            'recommended_crop' => Crop::topMatchName($ph, $n, $p, $k),
            'analyzed_at' => now(),
        ]);

        $assignment->markCompleted($user, $sample);

        return response()->json([
            'message' => 'Assignment completed.',
            'sample_id' => $sample->id,
            'fertility_score' => $sample->fertility_score,
            'recommended_crop' => $sample->recommended_crop,
        ]);
    }

    private function authoriseAssignment(SoilTestAssignment $assignment): void
    {
        $user = Auth::user();
        abort_unless($user->isAdmin() || $assignment->assigned_technician_id === $user->id, 403);
    }

    private function transform(SoilTestAssignment $a, bool $detailed = false): array
    {
        $data = [
            'id' => $a->id,
            'scheduled_date' => $a->scheduled_date->toDateString(),
            'status' => $a->status,
            'notes' => $a->notes,
            'farm' => [
                'id' => $a->farm->id,
                'name' => $a->farm->farm_name,
                'address' => $a->farm->farm_address,
                'farmer_name' => $a->farm->farmer->full_name,
                'barangay' => $a->farm->locationBarangay->name,
                'latitude' => $a->farm->display_latitude,
                'longitude' => $a->farm->display_longitude,
            ],
        ];

        if ($detailed) {
            $data['farm']['farm_type'] = $a->farm->farm_type;
            $data['farm']['area_hectares'] = $a->farm->area_hectares;
        }

        return $data;
    }
}
