@php
    $polygon = old('polygon_coordinates')
        ? json_decode(old('polygon_coordinates'), true)
        : ($farm?->polygon_coordinates ?? []);
@endphp

<div class="row g-3">
    <!-- Left Column: Basic Details -->
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
                <div class="form-text">Leave blank to use calculated GPS area.</div>
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

    <!-- Right Column: GPS Point Picker & Map Preview -->
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-map-marked-alt me-1"></i>Farm GPS Boundary Points</span>
                <span id="pointCountBadge" class="badge bg-secondary">0 Points Added</span>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">Add GPS coordinates sequentially (minimum 3 points required to generate map polygon).</p>
                
                <!-- GPS Point Inputs -->
                <div class="row g-2 mb-3 bg-light p-2 rounded border">
                    <div class="col-md-5">
                        <label class="form-label small mb-1 fw-bold">Latitude</label>
                        <input type="number" step="0.00000001" id="input_lat" class="form-control form-control-sm" placeholder="e.g. 12.823400">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small mb-1 fw-bold">Longitude</label>
                        <input type="number" step="0.00000001" id="input_lng" class="form-control form-control-sm" placeholder="e.g. 120.875600">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="button" class="btn btn-primary btn-sm w-100" id="btnAddGpsPoint">
                            <i class="fas fa-plus me-1"></i> Add
                        </button>
                    </div>
                    <div class="col-12 mt-2">
                        <button type="button" class="btn btn-outline-primary btn-sm w-100" id="btnUseCurrentGps">
                            <i class="fas fa-crosshairs me-1"></i> Get Current Device Location
                        </button>
                    </div>
                </div>

                <!-- GPS Points Data Table -->
                <div class="table-responsive mb-3">
                    <table class="table table-sm table-bordered bg-white mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Latitude</th>
                                <th>Longitude</th>
                                <th class="text-center" style="width: 60px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="gpsPointsTableBody">
                            <tr id="emptyPointsRow">
                                <td colspan="4" class="text-center text-muted small py-3">No GPS points added yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Action Controls for Map Preview -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <button type="button" id="btnPreviewMap" class="btn btn-success" disabled>
                        <i class="fas fa-map-marked-alt me-1"></i> Preview Map Boundary
                    </button>
                    <button type="button" id="btnClearGpsPoints" class="btn btn-sm btn-outline-danger">
                        Clear All Points
                    </button>
                </div>

                <!-- Map Section (HIDDEN UNTIL PREVIEW CLICKED) -->
                <div id="mapContainerWrapper" style="display: none;">
                    <hr>
                    <div id="overlapWarning" class="alert alert-danger mt-2 d-none"></div>
                    <div id="farmMap" style="height: 380px; border-radius: 8px;" class="mb-2"></div>
                    <div class="row small text-muted">
                        <div class="col-md-6">GPS Area: <strong id="gpsArea">—</strong></div>
                        <div class="col-md-6">Center: <strong id="gpsCenter">—</strong></div>
                    </div>
                </div>
            </div>
        </div>

        <input type="hidden" name="polygon_coordinates" id="polygon_coordinates" value="{{ old('polygon_coordinates') }}">
        <input type="hidden" name="display_latitude" id="display_latitude" value="{{ old('display_latitude', $farm?->display_latitude) }}">
        <input type="hidden" name="display_longitude" id="display_longitude" value="{{ old('display_longitude', $farm?->display_longitude) }}">
        @error('polygon_coordinates')<div class="alert alert-danger mt-2">{{ $message }}</div>@enderror
    </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    let gpsPoints = @json($polygon ?: []);
    const excludeFarmId = {{ $farm?->id ?? 'null' }};
    const MIN_POINTS = 3;

    let map = null;
    let polygonLayer = null;
    let markerLayers = [];
    let existingFarms = [];

    // Pre-load existing boundaries for overlap checking
    fetch('{{ route('api.farms.all-polygons') }}' + (excludeFarmId ? '?exclude_farm_id=' + excludeFarmId : ''))
        .then(r => r.json())
        .then(data => {
            existingFarms = data.farms || [];
        });

    function renderPointsTable() {
        const tbody = document.getElementById('gpsPointsTableBody');
        const badge = document.getElementById('pointCountBadge');
        const btnPreview = document.getElementById('btnPreviewMap');
        const hiddenPolygonInput = document.getElementById('polygon_coordinates');

        badge.textContent = `${gpsPoints.length} Point${gpsPoints.length === 1 ? '' : 's'} Added`;
        
        if (gpsPoints.length >= MIN_POINTS) {
            btnPreview.disabled = false;
            hiddenPolygonInput.value = JSON.stringify(gpsPoints);
        } else {
            btnPreview.disabled = true;
            hiddenPolygonInput.value = '';
        }

        if (gpsPoints.length === 0) {
            tbody.innerHTML = `<tr id="emptyPointsRow"><td colspan="4" class="text-center text-muted small py-3">No GPS points added yet.</td></tr>`;
            return;
        }

        tbody.innerHTML = gpsPoints.map((pt, idx) => `
            <tr>
                <td><strong>Pt ${idx + 1}</strong></td>
                <td>${parseFloat(pt.lat).toFixed(8)}</td>
                <td>${parseFloat(pt.lng).toFixed(8)}</td>
                <td class="text-center">
                    <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2" onclick="removeGpsPoint(${idx})">
                        &times;
                    </button>
                </td>
            </tr>
        `).join('');
    }

    document.getElementById('btnAddGpsPoint').addEventListener('click', function() {
        const latInput = document.getElementById('input_lat');
        const lngInput = document.getElementById('input_lng');
        const lat = parseFloat(latInput.value);
        const lng = parseFloat(lngInput.value);

        if (isNaN(lat) || isNaN(lng)) {
            alert('Please enter valid Latitude and Longitude coordinates.');
            return;
        }

        if (lat < -90 || lat > 90 || lng < -180 || lng > 180) {
            alert('Coordinates are out of bounds.');
            return;
        }

        gpsPoints.push({ lat, lng });
        latInput.value = '';
        lngInput.value = '';
        
        renderPointsTable();
    });

    window.removeGpsPoint = function(index) {
        gpsPoints.splice(index, 1);
        renderPointsTable();
        
        // Hide map preview if points drop below minimum
        if (gpsPoints.length < MIN_POINTS) {
            document.getElementById('mapContainerWrapper').style.display = 'none';
        } else if (map && document.getElementById('mapContainerWrapper').style.display !== 'none') {
            drawMapPreview();
        }
    };

    document.getElementById('btnClearGpsPoints').addEventListener('click', function() {
        gpsPoints = [];
        renderPointsTable();
        document.getElementById('mapContainerWrapper').style.display = 'none';
    });

    document.getElementById('btnUseCurrentGps').addEventListener('click', function() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                pos => {
                    document.getElementById('input_lat').value = pos.coords.latitude.toFixed(8);
                    document.getElementById('input_lng').value = pos.coords.longitude.toFixed(8);
                },
                () => alert('Unable to fetch device location.'),
                { enableHighAccuracy: true }
            );
        }
    });

    document.getElementById('btnPreviewMap').addEventListener('click', function() {
        if (gpsPoints.length < MIN_POINTS) {
            alert(`At least ${MIN_POINTS} GPS points are required to render the farm boundary.`);
            return;
        }
        document.getElementById('mapContainerWrapper').style.display = 'block';
        drawMapPreview();
    });

    function drawMapPreview() {
        const latLngs = gpsPoints.map(p => [p.lat, p.lng]);

        if (!map) {
            map = L.map('farmMap');
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { 
                attribution: '&copy; OpenStreetMap contributors', maxZoom: 19 
            }).addTo(map);
        }

        // Reset layers
        markerLayers.forEach(m => map.removeLayer(m));
        markerLayers = [];
        if (polygonLayer) map.removeLayer(polygonLayer);

        // Render markers
        latLngs.forEach((coord, idx) => {
            const m = L.marker(coord).addTo(map).bindPopup(`Point ${idx + 1}`);
            markerLayers.push(m);
        });

        // Plot existing neighboring farms in red
        existingFarms.forEach(f => {
            if (!f.polygon_coordinates || f.polygon_coordinates.length < 3) return;
            L.polygon(f.polygon_coordinates.map(c => [c.lat, c.lng]), { color: '#dc3545', weight: 2, fillOpacity: 0.15, dashArray: '5,5' })
                .bindPopup(`<strong>${f.farm_name}</strong><br>Farmer: ${f.farmer_name}`)
                .addTo(map);
        });

        // Render boundary polygon
        polygonLayer = L.polygon(latLngs, { color: '#28a745', fillColor: '#28a745', fillOpacity: 0.35 }).addTo(map);
        map.fitBounds(polygonLayer.getBounds(), { padding: [30, 30] });

        // Geometric area calculations
        const centerLat = gpsPoints.reduce((s, p) => s + parseFloat(p.lat), 0) / gpsPoints.length;
        const centerLng = gpsPoints.reduce((s, p) => s + parseFloat(p.lng), 0) / gpsPoints.length;
        
        document.getElementById('display_latitude').value = centerLat.toFixed(8);
        document.getElementById('display_longitude').value = centerLng.toFixed(8);
        document.getElementById('gpsCenter').textContent = centerLat.toFixed(6) + ', ' + centerLng.toFixed(6);

        if (typeof L.GeometryUtil !== 'undefined') {
            const area = L.GeometryUtil.geodesicArea(polygonLayer.getLatLngs()[0]) / 10000;
            document.getElementById('gpsArea').textContent = area.toFixed(4) + ' ha';
        }

        // Overlap checks
        const overlap = existingFarms.find(f => f.polygon_coordinates && f.polygon_coordinates.length >= 3 && polygonsOverlap(gpsPoints, f.polygon_coordinates));
        const warningEl = document.getElementById('overlapWarning');
        if (overlap) {
            warningEl.textContent = `This boundary overlaps with existing farm "${overlap.farm_name}". Please adjust points.`;
            warningEl.classList.remove('d-none');
            polygonLayer.setStyle({ color: '#dc3545', fillColor: '#dc3545' });
        } else {
            warningEl.classList.add('d-none');
            polygonLayer.setStyle({ color: '#28a745', fillColor: '#28a745' });
        }

        setTimeout(() => map.invalidateSize(), 200);
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

    // Initial render call
    renderPointsTable();
    if (gpsPoints.length >= MIN_POINTS) {
        document.getElementById('mapContainerWrapper').style.display = 'block';
        drawMapPreview();
    }
});
</script>