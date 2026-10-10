<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SoilSample;
use App\Models\SoilTestAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    /** Consolidated task list for the mobile homepage: ?status=pending|completed (default pending). */
    public function index(Request $request)
    {
        $user = Auth::user();
        $status = $request->get('status', 'pending');

        if ($status === 'completed') {
            $tasks = $user->soilSamples()
                ->whereNotNull('analyzed_at')
                ->with(['farm.locationBarangay'])
                ->latest('analyzed_at')
                ->get()
                ->map(fn (SoilSample $s) => [
                    'id' => $s->id,
                    'status' => 'completed',
                    'sample_id' => $s->id,
                    'sample_name' => $s->sample_name,
                    'farmer_name' => $s->farmer_name,
                    'farm_name' => $s->farm?->farm_name,
                    'barangay' => $s->farm?->locationBarangay?->name,
                    'date' => optional($s->analyzed_at)->toDateString(),
                    'fertility_score' => $s->fertility_score,
                ]);

            return response()->json(['tasks' => $tasks]);
        }

        $tasks = SoilTestAssignment::forTechnician($user)
            ->active()
            ->with(['farm.farmer', 'farm.locationBarangay'])
            ->orderBy('scheduled_date')
            ->get()
            ->map(fn (SoilTestAssignment $a) => [
                'id' => $a->id,
                'status' => 'pending',
                'assignment_id' => $a->id,
                'scheduled_date' => $a->scheduled_date->toDateString(),
                'farmer_name' => $a->farm->farmer->full_name,
                'farm_name' => $a->farm->farm_name,
                'barangay' => $a->farm->locationBarangay->name,
                'notes' => $a->notes,
            ]);

        return response()->json(['tasks' => $tasks]);
    }
}
