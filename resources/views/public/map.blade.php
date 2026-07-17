@extends('layouts.public')
@section('title', 'Public Map')
@section('content')

<div class="container py-4">
<div class="row mb-3">
    <div class="col-md-8">
        <h2><i class="fas fa-map me-2"></i>Interactive Farm Map</h2>
        <p class="text-muted mb-0">Public overview of registered farms and soil health</p>
    </div>
    <div class="col-md-4">
        <select id="barangayFilter" class="form-select">
            <option value="">All Barangays</option>
            @foreach($barangays as $b)
                <option value="{{ $b->id }}">{{ $b->name }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card text-center"><div class="card-body">
            <div class="fs-4 fw-bold text-success">{{ $stats['total_farms'] }}</div>
            <div class="text-muted small">Active Farms</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card text-center"><div class="card-body">
            <div class="fs-4 fw-bold text-success">{{ $stats['total_analyses'] }}</div>
            <div class="text-muted small">Soil Analyses</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card text-center"><div class="card-body">
            <div class="fs-4 fw-bold text-success">{{ number_format($stats['total_area'] ?? 0, 1) }} ha</div>
            <div class="text-muted small">Total Farm Area</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card text-center"><div class="card-body">
            <div class="fs-4 fw-bold text-success">{{ $stats['barangays'] }}</div>
            <div class="text-muted small">Barangays</div>
        </div></div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div id="publicMap" style="height: 65vh; border-radius: 10px;"></div>
    </div>
</div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const map = L.map('publicMap').setView([12.85, 120.9], 11);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap contributors', maxZoom: 19 }).addTo(map);

    let farmLayer = L.layerGroup().addTo(map);
    let allFarms = [];

    function fertilityColor(cls) {
        return { success: '#28a745', warning: '#ffc107', danger: '#dc3545', secondary: '#6c757d' }[cls] || '#6c757d';
    }

    function render() {
        farmLayer.clearLayers();
        const bounds = [];

        allFarms.forEach(function (farm) {
            if (!farm.center) return;
            bounds.push([farm.center.lat, farm.center.lng]);

            const color = farm.fertility ? '#28a745' : '#6c757d';
            const marker = L.circleMarker([farm.center.lat, farm.center.lng], {
                radius: 8, color: color, fillColor: color, fillOpacity: 0.7,
            }).addTo(farmLayer);

            const fertility = farm.fertility
                ? `<div>Fertility: <strong>${farm.fertility.score ?? '—'}%</strong> (${farm.fertility.analysis_date})</div>
                   <div>pH ${farm.fertility.ph ?? '—'} · N ${farm.fertility.nitrogen ?? '—'} · P ${farm.fertility.phosphorus ?? '—'} · K ${farm.fertility.potassium ?? '—'}</div>`
                : '<div class="text-muted">No soil analysis yet</div>';

            marker.bindPopup(`
                <strong>${farm.name}</strong><br>
                Farmer: ${farm.farmer}<br>
                Barangay: ${farm.barangay} &middot; ${farm.area} ha<br>
                ${fertility}
                <div class="mt-2"><a href="/map/farm/${farm.id}">View Details →</a></div>
            `);
        });

        if (bounds.length) map.fitBounds(bounds, { padding: [30, 30] });
    }

    function load(barangayId) {
        const url = '{{ route('api.farms-data') }}' + (barangayId ? '?barangay_id=' + barangayId : '');
        fetch(url).then(r => r.json()).then(data => {
            allFarms = data.farms || [];
            render();
        });
    }

    document.getElementById('barangayFilter').addEventListener('change', function (e) {
        load(e.target.value);
    });

    load(null);
});
</script>
@endsection
