<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Barangay;
use App\Models\SoilTestAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TechnicianBarangayController extends Controller
{
    /** Barangays the authenticated technician is assigned to, with per-barangay counts. */
    public function index(Request $request)
    {
        $user = Auth::user();
        $barangayIds = $user->getAccessibleBarangayIds();

        $barangays = Barangay::whereIn('id', $barangayIds)
            ->withCount(['farmers', 'farms'])
            ->orderBy('name')
            ->get();

        $pendingCounts = SoilTestAssignment::forTechnician($user)
            ->active()
            ->join('farms', 'farms.id', '=', 'soil_test_assignments.farm_id')
            ->whereIn('farms.location_barangay_id', $barangayIds)
            ->selectRaw('farms.location_barangay_id as barangay_id, count(*) as cnt')
            ->groupBy('farms.location_barangay_id')
            ->pluck('cnt', 'barangay_id');

        $completedCounts = $user->soilSamples()
            ->whereNotNull('analyzed_at')
            ->join('farms', 'farms.id', '=', 'soil_samples.farm_id')
            ->whereIn('farms.location_barangay_id', $barangayIds)
            ->selectRaw('farms.location_barangay_id as barangay_id, count(*) as cnt')
            ->groupBy('farms.location_barangay_id')
            ->pluck('cnt', 'barangay_id');

        return response()->json([
            'barangays' => $barangays->map(fn (Barangay $b) => [
                'id' => $b->id,
                'name' => $b->name,
                'farmers_count' => $b->farmers_count,
                'farms_count' => $b->farms_count,
                'pending_tasks_count' => (int) $pendingCounts->get($b->id, 0),
                'completed_count' => (int) $completedCounts->get($b->id, 0),
            ]),
        ]);
    }

    /** Farmers registered under a barangay this technician is assigned to. */
    public function farmers(Request $request, Barangay $barangay)
    {
        $user = Auth::user();
        abort_unless($user->isAdmin() || in_array($barangay->id, $user->getAccessibleBarangayIds()), 403);

        $query = $barangay->farmers()->withCount('farms');

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $farmers = $query->orderBy('last_name')->get();
        $farmerIds = $farmers->pluck('id');

        $pendingCounts = SoilTestAssignment::forTechnician($user)
            ->active()
            ->join('farms', 'farms.id', '=', 'soil_test_assignments.farm_id')
            ->whereIn('farms.farmer_id', $farmerIds)
            ->selectRaw('farms.farmer_id, count(*) as cnt')
            ->groupBy('farms.farmer_id')
            ->pluck('cnt', 'farmer_id');

        $completedCounts = $user->soilSamples()
            ->whereNotNull('analyzed_at')
            ->whereIn('farmer_id', $farmerIds)
            ->selectRaw('farmer_id, count(*) as cnt')
            ->groupBy('farmer_id')
            ->pluck('cnt', 'farmer_id');

        return response()->json([
            'farmers' => $farmers->map(fn ($farmer) => [
                'id' => $farmer->id,
                'full_name' => $farmer->full_name,
                'farms_count' => $farmer->farms_count,
                'pending_tasks_count' => (int) $pendingCounts->get($farmer->id, 0),
                'completed_count' => (int) $completedCounts->get($farmer->id, 0),
            ]),
        ]);
    }
}
