@extends('layouts.app')
@section('title', 'Digital Probe Analysis')
@section('page-title', 'Digital Probe Analysis')
@section('content')

<div class="row mb-3">
    <div class="col-md-8">
        <h2><i class="fas fa-qrcode me-2"></i>Digital Probe Analysis</h2>
        <p class="text-muted mb-0">{{ $sample->sample_name }} — {{ $sample->farm->farm_name }} — {{ $sample->farm->farmer->full_name }}</p>
    </div>
    <div class="col-md-4 text-end">
        <a href="{{ route('analyses.choose', $sample) }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">1. Scan Probe QR Code</div>
            <div class="card-body">
                <div id="qr-reader" style="width: 100%;"></div>
                <p class="text-muted small mt-2 mb-0" id="qr-status">Point the camera at the QR code shown on the probe's screen.</p>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">Or paste payload manually</div>
            <div class="card-body">
                <textarea id="manualPayload" class="form-control" rows="4" placeholder='{"v":1,"probe_id":"PROBE-001","ph":6.4,"n":42,"p":18.5,"k":150}'></textarea>
                <button type="button" id="parseManualBtn" class="btn btn-sm btn-outline-secondary mt-2">
                    <i class="fas fa-check me-1"></i>Parse Payload
                </button>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">2. Review &amp; Confirm</div>
            <div class="card-body">
                <div id="reviewEmpty" class="text-center text-muted py-5">
                    <i class="fas fa-satellite-dish fa-3x mb-3"></i>
                    <p>Waiting for a scan...</p>
                </div>

                <form method="POST" action="{{ route('analyses.store.probe', $sample) }}" id="reviewForm" class="d-none">
                    @csrf
                    <div class="alert alert-info small">Values were read from the probe — review and edit if needed before saving.</div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Probe ID *</label>
                            <input type="text" name="probe_id" id="field_probe_id" class="form-control" required readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">pH *</label>
                            <input type="number" step="0.01" name="ph_level" id="field_ph" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Nitrogen (ppm) *</label>
                            <input type="number" step="0.01" name="nitrogen_level" id="field_n" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phosphorus (ppm) *</label>
                            <input type="number" step="0.01" name="phosphorus_level" id="field_p" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Potassium (ppm) *</label>
                            <input type="number" step="0.01" name="potassium_level" id="field_k" class="form-control" required>
                        </div>
                    </div>

                    <input type="hidden" name="probe_raw_payload" id="field_raw_payload">

                    <div class="mt-4">
                        <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i> Confirm &amp; Save</button>
                        <button type="button" id="rescanBtn" class="btn btn-outline-secondary">Scan Again</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const reviewEmpty = document.getElementById('reviewEmpty');
    const reviewForm = document.getElementById('reviewForm');
    const qrStatus = document.getElementById('qr-status');

    function populateReview(payload) {
        if (!payload || typeof payload !== 'object') {
            qrStatus.textContent = 'Could not read a valid payload from that code/text.';
            return;
        }
        const required = ['probe_id', 'ph', 'n', 'p', 'k'];
        const missing = required.filter(k => payload[k] === undefined || payload[k] === null);
        if (missing.length) {
            qrStatus.textContent = 'Payload is missing required field(s): ' + missing.join(', ');
            return;
        }

        document.getElementById('field_probe_id').value = payload.probe_id;
        document.getElementById('field_ph').value = payload.ph;
        document.getElementById('field_n').value = payload.n;
        document.getElementById('field_p').value = payload.p;
        document.getElementById('field_k').value = payload.k;
        document.getElementById('field_raw_payload').value = JSON.stringify(payload);

        reviewEmpty.classList.add('d-none');
        reviewForm.classList.remove('d-none');
        qrStatus.textContent = 'Payload loaded — review the values below before saving.';
    }

    let scanner = null;
    function startScanner() {
        if (typeof Html5Qrcode === 'undefined') {
            qrStatus.textContent = 'Camera scanner failed to load — use the manual paste option below.';
            return;
        }
        scanner = new Html5Qrcode('qr-reader');
        scanner.start(
            { facingMode: 'environment' },
            { fps: 10, qrbox: 250 },
            function (decodedText) {
                scanner.stop().catch(() => {});
                try {
                    populateReview(JSON.parse(decodedText));
                } catch (e) {
                    qrStatus.textContent = 'Scanned code did not contain valid JSON.';
                }
            },
            function () { /* per-frame scan failure, ignore */ }
        ).catch(function () {
            qrStatus.textContent = 'Could not access the camera — use the manual paste option below.';
        });
    }
    startScanner();

    document.getElementById('rescanBtn').addEventListener('click', function () {
        reviewForm.classList.add('d-none');
        reviewEmpty.classList.remove('d-none');
        qrStatus.textContent = 'Point the camera at the QR code shown on the probe\'s screen.';
        if (scanner) startScanner();
    });

    document.getElementById('parseManualBtn').addEventListener('click', function () {
        try {
            populateReview(JSON.parse(document.getElementById('manualPayload').value));
        } catch (e) {
            qrStatus.textContent = 'That text is not valid JSON.';
        }
    });
});
</script>
@endsection
