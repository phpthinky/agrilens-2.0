@extends('layouts.app')
@section('title', 'Manual Soil Analysis')
@section('page-title', 'Manual Soil Analysis')
@section('content')

<div class="row mb-3">
    <div class="col-md-8">
        <h2><i class="fas fa-keyboard me-2"></i>Manual Soil Analysis</h2>
        <p class="text-muted mb-0">{{ $sample->sample_name }} — {{ $sample->farm->farm_name }}</p>
    </div>
    <div class="col-md-4 text-end">
        <a href="{{ route('analyses.choose', $sample) }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
    </div>
</div>

<form method="POST" action="{{ route('analyses.store.manual', $sample) }}" id="manualForm">
    @csrf

    <div class="card mb-3">
        <div class="card-body">
            <label class="form-label">Soil Type</label>
            <input type="text" name="soil_type" class="form-control" style="max-width: 320px;" value="{{ old('soil_type') }}" placeholder="e.g. Clay loam">
        </div>
    </div>

    {{-- pH --}}
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-tint me-1"></i>Soil pH</span>
            <div class="btn-group btn-group-sm mode-toggle" data-param="ph">
                <button type="button" class="btn btn-outline-success active" data-mode="value">Enter Value</button>
                <button type="button" class="btn btn-outline-success" data-mode="color">Select Color</button>
            </div>
        </div>
        <div class="card-body">
            <input type="hidden" name="ph_mode" id="ph_mode" value="value">

            <div class="param-panel" data-param="ph" data-mode="value">
                <label class="form-label">pH Value (0&ndash;14) *</label>
                <input type="number" step="0.01" min="0" max="14" name="ph_value" id="ph_value" class="form-control" style="max-width: 200px;" value="{{ old('ph_value') }}">
            </div>

            <div class="param-panel d-none" data-param="ph" data-mode="color">
                <label class="form-label">Reagent Indicator *</label>
                <div class="btn-group btn-group-sm mb-3 indicator-toggle">
                    @foreach(['CPR', 'BCG', 'BTB'] as $indicator)
                        <button type="button" class="btn btn-outline-secondary {{ $loop->first ? 'active' : '' }}" data-indicator="{{ $indicator }}">{{ $indicator }}</button>
                    @endforeach
                </div>
                <input type="hidden" name="ph_indicator" id="ph_indicator" value="CPR">
                <input type="hidden" name="ph_hex" id="ph_hex" value="">

                @foreach(['CPR', 'BCG', 'BTB'] as $indicator)
                    <div class="swatch-grid {{ $indicator !== 'CPR' ? 'd-none' : '' }}" data-indicator-grid="{{ $indicator }}">
                        @foreach($charts['ph'][$indicator] as $hex => $value)
                            <button type="button" class="swatch" data-hex="{{ $hex }}" style="background:{{ $hex }};" title="pH {{ $value }}">
                                <span>{{ $value }}</span>
                            </button>
                        @endforeach
                    </div>
                @endforeach
                <div class="form-text">Click the swatch that most closely matches the reagent color. Selected: <strong id="ph_selection_label">none</strong></div>
            </div>
        </div>
    </div>

    {{-- N / P / K --}}
    @foreach(['nitrogen' => ['label' => 'Nitrogen (N)', 'icon' => 'fa-leaf'], 'phosphorus' => ['label' => 'Phosphorus (P)', 'icon' => 'fa-atom'], 'potassium' => ['label' => 'Potassium (K)', 'icon' => 'fa-bolt']] as $param => $meta)
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas {{ $meta['icon'] }} me-1"></i>{{ $meta['label'] }}</span>
            <div class="btn-group btn-group-sm mode-toggle" data-param="{{ $param }}">
                <button type="button" class="btn btn-outline-success active" data-mode="value">Enter Value</button>
                <button type="button" class="btn btn-outline-success" data-mode="color">Select Color</button>
            </div>
        </div>
        <div class="card-body">
            <input type="hidden" name="{{ $param }}_mode" id="{{ $param }}_mode" value="value">

            <div class="param-panel" data-param="{{ $param }}" data-mode="value">
                <label class="form-label">{{ $meta['label'] }} — ppm *</label>
                <input type="number" step="0.01" min="0" name="{{ $param }}_value" id="{{ $param }}_value" class="form-control" style="max-width: 200px;" value="{{ old($param . '_value') }}">
            </div>

            <div class="param-panel d-none" data-param="{{ $param }}" data-mode="color">
                <input type="hidden" name="{{ $param }}_hex" id="{{ $param }}_hex" value="">
                <div class="swatch-grid">
                    @foreach($charts[$param] as $hex => $value)
                        <button type="button" class="swatch" data-hex="{{ $hex }}" style="background:{{ $hex }};" title="{{ $value }} ppm">
                            <span>{{ $value }}</span>
                        </button>
                    @endforeach
                </div>
                <div class="form-text">Click the swatch that most closely matches the reagent color. Selected: <strong id="{{ $param }}_selection_label">none</strong></div>
            </div>
        </div>
    </div>
    @endforeach

    <div class="mt-4">
        <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i> Save Analysis</button>
        <a href="{{ route('analyses.choose', $sample) }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>

<style>
    .swatch-grid { display: flex; flex-wrap: wrap; gap: 8px; }
    .swatch {
        width: 56px; height: 56px; border-radius: 8px; border: 2px solid #dee2e6;
        display: flex; align-items: flex-end; justify-content: center; padding-bottom: 3px;
        cursor: pointer; position: relative;
    }
    .swatch span {
        font-size: .65rem; font-weight: 700; color: #fff;
        text-shadow: 0 0 3px rgba(0,0,0,.9);
    }
    .swatch.selected { border-color: #198754; box-shadow: 0 0 0 2px rgba(25,135,84,.35); }
</style>

@endsection
@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Mode toggle (Enter Value / Select Color) per parameter
    document.querySelectorAll('.mode-toggle').forEach(function (group) {
        const param = group.dataset.param;
        group.querySelectorAll('button').forEach(function (btn) {
            btn.addEventListener('click', function () {
                group.querySelectorAll('button').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                document.getElementById(param + '_mode').value = btn.dataset.mode;
                document.querySelectorAll('.param-panel[data-param="' + param + '"]').forEach(function (panel) {
                    panel.classList.toggle('d-none', panel.dataset.mode !== btn.dataset.mode);
                });
            });
        });
    });

    // pH indicator toggle (CPR / BCG / BTB)
    const indicatorToggle = document.querySelector('.indicator-toggle');
    if (indicatorToggle) {
        indicatorToggle.querySelectorAll('button').forEach(function (btn) {
            btn.addEventListener('click', function () {
                indicatorToggle.querySelectorAll('button').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                document.getElementById('ph_indicator').value = btn.dataset.indicator;
                document.querySelectorAll('[data-indicator-grid]').forEach(function (grid) {
                    grid.classList.toggle('d-none', grid.dataset.indicatorGrid !== btn.dataset.indicator);
                });
                document.getElementById('ph_hex').value = '';
                document.getElementById('ph_selection_label').textContent = 'none';
                document.querySelectorAll('.swatch.selected').forEach(s => s.classList.remove('selected'));
            });
        });
    }

    // Swatch selection
    document.querySelectorAll('.swatch').forEach(function (swatch) {
        swatch.addEventListener('click', function () {
            const panel = swatch.closest('.param-panel');
            const param = panel.dataset.param;
            panel.querySelectorAll('.swatch').forEach(s => s.classList.remove('selected'));
            swatch.classList.add('selected');
            document.getElementById(param + '_hex').value = swatch.dataset.hex;
            const label = document.getElementById(param + '_selection_label');
            if (label) label.textContent = swatch.title;
        });
    });
});
</script>
@endsection
