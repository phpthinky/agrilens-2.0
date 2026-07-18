@extends('layouts.app')
@section('title', 'Create Sample')
@section('page-title', 'Create Sample')
@section('content')

<div class="row mb-3">
    <div class="col-md-8">
        <h2><i class="fas fa-vial me-2"></i>Create Sample</h2>
        <p class="text-muted mb-0">For <strong>{{ $farm->farm_name }}</strong> — {{ $farm->farmer->full_name }}</p>
    </div>
    <div class="col-md-4 text-end">
        <a href="{{ route('farms.show', $farm) }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back to Farm</a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('samples.store', $farm) }}">
            @csrf

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Sample Name *</label>
                    <input type="text" name="sample_name" class="form-control @error('sample_name') is-invalid @enderror"
                           value="{{ old('sample_name') }}" required autofocus>
                    @error('sample_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Farmer</label>
                    <input type="text" class="form-control" value="{{ $farm->farmer->full_name }}" disabled>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Farm</label>
                    <input type="text" class="form-control" value="{{ $farm->farm_name }}" disabled>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Date Received *</label>
                    <input type="date" name="sample_date" class="form-control @error('sample_date') is-invalid @enderror"
                           value="{{ old('sample_date', date('Y-m-d')) }}" required>
                    @error('sample_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Date of Analysis *</label>
                    <input type="date" name="date_tested" class="form-control @error('date_tested') is-invalid @enderror"
                           value="{{ old('date_tested', date('Y-m-d')) }}" required>
                    @error('date_tested')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Analyst</label>
                    <input type="text" class="form-control" value="{{ Auth::user()->username }}" disabled>
                </div>

                <div class="col-12">
                    <label class="form-label">Notes <span class="text-muted fw-normal">(optional)</span></label>
                    <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-success"><i class="fas fa-arrow-right me-1"></i> Save &amp; Choose Analysis Method</button>
                <a href="{{ route('farms.show', $farm) }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
