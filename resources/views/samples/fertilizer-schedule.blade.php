<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fertilizer Schedule — {{ $sample->sample_name }}</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11pt;
            color: #000;
            background: #fff;
            padding: 20px;
        }

        /* ── Print button (screen only) ── */
        .no-print { margin-bottom: 16px; }
        .print-btn {
            display: inline-block; padding: 8px 20px; background: #1565c0;
            color: #fff; border: none; border-radius: 4px; cursor: pointer;
            font-size: 11pt; text-decoration: none; margin-right: 10px;
        }
        .print-btn:hover { background: #0d47a1; }
        .back-link { font-size: 11pt; color: #555; text-decoration: none; }

        /* ── Form wrapper ── */
        .form-page { max-width: 740px; margin: 0 auto; border: 1px solid #000; padding: 0; }

        /* ── Header ── */
        .header {
            display: flex; align-items: center; padding: 10px 14px 8px;
            border-bottom: 2px solid #000; gap: 12px;
        }
        .header-logo { width: 72px; height: 72px; flex-shrink: 0; }
        .header-text { flex: 1; text-align: center; line-height: 1.5; }
        .header-text .republic { font-size: 10.5pt; }
        .header-text .province  { font-size: 10.5pt; }
        .header-text .muni      { font-size: 11pt; font-weight: bold; }
        .header-text .olo       { font-size: 10.5pt; }
        .header-text .office    { font-size: 11.5pt; font-weight: bold; letter-spacing: .5px; }
        .header-code { text-align: right; font-size: 8.5pt; line-height: 1.6; white-space: nowrap; }

        /* ── Form title ── */
        .form-title {
            text-align: center; font-weight: bold; font-size: 11.5pt;
            padding: 8px 14px 6px; border-bottom: 1px solid #000;
            text-transform: uppercase; letter-spacing: .3px;
        }

        /* ── Info fields ── */
        .info-section { padding: 8px 14px 6px; border-bottom: 1px solid #000; }
        .info-row {
            display: flex; gap: 20px; margin-bottom: 5px;
            align-items: baseline; flex-wrap: wrap;
        }
        .info-label { font-size: 10.5pt; white-space: nowrap; }
        .info-value {
            font-size: 10.5pt; border-bottom: 1px solid #000;
            min-width: 220px; padding-bottom: 1px;
        }

        /* ── Section heading ── */
        .section-heading {
            font-weight: bold; font-size: 10.5pt;
            padding: 5px 14px 3px; text-transform: uppercase;
        }

        /* ── Tables ── */
        table { width: 100%; border-collapse: collapse; font-size: 10pt; }
        th, td { border: 1px solid #000; padding: 4px 6px; text-align: center; }
        th { font-weight: bold; background: #fff; }
        td.row-label { text-align: left; }

        .soil-table th { font-size: 10pt; }
        .soil-table td { height: 26px; font-size: 10.5pt; font-weight: bold; }

        .fert-table th  { font-size: 9.5pt; }
        .fert-table td  { height: 24px; font-size: 9.5pt; }
        .fert-table .col-label { min-width: 190px; text-align: left; padding-left: 8px; }

        .totals-row td { font-weight: bold; background: #f5f5f5; }

        /* ── Target summary box ── */
        .target-box {
            margin: 0 14px 0; padding: 5px 10px; font-size: 9pt;
            border: 1px dashed #888; border-top: none;
            background: #fafafa; color: #444;
            display: flex; gap: 20px; flex-wrap: wrap;
        }
        .target-box span { white-space: nowrap; }

        /* ── Notes ── */
        .notes-section {
            padding: 5px 14px 6px; font-size: 9.5pt; border-top: 1px solid #000;
        }

        /* ── Signatures ── */
        .sig-section {
            display: flex; justify-content: space-between;
            padding: 16px 30px 10px; border-top: 1px solid #000;
        }
        .sig-block  { text-align: center; }
        .sig-label  { font-size: 9.5pt; color: #333; margin-bottom: 24px; }
        .sig-name   { font-weight: bold; font-size: 10.5pt; }
        .sig-title  { font-size: 9.5pt; }

        /* ── Print overrides ── */
        @media print {
            body { padding: 0; font-size: 10pt; }
            .no-print { display: none !important; }
            .form-page { border: none; max-width: 100%; }
            @page { size: A4 portrait; margin: 12mm 15mm; }
            table { font-size: 9pt; }
        }
    </style>
</head>
<body>

{{-- Screen-only controls --}}
<div class="no-print">
    <button class="print-btn" onclick="window.print()">&#128438; Print / Save as PDF</button>
    <a class="back-link" href="{{ route('samples.show', $sample) }}">&larr; Back to Sample</a>
</div>

<div class="form-page">

    {{-- ── HEADER ── --}}
    <div class="header">
        <div class="header-logo" style="display:flex;align-items:center;justify-content:center;">
            <div style="width:68px;height:68px;border:2px solid #000;border-radius:50%;
                        display:flex;align-items:center;justify-content:center;font-size:8pt;
                        text-align:center;color:#555;padding:4px;">
                MUNI<br>SEAL
            </div>
        </div>

        <div class="header-text">
            <div class="republic">Republic of the Philippines</div>
            <div class="province">Province of Occidental Mindoro</div>
            <div class="muni">Municipality of Sablayan</div>
            <div class="olo">o0o</div>
            <div class="office">Office of the Municipal Agriculturist</div>
        </div>

        <div class="header-code">
            <div>Code &nbsp;&nbsp; MGSBY-SOO-FORM NO.4</div>
            <div>Effectivity &nbsp;March 1, 2025</div>
            <div>Version &nbsp;&nbsp; 2</div>
        </div>
    </div>

    {{-- ── FORM TITLE ── --}}
    <div class="form-title">
        Soil Test Result and Fertilizer Recommendation / Schedule
    </div>

    {{-- ── FARMER INFO ── --}}
    <div class="info-section">
        <div class="info-row">
            <span class="info-label">Name of Farmer:</span>
            <span class="info-value">{{ $sample->farmer_name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Address:</span>
            <span class="info-value">{{ $sample->address }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Farm Location / Area (ha):</span>
            <span class="info-value">{{ $sample->location }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Date Tested:</span>
            <span class="info-value">
                {{ $sample->date_tested ? $sample->date_tested->format('F j, Y') : '—' }}
            </span>
        </div>
        <div class="info-row">
            <span class="info-label">Crop:</span>
            <span class="info-value" style="font-weight:bold;">
                {{ strtoupper($crop->name) }}
            </span>
        </div>
    </div>

    {{-- ── SOIL TEST ANALYSIS RESULT ── --}}
    <div class="section-heading">Soil Test Analysis Result:</div>
    <table class="soil-table">
        <thead>
            <tr>
                <th>Soil pH</th>
                <th>Nitrogen (N)</th>
                <th>Phosphorus (P)</th>
                <th>Potassium (K)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ !is_null($sample->ph_level) ? number_format($sample->ph_level, 2) : '—' }}</td>
                <td>
                    {{ $nStatus ?? '—' }}
                    @if(!is_null($sample->nitrogen_level))
                    <br><span style="font-size:8.5pt;font-weight:normal;color:#555;">{{ number_format($sample->nitrogen_level, 1) }} kg/ha</span>
                    @endif
                </td>
                <td>
                    {{ $pStatus ?? '—' }}
                    @if(!is_null($sample->phosphorus_level))
                    <br><span style="font-size:8.5pt;font-weight:normal;color:#555;">{{ number_format($sample->phosphorus_level, 1) }} kg/ha</span>
                    @endif
                </td>
                <td>
                    {{ $kStatus ?? '—' }}
                    @if(!is_null($sample->potassium_level))
                    <br><span style="font-size:8.5pt;font-weight:normal;color:#555;">{{ number_format($sample->potassium_level, 1) }} kg/ha</span>
                    @endif
                </td>
            </tr>
        </tbody>
    </table>

    {{-- ── NPK TARGET SUMMARY ── --}}
    @if(isset($targets))
    @php
        $lmhLabel = fn(?string $s) => match(strtolower((string)$s)) {
            'low'    => 'Low',
            'medium' => 'Med',
            'high'   => 'High',
            default  => '—',
        };
        $lmhCol = fn(?string $s, string $nutrient) => match(strtolower((string)$s)) {
            'low'    => "{$nutrient}_low",
            'medium' => "{$nutrient}_med",
            'high'   => "{$nutrient}_high",
            default  => "{$nutrient}_med",
        };
    @endphp
    <div class="target-box" style="margin-top:5px;border-top:dashed 1px gray;">
        <span><strong>NPK Targets (kg/ha) — matched to {{ $crop->name }} LMH schedule:</strong></span>
        <span>
            N: <strong>{{ number_format($targets['n'], 0) }}</strong>
        </span>
        <span>
            P: <strong>{{ number_format($targets['p'], 0) }}</strong>
        </span>
        <span>
            K: <strong>{{ number_format($targets['k'], 0) }}</strong>
        </span>
    </div>
    @endif

    {{-- ── FERTILIZER RECOMMENDATION AND APPLICATION SCHEDULE ── --}}
    @php
        $useNonUreaBasal = isset($basalFertInfo) && ($basalFertInfo['key'] ?? 'urea') !== 'urea';
        $basalFertName   = $basalFertInfo['name'] ?? 'Urea (46-0-0)';

        $fmtKgBag = function ($kg) {
            if ($kg === null || $kg <= 0) return '—';
            $bags = $kg / 50;
            return number_format($kg, 2) . ' kg/ha<br><span style="font-size:8.5pt;color:#555;">'
                . number_format($bags, 2) . ' bag' . ($bags == 1 ? '' : 's') . '/ha</span>';
        };
    @endphp

    <div class="section-heading" style="padding-top:8px;">
        Fertilizer Recommendation and Application Schedule (kg/ha · 1 bag = 50 kg):
    </div>

    <table class="fert-table">
        <thead>
            <tr>
                <th class="col-label">Application Period</th>
                @if($useNonUreaBasal)
                    <th>{{ $basalFertName }}<br><small>(Basal · kg/ha · bags/ha)</small></th>
                    <th>UREA 46-0-0<br><small>(Topdress · kg/ha · bags/ha)</small></th>
                @else
                    <th>UREA 46-0-0<br><small>(kg/ha · bags/ha)</small></th>
                @endif
                <th>DAP 18-46-0<br><small>(kg/ha · bags/ha)</small></th>
                <th>MOP 0-0-60<br><small>(kg/ha · bags/ha)</small></th>
            </tr>
        </thead>
        <tbody>
            @foreach($scheduleMatrix as $order => $app)
            <tr>
                <td class="col-label row-label">{{ $app['label'] }}</td>
                @if($useNonUreaBasal)
                    <td>{!! $fmtKgBag($app['basal_fert'] ?? 0) !!}</td>
                    <td>{!! $fmtKgBag($app['urea']       ?? 0) !!}</td>
                @else
                    <td>{!! $fmtKgBag($app['urea'] ?? 0) !!}</td>
                @endif
                <td>{!! $fmtKgBag($app['dap'] ?? 0) !!}</td>
                <td>{!! $fmtKgBag($app['mop'] ?? 0) !!}</td>
            </tr>
            @endforeach

            {{-- Totals row --}}
            @if(isset($totals))
            <tr class="totals-row">
                <td class="col-label row-label">TOTAL</td>
                @if($useNonUreaBasal)
                    <td>{!! $fmtKgBag($totals['basal_fert'] ?? 0) !!}</td>
                    <td>{!! $fmtKgBag($totals['urea']       ?? 0) !!}</td>
                @else
                    <td>{!! $fmtKgBag($totals['urea'] ?? 0) !!}</td>
                @endif
                <td>{!! $fmtKgBag($totals['dap'] ?? 0) !!}</td>
                <td>{!! $fmtKgBag($totals['mop'] ?? 0) !!}</td>
            </tr>
            @endif
        </tbody>
    </table>

    {{-- ── NOTES ── --}}
    <div class="notes-section">
        <div>*DAT – Days After Transplanting &nbsp;|&nbsp; DAP – Days After Planting &nbsp;|&nbsp; 1 bag = 50 kg</div>

        @if($useNonUreaBasal)
        <div style="margin-top:3px; font-style:italic; color:#555;">
            Basal (1st application): <strong>{{ $basalFertName }}</strong> —
            Topdress (2nd &amp; 3rd): Urea (46-0-0)
        </div>
        @endif

        @if(strtoupper($crop->name) === 'RICE')
        <div style="margin-left:8px; margin-top:2px;">
            Note: If direct seeding, 1<sup>st</sup> app 0–2 DAS,
            2<sup>nd</sup> app 21–30 DAS and 3<sup>rd</sup> app 50–60 DAS
        </div>
        @endif

        @if(strtoupper($crop->name) === 'BEANS')
        <div style="margin-top:4px; font-style:italic; color:#555;">
            Note: For beans, a 3rd application is rarely needed. Omit if crop
            is performing well after the 2nd application.
        </div>
        @endif

        @if(isset($sample->ph_level) && $sample->ph_level < 5.5)
        <div style="margin-top:4px; font-weight:bold; color:#b91c1c;">
            ⚠ pH Advisory: Soil pH is acidic ({{ number_format($sample->ph_level, 2) }}).
            This must be addressed for optimal crop performance.
        </div>
        @endif

        <div style="margin-top:5px; font-style:italic;">
            This analysis applies to <strong>{{ $crop->name }}</strong> only.
            Fertilizer rates and timing may differ for other crops.
        </div>
    </div>

    {{-- ── SIGNATURES ── --}}
    <div class="sig-section">
        <div class="sig-block">
            <div class="sig-label">Recommended by:</div>
            <div class="sig-name">JOEL M. ORDENES / JOHN HAROLD S. TAPAR</div>
            <div class="sig-title">Agriculturist II &nbsp;&nbsp;&nbsp; Agri. Technician</div>
        </div>
        <div class="sig-block">
            <div class="sig-label">Noted by:</div>
            <div class="sig-name">PETER L. GALLINERA</div>
            <div class="sig-title">Municipal Agriculturist</div>
        </div>
    </div>

</div>

</body>
</html>