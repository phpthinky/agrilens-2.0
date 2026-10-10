<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SoilSample;
use App\Models\SoilTestAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Mobile homepage summary: counts + recent activity, scoped to the
     * authenticated technician's barangay assignments.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $recentActivity = $user->soilSamples()
            ->whereNotNull('analyzed_at')
            ->with(['farm.locationBarangay'])
            ->latest('analyzed_at')
            ->limit(5)
            ->get()
            ->map(fn (SoilSample $s) => [
                'id' => $s->id,
                'sample_name' => $s->sample_name,
                'farmer_name' => $s->farmer_name,
                'farm_name' => $s->farm?->farm_name,
                'barangay' => $s->farm?->locationBarangay?->name,
                'fertility_score' => $s->fertility_score,
                'date' => optional($s->analyzed_at)->toDateString(),
            ]);

        $today = now()->toDateString();

        return response()->json([
            'assigned_barangays_count' => count($user->getAccessibleBarangayIds()),
            'registered_farmers_count' => $user->getAccessibleFarmers()->count(),
            'pending_tasks_count' => SoilTestAssignment::forTechnician($user)->active()->count(),
            'today_scheduled_count' => SoilTestAssignment::forTechnician($user)->active()->whereDate('scheduled_date', $today)->count(),
            'overdue_count' => SoilTestAssignment::forTechnician($user)->active()->whereDate('scheduled_date', '<', $today)->count(),
            'completed_analyses_count' => $user->soilSamples()->whereNotNull('analyzed_at')->count(),
            'recent_activity' => $recentActivity,
        ]);
    }
}
