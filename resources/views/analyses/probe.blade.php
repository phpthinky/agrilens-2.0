@extends('layouts.app')
@section('title', 'Digital Probe Analysis')
@section('page-title', 'Digital Probe Analysis')
@section('content')

<div class="row mb-3">
    <div class="col-md-8">
        <h2><i class="fas fa-satellite-dish me-2"></i>Digital Probe Analysis</h2>
        <p class="text-muted mb-0">{{ $sample->sample_name }} — {{ $sample->farm->farm_name }} — {{ $sample->farm->farmer->full_name }}</p>
    </div>
    <div class="col-md-4 text-end">
        <a href="{{ route('analyses.choose', $sample) }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">1. Connect the 8-in-1 NPK Sensor</div>
            <div class="card-body">
                <p class="text-muted small">Plug in the sensor's USB cable (built-in RS485-to-USB), then click Connect and pick its port.</p>

                <div id="serialUnsupported" class="alert alert-warning small d-none">
                    This browser does not support the Web Serial API. Use Chrome or Edge, and make sure the page is loaded over HTTPS or on localhost.
                </div>

                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label small mb-1">Baud rate</label>
                        <select id="baudRate" class="form-select form-select-sm">
                            <option value="4800">4800</option>
                            <option value="9600" selected>9600</option>
                            <option value="19200">19200</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small mb-1">Slave address</label>
                        <input type="number" id="slaveAddress" class="form-control form-control-sm" value="1" min="1" max="247">
                    </div>
                </div>

                <div class="d-flex gap-2 mt-3">
                    <button type="button" id="connectBtn" class="btn btn-success btn-sm"><i class="fas fa-plug me-1"></i>Connect</button>
                    <button type="button" id="readBtn" class="btn btn-outline-success btn-sm" disabled><i class="fas fa-sync me-1"></i>Read Sensor</button>
                    <button type="button" id="disconnectBtn" class="btn btn-outline-secondary btn-sm d-none">Disconnect</button>
                </div>

                <p class="text-muted small mt-2 mb-0" id="sensorStatus">Not connected.</p>

                <details class="mt-3">
                    <summary class="text-muted small">Advanced: register map</summary>
                    <div class="row g-2 mt-1">
                        <div class="col-6">
                            <label class="form-label small mb-1">Start register</label>
                            <input type="number" id="regStart" class="form-control form-control-sm" value="0" min="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label small mb-1">Register count</label>
                            <input type="number" id="regCount" class="form-control form-control-sm" value="7" min="4" max="16">
                        </div>
                    </div>
                    <p class="text-muted small mt-2 mb-0">
                        Default map assumes registers [moisture, temperature, EC, pH, N, P, K] as used by most
                        7/8-in-1 RS485 soil sensors. Adjust here if this unit's datasheet differs.
                    </p>
                </details>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">Or enter a reading manually</div>
            <div class="card-body">
                <textarea id="manualPayload" class="form-control" rows="4" placeholder='{"probe_id":"SENSOR-01","ph":6.5,"n":42,"p":18.5,"k":150}'></textarea>
                <button type="button" id="parseManualBtn" class="btn btn-sm btn-outline-secondary mt-2">
                    <i class="fas fa-check me-1"></i>Use These Values
                </button>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">2. Review &amp; Confirm</div>
            <div class="card-body">
                <div id="reviewEmpty" class="text-center text-muted py-5">
                    <i class="fas fa-microchip fa-3x mb-3"></i>
                    <p>Waiting for a sensor reading...</p>
                </div>

                <form method="POST" action="{{ route('analyses.store.probe', $sample) }}" id="reviewForm" class="d-none">
                    @csrf
                    <div class="alert alert-info small">Values were read from the sensor — review and edit if needed before saving.</div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Probe ID *</label>
                            <input type="text" name="probe_id" id="field_probe_id" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Soil pH</label>
                            <input type="number" step="0.01" min="0" max="14" name="ph_level" id="field_ph" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nitrogen (mg/kg) *</label>
                            <input type="number" step="0.01" name="nitrogen_level" id="field_n" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Phosphorus (mg/kg) *</label>
                            <input type="number" step="0.01" name="phosphorus_level" id="field_p" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Potassium (mg/kg) *</label>
                            <input type="number" step="0.01" name="potassium_level" id="field_k" class="form-control" required>
                        </div>
                    </div>

                    <input type="hidden" name="probe_raw_payload" id="field_raw_payload">

                    <div class="mt-4">
                        <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i> Confirm &amp; Save</button>
                        <button type="button" id="rereadBtn" class="btn btn-outline-secondary">Read Again</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const reviewEmpty = document.getElementById('reviewEmpty');
    const reviewForm = document.getElementById('reviewForm');
    const sensorStatus = document.getElementById('sensorStatus');
    const connectBtn = document.getElementById('connectBtn');
    const readBtn = document.getElementById('readBtn');
    const disconnectBtn = document.getElementById('disconnectBtn');

    let port = null;
    let reader = null;
    let writer = null;

    function populateReview(payload) {
        if (!payload || typeof payload !== 'object') {
            sensorStatus.textContent = 'Could not read a valid reading from that source.';
            return;
        }
        const required = ['n', 'p', 'k'];
        const missing = required.filter(k => payload[k] === undefined || payload[k] === null);
        if (missing.length) {
            sensorStatus.textContent = 'Reading is missing required field(s): ' + missing.join(', ');
            return;
        }

        document.getElementById('field_probe_id').value = payload.probe_id || ('RS485-' + Date.now());
        document.getElementById('field_ph').value = (payload.ph !== undefined && payload.ph !== null) ? payload.ph : '';
        document.getElementById('field_n').value = payload.n;
        document.getElementById('field_p').value = payload.p;
        document.getElementById('field_k').value = payload.k;
        document.getElementById('field_raw_payload').value = JSON.stringify(payload);

        reviewEmpty.classList.add('d-none');
        reviewForm.classList.remove('d-none');
        sensorStatus.textContent = 'Reading loaded — review the values below before saving.';
    }

    // ── Modbus RTU (function 0x03 — read holding registers) over Web Serial ──

    function modbusCrc16(bytes) {
        let crc = 0xFFFF;
        for (let i = 0; i < bytes.length; i++) {
            crc ^= bytes[i];
            for (let b = 0; b < 8; b++) {
                if (crc & 0x0001) {
                    crc = (crc >> 1) ^ 0xA001;
                } else {
                    crc = crc >> 1;
                }
            }
        }
        return crc;
    }

    function buildReadRequest(slaveId, startReg, count) {
        const frame = [
            slaveId & 0xFF,
            0x03,
            (startReg >> 8) & 0xFF, startReg & 0xFF,
            (count >> 8) & 0xFF, count & 0xFF,
        ];
        const crc = modbusCrc16(frame);
        frame.push(crc & 0xFF, (crc >> 8) & 0xFF);
        return new Uint8Array(frame);
    }

    function parseReadResponse(bytes, expectedCount) {
        if (bytes.length < 5 + expectedCount * 2) {
            throw new Error('Response too short');
        }
        const byteCount = bytes[2];
        if (byteCount !== expectedCount * 2) {
            throw new Error('Unexpected register count in response');
        }
        const receivedCrc = bytes[3 + byteCount] | (bytes[4 + byteCount] << 8);
        const computedCrc = modbusCrc16(bytes.slice(0, 3 + byteCount));
        if (receivedCrc !== computedCrc) {
            throw new Error('CRC mismatch');
        }

        const registers = [];
        for (let i = 0; i < expectedCount; i++) {
            const hi = bytes[3 + i * 2];
            const lo = bytes[4 + i * 2];
            registers.push((hi << 8) | lo);
        }
        return registers;
    }

    // Default map for common 7/8-in-1 RS485 soil sensors:
    // [0]=moisture(x0.1%) [1]=temperature(x0.1C) [2]=EC(us/cm) [3]=pH(x0.1) [4]=N(mg/kg) [5]=P(mg/kg) [6]=K(mg/kg)
    function registersToPayload(registers) {
        const signed16 = (v) => (v > 0x7FFF ? v - 0x10000 : v);
        return {
            probe_id: 'RS485-' + document.getElementById('slaveAddress').value,
            moisture: registers[0] !== undefined ? registers[0] / 10 : undefined,
            temperature: registers[1] !== undefined ? signed16(registers[1]) / 10 : undefined,
            ec: registers[2],
            ph: registers[3] !== undefined ? registers[3] / 10 : null,
            n: registers[4],
            p: registers[5],
            k: registers[6],
        };
    }

    async function readOnce() {
        if (!port) return;
        const slaveId = parseInt(document.getElementById('slaveAddress').value, 10) || 1;
        const startReg = parseInt(document.getElementById('regStart').value, 10) || 0;
        const count = parseInt(document.getElementById('regCount').value, 10) || 7;

        sensorStatus.textContent = 'Reading…';
        try {
            const request = buildReadRequest(slaveId, startReg, count);
            await writer.write(request);

            const responseBytes = await readResponseBytes(3 + count * 2 + 2);
            const registers = parseReadResponse(responseBytes, count);
            populateReview(registersToPayload(registers));
        } catch (e) {
            sensorStatus.textContent = 'Read failed: ' + e.message;
        }
    }

    async function readResponseBytes(minLength) {
        const chunks = [];
        let total = 0;
        const timeoutAt = Date.now() + 2000;

        while (total < minLength && Date.now() < timeoutAt) {
            const { value, done } = await reader.read();
            if (done) break;
            if (value) {
                chunks.push(value);
                total += value.length;
            }
        }

        const merged = new Uint8Array(total);
        let offset = 0;
        for (const chunk of chunks) {
            merged.set(chunk, offset);
            offset += chunk.length;
        }
        return merged;
    }

    async function connect() {
        if (!('serial' in navigator)) {
            document.getElementById('serialUnsupported').classList.remove('d-none');
            return;
        }
        try {
            port = await navigator.serial.requestPort();
            const baudRate = parseInt(document.getElementById('baudRate').value, 10);
            await port.open({ baudRate });
            writer = port.writable.getWriter();
            reader = port.readable.getReader();

            connectBtn.classList.add('d-none');
            disconnectBtn.classList.remove('d-none');
            readBtn.disabled = false;
            sensorStatus.textContent = 'Connected — click "Read Sensor" to take a reading.';
        } catch (e) {
            sensorStatus.textContent = 'Could not connect: ' + e.message;
        }
    }

    async function disconnect() {
        try {
            if (reader) { await reader.cancel().catch(() => {}); reader.releaseLock(); reader = null; }
            if (writer) { writer.releaseLock(); writer = null; }
            if (port) { await port.close().catch(() => {}); port = null; }
        } finally {
            connectBtn.classList.remove('d-none');
            disconnectBtn.classList.add('d-none');
            readBtn.disabled = true;
            sensorStatus.textContent = 'Disconnected.';
        }
    }

    connectBtn.addEventListener('click', connect);
    disconnectBtn.addEventListener('click', disconnect);
    readBtn.addEventListener('click', readOnce);

    document.getElementById('rereadBtn').addEventListener('click', function () {
        reviewForm.classList.add('d-none');
        reviewEmpty.classList.remove('d-none');
        sensorStatus.textContent = port ? 'Connected — click "Read Sensor" to take a reading.' : 'Not connected.';
        if (port) readOnce();
    });

    document.getElementById('parseManualBtn').addEventListener('click', function () {
        try {
            populateReview(JSON.parse(document.getElementById('manualPayload').value));
        } catch (e) {
            sensorStatus.textContent = 'That text is not valid JSON.';
        }
    });

    if (!('serial' in navigator)) {
        document.getElementById('serialUnsupported').classList.remove('d-none');
    }
});
</script>
@endsection
