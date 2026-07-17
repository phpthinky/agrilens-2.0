<?php

namespace App\Http\Controllers;

use App\Models\Barangay;
use App\Models\Farm;
use App\Models\Farmer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FarmController extends Controller
{
    private function rules(): array
    {
        return [
            'farmer_id' => 'required|exists:farmers,id',
            'location_barangay_id' => 'required|exists:barangays,id',
            'farm_name' => 'required|string|max:255',
            'farm_address' => 'nullable|string',
            'area_hectares' => 'nullable|numeric|min:0.0001',
            'farm_type' => 'required|string|in:Riceland,Cornland,Vegetable Farm,Fruit Orchard,Coconut Farm,Mixed Crops,Pasture Land,Fish Pond,Other',
            'polygon_coordinates' => 'nullable|array|min:3',
            'polygon_coordinates.*.lat' => 'required_with:polygon_coordinates|numeric|between:-90,90',
            'polygon_coordinates.*.lng' => 'required_with:polygon_coordinates|numeric|between:-180,180',
            'display_latitude' => 'nullable|numeric|between:-90,90',
            'display_longitude' => 'nullable|numeric|between:-180,180',
            'description' => 'nullable|string',
            'land_tenure' => 'nullable|string|in:Owned,Rented,Shared/Partnership,Caretaker,Government Land,Other',
            'irrigation_type' => 'nullable|string|in:Irrigated,Rainfed,Partially Irrigated,Not Applicable',
            'slope_category' => 'nullable|string|in:Flat (0-3%),Gently Rolling (3-8%),Rolling (8-15%),Moderately Steep (15-25%),Steep (25-35%),Very Steep (>35%)',
            'elevation_meters' => 'nullable|numeric|min:0|max:5000',
            'current_crops' => 'nullable|string',
            'established_year' => 'nullable|integer|min:1900|max:' . date('Y'),
            'notes' => 'nullable|string',
        ];
    }

    public function index(Request $request)
    {
        $query = Auth::user()->getAccessibleFarms()->with(['farmer', 'locationBarangay']);

        if ($request->filled('search')) {
            $query->search($request->search);
        }
        if ($request->filled('farmer')) {
            $query->byFarmer($request->farmer);
        }
        if ($request->filled('location_barangay')) {
            $query->byLocationBarangay($request->location_barangay);
        }
        if ($request->filled('farm_type')) {
            $query->where('farm_type', $request->farm_type);
        }
        if ($request->filled('status')) {
            $request->status === 'active' ? $query->active() : $query->where('is_active', false);
        }

        $sortBy = $request->get('sort', 'farm_name');
        $sortOrder = $request->get('order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $farms = $query->paginate(20)->withQueryString();
        $farmers = Farmer::active()->orderBy('last_name')->get();
        $barangays = Barangay::active()->orderBy('name')->get();

        return view('farms.index', compact('farms', 'farmers', 'barangays'));
    }

    public function create(Request $request)
    {
        $farmers = Farmer::active()->with('barangay')->orderBy('last_name')->get();
        $barangays = Barangay::active()->orderBy('name')->get();
        $selectedFarmer = $request->get('farmer_id') ? Farmer::find($request->get('farmer_id')) : null;

        return view('farms.create', compact('farmers', 'barangays', 'selectedFarmer'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());
        $validated['is_active'] = $request->boolean('is_active', true);

        if (!empty($validated['polygon_coordinates'])) {
            if ($overlap = $this->findOverlappingFarm($validated['polygon_coordinates'])) {
                return back()->withErrors([
                    'polygon_coordinates' => "The farm boundary overlaps with existing farm '{$overlap->farm_name}'. Please redraw the boundaries.",
                ])->withInput();
            }

            $center = (new Farm(['polygon_coordinates' => $validated['polygon_coordinates']]))->calculateCenterPoint();
            $validated['display_latitude'] ??= $center['lat'];
            $validated['display_longitude'] ??= $center['lng'];
            $validated['location_source'] = 'polygon';
        } elseif (!empty($validated['display_latitude']) && !empty($validated['display_longitude'])) {
            $validated['location_source'] = 'manual';
        }

        if (empty($validated['area_hectares'])) {
            $temp = new Farm(['polygon_coordinates' => $validated['polygon_coordinates'] ?? null, 'display_latitude' => $validated['display_latitude'] ?? null]);
            $validated['area_hectares'] = $temp->calculateAreaFromPolygon();
        }

        $farm = Farm::create($validated);

        return redirect()->route('farms.show', $farm)
            ->with('success', "Farm '{$farm->farm_name}' created successfully.");
    }

    public function show(Farm $farm)
    {
        $this->authorise($farm);
        $farm->load(['farmer.barangay', 'locationBarangay', 'soilSamples' => fn ($q) => $q->latest('sample_date')]);
        return view('farms.show', compact('farm'));
    }

    public function edit(Farm $farm)
    {
        $this->authorise($farm);
        $farmers = Farmer::active()->with('barangay')->orderBy('last_name')->get();
        $barangays = Barangay::active()->orderBy('name')->get();

        return view('farms.edit', compact('farm', 'farmers', 'barangays'));
    }

    public function update(Request $request, Farm $farm)
    {
        $this->authorise($farm);
        $validated = $request->validate($this->rules());
        $validated['is_active'] = $request->boolean('is_active', true);

        if (!empty($validated['polygon_coordinates'])) {
            if ($overlap = $this->findOverlappingFarm($validated['polygon_coordinates'], excludeFarmId: $farm->id)) {
                return back()->withErrors([
                    'polygon_coordinates' => "The farm boundary overlaps with existing farm '{$overlap->farm_name}'. Please redraw the boundaries.",
                ])->withInput();
            }
        }

        $farm->update($validated);

        return redirect()->route('farms.show', $farm)
            ->with('success', 'Farm updated successfully.');
    }

    public function destroy(Farm $farm)
    {
        $this->authorise($farm);
        $farmName = $farm->farm_name;
        $farmerName = $farm->farmer->full_name;

        $farm->delete();

        return redirect()->route('farms.index')
            ->with('success', "Farm '{$farmName}' owned by {$farmerName} has been deleted successfully.");
    }

    private function authorise(Farm $farm): void
    {
        if (!Auth::user()->getAccessibleFarms()->whereKey($farm->id)->exists()) {
            abort(403);
        }
    }

    // ── AJAX / API endpoints for the map ────────────────────────────

    public function getFarmersByBarangay(Request $request)
    {
        $barangayId = $request->get('barangay_id');
        if (!$barangayId) {
            return response()->json([]);
        }

        $farmers = Farmer::active()
            ->where('barangay_id', $barangayId)
            ->orderBy('last_name')->orderBy('first_name')
            ->get()
            ->map(fn ($f) => [
                'id' => $f->id,
                'name' => $f->full_name,
                'barangay' => $f->barangay->name,
                'farmer_type' => $f->farmer_type,
            ]);

        return response()->json($farmers);
    }

    public function getFarmsForMap(Request $request)
    {
        $query = Farm::with(['farmer', 'locationBarangay'])->active()->whereNotNull('display_latitude');

        if ($request->filled('barangay_id')) {
            $query->where('location_barangay_id', $request->barangay_id);
        }
        if ($request->filled('farm_type')) {
            $query->where('farm_type', $request->farm_type);
        }

        $farms = $query->get()->map(fn ($farm) => [
            'id' => $farm->id,
            'name' => $farm->farm_name,
            'farmer' => $farm->farmer->full_name,
            'farmer_id' => $farm->farmer->id,
            'type' => $farm->farm_type,
            'area' => $farm->formatted_area,
            'area_numeric' => $farm->area_hectares,
            'location_barangay' => $farm->locationBarangay->name,
            'location_barangay_id' => $farm->locationBarangay->id,
            'polygon_coordinates' => $farm->polygon_coordinates,
            'center' => $farm->center_coordinates,
            'crops' => $farm->formatted_current_crops,
            'irrigation' => $farm->irrigation_type,
            'tenure' => $farm->land_tenure,
            'established_year' => $farm->established_year,
        ]);

        return response()->json($farms);
    }

    public function getStatistics()
    {
        $totalFarms = Farm::count();
        $activeFarms = Farm::active()->count();

        return response()->json([
            'total_farms' => $totalFarms,
            'active_farms' => $activeFarms,
            'inactive_farms' => $totalFarms - $activeFarms,
            'total_area' => round(Farm::active()->sum('area_hectares'), 2),
            'average_farm_size' => $activeFarms > 0 ? round(Farm::active()->avg('area_hectares'), 4) : 0,
            'by_type' => Farm::active()
                ->selectRaw('farm_type, COUNT(*) as count, ROUND(SUM(area_hectares), 2) as total_area')
                ->groupBy('farm_type')->orderByDesc('count')->get(),
            'by_location_barangay' => Farm::active()
                ->join('barangays', 'farms.location_barangay_id', '=', 'barangays.id')
                ->selectRaw('barangays.name, barangays.code, COUNT(*) as count, ROUND(SUM(farms.area_hectares), 2) as total_area')
                ->groupBy('barangays.id', 'barangays.name', 'barangays.code')->orderByDesc('count')->get(),
        ]);
    }

    public function getAllFarmPolygons(Request $request)
    {
        $excludeFarmId = $request->get('exclude_farm_id');

        $farms = Farm::active()
            ->whereNotNull('polygon_coordinates')
            ->when($excludeFarmId, fn ($q) => $q->where('id', '!=', $excludeFarmId))
            ->with('farmer:id,first_name,last_name')
            ->get(['id', 'farm_name', 'farmer_id', 'polygon_coordinates', 'area_hectares'])
            ->map(fn ($farm) => [
                'id' => $farm->id,
                'farm_name' => $farm->farm_name,
                'farmer_name' => $farm->farmer->full_name ?? 'Unknown',
                'area_hectares' => number_format($farm->area_hectares ?? 0, 4),
                'polygon_coordinates' => $farm->polygon_coordinates,
            ]);

        return response()->json(['success' => true, 'farms' => $farms, 'count' => $farms->count()]);
    }

    public function validatePolygonOverlap(Request $request)
    {
        $request->validate([
            'polygon_coordinates' => 'required|array|min:3',
            'polygon_coordinates.*.lat' => 'required|numeric',
            'polygon_coordinates.*.lng' => 'required|numeric',
            'exclude_farm_id' => 'nullable|exists:farms,id',
        ]);

        $overlap = $this->findOverlappingFarm($request->polygon_coordinates, $request->exclude_farm_id);

        if ($overlap) {
            return response()->json([
                'valid' => false,
                'has_overlap' => true,
                'message' => "The polygon overlaps with existing farm '{$overlap->farm_name}'.",
            ], 422);
        }

        return response()->json(['valid' => true, 'has_overlap' => false, 'message' => 'No overlap detected.']);
    }

    private function findOverlappingFarm(array $newPolygon, ?int $excludeFarmId = null): ?Farm
    {
        $existingFarms = Farm::active()
            ->whereNotNull('polygon_coordinates')
            ->when($excludeFarmId, fn ($q) => $q->where('id', '!=', $excludeFarmId))
            ->get();

        foreach ($existingFarms as $farm) {
            if (count($farm->polygon_coordinates ?? []) < 3) {
                continue;
            }
            if ($this->polygonsOverlap($newPolygon, $farm->polygon_coordinates)) {
                return $farm;
            }
        }

        return null;
    }

    private function polygonsOverlap(array $poly1, array $poly2): bool
    {
        foreach ($poly1 as $point) {
            if ($this->pointInPolygon($point, $poly2)) {
                return true;
            }
        }
        foreach ($poly2 as $point) {
            if ($this->pointInPolygon($point, $poly1)) {
                return true;
            }
        }

        $n1 = count($poly1);
        $n2 = count($poly2);
        for ($i = 0; $i < $n1; $i++) {
            $p1 = $poly1[$i];
            $p2 = $poly1[($i + 1) % $n1];
            for ($j = 0; $j < $n2; $j++) {
                $p3 = $poly2[$j];
                $p4 = $poly2[($j + 1) % $n2];
                if ($this->lineSegmentsIntersect($p1, $p2, $p3, $p4)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function pointInPolygon(array $point, array $polygon): bool
    {
        $x = $point['lat'];
        $y = $point['lng'];
        $inside = false;
        $n = count($polygon);

        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $xi = $polygon[$i]['lat'];
            $yi = $polygon[$i]['lng'];
            $xj = $polygon[$j]['lat'];
            $yj = $polygon[$j]['lng'];

            $intersect = (($yi > $y) !== ($yj > $y))
                && ($x < ($xj - $xi) * ($y - $yi) / ($yj - $yi) + $xi);

            if ($intersect) {
                $inside = !$inside;
            }
        }

        return $inside;
    }

    private function lineSegmentsIntersect(array $p1, array $p2, array $p3, array $p4): bool
    {
        $denominator = (($p4['lng'] - $p3['lng']) * ($p2['lat'] - $p1['lat']))
                     - (($p4['lat'] - $p3['lat']) * ($p2['lng'] - $p1['lng']));

        if ($denominator == 0) {
            return false;
        }

        $ua = ((($p4['lat'] - $p3['lat']) * ($p1['lng'] - $p3['lng']))
             - (($p4['lng'] - $p3['lng']) * ($p1['lat'] - $p3['lat']))) / $denominator;

        $ub = ((($p2['lat'] - $p1['lat']) * ($p1['lng'] - $p3['lng']))
             - (($p2['lng'] - $p1['lng']) * ($p1['lat'] - $p3['lat']))) / $denominator;

        return ($ua >= 0 && $ua <= 1 && $ub >= 0 && $ub <= 1);
    }
}
