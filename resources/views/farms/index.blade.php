@extends('layouts.app')
@section('title', 'Farms')
@section('content')

<div class="row mb-3">
    <div class="col-md-8">
        <h2><i class="fas fa-tractor me-2"></i>Farms</h2>
        <p class="lead text-muted">Registered farms with GPS boundaries</p>
    </div>
    <div class="col-md-4 text-end">
        <a href="{{ route('farms.create') }}" class="btn btn-success">
            <i class="fas fa-plus-circle me-1"></i> New Farm
        </a>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search farm, farmer, crops..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="location_barangay" class="form-select">
                    <option value="">All Barangays</option>
                    @foreach($barangays as $b)
                        <option value="{{ $b->id }}" {{ (string) request('location_barangay') === (string) $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-outline-success w-100"><i class="fas fa-filter me-1"></i>Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        @if($farms->isEmpty())
        <div class="text-center py-5">
            <i class="fas fa-tractor fa-3x text-muted mb-3"></i>
            <p class="text-muted">No farms registered yet.</p>
            <a href="{{ route('farms.create') }}" class="btn btn-success">
                <i class="fas fa-plus-circle"></i> Add Your First Farm
            </a>
        </div>
        @else
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-success">
                    <tr>
                        <th>Farm</th><th>Farmer</th><th>Barangay</th><th>Type</th><th>Area</th><th>Analyses</th><th>Status</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($farms as $farm)
                    <tr>
                        <td><a href="{{ route('farms.show', $farm) }}">{{ $farm->farm_name }}</a></td>
                        <td>{{ $farm->farmer->full_name }}</td>
                        <td>{{ $farm->locationBarangay->name }}</td>
                        <td>{{ $farm->farm_type }}</td>
                        <td>{{ $farm->formatted_area }}</td>
                        <td>{{ $farm->soil_samples_count }}</td>
                        <td>
                            @if($farm->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('farms.show', $farm) }}" class="btn btn-sm btn-outline-primary me-1"><i class="fas fa-eye"></i></a>
                            <a href="{{ route('farms.edit', $farm) }}" class="btn btn-sm btn-outline-secondary me-1"><i class="fas fa-edit"></i></a>
                            <form action="{{ route('farms.destroy', $farm) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this farm?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $farms->links() }}</div>
        @endif
    </div>
</div>
@endsection
