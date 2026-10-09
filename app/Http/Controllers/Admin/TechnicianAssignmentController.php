<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SoilTestAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TechnicianAssignmentController extends Controller
{
    public function store(Request $request, User $user)
    {
        $validated = $request->validate([
            'farm_id' => 'required|exists:farms,id',
            'scheduled_date' => 'required|date',
            'notes' => 'nullable|string|max:2000',
        ]);

        SoilTestAssignment::create([
            'farm_id' => $validated['farm_id'],
            'assigned_technician_id' => $user->id,
            'scheduled_date' => $validated['scheduled_date'],
            'notes' => $validated['notes'] ?? null,
            'status' => 'pending',
            'assigned_by' => Auth::id(),
        ]);

        return redirect()->route('admin.users.show', $user)
            ->with('success', "Farm testing scheduled for {$user->username}.");
    }

    public function cancel(User $user, SoilTestAssignment $assignment)
    {
        abort_unless($assignment->assigned_technician_id === $user->id, 404);

        $assignment->update(['status' => 'cancelled']);

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'Assignment cancelled.');
    }

    public function destroy(User $user, SoilTestAssignment $assignment)
    {
        abort_unless($assignment->assigned_technician_id === $user->id, 404);

        $assignment->delete();

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'Assignment removed.');
    }
}
