<?php

namespace App\Http\Controllers;

use App\Models\Barangay;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BarangayController extends Controller
{
    public function index(Request $request)
    {
        $query = Barangay::withCount(['farmers', 'farms']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $sortBy = $request->get('sort', 'name');
        $sortOrder = $request->get('order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $barangays = $query->paginate(15)->withQueryString();

        return view('barangays.index', compact('barangays'));
    }

    public function create()
    {
        $technicians = User::where('user_type', '!=', 'admin')->orderBy('username')->get();
        return view('barangays.create', compact('technicians'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:barangays,name',
            'code' => 'required|string|max:10|unique:barangays,code',
            'description' => 'nullable|string|max:1000',
            'area_hectares' => 'nullable|numeric|min:0|max:999999.99',
            'population' => 'nullable|integer|min:0|max:999999',
            'technician_ids' => 'nullable|array',
            'technician_ids.*' => 'exists:users,id',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $technicianIds = $validated['technician_ids'] ?? [];
        unset($validated['technician_ids']);

        $barangay = Barangay::create($validated);
        $barangay->assignedTechnicians()->sync($technicianIds);

        return redirect()->route('barangays.index')
            ->with('success', "Barangay '{$barangay->name}' created successfully.");
    }

    public function show(Barangay $barangay)
    {
        $barangay->loadCount(['farmers', 'farms']);
        $barangay->load('assignedTechnicians');

        $stats = [
            'total_farmers' => $barangay->farmers_count,
            'active_farmers' => $barangay->farmers()->where('is_active', true)->count(),
            'total_farms' => $barangay->farms_count,
            'total_area' => $barangay->farms()->sum('area_hectares'),
            'soil_samples' => $barangay->soil_samples_count,
        ];

        return view('barangays.show', compact('barangay', 'stats'));
    }

    public function edit(Barangay $barangay)
    {
        $technicians = User::where('user_type', '!=', 'admin')->orderBy('username')->get();
        $assignedTechnicianIds = $barangay->assignedTechnicians()->pluck('users.id')->toArray();

        return view('barangays.edit', compact('barangay', 'technicians', 'assignedTechnicianIds'));
    }

    public function update(Request $request, Barangay $barangay)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('barangays', 'name')->ignore($barangay->id)],
            'code' => ['required', 'string', 'max:10', Rule::unique('barangays', 'code')->ignore($barangay->id)],
            'description' => 'nullable|string|max:1000',
            'area_hectares' => 'nullable|numeric|min:0|max:999999.99',
            'population' => 'nullable|integer|min:0|max:999999',
            'technician_ids' => 'nullable|array',
            'technician_ids.*' => 'exists:users,id',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $technicianIds = $validated['technician_ids'] ?? [];
        unset($validated['technician_ids']);

        $barangay->update($validated);
        $barangay->assignedTechnicians()->sync($technicianIds);

        return redirect()->route('barangays.index')
            ->with('success', "Barangay '{$barangay->name}' updated successfully.");
    }

    public function destroy(Barangay $barangay)
    {
        if (!$barangay->canBeDeleted()) {
            return redirect()->route('barangays.index')
                ->with('error', "Cannot delete barangay '{$barangay->name}': " . implode(', ', $barangay->getDeletionBlockers()));
        }

        $name = $barangay->name;
        $barangay->delete();

        return redirect()->route('barangays.index')
            ->with('success', "Barangay '{$name}' deleted successfully.");
    }
}
