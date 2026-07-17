@extends('layouts.app')
@section('title', 'Manual Soil Analysis')
@section('content')

<div class="row mb-3">
    <div class="col-md-8">
        <h2><i class="fas fa-keyboard me-2"></i>Manual Soil Analysis</h2>
        <p class="text-muted mb-0">For <strong>{{ $farm->farm_name }}</strong> — {{ $farm->farmer->full_name }}</p>
    </div>
    <div class="col-md-4 text-end">
        <a href="{{ route('analyses.choose', $farm) }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('analyses.store', $farm) }}">
            @csrf
            <input type="hidden" name="analysis_type" value="manual">

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Sample Name *</label>
                    <input type="text" name="sample_name" class="form-control @error('sample_name') is-invalid @enderror"
                           value="{{ old('sample_name') }}" required>
                    @error('sample_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Soil Type</label>
                    <input type="text" name="soil_type" class="form-control" value="{{ old('soil_type') }}" placeholder="e.g. Clay loam">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Sample Date *</label>
                    <input type="date" name="sample_date" class="form-control @error('sample_date') is-invalid @enderror"
                           value="{{ old('sample_date', date('Y-m-d')) }}" required>
                    @error('sample_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Date Tested *</label>
                    <input type="date" name="date_tested" class="form-control @error('date_tested') is-invalid @enderror"
                           value="{{ old('date_tested', date('Y-m-d')) }}" required>
                    @error('date_tested')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <hr class="my-4">
            <h5>Soil Parameters</h5>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">pH (0–14) *</label>
                    <input type="number" step="0.01" name="ph_level" class="form-control @error('ph_level') is-invalid @enderror"
                           value="{{ old('ph_level') }}" required>
                    @error('ph_level')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Nitrogen (ppm) *</label>
                    <input type="number" step="0.01" name="nitrogen_level" class="form-control @error('nitrogen_level') is-invalid @enderror"
                           value="{{ old('nitrogen_level') }}" required>
                    @error('nitrogen_level')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Phosphorus (ppm) *</label>
                    <input type="number" step="0.01" name="phosphorus_level" class="form-control @error('phosphorus_level') is-invalid @enderror"
                           value="{{ old('phosphorus_level') }}" required>
                    @error('phosphorus_level')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Potassium (ppm) *</label>
                    <input type="number" step="0.01" name="potassium_level" class="form-control @error('potassium_level') is-invalid @enderror"
                           value="{{ old('potassium_level') }}" required>
                    @error('potassium_level')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i> Save Analysis</button>
                <a href="{{ route('analyses.choose', $farm) }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
