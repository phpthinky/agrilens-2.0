@php
    $polygon = old('polygon_coordinates')
        ? json_decode(old('polygon_coordinates'), true)
        : ($farm?->polygon_coordinates ?? []);
@endphp

<div class="row g-3">
    <div class="col-lg-5">
        <div class="mb-3">
            <label class="form-label">Farmer *</label>
            <select name="farmer_id" class="form-select @error('farmer_id') is-invalid @enderror" required>
                <option value="">Select farmer</option>
                @foreach($farmers as $f)
                    <option value="{{ $f->id }}"
                        {{ (string) old('farmer_id', $farm?->farmer_id ?? $selectedFarmer?->id) === (string) $f->id ? 'selected' : '' }}>
                        {{ $f->full_name }} ({{ $f->barangay?->name }})
                    </option>
                @endforeach
            </select>
            @error('farmer_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Farm Name *</label>
            <input type="text" name="farm_name" class="form-control @error('farm_name') is-invalid @enderror"
                   value="{{ old('farm_name', $farm?->farm_name) }}" required>
            @error('farm_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Farm Address</label>
            <textarea name="farm_address" class="form-control" rows="2">{{ old('farm_address', $farm?->farm_address) }}</textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Location Barangay *</label>
            <select name="location_barangay_id" class="form-select @error('location_barangay_id') is-invalid @enderror" required>
                <option value="">Select barangay</option>
                @foreach($barangays as $b)
                    <option value="{{ $b->id }}" {{ (string) old('location_barangay_id', $farm?->location_barangay_id) === (string) $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                @endforeach
            </select>
            @error('location_barangay_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Farm Type *</label>
                <select name="farm_type" class="form-select" required>
                    @foreach(['Riceland','Cornland','Vegetable Farm','Fruit Orchard','Coconut Farm','Mixed Crops','Pasture Land','Fish Pond','Other'] as $t)
                        <option value="{{ $t }}" {{ old('farm_type', $farm?->farm_type ?? 'Mixed Crops') === $t ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Land Tenure</label>
                <select name="land_tenure" class="form-select">
                    @foreach(['Owned','Rented','Shared/Partnership','Caretaker','Government Land','Other'] as $t)
                        <option value="{{ $t }}" {{ old('land_tenure', $farm?->land_tenure ?? 'Owned') === $t ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Irrigation</label>
                <select name="irrigation_type" class="form-select">
                    @foreach(['Rainfed','Irrigated','Partially Irrigated','Not Applicable'] as $t)
                        <option value="{{ $t }}" {{ old('irrigation_type', $farm?->irrigation_type ?? 'Rainfed') === $t ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Slope</label>
                <select name="slope_category" class="form-select">
                    <option value="">Not specified</option>
                    @foreach(['Flat (0-3%)','Gently Rolling (3-8%)','Rolling (8-15%)','Moderately Steep (15-25%)','Steep (25-35%)','Very Steep (>35%)'] as $t)
                        <option value="{{ $t }}" {{ old('slope_category', $farm?->slope_category) === $t ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Area (hectares)</label>
                <input type="number" step="0.0001" name="area_hectares" id="area_hectares" class="form-control"
                       value="{{ old('area_hectares', $farm?->area_hectares) }}">
                <div class="form-text">Leave blank to use the GPS-calculated area from the drawn polygon.</div>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Established Year</label>
                <input type="number" name="established_year" class="form-control" min="1900" max="{{ date('Y') }}"
                       value="{{ old('established_year', $farm?->established_year) }}">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Current Crops</label>
            <input type="text" name="current_crops" class="form-control" value="{{ old('current_crops', $farm?->current_crops) }}" placeholder="Comma-separated">
        </div>

        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="2">{{ old('description', $farm?->description) }}</textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-control" rows="2">{{ old('notes', $farm?->notes) }}</textarea>
        </div>

        <div class="form-check mb-3">
            <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1"
                   {{ old('is_active', $farm?->is_active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">Active</label>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-map-marked-alt me-1"></i>Farm Boundary</span>
                <span id="polygonStatus" class="text-muted small">No polygon drawn</span>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-2">Draw the farm boundary with the polygon tool (minimum 3 points). Existing farms are shown in red — boundaries must not overlap.</p>
                <div id="farmMap" style="height: 420px; border-radius: 8px;"></div>
                <div id="overlapWarning" class="alert alert-danger mt-2 d-none"></div>
                <div class="row mt-2 small text-muted">
                    <div class="col-md-6">GPS Area: <strong id="gpsArea">—</strong></div>
                    <div class="col-md-6">Center: <strong id="gpsCenter">—</strong></div>
                </div>
                <button type="button" id="clearPolygonBtn" class="btn btn-sm btn-outline-danger mt-2">
                    <i class="fas fa-trash me-1"></i>Clear Polygon
                </button>
            </div>
        </div>

        <input type="hidden" name="polygon_coordinates" id="polygon_coordinates" value="{{ old('polygon_coordinates') }}">
        <input type="hidden" name="display_latitude" id="display_latitude" value="{{ old('display_latitude', $farm?->display_latitude) }}">
        <input type="hidden" name="display_longitude" id="display_longitude" value="{{ old('display_longitude', $farm?->display_longitude) }}">
        @error('polygon_coordinates')<div class="alert alert-danger mt-2">{{ $message }}</div>@enderror
    </div>
</div>

<div class="mt-4">
    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i> Save Farm</button>
    <a href="{{ route('farms.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const existingPolygon = @json($polygon ?: null);
    const excludeFarmId = {{ $farm?->id ?? 'null' }};
    const startCenter = existingPolygon?.length
        ? [existingPolygon.reduce((s, p) => s + p.lat, 0) / existingPolygon.length, existingPolygon.reduce((s, p) => s + p.lng, 0) / existingPolygon.length]
        : [{{ old('display_latitude', $farm?->display_latitude ?? 12.85) }}, {{ old('display_longitude', $farm?->display_longitude ?? 120.9) }}];

    const map = L.map('farmMap').setView(startCenter, existingPolygon?.length ? 16 : 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap contributors', maxZoom: 19 }).addTo(map);
    L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { attribution: 'Tiles &copy; Esri', maxZoom: 19 }).addTo(new L.LayerGroup());

    const drawnItems = new L.FeatureGroup().addTo(map);
    let currentPolygon = null;

    const drawControl = new L.Control.Draw({
        draw: {
            polygon: { allowIntersection: false, shapeOptions: { color: '#28a745' } },
            polyline: false, rectangle: false, circle: false, marker: false, circlemarker: false,
        },
        edit: { featureGroup: drawnItems, remove: true },
    });
    map.addControl(drawControl);

    let existingFarms = [];
    fetch('{{ route('api.farms.all-polygons') }}' + (excludeFarmId ? '?exclude_farm_id=' + excludeFarmId : ''))
        .then(r => r.json())
        .then(data => {
            existingFarms = data.farms || [];
            existingFarms.forEach(f => {
                if (!f.polygon_coordinates || f.polygon_coordinates.length < 3) return;
                L.polygon(f.polygon_coordinates.map(c => [c.lat, c.lng]), { color: '#dc3545', weight: 2, fillOpacity: 0.15, dashArray: '5,5' })
                    .bindPopup(`<strong>${f.farm_name}</strong><br>Farmer: ${f.farmer_name}`)
                    .addTo(map);
            });
        });

    function updateFromPolygon(layer) {
        const coords = layer.getLatLngs()[0].map(ll => ({ lat: parseFloat(ll.lat.toFixed(8)), lng: parseFloat(ll.lng.toFixed(8)) }));
        document.getElementById('polygon_coordinates').value = JSON.stringify(coords);
        document.getElementById('polygonStatus').textContent = coords.length + ' points drawn';

        const area = L.GeometryUtil ? L.GeometryUtil.geodesicArea(layer.getLatLngs()[0]) / 10000 : null;
        const centerLat = coords.reduce((s, p) => s + p.lat, 0) / coords.length;
        const centerLng = coords.reduce((s, p) => s + p.lng, 0) / coords.length;
        document.getElementById('display_latitude').value = centerLat.toFixed(8);
        document.getElementById('display_longitude').value = centerLng.toFixed(8);
        document.getElementById('gpsCenter').textContent = centerLat.toFixed(6) + ', ' + centerLng.toFixed(6);
        if (area !== null) document.getElementById('gpsArea').textContent = area.toFixed(4) + ' ha';

        const overlap = existingFarms.find(f => f.polygon_coordinates && f.polygon_coordinates.length >= 3 && polygonsOverlap(coords, f.polygon_coordinates));
        const warningEl = document.getElementById('overlapWarning');
        if (overlap) {
            warningEl.textContent = `This boundary overlaps with existing farm "${overlap.farm_name}". Please redraw.`;
            warningEl.classList.remove('d-none');
            layer.setStyle({ color: '#dc3545' });
        } else {
            warningEl.classList.add('d-none');
            layer.setStyle({ color: '#28a745' });
        }
    }

    function pointInPolygon(point, polygon) {
        const x = point.lat, y = point.lng;
        let inside = false;
        for (let i = 0, j = polygon.length - 1; i < polygon.length; j = i++) {
            const xi = polygon[i].lat, yi = polygon[i].lng, xj = polygon[j].lat, yj = polygon[j].lng;
            if (((yi > y) !== (yj > y)) && (x < (xj - xi) * (y - yi) / (yj - yi) + xi)) inside = !inside;
        }
        return inside;
    }

    function segmentsIntersect(p1, p2, p3, p4) {
        const d = ((p4.lng - p3.lng) * (p2.lat - p1.lat)) - ((p4.lat - p3.lat) * (p2.lng - p1.lng));
        if (d === 0) return false;
        const ua = (((p4.lat - p3.lat) * (p1.lng - p3.lng)) - ((p4.lng - p3.lng) * (p1.lat - p3.lat))) / d;
        const ub = (((p2.lat - p1.lat) * (p1.lng - p3.lng)) - ((p2.lng - p1.lng) * (p1.lat - p3.lat))) / d;
        return ua >= 0 && ua <= 1 && ub >= 0 && ub <= 1;
    }

    function polygonsOverlap(poly1, poly2) {
        if (poly1.some(p => pointInPolygon(p, poly2)) || poly2.some(p => pointInPolygon(p, poly1))) return true;
        for (let i = 0; i < poly1.length; i++) {
            for (let j = 0; j < poly2.length; j++) {
                if (segmentsIntersect(poly1[i], poly1[(i + 1) % poly1.length], poly2[j], poly2[(j + 1) % poly2.length])) return true;
            }
        }
        return false;
    }

    map.on(L.Draw.Event.CREATED, e => {
        if (currentPolygon) drawnItems.removeLayer(currentPolygon);
        currentPolygon = e.layer;
        drawnItems.addLayer(currentPolygon);
        updateFromPolygon(currentPolygon);
    });
    map.on(L.Draw.Event.EDITED, e => e.layers.eachLayer(updateFromPolygon));
    map.on(L.Draw.Event.DELETED, () => {
        currentPolygon = null;
        document.getElementById('polygon_coordinates').value = '';
        document.getElementById('polygonStatus').textContent = 'No polygon drawn';
    });

    document.getElementById('clearPolygonBtn').addEventListener('click', () => {
        drawnItems.clearLayers();
        currentPolygon = null;
        document.getElementById('polygon_coordinates').value = '';
        document.getElementById('polygonStatus').textContent = 'No polygon drawn';
        document.getElementById('overlapWarning').classList.add('d-none');
    });

    if (existingPolygon?.length) {
        currentPolygon = L.polygon(existingPolygon.map(p => [p.lat, p.lng]), { color: '#28a745' }).addTo(drawnItems);
        map.fitBounds(currentPolygon.getBounds(), { padding: [30, 30] });
        updateFromPolygon(currentPolygon);
    }
});
</script>
