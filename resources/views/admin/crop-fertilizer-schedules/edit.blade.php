@extends('layouts.app')
@section('title', 'Edit Schedule — ' . $crop->name)
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">
        <i class="fas fa-edit me-2 text-success"></i>Edit Fertilizer Schedule —
        <span class="text-success">{{ $crop->name }}</span>
    </h4>
    <a href="{{ route('admin.crop-fertilizer-schedules.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Back
    </a>
</div>

<div class="alert alert-info small mb-4">
    <i class="fas fa-info-circle me-1"></i>
    Enter the number of <strong>bags per hectare</strong> for each fertilizer type at each application timing.
    Leave a cell <strong>blank or 0</strong> to omit that fertilizer from that application.
    Fertilizer types: <em>14-14-14</em> (complete), <em>46-0-0</em> (Urea), <em>21-0-0</em> (Ammonium Sulfate),
    <em>25-0-0</em> (Ammonium Nitrate), <em>16-20-0</em> (Ammonium Phosphate), <em>0-0-60</em> (MOP / KCl).
</div>

@if($errors->any())
<div class="alert alert-danger">
    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<form method="POST" action="{{ route('admin.crop-fertilizer-schedules.update', $crop) }}">
    @csrf
    @method('PUT')

    @php
        $labels = [
            1 => 'First Application (0-14 DAT*)',
            2 => 'Second Application (22-26 DAT)',
            3 => 'Third Application (32-36 DAT)',
        ];
    @endphp

    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <i class="fas fa-table me-1"></i> Application Schedule Matrix (bags/ha)
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0" style="font-size:.9rem;">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width:220px;">Application Timing</th>
                            @foreach($types as $t)
                            <th class="text-center" style="min-width:90px;">{{ $t }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach([1,2,3] as $order)
                        <tr>
                            <td>
                                <input type="text" name="label[{{ $order }}]"
                                       class="form-control form-control-sm"
                                       value="{{ old("label.{$order}", $matrix[$order]['label'] ?? $labels[$order]) }}">
                            </td>
                            @foreach($types as $t)
                            @php
                                $val = $matrix[$order]['fertilizers'][$t] ?? null;
                                $old = old("schedule.{$order}.{$t}");
                            @endphp
                            <td class="text-center">
                                <input type="number"
                                       name="schedule[{{ $order }}][{{ $t }}]"
                                       class="form-control form-control-sm text-center"
                                       min="0" max="999" step="0.5"
                                       placeholder="0"
                                       value="{{ $old !== null ? $old : ($val > 0 ? $val : '') }}">
                            </td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="alert alert-warning small">
        <i class="fas fa-triangle-exclamation me-1"></i>
        <strong>PhilRice Reference (111–120 day variety, 7–8 t/ha):</strong>
        1st App → 2 bags 14-14-14 &nbsp;|&nbsp;
        2nd App → 2 bags 46-0-0 &nbsp;|&nbsp;
        3rd App → 2 bags 46-0-0 + 0.5 bag 0-0-60
        <br>Update to BSWM site-specific rates when available.
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-success">
            <i class="fas fa-save me-1"></i> Save Schedule
        </button>
        <a href="{{ route('admin.crop-fertilizer-schedules.index') }}" class="btn btn-outline-secondary">
            Cancel
        </a>
    </div>
</form>

@endsection
