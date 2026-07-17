@extends('layouts.app')
@section('title', $farmer->full_name)
@section('content')

<div class="row mb-3">
    <div class="col-md-8">
        <h2><i class="fas fa-user-tie me-2"></i>{{ $farmer->full_name }}</h2>
        <p class="text-muted mb-0">{{ $farmer->barangay?->name ?? 'No barangay assigned' }} &middot; {{ $farmer->farmer_type }}</p>
    </div>
    <div class="col-md-4 text-end">
        <a href="{{ route('farmers.edit', $farmer) }}" class="btn btn-outline-secondary"><i class="fas fa-edit me-1"></i>Edit</a>
        <a href="{{ route('farmers.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i></a>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Farmer Details</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Gender</dt><dd class="col-sm-8">{{ $farmer->gender }}</dd>
                    <dt class="col-sm-4">Age</dt><dd class="col-sm-8">{{ $farmer->age ?? '—' }}</dd>
                    <dt class="col-sm-4">Contact</dt><dd class="col-sm-8">{{ $farmer->contact_number ?: '—' }}</dd>
                    <dt class="col-sm-4">Email</dt><dd class="col-sm-8">{{ $farmer->email ?: '—' }}</dd>
                    <dt class="col-sm-4">Address</dt><dd class="col-sm-8">{{ $farmer->address }}</dd>
                    <dt class="col-sm-4">ID</dt><dd class="col-sm-8">{{ $farmer->id_type ? "{$farmer->id_type} — {$farmer->id_number}" : '—' }}</dd>
                    <dt class="col-sm-4">Years Farming</dt><dd class="col-sm-8">{{ $farmer->years_farming ?? '—' }}</dd>
                    <dt class="col-sm-4">Crops Grown</dt><dd class="col-sm-8">{{ $farmer->formatted_crops }}</dd>
                    <dt class="col-sm-4">Education</dt><dd class="col-sm-8">{{ $farmer->education_level ?: '—' }}</dd>
                    <dt class="col-sm-4">Notes</dt><dd class="col-sm-8">{{ $farmer->notes ?: '—' }}</dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Farms ({{ $farmer->farms->count() }})</span>
                <a href="{{ route('farms.create', ['farmer_id' => $farmer->id]) }}" class="btn btn-sm btn-success">
                    <i class="fas fa-plus me-1"></i>Add Farm
                </a>
            </div>
            <div class="card-body">
                @forelse($farmer->farms as $farm)
                    <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                        <a href="{{ route('farms.show', $farm) }}">{{ $farm->farm_name }}</a>
                        <span class="text-muted small">{{ $farm->formatted_area }}</span>
                    </div>
                @empty
                    <p class="text-muted mb-0">No farms registered for this farmer yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
