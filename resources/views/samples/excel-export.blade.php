<html xmlns:o="urn:schemas-microsoft-com:office:office"
      xmlns:x="urn:schemas-microsoft-com:office:excel"
      xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta charset="UTF-8">
    <!--[if gte mso 9]>
    <xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet>
    <x:Name>Soil Test Report</x:Name>
    <x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions>
    </x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml>
    <![endif]-->
    <style>
        body  { font-family: Arial, Helvetica, sans-serif; font-size: 10pt; }
        table { border-collapse: collapse; width: 100%; }
        td, th { font-family: Arial, Helvetica, sans-serif; font-size: 10pt; vertical-align: middle; }
        .outer { border: 2px solid #000; width: 740px; margin: 0 auto; }

        /* Header */
        .hdr-seal { width: 80px; text-align: center; padding: 8px 6px; border: none; }
        .hdr-text  { text-align: center; padding: 8px 4px; border: none; }
        .hdr-code  { text-align: right; font-size: 8pt; white-space: nowrap; padding: 8px 8px 8px 4px; vertical-align: top; border: none; }

        /* Form title */
        .form-title { text-align: center; font-weight: bold; font-size: 11pt;
                      text-transform: uppercase; letter-spacing: 0.3px;
                      padding: 6px 14px; border-top: 2px solid #000; border-bottom: 1px solid #000; }

        /* Info section */
        .info-label { font-size: 10pt; white-space: nowrap; padding: 4px 6px 4px 14px;
                      border: none; width: 1px; }
        .info-value { font-size: 10pt; border-bottom: 1px solid #000; padding: 4px 6px 2px 2px;
                      border-top: none; border-left: none; border-right: none; }
        .info-spacer { border: none; width: 10px; }

        /* Section heading */
        .sec-head { font-weight: bold; font-size: 10pt; text-transform: uppercase;
                    padding: 6px 14px 3px; border: none; }

        /* Soil test table */
        .soil-th { font-weight: bold; font-size: 10pt; background: #d9e1f2;
                   border: 1px solid #000; text-align: center; padding: 5px 8px; }
        .soil-td { font-size: 11pt; font-weight: bold; border: 1px solid #000;
                   text-align: center; padding: 6px 8px; height: 30px; }

        /* Fertilizer schedule table */
        .fert-th { font-weight: bold; font-size: 9.5pt; background: #e2efda;
                   border: 1px solid #000; text-align: center; padding: 4px 6px; }
        .fert-th-label { font-weight: bold; font-size: 9.5pt; background: #e2efda;
                         border: 1px solid #000; text-align: left; padding: 4px 8px; min-width: 180px; }
        .fert-td { font-size: 9.5pt; border: 1px solid #000; text-align: center;
                   padding: 4px 6px; height: 26px; }
        .fert-td-label { font-size: 9.5pt; border: 1px solid #000; text-align: left;
                         padding: 4px 8px; }

        /* Notes */
        .notes-cell { font-size: 9pt; padding: 5px 14px 6px; border-top: 1px solid #000;
                      border-bottom: 1px solid #000; border-left: none; border-right: none; }

        /* Signatures */
        .sig-label { font-size: 9pt; color: #333; }
        .sig-name  { font-weight: bold; font-size: 10pt; }
        .sig-title { font-size: 9pt; }
        .sig-cell  { text-align: center; padding: 20px 20px 10px; border: none; width: 50%; }
    </style>
</head>
<body>
<table class="outer">

    {{-- ── HEADER ── --}}
    <tr style="border-bottom: 2px solid #000;">
        <td class="hdr-seal">
            <div style="width:64px;height:64px;border:2px solid #000;border-radius:50%;
                        display:table-cell;vertical-align:middle;text-align:center;
                        font-size:7.5pt;color:#555;margin:0 auto;padding:4px;">
                MUNI<br>SEAL
            </div>
        </td>
        <td class="hdr-text">
            <div style="font-size:10pt;">Republic of the Philippines</div>
            <div style="font-size:10pt;">Province of Occidental Mindoro</div>
            <div style="font-size:10.5pt;font-weight:bold;">Municipality of Sablayan</div>
            <div style="font-size:10pt;">o0o</div>
            <div style="font-size:11pt;font-weight:bold;">Office of the Municipal Agriculturist</div>
        </td>
        <td class="hdr-code">
            <div>Code &nbsp;&nbsp; MGSBY-SOO-FORM NO.4</div>
            <div>Effectivity &nbsp; March 1, 2025</div>
            <div>Version &nbsp;&nbsp; 2</div>
        </td>
    </tr>

    {{-- ── FORM TITLE ── --}}
    <tr>
        <td colspan="3" class="form-title">
            Soil Test Result and Fertilizer Recommendation / Schedule
        </td>
    </tr>

    {{-- ── FARMER INFO ── --}}
    <tr>
        <td colspan="3" style="padding:0;border:none;">
            <table style="width:100%;border-collapse:collapse;">
                <tr>
                    <td class="info-label">Name of Farmer:</td>
                    <td class="info-value">{{ $sample->farmer_name }}</td>
                    <td class="info-spacer"></td>
                    <td class="info-label">Date Tested:</td>
                    <td class="info-value">{{ $sample->date_tested ? $sample->date_tested->format('F j, Y') : '—' }}</td>
                </tr>
                <tr>
                    <td class="info-label">Address:</td>
                    <td class="info-value">{{ $sample->address }}</td>
                    <td class="info-spacer"></td>
                    <td class="info-label">Farm Location / Area (ha):</td>
                    <td class="info-value">{{ $sample->location }}</td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- ── SOIL TEST ANALYSIS RESULT ── --}}
    <tr>
        <td colspan="3" class="sec-head">Soil Test Analysis Result:</td>
    </tr>
    <tr>
        <td colspan="3" style="padding:0;border:none;">
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr>
                        <th class="soil-th">Soil pH</th>
                        <th class="soil-th">Nitrogen (N)</th>
                        <th class="soil-th">Phosphorus (P)</th>
                        <th class="soil-th">Potassium (K)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="soil-td">
                            @if(!is_null($sample->ph_level))
                                {{ number_format($sample->ph_level, 2) }}
                            @endif
                        </td>
                        <td class="soil-td">{{ $nStatus ?? '—' }}</td>
                        <td class="soil-td">{{ $pStatus ?? '—' }}</td>
                        <td class="soil-td">{{ $kStatus ?? '—' }}</td>
                    </tr>
                </tbody>
            </table>
        </td>
    </tr>

    {{-- ── FERTILIZER RECOMMENDATION AND APPLICATION SCHEDULE ── --}}
    <tr>
        <td colspan="3" class="sec-head" style="padding-top:8px;">
            Fertilizer Recommendation and Application Schedule per Hectare:
        </td>
    </tr>
    <tr>
        <td colspan="3" style="padding:0;border:none;">
            @php
                $defaultRows = [
                    1 => 'First Application (0-14 DAT*)',
                    2 => 'Second Application (22-26 DAT)',
                    3 => 'Third Application (32-36 DAT)',
                ];
                $rows = !empty($scheduleMatrix)
                    ? $scheduleMatrix
                    : array_map(fn($l) => ['label' => $l, 'fertilizers' => []], $defaultRows);
                ksort($rows);
            @endphp
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr>
                        <th class="fert-th-label">No. of kg/ha</th>
                        @foreach($fertTypes as $t)<th class="fert-th">{{ $t }}</th>@endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $order => $app)
                    <tr>
                        <td class="fert-td-label">{{ $app['label'] }}</td>
                        @foreach($fertTypes as $t)
                        @php $v = $app['fertilizers'][$t] ?? null; @endphp
                        <td class="fert-td">{{ ($v !== null && $v > 0) ? $v : '' }}</td>
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </td>
    </tr>

    {{-- ── NOTES ── --}}
    <tr>
        <td colspan="3" class="notes-cell">
            <div>*DAT - Days After Transplanting</div>
            <div style="margin-left:8px;">
                Note: If direct seeding, 1st app 10-14 DAS, 2nd app 32-36 DAS and 3rd app 48-52 DAS
            </div>
            @if(!is_null($sample->ph_level) && $sample->ph_level < 5.5)
            <div style="margin-top:4px;font-weight:bold;">
                ⚠ pH Advisory: Soil pH is acidic — this must be addressed, as some crops may not thrive or may not perform at their best under acidic soil conditions.
            </div>
            @endif
            <div style="margin-top:5px;font-style:italic;">
                This analysis applies to Rice (Oryza sativa) only.
                Fertilizer rates and timing may differ for other crops.
            </div>
        </td>
    </tr>

    {{-- ── SIGNATURES ── --}}
    <tr style="border-top: 1px solid #000;">
        <td colspan="3" style="padding:0;border:none;">
            <table style="width:100%;border-collapse:collapse;">
                <tr>
                    <td class="sig-cell">
                        <div class="sig-label">Recommended by:</div>
                        <div style="height:30px;"></div>
                        <div class="sig-name">JOEL M. ORDENES / JOHN HAROLD S. TAPAR</div>
                        <div class="sig-title">Agriculturist II &nbsp;&nbsp;&nbsp; Agri. Technician</div>
                    </td>
                    <td class="sig-cell">
                        <div class="sig-label">Noted by:</div>
                        <div style="height:30px;"></div>
                        <div class="sig-name">PETER L. GALLINERA</div>
                        <div class="sig-title">Municipal Agriculturist</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

</table>
</body>
</html>
