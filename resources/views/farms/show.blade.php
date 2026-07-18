@extends('layouts.app')
@section('title', $farm->farm_name)
@section('content')

<div class="row mb-3">
    <div class="col-md-8">
        <h2><i class="fas fa-tractor me-2"></i>{{ $farm->farm_name }}</h2>
        <p class="text-muted mb-0">
            <a href="{{ route('farmers.show', $farm->farmer) }}">{{ $farm->farmer->full_name }}</a>
            &middot; {{ $farm->locationBarangay->name }} &middot; {{ $farm->farm_type }}
        </p>
    </div>
    <div class="col-md-4 text-end">
        <a href="{{ route('farms.edit', $farm) }}" class="btn btn-outline-secondary"><i class="fas fa-edit me-1"></i>Edit</a>
        <a href="{{ route('farms.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i></a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-header">Farm Details</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Area</dt><dd class="col-sm-7">{{ $farm->formatted_area }}</dd>
                    <dt class="col-sm-5">Land Tenure</dt><dd class="col-sm-7">{{ $farm->land_tenure }}</dd>
                    <dt class="col-sm-5">Irrigation</dt><dd class="col-sm-7">{{ $farm->irrigation_type }}</dd>
                    <dt class="col-sm-5">Slope</dt><dd class="col-sm-7">{{ $farm->slope_category ?: '—' }}</dd>
                    <dt class="col-sm-5">Elevation</dt><dd class="col-sm-7">{{ $farm->elevation_meters ? $farm->elevation_meters.' m' : '—' }}</dd>
                    <dt class="col-sm-5">Established</dt><dd class="col-sm-7">{{ $farm->established_year ?? '—' }}</dd>
                    <dt class="col-sm-5">Current Crops</dt><dd class="col-sm-7">{{ $farm->formatted_current_crops }}</dd>
                    <dt class="col-sm-5">Address</dt><dd class="col-sm-7">{{ $farm->formatted_address }}</dd>
                </dl>
            </div>
        </div>

        @if($farm->polygon_coordinates)
        <div class="card">
            <div class="card-header">Boundary</div>
            <div class="card-body">
                <div id="farmShowMap" style="height: 280px; border-radius: 8px;"></div>
            </div>
        </div>
        @endif
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Soil Analyses ({{ $farm->soilSamples->count() }})</span>
                @if(Route::has('samples.create'))
                <a href="{{ route('samples.create', $farm) }}" class="btn btn-sm btn-success">
                    <i class="fas fa-plus me-1"></i>Create Analysis
                </a>
                @endif
            </div>
            <div class="card-body">
                @forelse($farm->soilSamples as $sample)
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <div>
                            <a href="{{ route('samples.show', $sample) }}">{{ $sample->sample_name }}</a>
                            <span class="badge bg-secondary ms-1">{{ ucfirst($sample->analysis_type ?? 'colorimetric') }}</span>
                        </div>
                        <div class="text-end">
                            @if(!is_null($sample->fertility_score))
                                <span class="badge bg-{{ $sample->fertilityColorClass() }}">{{ $sample->fertility_score }}%</span>
                            @endif
                            <span class="text-muted small ms-1">{{ $sample->sample_date->format('M j, Y') }}</span>
                        </div>
                    </div>
                @empty
                    <p class="text-muted mb-0">No soil analyses recorded for this farm yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

@if($farm->polygon_coordinates)
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const coords = @json($farm->polygon_coordinates).map(p => [p.lat, p.lng]);
    const map = L.map('farmShowMap');
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap contributors' }).addTo(map);
    const polygon = L.polygon(coords, { color: '#28a745' }).addTo(map);
    map.fitBounds(polygon.getBounds(), { padding: [20, 20] });
});
</script>
@endif
@endsection
