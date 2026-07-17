<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soil Test Report — {{ $sample->sample_name }}</title>
    <style>
        /* ── Base ────────────────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11pt;
            color: #111;
            background: #fff;
            padding: 20px;
        }
        h1 { font-size: 16pt; margin-bottom: 4px; }
        h2 { font-size: 13pt; margin: 16px 0 6px; border-bottom: 2px solid #2e7d32; padding-bottom: 4px; color: #2e7d32; }
        h3 { font-size: 11pt; margin: 12px 0 4px; color: #1565c0; }
        p  { margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 10pt; }
        th, td { border: 1px solid #bbb; padding: 4px 6px; text-align: left; }
        th { background: #e8f5e9; font-weight: bold; }
        .badge {
            display: inline-block; padding: 1px 7px; border-radius: 10px;
            font-size: 9pt; font-weight: bold; color: #fff;
        }
        .badge-success  { background: #388e3c; }
        .badge-warning  { background: #f9a825; color: #111; }
        .badge-info     { background: #0288d1; }
        .badge-danger   { background: #c62828; }
        .badge-secondary{ background: #757575; }
        .badge-primary  { background: #1565c0; }
        .swatch {
            display: inline-block; width: 18px; height: 14px;
            border: 1px solid #999; vertical-align: middle; margin-right: 4px;
            border-radius: 2px;
        }
        .info-grid { display: flex; flex-wrap: wrap; gap: 6px 24px; margin-bottom: 10px; }
        .info-grid span { font-size: 10.5pt; }
        .info-grid strong { color: #333; }
        .section { margin-bottom: 18px; }
        .note-box { background: #fff9c4; border: 1px solid #f9a825; padding: 6px 10px; border-radius: 4px; font-size: 10pt; margin-bottom: 8px; }
        .result-row { display: flex; gap: 20px; margin-bottom: 10px; flex-wrap: wrap; }
        .result-card {
            border: 1px solid #bbb; border-radius: 6px;
            padding: 8px 14px; min-width: 120px; text-align: center;
        }
        .result-card .value { font-size: 18pt; font-weight: bold; }
        .result-card .label { font-size: 9pt; color: #555; }
        .stage-header { background: #e3f2fd; border-left: 4px solid #1565c0; padding: 4px 8px; margin-bottom: 6px; font-weight: bold; }
        .stage2-header { background: #e8f5e9; border-left: 4px solid #2e7d32; padding: 4px 8px; margin-bottom: 6px; font-weight: bold; }
        .final-box {
            border: 2px solid #1565c0; border-radius: 6px;
            padding: 10px 16px; display: flex; justify-content: space-between;
            align-items: center; background: #e3f2fd; margin: 10px 0;
        }
        .final-box .ph-value { font-size: 22pt; font-weight: bold; color: #1565c0; }
        .print-btn {
            display: inline-block; padding: 8px 20px; background: #1565c0; color: #fff;
            border: none; border-radius: 4px; cursor: pointer; font-size: 11pt;
            text-decoration: none; margin-bottom: 16px;
        }
        .print-btn:hover { background: #0d47a1; }
        .no-print { }
        /* ── Print overrides ──────────────────────────────────────── */
        @media print {
            body { padding: 0; font-size: 10pt; }
            .no-print { display: none !important; }
            h2 { font-size: 12pt; }
            table { font-size: 9pt; }
            @page { size: A4; margin: 15mm 12mm; }
        }
    </style>
</head>
<body>



{{-- ── Header ─────────────────────────────────────────────────────── --}}
<div style="border-bottom:3px solid #2e7d32;padding-bottom:10px;margin-bottom:14px;">
    <h1>Soil Test Report</h1>
    <div style="font-size:13pt;color:#2e7d32;font-weight:bold;">{{ $sample->sample_name }}</div>
</div>

<div class="info-grid">
    <span><strong>Farmer:</strong> {{ $sample->farmer_name }}</span>
    <span><strong>Address:</strong> {{ $sample->address }}</span>
    @if($sample->location)<span><strong>Farm Location:</strong> {{ $sample->location }}</span>@endif
    <span><strong>Date Received:</strong> {{ $sample->sample_date->format('F j, Y') }}</span>
    <span><strong>Date Tested:</strong> {{ $sample->date_tested->format('F j, Y') }}</span>
    @if($sample->analyzed_at)<span><strong>Analyzed:</strong> {{ $sample->analyzed_at->format('F j, Y g:i A') }}</span>@endif
    <span><strong>Tests Captured:</strong> {{ $sample->tests_completed ?? 0 }}/12</span>
    @if(!is_null($sample->fertility_score))
    <span><strong>Fertility Score:</strong>
        <span class="badge badge-{{ $sample->fertilityColorClass() }}">{{ $sample->fertility_score }}%</span>
    </span>
    @endif
</div>

{{-- ── Soil Parameters Summary ─────────────────────────────────────── --}}
@if($sample->isAnalyzed())

<h2>Soil Analysis Results</h2>
<div class="result-row">
    <div class="result-card" style="display: inline-block; max-width: 25%;margin-top: 50px;">
        <div class="label">Soil pH</div>
        <div class="value" style="color:#1565c0;">{{ number_format($sample->ph_level,2) }}</div>
    </div>
    <div class="result-card"  style="display: inline-block; max-width: 25%;">
        <div class="label">Nitrogen (N)</div>
        <div class="value" style="color:#2e7d32;">{{ number_format($sample->nitrogen_level,1) }}</div>
        <div class="label">kg/ha</div>
    </div>
    <div class="result-card"  style="display: inline-block; max-width: 25%;">
        <div class="label">Phosphorus (P)</div>
        <div class="value" style="color:#0288d1;">{{ number_format($sample->phosphorus_level,1) }}</div>
        <div class="label">kg/ha</div>
    </div>
    <div class="result-card"  style="display: inline-block; max-width: 25%;">
        <div class="label">Potassium (K)</div>
        <div class="value" style="color:#f57f17;">{{ number_format($sample->potassium_level,1) }}</div>
        <div class="label">kg/ha</div>
    </div>
    @if(!is_null($sample->fertility_score))
    <div class="result-card">
        <div class="label">Fertility Score</div>
        <div class="value" style="color:#{{ $sample->fertility_score>=75?'388e3c':($sample->fertility_score>=50?'f9a825':'c62828') }};">
            {{ $sample->fertility_score }}%
        </div>
    </div>
    @endif
</div>
@endif

{{-- ── pH Test Details ─────────────────────────────────────────────── --}}
@if($phTest)
<h2>pH Test — BSWM Two-Step Protocol</h2>

{{-- Stage 1 --}}
<div class="stage-header">Stage 1 — CPR Solution (Cresol Red Purple)</div>
<table>
    <thead>
        <tr><th>Capture</th><th>Hex Color</th><th>System Color</th><th>Computed pH</th></tr>
    </thead>
    <tbody>
        @foreach(range(1,3) as $i)
        @php $rd = $phTest->step1_readings[$i-1] ?? null; @endphp
        <tr>
            <td>Capture {{ $i }}</td>
            <td>{{ $rd ? $rd['hex'] : '—' }}</td>
            <td>
                @if($rd)
                    <span class="swatch" style="background:{{ $rd['hex'] }};"></span>{{ $rd['hex'] }}
                @else —
                @endif
            </td>
            <td>{{ $rd ? number_format($rd['computed_value'],2) : '—' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@if($phTest->step1_ph)
<p style="margin-bottom:8px;">
    CPR Average pH: <strong>{{ number_format($phTest->step1_ph,2) }}</strong> &nbsp;
    Confidence: <strong>{{ $phTest->step1_confidence }}</strong> &nbsp;
    Decision: <strong>
        @switch($phTest->next_solution)
            @case('BCG') Proceed to BCG @break
            @case('BTB') Proceed to BTB @break
            @case('CPR') CPR Result is Final @break
            @case('RETEST') Retest Required @break
            @default Pending
        @endswitch
    </strong>
</p>
@endif

{{-- Stage 2 --}}
@if($phTest->step2_readings && count($phTest->step2_readings))
<div class="stage2-header">
    Stage 2 — {{ $phTest->step2_solution }}
    @if($phTest->step2_solution === 'BCG') (Bromocresol Green — acidic range)
    @elseif($phTest->step2_solution === 'BTB') (Bromothymol Blue — near-neutral range)
    @endif
</div>
<table>
    <thead>
        <tr><th>Capture</th><th>Hex Color</th><th>System Color</th><th>Computed pH</th></tr>
    </thead>
    <tbody>
        @foreach(range(1,3) as $i)
        @php $rd = $phTest->step2_readings[$i-1] ?? null; @endphp
        <tr>
            <td>Capture {{ $i }}</td>
            <td>{{ $rd ? $rd['hex'] : '—' }}</td>
            <td>
                @if($rd)
                    <span class="swatch" style="background:{{ $rd['hex'] }};"></span>{{ $rd['hex'] }}
                @else —
                @endif
            </td>
            <td>{{ $rd ? number_format($rd['computed_value'],2) : '—' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@if($phTest->step2_ph)
<p style="margin-bottom:8px;">
    {{ $phTest->step2_solution }} Average pH: <strong>{{ number_format($phTest->step2_ph,2) }}</strong> &nbsp;
    Confidence: <strong>{{ $phTest->step2_confidence }}</strong>
</p>
@endif
@endif

{{-- Final pH --}}
@if($phTest->final_ph)
<div class="final-box">
    <div>
        <div style="font-weight:bold;font-size:12pt;">Final pH Result</div>
        <div style="font-size:10pt;color:#555;">
            Based on
            @if($phTest->next_solution==='CPR') CPR (transitional range 5.4–5.8)
            @elseif($phTest->step2_solution==='BCG') BCG — Stage 2 (acidic range ≤ 5.4)
            @elseif($phTest->step2_solution==='BTB') BTB — Stage 2 (near-neutral range > 5.8)
            @else BSWM protocol
            @endif
        </div>
    </div>
    <div style="text-align:center;">
        @if($sample->ph_color_hex)
        <span class="swatch" style="background:{{ $sample->ph_color_hex }};width:30px;height:20px;"></span>
        @endif
        <span class="ph-value">{{ number_format($phTest->final_ph,2) }}</span>
        <span style="font-size:12pt;color:#555;"> pH</span>
    </div>
</div>
@endif
@endif

{{-- ── NPK Soil Status ──────────────────────────────────────────────── --}}
@if($sample->isAnalyzed() && $nStatus)
@php
$statusColor = fn($s) => match($s) { 'Low' => '#c62828', 'Medium' => '#f9a825', 'High' => '#388e3c', default => '#757575' };
$statusRows = [
    ['label' => 'Nitrogen (N)',   'status' => $nStatus],
    ['label' => 'Phosphorus (P)', 'status' => $pStatus],
    ['label' => 'Potassium (K)',  'status' => $kStatus],
];
@endphp
<h2>&#128200; Soil Nutrient Status</h2>
<table>
    <thead>
        <tr><th>Parameter</th><th>Status</th><th>Interpretation</th></tr>
    </thead>
    <tbody>
        <tr>
            <td>Soil pH</td>
            <td><span class="badge badge-{{ $sample->ph_level < 5.5 ? 'danger' : ($sample->ph_level > 7.0 ? 'info' : 'success') }}">
                {{ $sample->ph_level < 5.5 ? 'Acidic' : ($sample->ph_level > 7.0 ? 'Alkaline' : 'Optimal') }}
            </span></td>
            <td>{{ $sample->ph_level < 5.5 ? 'Acidic — soil pH must be addressed' : ($sample->ph_level > 7.5 ? 'Alkaline — organic matter or sulfur' : 'Within optimal range') }}</td>
        </tr>
        @foreach($statusRows as $row)
        <tr>
            <td>{{ $row['label'] }}</td>
            <td><span class="badge badge-{{ $row['status'] === 'Low' ? 'danger' : ($row['status'] === 'Medium' ? 'warning' : 'success') }}">
                {{ $row['status'] }}
            </span></td>
            <td>{{ $row['status'] === 'Low' ? 'Deficient — high fertilizer application needed' : ($row['status'] === 'Medium' ? 'Moderate — standard application' : 'Sufficient — limited/maintenance application') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- ── Fertilizer Recommendations ──────────────────────────────────── --}}
@if(!empty($fertRec))
<h2>Fertilizer Recommendations</h2>

@if($sample->ph_level < 5.5)
<div class="note-box">
    <strong>&#9888; pH Advisory:</strong> Soil pH is acidic — this must be addressed, as some crops may not thrive or may not perform at their best under acidic soil conditions.
</div>
@endif

<table>
    <thead>
        <tr>
            <th>Fertilizer</th>
            <th>Status</th>
            <th>Rate</th>
            <th>Application</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Urea (46-0-0) — Nitrogen</td>
            <td><span class="badge badge-{{ $nStatus === 'Low' ? 'danger' : ($nStatus === 'Medium' ? 'warning' : 'success') }}">{{ $nStatus }}</span></td>
            <td>{{ $fertRec['urea_kgha'] }} kg/ha</td>
            <td>{{ $nStatus === 'Low' ? '½ basal + ½ at panicle initiation' : ($nStatus === 'Medium' ? '½ basal + ½ at tillering' : 'Maintenance — basal only') }}</td>
        </tr>
        <tr>
            <td>TSP (0-46-0) — Phosphorus</td>
            <td><span class="badge badge-{{ $pStatus === 'Low' ? 'danger' : ($pStatus === 'Medium' ? 'warning' : 'success') }}">{{ $pStatus }}</span></td>
            <td>{{ $fertRec['tsp_kgha'] }} kg/ha</td>
            <td>Basal (at planting)</td>
        </tr>
        <tr>
            <td>MOP (0-0-60) — Potassium</td>
            <td><span class="badge badge-{{ $kStatus === 'Low' ? 'danger' : ($kStatus === 'Medium' ? 'warning' : 'success') }}">{{ $kStatus }}</span></td>
            <td>{{ $fertRec['mop_kgha'] }} kg/ha</td>
            <td>Basal or split</td>
        </tr>
    </tbody>
</table>
@if(!empty($fertRec['notes']))
<div class="note-box">
    @foreach($fertRec['notes'] as $note)
    <div>&#8226; {{ $note }}</div>
    @endforeach
</div>
@endif
@endif

{{-- ── Crop Recommendations ─────────────────────────────────────── --}}
@if(!empty($scoredCrops))
<h2>&#127807; Crop Recommendations (Top 10)</h2>
<p style="font-size:9.5pt;color:#555;margin-bottom:8px;">
    Ranked by nutrient sufficiency match. UREA/DAP/MOP rates are per-hectare product requirements
    computed using the fixed formula: MOP&nbsp;=&nbsp;K&nbsp;&divide;&nbsp;0.60 &middot;
    DAP&nbsp;=&nbsp;P&nbsp;&divide;&nbsp;0.46 &middot;
    UREA&nbsp;=&nbsp;(N&nbsp;&minus;&nbsp;DAP&times;0.18)&nbsp;&divide;&nbsp;0.46.
</p>
<table>
    <thead>
        <tr>
            <th style="width:4%;">#</th>
            <th style="width:14%;">Crop</th>
            <th style="width:8%;text-align:center;">Soil N</th>
            <th style="width:8%;text-align:center;">Soil P</th>
            <th style="width:8%;text-align:center;">Soil K</th>
            <th style="width:12%;text-align:center;">UREA (kg/ha)</th>
            <th style="width:12%;text-align:center;">DAP (kg/ha)</th>
            <th style="width:12%;text-align:center;">MOP (kg/ha)</th>
            <th>pH Range &amp; Soil Match</th>
        </tr>
    </thead>
    <tbody>
        @foreach($scoredCrops as $i => $row)
        @php
            $statusBadge = fn($s) => match($s) {
                'Low'    => ['bg'=>'#c62828','label'=>'Low'],
                'Medium' => ['bg'=>'#f9a825','label'=>'Med'],
                'High'   => ['bg'=>'#388e3c','label'=>'High'],
                default  => ['bg'=>'#757575','label'=>$s],
            };
            $nb = $statusBadge($row['nSt']);
            $pb = $statusBadge($row['pSt']);
            $kb = $statusBadge($row['kSt']);
            $phNote = $row['phInRange']
                ? '&#10003; pH suitable ('.$row['phRange'].')'
                : '&#9888; pH out of range ('.$row['phRange'].')';
        @endphp
        <tr style="{{ $i === 0 ? 'background:#fffde7;' : '' }}">
            <td style="text-align:center;color:#888;">{{ $i + 1 }}</td>
            <td>
                <strong>{{ $row['crop']->name }}</strong>
                @if($i === 0)
                    <span class="badge badge-warning" style="font-size:7.5pt;">Top Pick</span>
                @elseif($i < 3)
                    <span class="badge badge-success" style="font-size:7.5pt;">Recommended</span>
                @endif
            </td>
            <td style="text-align:center;">
                <span class="badge" style="background:{{ $nb['bg'] }};color:{{ $row['nSt']==='Medium'?'#111':'#fff' }};">{{ $nb['label'] }}</span>
            </td>
            <td style="text-align:center;">
                <span class="badge" style="background:{{ $pb['bg'] }};color:{{ $row['pSt']==='Medium'?'#111':'#fff' }};">{{ $pb['label'] }}</span>
            </td>
            <td style="text-align:center;">
                <span class="badge" style="background:{{ $kb['bg'] }};color:{{ $row['kSt']==='Medium'?'#111':'#fff' }};">{{ $kb['label'] }}</span>
            </td>
            <td style="text-align:center;">{{ $row['urea'] !== null ? number_format($row['urea'],2) : '—' }}</td>
            <td style="text-align:center;">{{ $row['dap']  !== null ? number_format($row['dap'], 2) : '—' }}</td>
            <td style="text-align:center;">{{ $row['mop']  !== null ? number_format($row['mop'], 2) : '—' }}</td>
            <td style="font-size:9pt;color:#444;">{!! $phNote !!}</td>
        </tr>
        @endforeach
    </tbody>
</table>
<div style="font-size:8.5pt;color:#777;margin-top:4px;">
    Soil N/P/K status: <strong>Low</strong> = deficient (more fertilizer needed) &bull;
    <strong>Med</strong> = moderate &bull; <strong>High</strong> = sufficient.
    Crops sorted by descending deficiency score.
</div>
@endif

{{-- Footer --}}
<div style="margin-top:24px;border-top:1px solid #ccc;padding-top:8px;font-size:9pt;color:#777;">
    Soil Analysis System &bull; BSWM Protocol &bull;
    Generated: {{ now()->format('F j, Y g:i A') }}
</div>

</body>
</html>
