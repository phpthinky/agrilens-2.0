@extends('layouts.app')
@section('title', 'Fertilizer Application Schedules')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fas fa-calendar-alt me-2 text-success"></i>Fertilizer Application Schedules</h4>
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

<div class="alert alert-info small mb-4">
    <i class="fas fa-info-circle me-1"></i>
    Schedules are based on <strong>DA-PhilRice "Abonong Swak"</strong> balanced fertilization reference
    (111–120 day variety, transplanted). Values shown are <em>bags/ha</em>.
    Update values here when site-specific BSWM rates are available.
</div>

@forelse($crops as $crop)
@php
    $matrix = [];
    foreach ($crop->fertilizerSchedules as $row) {
        $o = $row->application_order;
        $matrix[$o]['label'] = $row->application_label;
        $matrix[$o]['fertilizers'][$row->fertilizer_type] = $row->bags;
    }
    ksort($matrix);
    $types = \App\Models\CropFertilizerSchedule::fertilizerTypes();
@endphp
<div class="card mb-4">
    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
        <span><i class="fas fa-seedling me-1"></i> {{ $crop->name }}</span>
        <a href="{{ route('admin.crop-fertilizer-schedules.edit', $crop) }}"
           class="btn btn-sm btn-light text-success">
            <i class="fas fa-edit me-1"></i> Edit Schedule
        </a>
    </div>
    <div class="card-body p-0">
        @if(empty($matrix))
            <p class="text-muted p-3 mb-0"><em>No schedule defined yet.</em>
                <a href="{{ route('admin.crop-fertilizer-schedules.edit', $crop) }}">Add one</a>
            </p>
        @else
        <div class="table-responsive">
            <table class="table table-bordered table-sm align-middle mb-0" style="font-size:.85rem;">
                <thead class="table-light">
                    <tr>
                        <th>Application</th>
                        @foreach($types as $t)<th class="text-center">{{ $t }}</th>@endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($matrix as $order => $app)
                    <tr>
                        <td class="fw-semibold">{{ $app['label'] }}</td>
                        @foreach($types as $t)
                        <td class="text-center">
                            @if(isset($app['fertilizers'][$t]) && $app['fertilizers'][$t] > 0)
                                <span class="badge bg-success">{{ $app['fertilizers'][$t] }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@empty
<div class="alert alert-warning">No active crops found. <a href="{{ route('crops.create') }}">Add a crop</a> first.</div>
@endforelse

@endsection
