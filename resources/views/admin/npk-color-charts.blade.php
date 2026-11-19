@extends('layouts.app')
@section('title', 'NPK Color Chart Manager')

@section('styles')
    .color-swatch {
        display: inline-block;
        width: 36px;
        height: 24px;
        border-radius: 4px;
        border: 1px solid rgba(0,0,0,.25);
        vertical-align: middle;
        box-shadow: inset 0 1px 2px rgba(0,0,0,.15);
    }
    .entry-row.inactive td { opacity: .45; }
    .badge-low    { background-color: #c0642a; }
    .badge-medium { background-color: #2e7d32; }
    .badge-high   { background-color: #1565c0; }
    .nutrient-header-N { border-top: 3px solid #c0642a; }
    .nutrient-header-P { border-top: 3px solid #7b1fa2; }
    .nutrient-header-K { border-top: 3px solid #0277bd; }
@endsection

@section('content')
<div class="row mb-3">
    <div class="col">
        <h2><i class="fa fa-seedling me-2 text-success"></i>NPK Color Chart Manager</h2>
        <p class="text-muted mb-0">
            Manage the reference hex colors used by CIEDE2000 delta-E matching for Nitrogen, Phosphorus, and Potassium.
            Each entry maps a captured color to a ppm value and LOW / MEDIUM / HIGH category.
        </p>
    </div>
</div>

{{-- Alerts --}}
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">
    <i class="fa fa-check-circle me-1"></i> {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">
    <i class="fa fa-exclamation-circle me-1"></i> {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show">
    <i class="fa fa-exclamation-circle me-1"></i> {{ $errors->first() }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row g-4">

    {{-- ── Add entries: device calibration ─────────────────────── --}}
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header bg-success text-white">
                <i class="fa fa-eye-dropper me-1"></i> Add Colors
            </div>
            <div class="card-body">
                <p class="small text-muted">
                    New reference colors are captured with the colorimeter box: put a reference solution in the box,
                    capture it, and label it with its LOW / MEDIUM / HIGH status.
                </p>
                <a href="{{ route('admin.color-calibration') }}" class="btn btn-success w-100">
                    <i class="fa fa-video me-1"></i> Open Color Calibration
                </a>
                <p class="small text-muted mt-3 mb-0">
                    <span class="badge bg-secondary">BSWM card</span> entries are the defaults read from the printed kit card;
                    <span class="badge bg-success">Device</span> entries were captured with the box camera.
                    The charts here are the only color source — a chart with no active entries stops that test.
                </p>
            </div>
        </div>
    </div>

    {{-- ── Chart Tables ─────────────────────────────────────────── --}}
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs" id="nutrientTabs">
                    @foreach(['N' => 'Nitrogen', 'P' => 'Phosphorus', 'K' => 'Potassium'] as $nut => $name)
                    <li class="nav-item">
                        <button class="nav-link {{ $loop->first ? 'active' : '' }}"
                                data-bs-toggle="tab" data-bs-target="#tab-{{ $nut }}">
                            {{ $nut }} — {{ $name }}
                            <span class="badge bg-secondary ms-1">{{ ($charts[$nut] ?? collect())->count() }}</span>
                        </button>
                    </li>
                    @endforeach
                </ul>
            </div>
            <div class="card-body p-0">
                <div class="tab-content">
                    @foreach(['N' => 'Nitrogen', 'P' => 'Phosphorus', 'K' => 'Potassium'] as $nut => $name)
                    <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="tab-{{ $nut }}">
                        @php $entries = $charts[$nut] ?? collect(); @endphp
                        @if($entries->isEmpty())
                        <div class="p-4 text-muted text-center">
                            <i class="fa fa-inbox fa-2x mb-2 d-block"></i>No entries for {{ $name }} yet.
                        </div>
                        @else
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0 align-middle">
                                <thead class="table-light nutrient-header-{{ $nut }}">
                                    <tr>
                                        <th>Swatch</th>
                                        <th>Hex</th>
                                        <th>ppm</th>
                                        <th>Category</th>
                                        <th>Source</th>
                                        <th class="text-center">Active</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($entries as $entry)
                                    <tr class="entry-row {{ $entry->active ? '' : 'inactive' }}">
                                        <td>
                                            <span class="color-swatch" style="background:{{ $entry->hex_value }}"></span>
                                        </td>
                                        <td class="font-monospace">{{ $entry->hex_value }}</td>
                                        <td><strong>{{ number_format($entry->ppm_value, 1) }}</strong></td>
                                        <td>
                                            <span class="badge badge-{{ $entry->category }} text-white text-uppercase">
                                                {{ $entry->category }}
                                            </span>
                                        </td>
                                        <td>@if($entry->source === 'device')<span class="badge bg-success">Device</span>@if($entry->snapshot) <img src="{{ asset($entry->snapshot) }}" width="40" height="30" class="ms-1 align-middle" style="border-radius:3px;border:1px solid #ccc;object-fit:cover;" data-preview="{{ asset($entry->snapshot) }}" data-caption="{{ $entry->nutrient }} {{ strtoupper($entry->category) }} {{ number_format($entry->ppm_value, 1) }} ppm · {{ $entry->hex_value }}" title="Click to view the whole image">@endif @else<span class="badge bg-secondary">BSWM card</span>@endif</td>
                                        <td class="text-center">
                                            @if($entry->active)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-secondary">Inactive</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <form method="POST" action="{{ route('admin.npk-color-charts.toggle', $entry) }}">
                                                    @csrf @method('PATCH')
                                                    <button type="submit"
                                                            class="btn {{ $entry->active ? 'btn-outline-secondary' : 'btn-outline-success' }}"
                                                            title="{{ $entry->active ? 'Deactivate' : 'Activate' }}">
                                                        <i class="fa {{ $entry->active ? 'fa-eye-slash' : 'fa-eye' }}"></i>
                                                    </button>
                                                </form>
                                                <button type="button"
                                                        class="btn btn-outline-danger"
                                                        title="Delete permanently"
                                                        onclick="openDeleteModal(
                                                            {{ $entry->id }},
                                                            '{{ $entry->hex_value }}',
                                                            '{{ $nut }}',
                                                            {{ $entry->ppm_value }},
                                                            '{{ $entry->category }}'
                                                        )">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif

                        {{-- Category legend for this nutrient --}}
                        <div class="p-3 border-top d-flex gap-3 flex-wrap small text-muted">
                            <span><span class="badge badge-low text-white">LOW</span>
                                {{ ['N' => '< 45 ppm', 'P' => '< 15 ppm', 'K' => '< 20 ppm'][$nut] }}
                            </span>
                            <span><span class="badge badge-medium text-white">MEDIUM</span>
                                {{ ['N' => '45–160 ppm', 'P' => '15–30 ppm', 'K' => '20–40 ppm'][$nut] }}
                            </span>
                            <span><span class="badge badge-high text-white">HIGH</span>
                                {{ ['N' => '≥ 160 ppm', 'P' => '≥ 30 ppm', 'K' => '≥ 40 ppm'][$nut] }}
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="mt-3 p-3 bg-light rounded border small text-muted">
            <i class="fa fa-info-circle me-1 text-primary"></i>
            <strong>Tip:</strong> Deactivating an entry removes it from delta-E matching without deleting it.
            Add multiple hex values per ppm point to improve CIEDE2000 accuracy for colors that vary across the card surface.
        </div>
    </div>

</div>

{{-- ── Delete Danger Modal ──────────────────────────────────────── --}}
<div class="modal fade" id="deleteModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-danger">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fa fa-exclamation-triangle me-2"></i>Permanent Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger mb-3">
                    <strong>Warning:</strong> You are about to permanently delete this color reference entry.
                    This action <strong>cannot be undone</strong> and will immediately affect color matching
                    for all future N/P/K test captures.
                </div>
                <p class="mb-1">Entry to delete:</p>
                <div class="d-flex align-items-center gap-3 p-2 rounded bg-light border mb-3">
                    <span id="modal-swatch" class="color-swatch" style="width:40px;height:28px;"></span>
                    <span id="modal-label" class="font-monospace fw-bold"></span>
                </div>
                <form id="deleteForm" method="POST">
                    @csrf @method('DELETE')
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fa fa-times me-1"></i> Cancel
                </button>
                <button type="button" class="btn btn-danger" onclick="document.getElementById('deleteForm').submit()">
                    <i class="fa fa-trash me-1"></i> Delete Permanently
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function openDeleteModal(id, hex, nutrient, ppm, category) {
    document.getElementById('deleteForm').action = `/admin/npk-color-charts/${id}`;
    document.getElementById('modal-swatch').style.background = hex;
    document.getElementById('modal-label').textContent =
        `${hex}  —  ${nutrient}  ${ppm.toFixed(1)} ppm  [${category.toUpperCase()}]`;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

</script>
@endsection
