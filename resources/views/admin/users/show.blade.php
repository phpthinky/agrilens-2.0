@extends('layouts.app')
@section('title', $user->username . ' — Technician Profile')
@section('content')

<div class="row mb-3">
    <div class="col">
        <a href="{{ route('admin.users') }}" class="text-muted small"><i class="fa fa-arrow-left me-1"></i>Back to Technician Management</a>
        <div class="d-flex align-items-center mt-2 gap-3">
            <div class="position-relative">
                @if($user->photo_url)
                    <img src="{{ $user->photo_url }}" alt="{{ $user->username }}" class="rounded-circle" style="width:72px;height:72px;object-fit:cover;">
                @else
                    <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center text-white" style="width:72px;height:72px;font-size:28px;">
                        {{ strtoupper(substr($user->username, 0, 1)) }}
                    </div>
                @endif
                <button type="button" class="btn btn-sm btn-success rounded-circle position-absolute bottom-0 end-0 p-1" style="width:26px;height:26px;line-height:1;" data-bs-toggle="modal" data-bs-target="#photoModal" title="Change photo">
                    <i class="fa fa-camera" style="font-size:11px;"></i>
                </button>
            </div>
            <div>
                <h2 class="mb-0">
                    {{ $user->full_name }}
                    <span class="badge bg-{{ $user->user_type === 'admin' ? 'danger' : 'warning' }}">{{ ucfirst($user->user_type) }}</span>
                </h2>
                <p class="text-muted mb-0">
                    <i class="fa fa-at me-1"></i>{{ $user->username }} &middot; {{ $user->email }}
                    @if($user->address)
                        <br><i class="fa fa-location-dot me-1"></i>{{ $user->address }}
                    @endif
                </p>
            </div>
        </div>
    </div>
</div>

{{-- Photo upload modal --}}
<div class="modal fade" id="photoModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.users.photo', $user) }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Update Profile Photo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="file" name="photo" accept="image/*" class="form-control" required>
                    <div class="form-text">JPG or PNG, up to 4MB.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">
    <i class="fa fa-check-circle me-1"></i> {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show">
    {{ $errors->first() }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row g-3">
    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-header">Assigned Barangays (Territory)</div>
            <div class="card-body">
                @forelse($user->assignedBarangays as $b)
                    <span class="badge bg-info text-dark me-1 mb-1">{{ $b->name }}</span>
                @empty
                    <p class="text-muted small mb-0">No barangay territory assigned yet. Edit this technician from the list to assign one.</p>
                @endforelse
            </div>
        </div>

        <div class="card">
            <div class="card-header">Schedule a Farm for Testing</div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.users.assignments.store', $user) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Farm *</label>
                        <select name="farm_id" class="form-select @error('farm_id') is-invalid @enderror" required>
                            <option value="">Select farm</option>
                            @foreach($farms as $farm)
                                <option value="{{ $farm->id }}" {{ (string) old('farm_id') === (string) $farm->id ? 'selected' : '' }}>
                                    {{ $farm->farm_name }} — {{ $farm->farmer->full_name }} ({{ $farm->locationBarangay->name }})
                                </option>
                            @endforeach
                        </select>
                        @error('farm_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Scheduled Date *</label>
                        <input type="date" name="scheduled_date" class="form-control @error('scheduled_date') is-invalid @enderror" value="{{ old('scheduled_date') }}" required>
                        @error('scheduled_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <input type="text" name="notes" class="form-control" value="{{ old('notes') }}" placeholder="Optional">
                    </div>
                    <button type="submit" class="btn btn-success w-100"><i class="fa fa-calendar-plus me-1"></i>Schedule</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Testing History</span>
                <span class="text-muted small">
                    {{ $assignments->where('status', 'pending')->count() }} incomplete,
                    {{ $assignments->where('status', 'completed')->count() }} complete
                </span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Scheduled Date</th>
                            <th>Farm</th>
                            <th>Barangay</th>
                            <th>Status</th>
                            <th>Sample</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($assignments as $a)
                        <tr>
                            <td>{{ $a->scheduled_date->format('M j, Y') }}</td>
                            <td>{{ $a->farm->farm_name }}</td>
                            <td>{{ $a->farm->locationBarangay->name }}</td>
                            <td>
                                <span class="badge bg-{{ ['pending' => 'warning', 'completed' => 'success', 'cancelled' => 'secondary'][$a->status] }}">
                                    {{ $a->status === 'pending' ? 'Incomplete' : ucfirst($a->status) }}
                                </span>
                            </td>
                            <td>
                                @if($a->soilSample)
                                    <a href="{{ route('samples.show', $a->soilSample) }}">View</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-center">
                                @if($a->status === 'pending')
                                <form method="POST" action="{{ route('admin.users.assignments.cancel', [$user, $a]) }}" class="d-inline" onsubmit="return confirm('Cancel this assignment?');">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Cancel"><i class="fa fa-ban"></i></button>
                                </form>
                                @else
                                <form method="POST" action="{{ route('admin.users.assignments.destroy', [$user, $a]) }}" class="d-inline" onsubmit="return confirm('Remove this record?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-secondary" title="Remove"><i class="fa fa-trash"></i></button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No farms scheduled for this technician yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
