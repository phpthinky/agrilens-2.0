@extends('layouts.app')
@section('title', 'Calibrate — ' . $crop->name)
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fas fa-sliders-h me-2 text-success"></i>Calibrate: {{ $crop->name }}</h4>
    <a href="{{ route('admin.fertilizer-calibration.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Back
    </a>
</div>

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show">
    {{ $errors->first() }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="alert alert-info small mb-4">
    <i class="fas fa-info-circle me-1"></i>
    For each nutrient (N, P, K), the 3 stage fractions must add up to <strong>1.0</strong> —
    that's the crop's full seasonal target split across the application timeline. Enter
    <strong>0</strong> for all three stages of a nutrient this crop doesn't need scheduled.
    Fractions are decimals (e.g. <code>0.5</code> = 50% of the target applied at that stage).
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.fertilizer-calibration.update', $crop) }}">
            @csrf @method('PUT')

            <div class="table-responsive">
                <table class="table table-bordered align-middle" id="calibrationTable">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width:260px;">Stage Label</th>
                            <th style="width:120px;">N fraction</th>
                            <th style="width:120px;">P fraction</th>
                            <th style="width:120px;">K fraction</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $defaultLabels = [1 => '1st Application', 2 => '2nd Application', 3 => '3rd Application'];
                        @endphp
                        @foreach([1, 2, 3] as $stage)
                        @php $row = $stages->get($stage); @endphp
                        <tr>
                            <td>
                                <input type="text" name="stages[{{ $stage }}][label]" class="form-control form-control-sm"
                                       value="{{ old("stages.$stage.label", $row->label ?? $defaultLabels[$stage]) }}" required maxlength="150">
                            </td>
                            <td>
                                <input type="number" step="0.0001" min="0" max="1" name="stages[{{ $stage }}][n_fraction]"
                                       class="form-control form-control-sm nutrient-input" data-nutrient="n"
                                       value="{{ old("stages.$stage.n_fraction", $row->n_fraction ?? 0) }}" required>
                            </td>
                            <td>
                                <input type="number" step="0.0001" min="0" max="1" name="stages[{{ $stage }}][p_fraction]"
                                       class="form-control form-control-sm nutrient-input" data-nutrient="p"
                                       value="{{ old("stages.$stage.p_fraction", $row->p_fraction ?? 0) }}" required>
                            </td>
                            <td>
                                <input type="number" step="0.0001" min="0" max="1" name="stages[{{ $stage }}][k_fraction]"
                                       class="form-control form-control-sm nutrient-input" data-nutrient="k"
                                       value="{{ old("stages.$stage.k_fraction", $row->k_fraction ?? 0) }}" required>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <td class="text-end fw-semibold">Sum</td>
                            <td><span id="sumN" class="fw-bold">0</span></td>
                            <td><span id="sumP" class="fw-bold">0</span></td>
                            <td><span id="sumK" class="fw-bold">0</span></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i> Save Calibration</button>
                <a href="{{ route('admin.fertilizer-calibration.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const inputs = document.querySelectorAll('.nutrient-input');

    function recalc() {
        ['n', 'p', 'k'].forEach(function (nutrient) {
            let sum = 0;
            document.querySelectorAll('.nutrient-input[data-nutrient="' + nutrient + '"]').forEach(function (el) {
                sum += parseFloat(el.value) || 0;
            });
            sum = Math.round(sum * 10000) / 10000;
            const el = document.getElementById('sum' + nutrient.toUpperCase());
            el.textContent = sum;
            el.className = (sum === 1 || sum === 0) ? 'fw-bold text-success' : 'fw-bold text-danger';
        });
    }

    inputs.forEach(function (el) { el.addEventListener('input', recalc); });
    recalc();
});
</script>
@endsection
