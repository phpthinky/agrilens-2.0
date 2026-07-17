@extends('layouts.app')
@section('title', 'Barangays')
@section('content')

<div class="row mb-3">
    <div class="col-md-8">
        <h2><i class="fas fa-city me-2"></i>Barangays</h2>
        <p class="lead text-muted">Manage barangays and technician territory assignments</p>
    </div>
    <div class="col-md-4 text-end">
        <a href="{{ route('barangays.create') }}" class="btn btn-success">
            <i class="fas fa-plus-circle me-1"></i> New Barangay
        </a>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-6">
                <input type="text" name="search" class="form-control" placeholder="Search name, code, description..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
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
        @if($barangays->isEmpty())
        <div class="text-center py-5">
            <i class="fas fa-city fa-3x text-muted mb-3"></i>
            <p class="text-muted">No barangays yet.</p>
            <a href="{{ route('barangays.create') }}" class="btn btn-success">
                <i class="fas fa-plus-circle"></i> Add Your First Barangay
            </a>
        </div>
        @else
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-success">
                    <tr>
                        <th>Name</th><th>Code</th><th>Farmers</th><th>Farms</th><th>Area</th><th>Status</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($barangays as $b)
                    <tr>
                        <td><a href="{{ route('barangays.show', $b) }}">{{ $b->name }}</a></td>
                        <td><span class="badge bg-secondary">{{ $b->code }}</span></td>
                        <td>{{ $b->farmers_count }}</td>
                        <td>{{ $b->farms_count }}</td>
                        <td>{{ $b->formatted_area }}</td>
                        <td>
                            @if($b->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('barangays.show', $b) }}" class="btn btn-sm btn-outline-primary me-1"><i class="fas fa-eye"></i></a>
                            <a href="{{ route('barangays.edit', $b) }}" class="btn btn-sm btn-outline-secondary me-1"><i class="fas fa-edit"></i></a>
                            <form action="{{ route('barangays.destroy', $b) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this barangay?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $barangays->links() }}</div>
        @endif
    </div>
</div>
@endsection
