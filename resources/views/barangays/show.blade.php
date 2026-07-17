@extends('layouts.app')
@section('title', $barangay->name)
@section('content')

<div class="row mb-3">
    <div class="col-md-8">
        <h2><i class="fas fa-city me-2"></i>{{ $barangay->name }} <span class="badge bg-secondary">{{ $barangay->code }}</span></h2>
    </div>
    <div class="col-md-4 text-end">
        <a href="{{ route('barangays.edit', $barangay) }}" class="btn btn-outline-secondary"><i class="fas fa-edit me-1"></i>Edit</a>
        <a href="{{ route('barangays.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i></a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card text-center"><div class="card-body">
            <div class="fs-3 fw-bold text-success">{{ $stats['total_farmers'] }}</div>
            <div class="text-muted">Farmers</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card text-center"><div class="card-body">
            <div class="fs-3 fw-bold text-success">{{ $stats['total_farms'] }}</div>
            <div class="text-muted">Farms</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card text-center"><div class="card-body">
            <div class="fs-3 fw-bold text-success">{{ number_format($stats['total_area'] ?? 0, 2) }} ha</div>
            <div class="text-muted">Total Farm Area</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card text-center"><div class="card-body">
            <div class="fs-3 fw-bold text-success">{{ $stats['soil_samples'] }}</div>
            <div class="text-muted">Soil Analyses</div>
        </div></div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">Description</div>
            <div class="card-body">{{ $barangay->description ?: 'No description.' }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">Assigned Technicians</div>
            <div class="card-body">
                @forelse($barangay->assignedTechnicians as $tech)
                    <div class="mb-1"><i class="fas fa-user-shield me-1 text-muted"></i>{{ $tech->username }}</div>
                @empty
                    <p class="text-muted mb-0">No technicians assigned.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
