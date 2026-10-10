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
     * A technician's work list: farms the admin (or the technician themself, via
     * the Schedule Soil Test path) has scheduled them to test
     * (SoilTestAssignment.assigned_technician_id), not everything in their
     * barangay. Barangay coverage is a separate, broader access gate. Overdue
     * items stay in this list — overdue is derived display status, not a
     * removal from the queue.
     */
    public function index(Request $request)
    {
        $assignments = SoilTestAssignment::forTechnician(Auth::user())
            ->active()
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

    /** The technician has opened the task and is about to begin testing — pending -> in_progress. */
    public function start(SoilTestAssignment $assignment)
    {
        $this->authoriseAssignment($assignment);

        if (!in_array($assignment->status, ['pending', 'in_progress'])) {
            return response()->json(['message' => 'This assignment is no longer open.'], 422);
        }

        if ($lockMessage = $assignment->lockMessage()) {
            return response()->json(['message' => $lockMessage, 'locked' => true], 422);
        }

        $assignment->markInProgress();

        return response()->json(['assignment' => $this->transform($assignment->fresh(['farm.farmer', 'farm.locationBarangay']), detailed: true)]);
    }

    public function complete(Request $request, SoilTestAssignment $assignment)
    {
        $this->authoriseAssignment($assignment);

        if (!in_array($assignment->status, ['pending', 'in_progress'])) {
            return response()->json(['message' => 'This assignment is no longer pending.'], 422);
        }

        // Re-checked server-side even if the mobile app already gated the "Begin
        // Soil Testing" button — a stale client or a direct API call must not be
        // able to bypass the scheduled-date restriction.
        if ($lockMessage = $assignment->lockMessage()) {
            return response()->json(['message' => $lockMessage, 'locked' => true], 422);
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

        $resultFields = [
            'analysis_type' => 'probe',
            'probe_id' => $validated['probe_id'],
            'probe_model' => $validated['probe_model'] ?? null,
            'ph_level' => $ph,
            'nitrogen_level' => $n,
            'phosphorus_level' => $p,
            'potassium_level' => $k,
            'fertility_score' => $this->fertilizer->computeFertilityScore($ph, $n, $p, $k),
            'recommended_crop' => Crop::topMatchName($ph, $n, $p, $k),
            'analyzed_at' => now(),
        ];

        // Scheduled from the web (AnalysisController::startProbe): finish the
        // already-existing sample instead of creating a duplicate.
        if ($assignment->soil_sample_id && ($sample = $assignment->soilSample)) {
            $sample->update($resultFields);
        } else {
            $sample = $user->soilSamples()->create(array_merge($resultFields, [
                'farm_id' => $farm->id,
                'farmer_id' => $farm->farmer_id,
                'sample_name' => $validated['sample_name'] ?? ('Field Probe — ' . now()->format('M j, Y')),
                'farmer_name' => $farm->farmer->full_name,
                'address' => $farm->farmer->address,
                'sample_date' => now()->toDateString(),
                'date_tested' => now()->toDateString(),
                'color_hex' => '#8B4513',
            ]));
        }

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
            'status' => $a->displayStatus(),
            'can_start' => $a->canStartToday(),
            'lock_message' => $a->lockMessage(),
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
