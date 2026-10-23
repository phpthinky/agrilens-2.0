@extends('layouts.app')
@section('title', 'Fertilizer Schedule Calibration')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fas fa-sliders-h me-2 text-success"></i>Fertilizer Schedule Calibration</h4>
    <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Admin Dashboard
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="alert alert-info small mb-4">
    <i class="fas fa-info-circle me-1"></i>
    This calibrates the <strong>live fertilizer recommendation</strong> shown on every soil
    sample report — the 3-stage application timing and the fraction of each crop's N/P/K
    target applied at each stage. A crop with no calibration here falls back to a generic
    default and won't appear as a selectable option on the sample's Fertilizer Schedule modal.
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead class="table-success">
                    <tr>
                        <th>Crop</th>
                        <th>Calibration</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($crops as $crop)
                    <tr>
                        <td>{{ $crop->name }}</td>
                        <td>
                            @if($crop->fertilizer_schedule_stages_count > 0)
                                <span class="badge bg-success">Calibrated ({{ $crop->fertilizer_schedule_stages_count }}/3 stages)</span>
                            @else
                                <span class="badge bg-secondary">Not calibrated — using default</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.fertilizer-calibration.edit', $crop) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-edit me-1"></i>{{ $crop->fertilizer_schedule_stages_count > 0 ? 'Edit' : 'Calibrate' }}
                            </a>
                            @if($crop->fertilizer_schedule_stages_count > 0)
                            <form action="{{ route('admin.fertilizer-calibration.destroy', $crop) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove calibration for {{ $crop->name }}? It will fall back to the generic default.');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="text-center text-muted py-4">No active crops found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
