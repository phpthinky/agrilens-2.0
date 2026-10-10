<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Models\SoilTestAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScheduleAssignmentController extends Controller
{
    /**
     * Mobile "Schedule Soil Test" path (New Analysis -> Option B): the
     * technician books a future date for themself on an already-registered
     * farm, instead of an admin doing it from the web Technician Management
     * page. Same SoilTestAssignment model/lifecycle either way.
     */
    public function store(Request $request, Farm $farm)
    {
        $user = Auth::user();
        abort_unless(
            $user->isAdmin() || in_array($farm->location_barangay_id, $user->getAccessibleBarangayIds()),
            403
        );

        $validated = $request->validate([
            'scheduled_date' => 'required|date|after_or_equal:today',
            'notes' => 'nullable|string|max:2000',
        ]);

        $assignment = SoilTestAssignment::create([
            'farm_id' => $farm->id,
            'assigned_technician_id' => $user->id,
            'scheduled_date' => $validated['scheduled_date'],
            'notes' => $validated['notes'] ?? null,
            'status' => 'pending',
            'assigned_by' => $user->id,
        ]);

        return response()->json([
            'message' => 'Soil test scheduled.',
            'assignment_id' => $assignment->id,
            'scheduled_date' => $assignment->scheduled_date->toDateString(),
        ], 201);
    }
}
