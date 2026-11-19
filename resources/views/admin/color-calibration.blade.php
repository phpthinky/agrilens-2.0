@extends('layouts.app')
@section('title', 'Color Calibration')

@section('styles')
    .swatch { display:inline-block; width:36px; height:24px; border-radius:4px; border:1px solid rgba(0,0,0,.25); vertical-align:middle; }
    .swatch-lg { width:72px; height:48px; border-radius:6px; }
    .cal-row.inactive td { opacity:.45; }
    .badge-low { background:#c0642a; } .badge-medium { background:#2e7d32; } .badge-high { background:#1565c0; }
    .cam-wrap { position:relative; display:inline-block; max-width:100%; }
    .cam-wrap video { border:2px solid var(--bs-success); border-radius:8px; display:block; max-width:100%; height:auto; }
@endsection

@section('content')
<div class="row mb-3">
    <div class="col">
        <h2><i class="fa fa-eye-dropper me-2 text-success"></i>Color Calibration</h2>
        <p class="text-muted mb-0">
            Put a reference solution in the colorimeter box, capture it with the box camera, and label the capture
            with its pH value or LOW / MEDIUM / HIGH status. Each capture is added to the chart that tests are matched
            against — the charts in the database are the only color source.
        </p>
    </div>
</div>

<div class="row g-4">
    {{-- ── Capture ──────────────────────────────────────────────── --}}
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header bg-success text-white"><i class="fa fa-video me-1"></i> Capture with the device</div>
            <div class="card-body">
                <label class="form-label fw-semibold">Chart to calibrate</label>
                <select id="target" class="form-select mb-3">
                    <optgroup label="pH indicator">
                        @foreach($indicators as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </optgroup>
                    <optgroup label="Nutrient">
                        @foreach($nutrients as $key => $label)
                        <option value="{{ $key }}">{{ $label }} ({{ $key }})</option>
                        @endforeach
                    </optgroup>
                </select>

                <div class="text-center">
                    <div class="cam-wrap">
                        <video id="webcam" width="320" height="240" autoplay playsinline></video>
                        <canvas class="js-zone-overlay" width="320" height="240"
                                style="position:absolute;inset:0;width:100%;height:100%;pointer-events:none;"></canvas>
                        <div class="js-zone-label" style="position:absolute;bottom:6px;left:50%;transform:translateX(-50%);
                                background:rgba(0,0,0,.55);color:#fff;font-size:10px;padding:1px 6px;border-radius:3px;
                                pointer-events:none;white-space:nowrap;">Stand the tube in the dashed guide</div>
                    </div>
                    <canvas id="snapshot" width="320" height="240" class="d-none"></canvas>
                    <div class="small mt-1 js-camera-label"></div>
                    <div class="mt-2 d-flex gap-2 justify-content-center flex-wrap">
                        <button id="startCameraBtn" class="btn btn-outline-secondary btn-sm" onclick="startCamera()">
                            <i class="fa fa-video me-1"></i> Start Camera
                        </button>
                        <button class="btn btn-outline-primary btn-sm d-none js-switch-camera" onclick="ColorimeterCamera.choose()">
                            <i class="fa fa-exchange-alt me-1"></i> Switch camera
                        </button>
                        <button id="captureBtn" class="btn btn-success btn-sm" onclick="capture()" disabled>
                            <i class="fa fa-camera me-1"></i> Capture
                        </button>
                    </div>
                </div>

                {{-- Label the capture --}}
                <div id="result" class="border rounded p-3 mt-3 d-none">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span id="resSwatch" class="swatch swatch-lg"></span>
                        <img id="resPhoto" width="72" height="54" alt="Capture"
                             style="border-radius:6px;border:1px solid #ccc;object-fit:cover;"
                             data-caption="Capture to calibrate" title="Click to view the whole image">
                        <div>
                            <div class="font-monospace fw-bold fs-5" id="resHex"></div>
                            <div class="small text-muted" id="resInfo"></div>
                        </div>
                    </div>

                    <div id="phFields">
                        <label class="form-label fw-semibold">pH value of this solution</label>
                        <input id="phValue" type="number" class="form-control" min="3" max="10" step="0.1" placeholder="e.g. 5.4">
                    </div>

                    <div id="npkFields" class="d-none">
                        <label class="form-label fw-semibold d-block">Status</label>
                        <div class="btn-group w-100 mb-2" role="group">
                            @foreach(['low' => 'LOW', 'medium' => 'MEDIUM', 'high' => 'HIGH'] as $val => $label)
                            <input type="radio" class="btn-check" name="category" id="cat-{{ $val }}" value="{{ $val }}" onchange="updatePpmHint()">
                            <label class="btn btn-outline-secondary" for="cat-{{ $val }}">{{ $label }}</label>
                            @endforeach
                        </div>
                        <label class="form-label small mb-1">ppm (optional)</label>
                        <input id="ppmValue" type="number" class="form-control" min="0" max="9999" step="0.1">
                        <div class="form-text">Leave blank to use the standard value for the status.</div>
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <button class="btn btn-success flex-grow-1" id="saveBtn" onclick="save()">
                            <i class="fa fa-save me-1"></i> Save to chart
                        </button>
                        <button class="btn btn-outline-secondary" onclick="hideResult()">Discard</button>
                    </div>
                </div>
                <div id="msg" class="small mt-2"></div>
            </div>
        </div>
        <div class="mt-3 p-3 bg-light rounded border small text-muted">
            <i class="fa fa-info-circle me-1 text-primary"></i>
            Capture each reference solution under the same box, LED and camera position used for tests.
            Several captures per value make matching more reliable. Turn off or delete card entries
            in the <a href="{{ route('admin.ph-color-charts') }}">pH</a> and
            <a href="{{ route('admin.npk-color-charts') }}">NPK</a> chart pages once device captures cover a chart.
        </div>
    </div>

    {{-- ── Chart entries ────────────────────────────────────────── --}}
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fa fa-palette me-1"></i> <span id="chartTitle"></span></span>
                <a id="manageLink" class="btn btn-sm btn-outline-secondary" href="#"><i class="fa fa-cog me-1"></i> Manage entries</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr><th>Swatch</th><th>Hex</th><th>Value</th><th>Status</th><th>Source</th><th class="text-center">Active</th></tr>
                        </thead>
                        <tbody id="chartRows"></tbody>
                    </table>
                </div>
                <div id="chartEmpty" class="p-4 text-muted text-center d-none">
                    <i class="fa fa-inbox fa-2x mb-2 d-block"></i>No colors in this chart — tests using it cannot be read until you add some.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/colorimeter-camera.js') }}?v={{ filemtime(public_path('js/colorimeter-camera.js')) }}"></script>
<script src="{{ asset('js/colorimeter-sample.js') }}?v={{ filemtime(public_path('js/colorimeter-sample.js')) }}"></script>
<script>
const csrf       = document.querySelector('meta[name="csrf-token"]').content;
const entries    = @json($entries);
const names      = Object.assign({}, @json($indicators), @json($nutrients));
const defaultPpm = @json($defaultPpm);
const PH         = ['CPR', 'BCG', 'BTB'];
const manageUrl  = { ph: @json(route('admin.ph-color-charts')), npk: @json(route('admin.npk-color-charts')) };
const targetEl   = document.getElementById('target');
let   stream = null, captured = null;

const isPh = t => PH.includes(t);
const esc  = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

function renderChart() {
    const t = targetEl.value, rows = entries[t] || [];
    document.getElementById('chartTitle').textContent = names[t] + ' chart (' + rows.length + ')';
    document.getElementById('manageLink').href = isPh(t) ? manageUrl.ph : manageUrl.npk;
    document.getElementById('chartEmpty').classList.toggle('d-none', rows.length > 0);
    document.getElementById('chartRows').innerHTML = rows.map(r => `
        <tr class="cal-row ${r.active ? '' : 'inactive'}">
            <td><span class="swatch" style="background:${esc(r.hex)}"></span></td>
            <td class="font-monospace">${esc(r.hex)}</td>
            <td><strong>${esc(r.value)}</strong></td>
            <td>${r.category ? `<span class="badge badge-${esc(r.category)} text-white text-uppercase">${esc(r.category)}</span>` : '—'}</td>
            <td>${r.source === 'device'
                ? `<span class="badge bg-success"><i class="fa fa-video me-1"></i>Device</span>${r.snapshot
                    ? ` <img src="${esc(r.snapshot)}" width="40" height="30" class="ms-1 align-middle"
                             style="border-radius:3px;border:1px solid #ccc;object-fit:cover;"
                             data-preview="${esc(r.snapshot)}" data-caption="${esc(names[targetEl.value])} — ${esc(r.hex)} · ${esc(r.value)}"
                             title="Click to view the whole image">`
                    : ''}`
                : '<span class="badge bg-secondary">BSWM card</span>'}</td>
            <td class="text-center">${r.active ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>'}</td>
        </tr>`).join('');
    document.getElementById('phFields').classList.toggle('d-none', !isPh(t));
    document.getElementById('npkFields').classList.toggle('d-none', isPh(t));
    updatePpmHint();
}

function updatePpmHint() {
    const t = targetEl.value, cat = document.querySelector('input[name="category"]:checked');
    document.getElementById('ppmValue').placeholder = (!isPh(t) && cat) ? 'standard: ' + defaultPpm[t][cat.value] + ' ppm' : '';
}

function startCamera() {
    ColorimeterCamera.start({
        width: 320, height: 240,
        onStream(s, info) {
            stream = s;
            document.getElementById('webcam').srcObject = s;
            const btn = document.getElementById('startCameraBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa fa-check-circle me-1"></i> Camera Active';
            document.getElementById('captureBtn').disabled = false;
            document.querySelectorAll('.js-switch-camera').forEach(b => b.classList.remove('d-none'));
            document.querySelectorAll('.js-camera-label').forEach(el => {
                el.innerHTML = info.external
                    ? '<i class="fa fa-plug text-success me-1"></i>Colorimeter camera: ' + esc(info.label || 'USB camera')
                    : '<i class="fa fa-exclamation-triangle text-warning me-1"></i>Using built-in camera — connect the colorimeter box';
            });
            ColorimeterSample.watchAll();
        },
    });
}

/** Average the tube-centre pick over 5 frames for a steadier reference color. */
async function capture() {
    if (!stream) return;
    const btn = document.getElementById('captureBtn');
    btn.disabled = true;
    const video = document.getElementById('webcam'), canvas = document.getElementById('snapshot');
    const ctx = canvas.getContext('2d', { willReadFrequently: true });
    const picks = [];
    for (let i = 0; i < 5; i++) {
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        picks.push(ColorimeterSample.pick(ctx.getImageData(0, 0, canvas.width, canvas.height)));
        await new Promise(r => setTimeout(r, 150));
    }
    const avg = k => Math.round(picks.reduce((s, p) => s + p[k], 0) / picks.length);
    const r = avg('r'), g = avg('g'), b = avg('b');
    const hex = '#' + [r, g, b].map(v => v.toString(16).padStart(2, '0')).join('').toUpperCase();
    captured = { hex, snapshot: canvas.toDataURL('image/jpeg', 0.8) };

    const tube = picks.every(p => p.method === 'tube');
    document.getElementById('resSwatch').style.background = hex;
    const photo = document.getElementById('resPhoto');
    photo.src = captured.snapshot;
    photo.dataset.preview = captured.snapshot;
    photo.dataset.caption = names[targetEl.value] + ' — ' + hex;
    document.getElementById('resHex').textContent = hex;
    document.getElementById('resInfo').innerHTML = `RGB ${r}, ${g}, ${b} · ` + (tube
        ? 'measured from the detected tube'
        : '<span class="text-warning">no colored liquid detected — centre strip used</span>');
    document.getElementById('result').classList.remove('d-none');
    document.getElementById('msg').textContent = '';
    btn.disabled = false;
}

function hideResult() {
    captured = null;
    document.getElementById('result').classList.add('d-none');
}

async function save() {
    if (!captured) return;
    const t = targetEl.value, body = { target: t, hex: captured.hex, snapshot: captured.snapshot };
    if (isPh(t)) {
        body.ph_value = document.getElementById('phValue').value;
        if (!body.ph_value) return showMsg('Enter the pH value of this solution.', true);
    } else {
        const cat = document.querySelector('input[name="category"]:checked');
        if (!cat) return showMsg('Choose LOW, MEDIUM or HIGH.', true);
        body.category = cat.value;
        const ppm = document.getElementById('ppmValue').value;
        if (ppm) body.ppm_value = ppm;
    }

    const btn = document.getElementById('saveBtn');
    btn.disabled = true;
    try {
        const res  = await fetch(@json(route('admin.color-calibration.store')), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify(body),
        });
        const data = await res.json();
        if (!res.ok) return showMsg(data.message || 'Could not save.', true);
        (entries[t] = entries[t] || []).push(data.entry);
        renderChart();
        hideResult();
        showMsg(data.message, false);
    } catch (e) {
        showMsg('Network error — please try again.', true);
    } finally {
        btn.disabled = false;
    }
}

function showMsg(text, error) {
    const el = document.getElementById('msg');
    el.className = 'small mt-2 ' + (error ? 'text-danger' : 'text-success');
    el.textContent = text;
}

targetEl.addEventListener('change', () => { hideResult(); renderChart(); });
renderChart();
</script>
@endsection
