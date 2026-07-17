@extends('layouts.app')
@section('title', $farm->farm_name)
@section('content')

<div class="row mb-3">
    <div class="col-md-8">
        <h2><i class="fas fa-map-marked-alt me-2"></i>{{ $farm->farm_name }}</h2>
        <p class="text-muted mb-0">{{ $farm->farmer->full_name }} &middot; {{ $farm->locationBarangay->name }}</p>
    </div>
    <div class="col-md-4 text-end">
        <a href="{{ route('public.map') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back to Map</a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card text-center"><div class="card-body">
            <div class="fs-4 fw-bold text-success">{{ $stats['total_analyses'] }}</div>
            <div class="text-muted small">Analyses</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card text-center"><div class="card-body">
            <div class="fs-4 fw-bold text-success">{{ $stats['avg_fertility'] ? round($stats['avg_fertility']) . '%' : '—' }}</div>
            <div class="text-muted small">Avg. Fertility</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card text-center"><div class="card-body">
            <div class="fs-6 fw-bold">{{ $stats['first_analysis_date']?->format('M Y') ?? '—' }}</div>
            <div class="text-muted small">First Analysis</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card text-center"><div class="card-body">
            <div class="fs-6 fw-bold">{{ $stats['latest_analysis_date']?->format('M Y') ?? '—' }}</div>
            <div class="text-muted small">Latest Analysis</div>
        </div></div>
    </div>
</div>

@if($latest)
<div class="card mb-3">
    <div class="card-header">Latest Soil Analysis — {{ $latest->sample_date->format('M j, Y') }}</div>
    <div class="card-body">
        <div class="row text-center">
            <div class="col-md-3">
                <div class="text-muted small">Fertility Score</div>
                <div class="fs-4"><span class="badge bg-{{ $latest->fertilityColorClass() }}">{{ $latest->fertility_score }}%</span></div>
            </div>
            <div class="col-md-3"><div class="text-muted small">pH</div><div class="fs-5">{{ $latest->ph_level ?? '—' }}</div></div>
            <div class="col-md-2"><div class="text-muted small">Nitrogen</div><div class="fs-5">{{ $latest->nitrogen_level ?? '—' }}</div></div>
            <div class="col-md-2"><div class="text-muted small">Phosphorus</div><div class="fs-5">{{ $latest->phosphorus_level ?? '—' }}</div></div>
            <div class="col-md-2"><div class="text-muted small">Potassium</div><div class="fs-5">{{ $latest->potassium_level ?? '—' }}</div></div>
        </div>
        @if($latest->recommended_crop)
        <p class="text-center mt-3 mb-0">Recommended crop: <strong>{{ $latest->recommended_crop }}</strong></p>
        @endif
    </div>
</div>
@endif

@if($trendAnalysis)
<div class="card mb-3">
    <div class="card-header">Trend Over Time</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm">
                <thead><tr><th>Parameter</th><th>First</th><th>Latest</th><th>Change</th></tr></thead>
                <tbody>
                    @foreach(['fertility' => 'Fertility Score', 'ph' => 'pH', 'nitrogen' => 'Nitrogen', 'phosphorus' => 'Phosphorus', 'potassium' => 'Potassium'] as $key => $label)
                        @if($trendAnalysis[$key])
                        <tr>
                            <td>{{ $label }}</td>
                            <td>{{ $trendAnalysis[$key]['first'] }}</td>
                            <td>{{ $trendAnalysis[$key]['latest'] }}</td>
                            <td class="{{ $trendAnalysis[$key]['change'] > 0 ? 'text-success' : ($trendAnalysis[$key]['change'] < 0 ? 'text-danger' : '') }}">
                                {{ $trendAnalysis[$key]['change'] > 0 ? '+' : '' }}{{ round($trendAnalysis[$key]['change'], 2) }}
                            </td>
                        </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

<div class="card">
    <div class="card-header">Analysis History</div>
    <div class="card-body">
        @forelse($soilSamples as $sample)
            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <span>{{ $sample->sample_date->format('M j, Y') }} <span class="badge bg-secondary ms-1">{{ ucfirst($sample->analysis_type ?? 'colorimetric') }}</span></span>
                @if(!is_null($sample->fertility_score))
                    <span class="badge bg-{{ $sample->fertilityColorClass() }}">{{ $sample->fertility_score }}%</span>
                @endif
            </div>
        @empty
            <p class="text-muted mb-0">No soil analyses recorded yet.</p>
        @endforelse
    </div>
</div>
@endsection
