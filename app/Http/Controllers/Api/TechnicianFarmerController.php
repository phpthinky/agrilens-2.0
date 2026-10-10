<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Farmer;
use Illuminate\Support\Facades\Auth;

class TechnicianFarmerController extends Controller
{
    /** Registered farms for a farmer, for the technician to pick before starting an analysis. */
    public function farms(Farmer $farmer)
    {
        $user = Auth::user();
        abort_unless(
            $user->isAdmin() || in_array($farmer->barangay_id, $user->getAccessibleBarangayIds()),
            403
        );

        $farms = $farmer->farms()
            ->active()
            ->with('locationBarangay')
            ->orderBy('farm_name')
            ->get()
            ->map(fn ($farm) => [
                'id' => $farm->id,
                'name' => $farm->farm_name,
                'address' => $farm->farm_address,
                'barangay' => $farm->locationBarangay->name,
                'farm_type' => $farm->farm_type,
                'area_hectares' => $farm->area_hectares,
                'latitude' => $farm->display_latitude,
                'longitude' => $farm->display_longitude,
            ]);

        return response()->json(['farms' => $farms]);
    }
}
