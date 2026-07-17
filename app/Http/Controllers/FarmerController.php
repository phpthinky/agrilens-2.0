<?php

namespace App\Http\Controllers;

use App\Models\Barangay;
use App\Models\Farmer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FarmerController extends Controller
{
    private function rules(?Farmer $farmer = null): array
    {
        return [
            'barangay_id' => 'required|exists:barangays,id',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'suffix' => 'nullable|string|max:10',
            'gender' => 'required|in:Male,Female,Other',
            'birth_date' => 'nullable|date|before:today',
            'contact_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255|unique:farmers,email' . ($farmer ? ",{$farmer->id}" : ''),
            'address' => 'required|string|max:500',
            'id_type' => 'nullable|string|max:100',
            'id_number' => 'nullable|string|max:100',
            'farmer_type' => 'required|in:Owner,Tenant,Caretaker,Other',
            'years_farming' => 'nullable|integer|min:0|max:99',
            'crops_grown' => 'nullable|string|max:1000',
            'total_farm_area' => 'nullable|numeric|min:0|max:999999.99',
            'education_level' => 'nullable|in:Elementary,Elementary Graduate,High School,High School Graduate,Vocational,College,College Graduate,Post Graduate',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function index(Request $request)
    {
        $query = Auth::user()->getAccessibleFarmers()->with('barangay');

        if ($request->filled('search')) {
            $query->search($request->search);
        }
        if ($request->filled('barangay')) {
            $query->byBarangay($request->barangay);
        }
        if ($request->filled('status')) {
            $request->status === 'active' ? $query->active() : $query->where('is_active', false);
        }
        if ($request->filled('farmer_type')) {
            $query->where('farmer_type', $request->farmer_type);
        }

        $sortBy = $request->get('sort', 'last_name');
        $sortOrder = $request->get('order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $farmers = $query->paginate(20)->withQueryString();
        $barangays = Barangay::active()->orderBy('name')->get();

        return view('farmers.index', compact('farmers', 'barangays'));
    }

    public function create()
    {
        $barangays = Barangay::active()->orderBy('name')->get();
        return view('farmers.create', compact('barangays'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());
        $validated['is_active'] = $request->boolean('is_active', true);

        $farmer = Farmer::create($validated);

        return redirect()->route('farmers.show', $farmer)
            ->with('success', 'Farmer registered successfully.');
    }

    public function show(Farmer $farmer)
    {
        $this->authorise($farmer);
        $farmer->load(['barangay', 'farms']);
        return view('farmers.show', compact('farmer'));
    }

    public function edit(Farmer $farmer)
    {
        $this->authorise($farmer);
        $barangays = Barangay::active()->orderBy('name')->get();
        return view('farmers.edit', compact('farmer', 'barangays'));
    }

    public function update(Request $request, Farmer $farmer)
    {
        $this->authorise($farmer);

        $validated = $request->validate($this->rules($farmer));
        $validated['is_active'] = $request->boolean('is_active', true);

        $farmer->update($validated);

        return redirect()->route('farmers.show', $farmer)
            ->with('success', 'Farmer information updated successfully.');
    }

    public function destroy(Farmer $farmer)
    {
        $this->authorise($farmer);
        $farmer->delete();

        return redirect()->route('farmers.index')
            ->with('success', 'Farmer record deleted successfully.');
    }

    private function authorise(Farmer $farmer): void
    {
        if (!Auth::user()->getAccessibleFarmers()->whereKey($farmer->id)->exists()) {
            abort(403);
        }
    }
}
