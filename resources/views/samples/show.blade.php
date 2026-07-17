@extends('layouts.app')
@section('title', $sample->sample_name)
@section('content')

<div class="row mb-3">
    <div class="col">
        <a href="{{ route('samples.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to All Samples
        </a>
        @if($sample->isAnalyzed())
        <a href="{{ route('samples.report', $sample) }}" class="btn btn-sm btn-outline-info ms-2">
            <i class="fas fa-microscope"></i> View Test Report
        </a>
        <a href="#openschedulemodal" onclick="openFertModal()" class="btn btn-sm btn-outline-success ms-2">
            <i class="fas fa-calendar-alt"></i> Fertilizer Schedule
        </a>
        <a href="{{ route('samples.export-excel', $sample) }}" class="btn btn-sm btn-success ms-2 d-none">
            <i class="fas fa-file-excel"></i> Export to Excel
        </a>
        @endif
    </div>
</div>

{{-- Sample info card --}}
<div class="card mb-4">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0"><i class="fas fa-vial me-2"></i>{{ $sample->sample_name }}</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <p><strong>Farmer:</strong> {{ $sample->farmer_name }}</p>
                <p><strong>Address:</strong> {{ $sample->address }}</p>
                <p><strong>Farm Location:</strong> {{ $sample->location ?? '—' }}</p>
            </div>
            <div class="col-md-4">
                <p><strong>Date Received:</strong> {{ $sample->sample_date->format('F j, Y') }}</p>
                <p><strong>Date Tested:</strong> {{ $sample->date_tested->format('F j, Y') }}</p>
                @if($sample->analyzed_at)
                <p><strong>Analyzed:</strong> {{ $sample->analyzed_at->format('F j, Y g:i A') }}</p>
                @endif
            </div>
            <div class="col-md-4 text-center">
                @if(!is_null($sample->fertility_score))
                    <div class="display-4 fw-bold text-{{ $sample->fertilityColorClass() }}">{{ $sample->fertility_score }}%</div>
                    <p class="text-muted mb-0">Fertility Score</p>
                    @if($sample->recommended_crop)
                    <span class="badge bg-success mt-1">
                        <i class="fas fa-seedling me-1"></i>Top: {{ $sample->recommended_crop }}
                    </span>
                    @endif
                @else
                    <p class="text-muted"><em>Not yet analyzed</em></p>
                    <small class="text-muted">{{ $sample->tests_completed }}/12 tests captured</small>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ── TESTING PROGRESS SECTION ────────────────────────────────────────────── --}}
@if(!$sample->isAnalyzed())
@php
$ph_count = count($readings['ph'] ?? []);
$n_count  = count($readings['nitrogen'] ?? []);
$p_count  = count($readings['phosphorus'] ?? []);
$k_count  = count($readings['potassium'] ?? []);
$totalDone = (int)($sample->tests_completed ?? 0);

$paramCards = [
    'ph' => [
        'label'   => 'Soil pH',
        'icon'    => 'fa-flask',
        'color'   => 'primary',
        'count'   => $ph_count,
        'avgHex'  => $sample->ph_color_hex,
        'route'   => route('ph-test.show', $sample),
        'badge'   => '2-Step',
    ],
    'nitrogen' => [
        'label'  => 'Nitrogen (N)',
        'icon'   => 'fa-leaf',
        'color'  => 'success',
        'count'  => $n_count,
        'avgHex' => $sample->nitrogen_color_hex,
        'route'  => route('parameter-test.show', [$sample, 'nitrogen']),
        'badge'  => null,
    ],
    'phosphorus' => [
        'label'  => 'Phosphorus (P)',
        'icon'   => 'fa-atom',
        'color'  => 'primary',
        'count'  => $p_count,
        'avgHex' => $sample->phosphorus_color_hex,
        'route'  => route('parameter-test.show', [$sample, 'phosphorus']),
        'badge'  => null,
    ],
    'potassium' => [
        'label'  => 'Potassium (K)',
        'icon'   => 'fa-seedling',
        'color'  => 'info',
        'count'  => $k_count,
        'avgHex' => $sample->potassium_color_hex,
        'route'  => route('parameter-test.show', [$sample, 'potassium']),
        'badge'  => null,
    ],
];
@endphp

<div class="card border-warning mb-4">
    <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-microscope me-2"></i>Soil Parameter Testing</h5>
        <span class="badge bg-dark">{{ $totalDone }}/12 captures</span>
    </div>
    <div class="card-body">

        <div class="alert alert-info py-2 mb-4">
            <i class="fas fa-info-circle me-1"></i>
            Each parameter has its own dedicated test page with <strong>3 captures</strong> for accuracy.
            Complete all 4 parameters to compute the soil analysis.
        </div>

        {{-- Parameter cards --}}
        <div class="row g-3 mb-4">
            @foreach($paramCards as $key => $pc)
            @php
                $done   = ($pc['count'] >= 3);
                $active = !$done;
            @endphp
            <div class="col-md-3 col-sm-6">
                <div class="card h-100 {{ $done ? 'border-success' : 'border-' . $pc['color'] }}">
                    <div class="card-header text-center py-2 bg-{{ $done ? 'success' : $pc['color'] }} text-white">
                        <i class="fas {{ $pc['icon'] }} me-1"></i>
                        <strong>{{ $pc['label'] }}</strong>
                        @if($pc['badge'])
                        <span class="badge bg-white text-dark ms-1" style="font-size:.6rem;">{{ $pc['badge'] }}</span>
                        @endif
                    </div>
                    <div class="card-body text-center py-3">
                        <div class="d-flex justify-content-center gap-2 mb-2">
                            @for($i = 1; $i <= 3; $i++)
                            <div style="width:18px;height:18px;border-radius:50%;
                                        background:{{ $pc['count'] >= $i ? ($done ? '#198754' : 'var(--bs-' . $pc['color'] . ')') : '#dee2e6' }};
                                        border:2px solid {{ $pc['count'] >= $i ? ($done ? '#157347' : 'currentColor') : '#adb5bd' }};">
                            </div>
                            @endfor
                        </div>
                        <div class="small mb-2 {{ $done ? 'text-success fw-bold' : 'text-muted' }}">
                            @if($done)
                                <i class="fas fa-check-circle me-1"></i>3/3 Complete
                            @else
                                {{ $pc['count'] }}/3 captures
                            @endif
                        </div>
                        @if($pc['avgHex'])
                        <div style="width:44px;height:24px;background:{{ $pc['avgHex'] }};border:1px solid #ccc;
                                    border-radius:4px;margin:0 auto 4px;"></div>
                        <div class="text-muted" style="font-size:10px;">{{ $pc['avgHex'] }}</div>
                        @endif
                    </div>
                    <div class="card-footer text-center py-2">
                        <a href="{{ $pc['route'] }}"
                           class="btn btn-sm {{ $done ? 'btn-outline-success' : 'btn-' . $pc['color'] }} w-100">
                            @if($done)
                                <i class="fas fa-redo me-1"></i>Re-test
                            @else
                                <i class="fas fa-camera me-1"></i>
                                {{ $pc['count'] > 0 ? 'Continue' : 'Start' }} Test
                            @endif
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="mb-3">
            <div class="d-flex justify-content-between small text-muted mb-1">
                <span>Overall Progress</span>
                <span>{{ $totalDone }}/12 captures</span>
            </div>
            <div class="progress" style="height:10px;">
                <div class="progress-bar bg-success"
                     style="width:{{ round($totalDone/12*100) }}%;transition:width .4s;"></div>
            </div>
        </div>

        @if($sample->allAveraged())
        <div class="alert alert-success py-2 mb-2">
            <i class="fas fa-check-circle me-1"></i>
            All 12 captures complete. Ready to compute the full soil analysis.
        </div>
        <a href="{{ route('samples.show', $sample) }}" class="btn btn-success w-100">
            <i class="fas fa-calculator me-1"></i>Compute &amp; View Results
        </a>
        @endif

    </div>
</div>
@endif

{{-- ── ANALYSIS RESULTS ────────────────────────────────────────────────────── --}}
@if($sample->isAnalyzed())
@php
$resultParams = [
    'ph'         => ['label'=>'Soil pH',       'value'=>$sample->ph_level,         'unit'=>'',    'hex'=>$sample->ph_color_hex],
    'nitrogen'   => ['label'=>'Nitrogen (N)',   'value'=>$sample->nitrogen_level,   'unit'=>'kg/ha', 'hex'=>$sample->nitrogen_color_hex],
    'phosphorus' => ['label'=>'Phosphorus (P)', 'value'=>$sample->phosphorus_level, 'unit'=>'kg/ha', 'hex'=>$sample->phosphorus_color_hex],
    'potassium'  => ['label'=>'Potassium (K)',  'value'=>$sample->potassium_level,  'unit'=>'kg/ha', 'hex'=>$sample->potassium_color_hex],
];
$fertilizerSvc = app(\App\Services\FertilizerService::class);
@endphp
<div class="row mb-4">
    <div class="col-12"><h4><i class="fas fa-chart-bar me-2"></i>Soil Analysis Results</h4></div>
    @foreach($resultParams as $key => $rp)
    @php
        $status = $fertilizerSvc->getNutrientStatus($key, (float)$rp['value']);
        $bsColor = match($status) {
            'Acidic', 'Low' => 'danger',
            'Medium'        => 'warning',
            'Optimal'       => 'success',
            'Alkaline'      => 'info',
            'High'          => 'primary',
            default         => 'secondary'
        };
    @endphp
    <div class="col-md-3 mb-3">
        <div class="card h-100 border-{{ $bsColor }}">
            <div class="card-header bg-{{ $bsColor }} text-white text-center py-2">
                <strong>{{ $rp['label'] }}</strong>
            </div>
            <div class="card-body text-center">
                @if($key === 'ph')
                <div class="display-6 fw-bold text-{{ $bsColor }}">
                    {{ number_format($rp['value'], 1) }}
                </div>
                <span class="badge bg-{{ $bsColor }} mt-1">{{ $status }}</span>
                @else
                <div class="display-5 fw-bold text-{{ $bsColor }} my-2">
                    {{ $status }}
                </div>
                @endif
                <div class="my-2">
                    <div style="width:50px;height:25px;background:{{ $rp['hex'] }};border:1px solid #ccc;border-radius:4px;margin:0 auto;"></div>
                    <small class="text-muted">{{ $rp['hex'] }}</small>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- FERTILIZER RECOMMENDATION --}}
@if(!empty($fertRec))
<div class="card mb-4">
    <div class="card-header bg-warning text-dark">
        <h5 class="mb-0">
            <i class="fas fa-spray-can me-2"></i>Fertilizer Recommendation
            <small class="fw-normal ms-2 text-muted" style="font-size:.75rem;">Based on BSWM/PhilRice guidelines (per hectare)</small>
        </h5>
    </div>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="card text-center h-100 border-success">
                    <div class="card-body py-3">
                        <i class="fas fa-seedling fa-2x text-success mb-2"></i>
                        <div class="fw-bold fs-4 text-success">{{ number_format($fertRec['urea_kgha'],1) }} kg/ha</div>
                        <small class="text-muted">Urea (46-0-0)</small>
                        <div style="font-size:10px;" class="text-muted mt-1">Nitrogen source</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center h-100 border-primary">
                    <div class="card-body py-3">
                        <i class="fas fa-atom fa-2x text-primary mb-2"></i>
                        <div class="fw-bold fs-4 text-primary">{{ number_format($fertRec['tsp_kgha'],1) }} kg/ha</div>
                        <small class="text-muted">DAP (18-46-0)</small>
                        <div style="font-size:10px;" class="text-muted mt-1">Phosphorus source</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center h-100 border-info">
                    <div class="card-body py-3">
                        <i class="fas fa-flask fa-2x text-info mb-2"></i>
                        <div class="fw-bold fs-4 text-info">{{ number_format($fertRec['mop_kgha'],1) }} kg/ha</div>
                        <small class="text-muted">MOP (0-0-60)</small>
                        <div style="font-size:10px;" class="text-muted mt-1">Potassium source</div>
                    </div>
                </div>
            </div>
        </div>
        <ul class="list-group list-group-flush mb-4">
            @foreach($fertRec['notes'] as $note)
            <li class="list-group-item py-1">
                <i class="fas fa-circle-info text-warning me-2"></i>
                <small>{{ $note }}</small>
            </li>
            @endforeach
        </ul>

        {{-- ── Crop-Specific Fertilizer Calculator ─────────────────────── --}}
        <hr class="my-3">
        <h6 class="fw-bold mb-3">
            <i class="fas fa-calculator me-2 text-success"></i>
            Crop-Specific Fertilizer Calculator
            <small class="fw-normal text-muted ms-2" style="font-size:.75rem;">
                Adjusts requirements by crop and farm area
            </small>
        </h6>


        <form id="fertilizerForm" onsubmit="return false;">
            <div class="row g-3">

                {{-- Left: inputs --}}
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Crop</label>
                        <select class="form-select" id="cropSelect">
                            <option value="">— Select a crop —</option>
                            @foreach($allCrops as $crop)
                            <option value="{{ $crop->id }}">{{ $crop->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Farm Area (hectares)</label>
                        <input type="number" class="form-control" id="areaSize"
                               step="0.01" min="0.01" value="1.00">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Primary Fertilizer Type</label>
                        <select class="form-select" id="fertilizerType">
                            <option value="urea">Urea (46-0-0)</option>
                            <option value="complete">Complete (14-14-14)</option>
                            <option value="ammonium_sulfate">Ammonium Sulfate (21-0-0)</option>
                            <option value="dap">DAP (18-46-0)</option>
                            <option value="mop">Muriate of Potash (0-0-60)</option>
                            <option value="organic">Organic Fertilizer (~2-1.5-1)</option>
                        </select>
                    </div>
                    <div class="row">
                        
                    <div class="col-md-6"><button type="button" class="btn btn-success w-100" onclick="calculateFertilizer()">
                        <i class="fas fa-calculator me-1"></i> Fertilizer Requirement
                    </button></div>
                    <div class="col-md-6"> <button type="button" class="btn btn-success w-100" onclick="openFertModal()">
                        <i class="fas fa-leaf me-1"></i> Fertilizer Schedule
                    </button> </div>

                    </div>
                    

                              
                          
                </div>

                {{-- Right: current soil status --}}
                <div class="col-md-6">
                    <p class="fw-semibold mb-2">Current Soil Status</p>
                    <table class="table table-bordered table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Parameter</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                            $calcParams = [
                                ['key'=>'ph',         'label'=>'Soil pH',    'value'=>(float)$sample->ph_level],
                                ['key'=>'nitrogen',   'label'=>'Nitrogen',   'value'=>(float)$sample->nitrogen_level],
                                ['key'=>'phosphorus', 'label'=>'Phosphorus', 'value'=>(float)$sample->phosphorus_level],
                                ['key'=>'potassium',  'label'=>'Potassium',  'value'=>(float)$sample->potassium_level],
                            ];
                            @endphp
                            @foreach($calcParams as $cp)
                            @php
                            $st   = $fertilizerSvc->getNutrientStatus($cp['key'], $cp['value']);
                            $stBg = match($st) {
                                'Acidic','Low' => 'danger',
                                'Medium'       => 'warning',
                                'Optimal'      => 'success',
                                'Alkaline'     => 'info',
                                'High'         => 'primary',
                                default        => 'secondary',
                            };
                            @endphp
                            <tr>
                                <td>{{ $cp['label'] }}</td>
                                <td class="text-center">
                                    @if($cp['key'] === 'ph')
                                        {{ number_format($cp['value'], 1) }}
                                    @else
                                        <span class="badge bg-{{ $stBg }}">{{ $st }}</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Results panel --}}
            <div id="calcResults" class="d-none mt-4">
                <hr class="mb-3">
                <h6 class="fw-bold mb-3" id="calcResultsTitle"></h6>
                <div class="row g-3" id="calcResultsCards"></div>
                <div class="alert mt-3 mb-0" id="calcResultsAlert"></div>
            </div>
        </form>

    </div>
</div>
@endif

{{-- ── CROP RECOMMENDATIONS ────────────────────────────────────────────────── --}}
<div class="card mb-4">
    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-seedling me-2"></i>Crop Recommendations</h5>
        @if($sample->isAnalyzed())
        <a href="{{ route('samples.pdf', $sample) }}" target="_blank" class="btn btn-sm btn-light">
            <i class="fas fa-print me-1"></i>Print / Save as PDF
        </a>
        @endif
    </div>
    <div class="card-body">
        @if(!$sample->isAnalyzed())
            <p class="text-muted mb-0">Complete all 4 soil tests to see crop recommendations.</p>
        @else


        <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle mb-0" id="cropRecoTable">
                <thead class="table-success">
                    <tr>
                        <th style="cursor:pointer;" onclick="sortCropTable(0)"># <i class="fas fa-sort ms-1 text-muted" style="font-size:.7rem;"></i></th>
                        <th style="cursor:pointer;" onclick="sortCropTable(1)">Crop <i class="fas fa-sort ms-1 text-muted" style="font-size:.7rem;"></i></th>
                        <th class="text-center text-nowrap" style="cursor:pointer;" onclick="sortCropTable(2)">UREA (kg/ha) <i class="fas fa-sort ms-1 text-muted" style="font-size:.7rem;"></i></th>
                        <th class="text-center text-nowrap" style="cursor:pointer;" onclick="sortCropTable(3)">DAP (kg/ha) <i class="fas fa-sort ms-1 text-muted" style="font-size:.7rem;"></i></th>
                        <th class="text-center text-nowrap" style="cursor:pointer;" onclick="sortCropTable(4)">MOP (kg/ha) <i class="fas fa-sort ms-1 text-muted" style="font-size:.7rem;"></i></th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $ph    = (float) $sample->ph_level;
                        $soilN = (float) $sample->nitrogen_level;
                        $soilP = (float) $sample->phosphorus_level;
                        $soilK = (float) $sample->potassium_level;

                        $statusColor = function(string $s): string {
                            return match($s) {
                                'Low'    => 'success',
                                'Medium' => 'warning',
                                'High'   => 'danger',
                                default  => 'secondary',
                            };
                        };

                        $scoredCrops = [];
                        foreach (Collect($allCrops)->take(10) as $crop) {
                            $fert = $cropFertData[$crop->id] ?? null;

                            $nSt = $fert['n']['status'] ?? 'N/A';
                            $pSt = $fert['p']['status'] ?? 'N/A';
                            $kSt = $fert['k']['status'] ?? 'N/A';

                            // Score based on N, P, K sufficiency only (max 3)
                            $score = count(array_filter([$nSt, $pSt, $kSt], fn($s) => $s === 'Low'));

                            $scoredCrops[] = [
                                'crop'  => $crop,
                                'nSt'   => $nSt,
                                'pSt'   => $pSt,
                                'kSt'   => $kSt,
                                'score' => $score,
                                'fert'  => $fert,
                            ];
                        }
                        usort($scoredCrops, fn($a, $b) => $b['score'] <=> $a['score']);
                    @endphp

                    @foreach($scoredCrops as $i => $row)
                    @php
                        $pct      = round($row['score'] / 3 * 100);
                        $barColor = $pct >= 75 ? 'success' : ($pct >= 50 ? 'warning' : ($pct >= 25 ? 'info' : 'danger'));

                        // pH acid-tolerance remark using crop ph_low and ph_high
                        $cropPhLow  = (float) ($row['crop']->ph_low  ?? 0);
                        $cropPhHigh = (float) ($row['crop']->ph_high ?? 14);
                        $isAcidTolerant = $cropPhLow <= 5.5;
                        $phInRange      = ($ph >= $cropPhLow && $ph <= $cropPhHigh);

                        if ($isAcidTolerant) {
                            $phRemark = $phInRange
                                ? "Acid-tolerant crop (pH {$cropPhLow}–{$cropPhHigh}); soil pH is suitable."
                                : "Acid-tolerant crop (pH {$cropPhLow}–{$cropPhHigh}); adjust soil pH to target range.";
                        } else {
                            if ($ph < $cropPhLow) {
                                $phRemark = "Not acid-tolerant (pH {$cropPhLow}–{$cropPhHigh}); soil pH is too acidic — soil pH must be addressed, as this crop may not thrive under acidic conditions.";
                            } elseif ($ph > $cropPhHigh) {
                                $phRemark = "Not acid-tolerant (pH {$cropPhLow}–{$cropPhHigh}); soil pH is too high.";
                            } else {
                                $phRemark = "Not acid-tolerant (pH {$cropPhLow}–{$cropPhHigh}); soil pH is within suitable range.";
                            }
                        }

                        // NPK amendment remarks
                        $highNeeds = [];
                        $medNeeds  = [];
                        if ($row['nSt'] === 'High')   $highNeeds[] = 'Nitrogen';
                        if ($row['pSt'] === 'High')   $highNeeds[] = 'Phosphorus';
                        if ($row['kSt'] === 'High')   $highNeeds[] = 'Potassium';
                        if ($row['nSt'] === 'Medium') $medNeeds[]  = 'Nitrogen';
                        if ($row['pSt'] === 'Medium') $medNeeds[]  = 'Phosphorus';
                        if ($row['kSt'] === 'Medium') $medNeeds[]  = 'Potassium';

                        if ($row['score'] === 3) {
                            $npkRemark = 'All nutrients sufficient — no NPK amendments needed.';
                        } elseif (!empty($highNeeds) && !empty($medNeeds)) {
                            $npkRemark = 'Significant ' . implode(', ', $highNeeds) . ' amendment needed; moderate ' . implode(', ', $medNeeds) . ' adjustment recommended.';
                        } elseif (!empty($highNeeds)) {
                            $npkRemark = 'Significant ' . implode(', ', $highNeeds) . ' amendment needed before planting.';
                        } elseif (!empty($medNeeds)) {
                            $npkRemark = 'Moderate ' . implode(', ', $medNeeds) . ' adjustment recommended.';
                        } else {
                            $npkRemark = 'Soil nutrients are well-suited.';
                        }

                        $remark = $phRemark . ' ' . $npkRemark;
                    @endphp
                    <tr class="{{ $i === 0 ? 'table-warning' : '' }}">
                        <td class="text-muted small">{{ $i + 1 }}</td>
                        <td>
                            <strong>{{ $row['crop']->name }}</strong>
                            @if($i === 0)
                                <span class="badge bg-warning text-dark ms-1">
                                    <i class="fas fa-star me-1" style="font-size:.65rem;"></i>Top Pick
                                </span>
                            @elseif($i < 3)
                                <span class="badge bg-success ms-1" style="font-size:.65rem;">Recommended</span>
                            @endif
                        </td>
                        @php
    $nTarget = $row['fert']['n']['target_ppm'] ?? null;
    $pTarget = $row['fert']['p']['target_ppm'] ?? null;
    $kTarget = $row['fert']['k']['target_ppm'] ?? null;

    // Fixed 3-fertilizer formula: MOP→K, DAP→P (+N credit), UREA→remaining N
    $mopKgHa  = $kTarget !== null ? round($kTarget / 0.60, 2) : null;
    $dapKgHa  = $pTarget !== null ? round($pTarget / 0.46, 2) : null;
    $nFromDap = $dapKgHa !== null ? round($dapKgHa * 0.18, 2) : 0;
    $nRemain  = $nTarget !== null ? max(0, $nTarget - $nFromDap) : null;
    $ureaKgHa = $nRemain !== null ? round($nRemain / 0.46, 2) : null;
@endphp

{{-- N Target → UREA kg/ha --}}
<td class="text-center">
    @if($ureaKgHa !== null)
        <span class="fw-semibold">{{ number_format($ureaKgHa, 2) }}</span>
        <div class="text-muted" style="font-size:10px;">Urea kg/ha</div>
    @else N/A @endif
</td>

{{-- P Target → DAP kg/ha --}}
<td class="text-center">
    @if($dapKgHa !== null)
        <span class="fw-semibold">{{ number_format($dapKgHa, 2) }}</span>
        <div class="text-muted" style="font-size:10px;">DAP kg/ha</div>
    @else N/A @endif
</td>

{{-- K Target → MOP kg/ha --}}
<td class="text-center">
    @if($mopKgHa !== null)
        <span class="fw-semibold">{{ number_format($mopKgHa, 2) }}</span>
        <div class="text-muted" style="font-size:10px;">MOP kg/ha</div>
    @else N/A @endif
</td>

                        <td class="small text-muted">{{ $remark }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-2 text-muted small">
            <i class="fas fa-circle-info me-1"></i>
            Sorted by highest overall score. UREA/DAP/MOP rates computed using fixed formula:
MOP = K ÷ 0.60 · DAP = P ÷ 0.46 · UREA = (N − DAP×0.18) ÷ 0.46
        </div>
        @endif
    </div>
</div>

{{-- ── GEMINI AI CROP RECOMMENDATIONS ─────────────────────────────────── --}}
<div class="card mb-4" id="geminiSection">
    <div class="card-header d-flex justify-content-between align-items-center"
         style="background:linear-gradient(135deg,#1a73e8 0%,#34a853 60%,#fbbc04 100%);color:#fff;">
        <h5 class="mb-0">
            <i class="fas fa-robot me-2"></i>
            Gemini AI — Philippine Crop Recommendations
        </h5>
        @if($geminiEnabled)
            <span class="badge bg-light text-dark">
                <i class="fas fa-circle text-success me-1" style="font-size:.6rem;"></i>Gemini Ready
            </span>
        @else
            <span class="badge bg-secondary">
                <i class="fas fa-circle me-1" style="font-size:.6rem;"></i>API Not Configured
            </span>
        @endif
    </div>
    <div class="card-body">
        @if(!$sample->isAnalyzed())
            <p class="text-muted mb-0">Complete all 4 soil tests first to enable Gemini crop recommendations.</p>
        @elseif(!$geminiEnabled)
            <div class="alert alert-warning mb-3">
                <h6 class="alert-heading mb-2"><i class="fas fa-key me-2"></i>Gemini API Key Required</h6>
                <p class="mb-2">This feature uses <strong>Google Gemini AI</strong> to analyze your soil profile and provide tailored Philippine crop recommendations with fertilizer guidance.</p>
                <ol class="mb-2 small">
                    <li>Create an account at <strong>aistudio.google.com</strong></li>
                    <li>Generate an API key from Google AI Studio</li>
                    <li>Add <code>GEMINI_API_KEY=your-key-here</code> to the server's <code>.env</code> file</li>
                    <li>Restart the application server</li>
                </ol>
                <hr class="my-2">
                <p class="mb-0 small text-muted">
                    <i class="fas fa-shield-alt me-1"></i>
                    The API key is stored server-side only and is <strong>never sent to the browser</strong>.
                </p>
            </div>
            <button class="btn btn-secondary" disabled>
                <i class="fas fa-seedling me-1"></i> Get Gemini Crop Recommendations
                <span class="ms-2 badge bg-light text-dark" style="font-size:.7rem;">Not Available</span>
            </button>
        @else
            <div class="alert alert-warning mb-3 py-2" style="font-size:.875rem;">
                <i class="fas fa-hand-point-right me-1"></i>
                <strong>On-demand only — not generated automatically.</strong>
                AI analysis is triggered manually to avoid unnecessary API charges per request. Click the button below when you are ready to generate a recommendation for this soil sample.
            </div>
            <div class="card border-0 bg-light p-3 mb-3">
                <label class="form-label fw-semibold mb-1">
                    <i class="fas fa-hand-pointer me-1 text-success"></i>
                    Farmer's Preferred Crop 
                </label>
                <div class="row g-2 align-items-center">
                    <div class="col-md-5">
                        <select class="form-select" id="geminiPreferredCrop" onchange="updateGeminiCropHighlight()">
                            <option value="">— No preference, recommend best crops —</option>
                            @foreach($allCrops as $crop)
                                <option value="{{ $crop->name }}">{{ $crop->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-7">
                        <div id="geminiCropHighlight" class="d-none">
                            <span class="badge bg-success fs-6 px-3 py-2">
                                <i class="fas fa-leaf me-1"></i>
                                <span id="geminiCropHighlightName"></span>
                            </span>
                            <div class="text-muted small mt-1">
                                <i class="fas fa-check-circle text-success me-1"></i>
                                Gemini will focus its analysis on this crop's soil compatibility.
                            </div>
                        </div>
                        <div id="geminiCropNone" class="text-muted small">
                            <i class="fas fa-info-circle me-1"></i>
                            Select a crop if the farmer has a specific one in mind.
                        </div>
                    </div>
                </div>
            </div>
            @if(!empty($sample->gemini_crop_recommendation))
            <div id="geminiStoredResult" class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-semibold text-success">
                        <i class="fas fa-check-circle me-1"></i> Gemini Recommendation (saved)
                    </span>
                    <button class="btn btn-sm btn-outline-primary" onclick="generateGemini()" id="geminiRegenerateBtn">
                        <i class="fas fa-sync me-1"></i> Regenerate
                    </button>
                </div>
                <div id="geminiRecommendationText"
                     class="p-3 rounded border bg-white"
                     style="white-space:pre-wrap;font-size:.92rem;line-height:1.6;">{{ $sample->gemini_crop_recommendation }}</div>
            </div>
            @endif
            <div id="geminiGenerateArea" class="{{ !empty($sample->gemini_crop_recommendation) ? 'd-none' : '' }}">
                <button class="btn btn-lg"
                        style="background:linear-gradient(135deg,#1a73e8,#34a853);color:#fff;"
                        onclick="generateGemini()" id="geminiBtn">
                    <i class="fas fa-seedling me-2"></i>Get Gemini Crop Recommendations
                </button>
            </div>
            <div id="geminiLoading" class="d-none mt-3">
                <div class="spinner-border spinner-border-sm me-2" style="color:#1a73e8;"></div>
                Consulting Gemini AI — analyzing soil profile and matching Philippine crops...
            </div>
            <div id="geminiResult" class="mt-3 p-3 rounded border bg-white d-none"
                 style="white-space:pre-wrap;font-size:.92rem;line-height:1.6;"></div>
            <div id="geminiError" class="alert alert-danger mt-3 d-none"></div>
        @endif
    </div>
</div>

<div class="row mb-4">
    <div class="col text-end">
        <a href="{{ route('samples.report', $sample) }}" class="btn btn-outline-info">
            <i class="fas fa-microscope me-1"></i> Test Report
        </a>
        <a href="{{ route('samples.fertilizer-schedule', $sample) }}" target="_blank" class="btn btn-outline-success ms-2">
            <i class="fas fa-calendar-alt me-1"></i> Fertilizer Schedule
        </a>
        <a href="{{ route('samples.reset', $sample) }}"
           class="btn btn-outline-warning ms-2"
           onclick="return confirm('This will reset ALL readings for this sample. Continue?');">
            <i class="fas fa-redo me-1"></i> Re-capture All
        </a>
        @if(Auth::user()->isAdmin())
        <button type="button" class="btn btn-danger ms-2"
                data-bs-toggle="modal" data-bs-target="#deleteSampleModal">
            <i class="fas fa-trash me-1"></i> Delete Sample
        </button>
        @endif
        <a href="{{ route('samples.export-excel', $sample) }}" class="btn btn-success ms-2">
            <i class="fas fa-file-excel me-1"></i> Export to Excel
        </a>
        <a href="{{ $sample->farm ? route('analyses.choose', $sample->farm) : route('farms.index') }}" class="btn btn-primary ms-2">
            <i class="fas fa-plus-circle me-1"></i> New Analysis
        </a>
    </div>
</div>
@endif

{{-- ── Delete Sample Modal (admin only) ───────────────────────── --}}
@if(Auth::user()->isAdmin())
<div class="modal fade" id="deleteSampleModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-danger">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>Delete Sample Permanently
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('samples.destroy', $sample) }}">
                @csrf
                @method('DELETE')
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <strong>This cannot be undone.</strong> Deleting this sample will permanently remove:
                        <ul class="mb-0 mt-1 small">
                            <li>All soil test readings (pH, N, P, K)</li>
                            <li>All captured webcam photos</li>
                            <li>The analysis result and fertilizer recommendation</li>
                        </ul>
                    </div>
                    <p class="mb-1">Sample to delete:</p>
                    <div class="p-2 rounded bg-light border mb-3 fw-semibold">
                        {{ $sample->sample_name }} — {{ $sample->farmer_name }}
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Enter your admin password to confirm:</label>
                        <input type="password" class="form-control" name="admin_password"
                               placeholder="Admin password" autocomplete="current-password" required>
                        @if(session('error'))
                        <div class="text-danger small mt-1">{{ session('error') }}</div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i> Delete Permanently
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@if($sample->isAnalyzed())
    @include('samples.partials.fertilizer-schedule-modal', [
        'sample'           => $sample,
        'schedulableCrops' => $schedulableCrops,
        'nStatus'          => $nStatus ?? null,
        'pStatus'          => $pStatus ?? null,
        'kStatus'          => $kStatus ?? null,
    ])
@endif
 

@endsection

@section('scripts')
@if($sample->isAnalyzed())
<script>
const CROP_FERT_DATA = @json($cropFertData ?? []);

const FERT_TYPE = {
    urea:             { n: 0.46, p: 0,    k: 0,    name: 'Urea (46-0-0)' },
    complete:         { n: 0.14, p: 0.14, k: 0.14, name: 'Complete (14-14-14)' },
    ammonium_sulfate: { n: 0.21, p: 0,    k: 0,    name: 'Ammonium Sulfate (21-0-0)' },
    dap:              { n: 0.18, p: 0.46, k: 0,    name: 'DAP (18-46-0)' },
    mop:              { n: 0,    p: 0,    k: 0.60, name: 'Muriate of Potash (0-0-60)' },
    organic:          { n: 0.02, p: 0.015,k: 0.01, name: 'Organic Fertilizer (~2-1.5-1)' },
};

const SOIL_PH = {{ (float)$sample->ph_level }};

function calculateFertilizer() {
    const cropId = document.getElementById('cropSelect').value;
    const area   = parseFloat(document.getElementById('areaSize').value) || 1;
    const fType  = document.getElementById('fertilizerType').value;

    if (!cropId) { alert('Please select a crop first.'); return; }

    const npk  = CROP_FERT_DATA[cropId];
    const fert = FERT_TYPE[fType];
    if (!npk) { alert('Fertilizer data not available for this crop.'); return; }

    const fmt = n => Number(n).toFixed(2);

    // Crop targets (target_ppm = kg/ha, no conversion needed)
    const nTarget = npk.n.target_ppm;
    const pTarget = npk.p.target_ppm;
    const kTarget = npk.k.target_ppm;

    // ── Fixed base: DAP for P, MOP for K ─────────────────────────────────
    // Step 1: MOP (0-0-60) always covers K
    const mopKgHa  = kTarget / 0.60;

    // Step 2: DAP (18-46-0) always covers P, but contributes N as byproduct
    const dapKgHa  = pTarget / 0.46;
    const nFromDap = dapKgHa * 0.18;

    // Step 3: Selected fertilizer covers remaining N after DAP's N credit
    // If the selected fertilizer has no N content (e.g. MOP), fall back to Urea's 46%
    const nRemain      = Math.max(0, nTarget - nFromDap);
    const nFraction    = fert.n > 0 ? fert.n : 0.46;
    const primaryKgHa  = nRemain / nFraction;

    // Special case: if selected fertilizer is DAP itself, no separate primary needed
    // (DAP already computed above). If selected is MOP, same — just show note.
    const primaryIsDap = (fType === 'dap');
    const primaryIsMop = (fType === 'mop');

    // pH note
    let phNote = '';
    if      (SOIL_PH < 5.5) phNote = 'Soil pH is acidic — this must be addressed, as some crops may not thrive under acidic conditions.';
    else if (SOIL_PH > 7.5) phNote = 'Alkaline soil (pH > 7.5) — consider organic matter or elemental sulfur to lower pH.';

    const statusBadge = s => {
        const map = { Low: 'success', Medium: 'warning', High: 'danger' };
        return `<span class="badge bg-${map[s] ?? 'secondary'}">${s}</span>`;
    };

    // ── Summary cards ─────────────────────────────────────────────────────
    let cards = '';

    // Primary fertilizer card (for N)
    if (!primaryIsDap && !primaryIsMop) {
        cards += card('fa-seedling', 'success', fert.name,
            `${fmt(primaryKgHa * area)} kg<div style="font-size:10px;font-weight:normal;opacity:.7;margin-top:2px;">${fmt(primaryKgHa * area / 50)} bags</div>`,
            `${fmt(primaryKgHa)} kg/ha · ${fmt(primaryKgHa / 50)} bags/ha × ${fmt(area)} ha`,
            'Nitrogen source');
    } else if (primaryIsDap) {
        // DAP selected: it covers both P and part of N — show merged note
        cards += card('fa-seedling', 'success', 'DAP (18-46-0)',
            `${fmt(dapKgHa * area)} kg<div style="font-size:10px;font-weight:normal;opacity:.7;margin-top:2px;">${fmt(dapKgHa * area / 50)} bags</div>`,
            `${fmt(dapKgHa)} kg/ha · ${fmt(dapKgHa / 50)} bags/ha × ${fmt(area)} ha`,
            'Phosphorus + Nitrogen source');
    } else {
        // MOP selected as primary: has no N, show Urea as fallback
        cards += card('fa-seedling', 'success', `Urea (46-0-0) <small class="text-warning">fallback — MOP has no N</small>`,
            `${fmt(primaryKgHa * area)} kg<div style="font-size:10px;font-weight:normal;opacity:.7;margin-top:2px;">${fmt(primaryKgHa * area / 50)} bags</div>`,
            `${fmt(primaryKgHa)} kg/ha · ${fmt(primaryKgHa / 50)} bags/ha × ${fmt(area)} ha`,
            'Nitrogen source (fallback)');
    }

    // DAP card (P) — only show separately if DAP is not already the primary
    if (!primaryIsDap) {
        cards += card('fa-atom', 'primary', 'DAP (18-46-0)',
            `${fmt(dapKgHa * area)} kg<div style="font-size:10px;font-weight:normal;opacity:.7;margin-top:2px;">${fmt(dapKgHa * area / 50)} bags</div>`,
            `${fmt(dapKgHa)} kg/ha · ${fmt(dapKgHa / 50)} bags/ha × ${fmt(area)} ha`,
            'Phosphorus source');
    }

    // MOP card (K) — only show separately if MOP is not already the primary
    if (!primaryIsMop) {
        cards += card('fa-flask', 'info', 'MOP (0-0-60)',
            `${fmt(mopKgHa * area)} kg<div style="font-size:10px;font-weight:normal;opacity:.7;margin-top:2px;">${fmt(mopKgHa * area / 50)} bags</div>`,
            `${fmt(mopKgHa)} kg/ha · ${fmt(mopKgHa / 50)} bags/ha × ${fmt(area)} ha`,
            'Potassium source');
    }

    // ── Detail breakdown table ────────────────────────────────────────────
    const effectivePrimaryName = primaryIsDap ? 'DAP (18-46-0)' 
                               : primaryIsMop ? 'Urea (46-0-0) [fallback]' 
                               : fert.name;

    const rows = [
        {
            label:      'Nitrogen (N)',
            status:     npk.n.status,
            target:     nTarget,
            fertilizer: effectivePrimaryName,
            kgHa:       primaryIsDap ? dapKgHa : primaryKgHa,
            note:       !primaryIsDap && nFromDap > 0
                            ? `DAP contributes ${fmt(nFromDap)} kg N/ha; ${effectivePrimaryName} covers remaining ${fmt(nRemain)} kg N/ha`
                            : primaryIsDap
                            ? `DAP covers P and contributes ${fmt(nFromDap)} kg N/ha (remaining ${fmt(nRemain)} kg N/ha covered within same DAP rate)`
                            : '',
        },
        {
            label:      'Phosphorus (P)',
            status:     npk.p.status,
            target:     pTarget,
            fertilizer: 'DAP (18-46-0)',
            kgHa:       dapKgHa,
            note:       '',
        },
        {
            label:      'Potassium (K)',
            status:     npk.k.status,
            target:     kTarget,
            fertilizer: 'MOP (0-0-60)',
            kgHa:       mopKgHa,
            note:       '',
        },
    ];

    const totalKgHa  = (primaryIsDap ? 0 : primaryKgHa) + dapKgHa + mopKgHa;

    cards += `
    <div class="col-12 mt-3">
        <div class="card border-info mb-0">
            <div class="card-header bg-info text-white py-2 small">
                <i class="fas fa-table me-1"></i>
                <strong>Fertilizer Requirement Breakdown</strong>
                <small class="ms-2 opacity-75">
                    ${effectivePrimaryName} · DAP 18-46-0 · MOP 0-0-60
                </small>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle mb-0" style="font-size:.82rem;">
                        <thead class="table-light">
                            <tr>
                                <th>Nutrient</th>
                                <th class="text-center">Soil Need</th>
                                <th class="text-center">Crop Target (kg/ha)</th>
                                <th class="text-center">Fertilizer Used</th>
                                <th class="text-center">Rate (kg/ha)</th>
                                <th class="text-center">Total for ${fmt(area)} ha</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${rows.map(r => `
                            <tr>
                                <td class="fw-semibold">${r.label}</td>
                                <td class="text-center">${statusBadge(r.status)}</td>
                                <td class="text-center">${fmt(r.target)}</td>
                                <td class="text-center text-muted small">${r.fertilizer}</td>
                                <td class="text-center fw-bold">${fmt(r.kgHa)}<br><small class="text-muted fw-normal" style="font-size:.75em;">${fmt(r.kgHa / 50)} bags/ha</small></td>
                                <td class="text-center fw-bold">${fmt(r.kgHa * area)} kg<br><small class="text-muted fw-normal" style="font-size:.75em;">${fmt(r.kgHa * area / 50)} bags</small></td>
                            </tr>
                            ${r.note ? `<tr><td colspan="6" class="text-muted small py-1 ps-3">
                                <i class="fas fa-info-circle me-1 text-warning"></i>${r.note}
                            </td></tr>` : ''}`).join('')}
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="4" class="text-end">Total fertilizer needed:</td>
                                <td class="text-center">${fmt(totalKgHa)} kg/ha<br><small class="text-muted fw-normal" style="font-size:.75em;">${fmt(totalKgHa / 50)} bags/ha</small></td>
                                <td class="text-center">${fmt(totalKgHa * area)} kg<br><small class="text-muted fw-normal" style="font-size:.75em;">${fmt(totalKgHa * area / 50)} bags</small></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>`;

    // ── Render ────────────────────────────────────────────────────────────
    const cropLabel = document.getElementById('cropSelect').selectedOptions[0].text;
    document.getElementById('calcResultsTitle').innerHTML =
        `<i class="fas fa-check-circle text-success me-1"></i>` +
        `Result for <strong>${cropLabel}</strong> — ` +
        `<strong>${fmt(area)} ha</strong> using <strong>${fert.name}</strong>`;
    document.getElementById('calcResultsCards').innerHTML = cards;

    const alertEl = document.getElementById('calcResultsAlert');
    alertEl.className = 'alert mt-3 mb-0 alert-' + (phNote
        ? (SOIL_PH < 5.5 ? 'warning' : 'info') : 'success');
    alertEl.innerHTML = phNote
        ? `<i class="fas fa-exclamation-triangle me-1"></i>${phNote}`
        : `<i class="fas fa-check-circle me-1"></i>Soil pH (${SOIL_PH}) is within acceptable range for most crops.`;

    document.getElementById('calcResults').classList.remove('d-none');
    document.getElementById('calcResults').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}
function card(icon, color, title, value, sub, footnote) {
    return `
    <div class="col-md-4 col-sm-6">
        <div class="card text-center h-100 border-${color}">
            <div class="card-body py-3">
                <i class="fas ${icon} fa-2x text-${color} mb-2"></i>
                <div class="fw-bold fs-5 text-${color}">${value}</div>
                <div class="fw-semibold small">${title}</div>
                <div class="text-muted" style="font-size:11px;">${sub}</div>
                <div class="text-muted" style="font-size:10px;">${footnote}</div>
            </div>
        </div>
    </div>`;
}

// ── Crop Table Sort ────────────────────────────────────────────────────────
let cropSortDir = {};
function sortCropTable(colIndex) {
    const table = document.getElementById('cropRecoTable');
    if (!table) return;
    const tbody = table.querySelector('tbody');
    const rows  = Array.from(tbody.querySelectorAll('tr'));
    const dir   = (cropSortDir[colIndex] === 'asc') ? 'desc' : 'asc';
    cropSortDir  = {};
    cropSortDir[colIndex] = dir;

    rows.sort((a, b) => {
        const aCell = a.cells[colIndex]?.textContent.trim() ?? '';
        const bCell = b.cells[colIndex]?.textContent.trim() ?? '';
        // Numeric columns: #(0), N target(2), P target(3), K target(4), Overall Score(5)
        if ([0, 2, 3, 4, 5].includes(colIndex)) {
            return dir === 'asc'
                ? (parseFloat(aCell)||0) - (parseFloat(bCell)||0)
                : (parseFloat(bCell)||0) - (parseFloat(aCell)||0);
        }
        return dir === 'asc' ? aCell.localeCompare(bCell) : bCell.localeCompare(aCell);
    });

    rows.forEach((r, i) => { r.cells[0].textContent = i + 1; tbody.appendChild(r); });
}

// ── Gemini ────────────────────────────────────────────────────────────────
function updateGeminiCropHighlight() {
    const sel       = document.getElementById('geminiPreferredCrop');
    const highlight = document.getElementById('geminiCropHighlight');
    const nameEl    = document.getElementById('geminiCropHighlightName');
    const noneEl    = document.getElementById('geminiCropNone');
    if (!sel) return;
    const val = sel.value.trim();
    if (val) {
        if (nameEl)    nameEl.textContent = val;
        if (highlight) highlight.classList.remove('d-none');
        if (noneEl)    noneEl.classList.add('d-none');
    } else {
        if (highlight) highlight.classList.add('d-none');
        if (noneEl)    noneEl.classList.remove('d-none');
    }
}

let geminiInFlight = false;
function generateGemini() {
    if (geminiInFlight) return;
    geminiInFlight = true;
    const btn          = document.getElementById('geminiBtn');
    const regenBtn     = document.getElementById('geminiRegenerateBtn');
    const loading      = document.getElementById('geminiLoading');
    const result       = document.getElementById('geminiResult');
    const errDiv       = document.getElementById('geminiError');
    const storedDiv    = document.getElementById('geminiStoredResult');
    const generateArea = document.getElementById('geminiGenerateArea');
    const cropSelect   = document.getElementById('geminiPreferredCrop');
    if (btn)     btn.disabled = true;
    if (regenBtn) regenBtn.disabled = true;
    if (loading) loading.classList.remove('d-none');
    if (result)  result.classList.add('d-none');
    if (errDiv)  errDiv.classList.add('d-none');
    fetch('{{ route("gemini-crop-recommendations.generate") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({ sample_id: {{ $sample->id }}, preferred_crop: cropSelect?.value.trim() || null, area_size: parseFloat(document.getElementById('areaSize')?.value) || 1 })
    })
    .then(r => r.json())
    .then(data => {
        if (loading) loading.classList.add('d-none');
        if (data.success) {
            if (result) { result.textContent = data.recommendation; result.classList.remove('d-none'); }
            const storedText = document.getElementById('geminiRecommendationText');
            if (storedText) { storedText.textContent = data.recommendation; if (storedDiv) storedDiv.classList.remove('d-none'); }
            if (generateArea) generateArea.classList.add('d-none');
            setTimeout(() => {
                const target = result && !result.classList.contains('d-none') ? result
                             : storedDiv && !storedDiv.classList.contains('d-none') ? storedDiv : null;
                if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 50);
        } else {
            if (errDiv) { errDiv.textContent = 'Gemini Error: ' + data.message; errDiv.classList.remove('d-none'); }
        }
        if (btn)     btn.disabled = false;
        if (regenBtn) regenBtn.disabled = false;
        geminiInFlight = false;
    })
    .catch(() => {
        if (loading) loading.classList.add('d-none');
        if (errDiv) { errDiv.textContent = 'Network error contacting Gemini AI service.'; errDiv.classList.remove('d-none'); }
        if (btn)     btn.disabled = false;
        if (regenBtn) regenBtn.disabled = false;
        geminiInFlight = false;
    });
}
</script>


@endif
@endsection