{{--
    ┌─────────────────────────────────────────────────────────────────────────┐
    │  Fertilizer Schedule — Crop Selection Modal                             │
    │  Include this partial anywhere in show.blade.php, then trigger it with  │
    │  the button:                                                             │
    │    <button … onclick="openFertModal()">Fertilizer Schedule</button>     │
    │                                                                          │
    │  Pass $schedulableCrops (Collection of Crop models) from the controller.│
    └─────────────────────────────────────────────────────────────────────────┘
--}}

{{-- ── Modal backdrop & dialog ───────────────────────────────────────────── --}}
<div id="fertScheduleModal"
     role="dialog" aria-modal="true" aria-labelledby="fertModalTitle"
     style="display:none; position:fixed; inset:0; z-index:9999;
            background:rgba(0,0,0,.5); align-items:center; justify-content:center;">

    <div style="background:#fff; border-radius:10px; width:100%; max-width:480px;
                margin:auto; box-shadow:0 8px 32px rgba(0,0,0,.25); overflow:hidden;">

        {{-- Header --}}
        <div style="background:#1565c0; color:#fff; padding:16px 20px;
                    display:flex; align-items:center; justify-content:space-between;">
            <div>
                <div style="font-weight:700; font-size:1rem;" id="fertModalTitle">
                    🌾 Fertilizer Schedule
                </div>
                <div style="font-size:.8rem; opacity:.85; margin-top:2px;">
                    Select the crop to generate the application schedule
                </div>
            </div>
            <button onclick="closeFertModal()"
                    style="background:none; border:none; color:#fff; font-size:1.4rem;
                           cursor:pointer; line-height:1; padding:0 4px;"
                    aria-label="Close">&times;</button>
        </div>

        {{-- Body --}}
        <div style="padding:24px 20px;">

            @if($schedulableCrops->isEmpty())
                <p style="color:#666; font-size:.9rem; text-align:center;">
                    No crops with a fertilizer schedule are available.
                </p>
            @else
                <form method="GET" action="{{ route('samples.fertilizer-schedule', $sample) }}"
                      id="fertScheduleForm">
                    @csrf

                    <label for="fertCropSelect"
                           style="display:block; font-weight:600; margin-bottom:8px; color:#333;">
                        Crop
                    </label>

                    <select id="fertCropSelect" name="crop_id" required
                            style="width:100%; padding:10px 12px; border:1px solid #ccc;
                                   border-radius:6px; font-size:.95rem; color:#222;
                                   appearance:auto; margin-bottom:14px;">
                        <option value="" disabled selected>— Select a crop —</option>
                        @foreach($schedulableCrops as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>

                    <label for="fertBasalSelect"
                           style="display:block; font-weight:600; margin-bottom:8px; color:#333;">
                        Basal (1st Application) Fertilizer
                    </label>
                    <select id="fertBasalSelect" name="basal_fert"
                            style="width:100%; padding:10px 12px; border:1px solid #ccc;
                                   border-radius:6px; font-size:.95rem; color:#222;
                                   appearance:auto; margin-bottom:6px;">
                        <option value="urea">Urea (46-0-0) — default</option>
                        <option value="complete">Complete (14-14-14)</option>
                        <option value="ammonium_sulfate">Ammonium Sulfate (21-0-0)</option>
                    </select>
                    <div style="font-size:.78rem; color:#777; margin-bottom:4px;">
                        Stages 2 &amp; 3 (topdress) always use Urea for nitrogen.
                    </div>

                    {{-- Nutrient targets preview (populated via JS) --}}
                    <div id="fertTargetPreview"
                         style="display:none; background:#f0f7ff; border:1px solid #90caf9;
                                border-radius:6px; padding:10px 14px; margin-top:12px;
                                font-size:.85rem; color:#1a237e;">
                        <strong>Target NPK (kg/ha) based on soil status:</strong>
                        <div style="margin-top:6px; display:flex; gap:16px; flex-wrap:wrap;">
                            <span>N: <strong id="prevN">—</strong></span>
                            <span>P: <strong id="prevP">—</strong></span>
                            <span>K: <strong id="prevK">—</strong></span>
                        </div>
                        <div style="margin-top:4px; font-size:.78rem; color:#555;">
                            Soil status — N: <em id="prevNSt">—</em> |
                                          P: <em id="prevPSt">—</em> |
                                          K: <em id="prevKSt">—</em>
                        </div>
                    </div>

                    <div style="margin-top:20px; display:flex; gap:10px; justify-content:flex-end;">
                        <button type="button" onclick="closeFertModal()"
                                style="padding:9px 20px; border:1px solid #ccc; border-radius:6px;
                                       background:#fff; cursor:pointer; font-size:.9rem;">
                            Cancel
                        </button>
                        <button type="submit"
                                style="padding:9px 22px; background:#1565c0; color:#fff;
                                       border:none; border-radius:6px; cursor:pointer;
                                       font-size:.9rem; font-weight:600;">
                            📄 Generate Schedule
                        </button>
                    </div>
                </form>
            @endif

        </div>
    </div>
</div>

{{-- ── JS: open / close + live NPK preview ──────────────────────────────── --}}
<script>
@php
    // Pre-build the crop data array here so Blade's parser never sees
    // arrow-function syntax inside @json(), which causes "Unclosed '['" errors.
    $__fertCropData = [];
    foreach ($schedulableCrops as $__c) {
        $__fertCropData[$__c->id] = [
            'n_low'  => $__c->n_low,  'n_med'  => $__c->n_med,  'n_high'  => $__c->n_high,
            'p_low'  => $__c->p_low,  'p_med'  => $__c->p_med,  'p_high'  => $__c->p_high,
            'k_low'  => $__c->k_low,  'k_med'  => $__c->k_med,  'k_high'  => $__c->k_high,
        ];
    }
    $__fertNStatus = strtolower($nStatus ?? 'medium');
    $__fertPStatus = strtolower($pStatus ?? 'medium');
    $__fertKStatus = strtolower($kStatus ?? 'medium');
@endphp
(function () {
    // Soil LMH status from server (injected safely via Blade)
    const nStatus  = @json($__fertNStatus);
    const pStatus  = @json($__fertPStatus);
    const kStatus  = @json($__fertKStatus);

    // All crop NPK data keyed by crop id — pulled from $schedulableCrops
    const cropData = @json($__fertCropData);

    function pick(data, nutrient, status) {
        const map = { low: `${nutrient}_low`, medium: `${nutrient}_med`, high: `${nutrient}_high` };
        return data[map[status] ?? `${nutrient}_med`] ?? '—';
    }

    window.openFertModal = function () {
        const modal = document.getElementById('fertScheduleModal');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    };

    window.closeFertModal = function () {
        document.getElementById('fertScheduleModal').style.display = 'none';
        document.body.style.overflow = '';
    };

    // Close on backdrop click
    document.getElementById('fertScheduleModal').addEventListener('click', function (e) {
        if (e.target === this) closeFertModal();
    });

    // Live preview when crop changes
    const sel     = document.getElementById('fertCropSelect');
    const preview = document.getElementById('fertTargetPreview');

    if (sel) {
        sel.addEventListener('change', function () {
            const data = cropData[this.value];
            if (!data) { preview.style.display = 'none'; return; }

            document.getElementById('prevN').textContent = pick(data, 'n', nStatus);
            document.getElementById('prevP').textContent = pick(data, 'p', pStatus);
            document.getElementById('prevK').textContent = pick(data, 'k', kStatus);
            document.getElementById('prevNSt').textContent = nStatus.charAt(0).toUpperCase() + nStatus.slice(1);
            document.getElementById('prevPSt').textContent = pStatus.charAt(0).toUpperCase() + pStatus.slice(1);
            document.getElementById('prevKSt').textContent = kStatus.charAt(0).toUpperCase() + kStatus.slice(1);

            preview.style.display = 'block';
        });
    }
})();
</script>