<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NpkColorChart;
use App\Models\PhColorChart;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Device color calibration: the admin puts a reference solution in the
 * colorimeter box, captures it with the box camera (same tube-centre pick as
 * the tests), and labels the capture with its pH value or LOW / MEDIUM / HIGH
 * status. Each capture becomes a chart entry (source = 'device'), so the
 * charts hold colors as this camera actually sees them.
 */
class ColorCalibrationController extends Controller
{
    /** Charts that can be calibrated: pH indicators and nutrients. */
    private const TARGETS = ['CPR', 'BCG', 'BTB', 'N', 'P', 'K'];

    public function index()
    {
        $ph  = PhColorChart::orderBy('indicator')->orderBy('ph_value')->get()->groupBy('indicator');
        $npk = NpkColorChart::orderBy('nutrient')->orderBy('ppm_value')->get()->groupBy('nutrient');

        $entries = [];
        foreach (array_keys(PhColorChart::INDICATORS) as $ind) {
            $entries[$ind] = ($ph[$ind] ?? collect())->map(fn ($e) => $this->row($e, 'ph'))->values();
        }
        foreach (array_keys(NpkColorChart::NAMES) as $nut) {
            $entries[$nut] = ($npk[$nut] ?? collect())->map(fn ($e) => $this->row($e, 'npk'))->values();
        }

        return view('admin.color-calibration', [
            'entries'    => $entries,
            'indicators' => PhColorChart::INDICATORS,
            'nutrients'  => NpkColorChart::NAMES,
            'defaultPpm' => NpkColorChart::DEFAULT_PPM,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'target'    => 'required|in:' . implode(',', self::TARGETS),
            'hex'       => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'snapshot'  => 'nullable|string',
            'ph_value'  => 'required_if:target,CPR,BCG,BTB|nullable|numeric|min:3|max:10',
            'category'  => 'required_if:target,N,P,K|nullable|in:low,medium,high',
            'ppm_value' => 'nullable|numeric|min:0|max:9999',
        ]);

        $target = $validated['target'];
        $hex    = strtoupper($validated['hex']);
        $isPh   = array_key_exists($target, PhColorChart::INDICATORS);

        $duplicate = $isPh
            ? PhColorChart::where('indicator', $target)->where('hex_value', $hex)->exists()
            : NpkColorChart::where('nutrient', $target)->where('hex_value', $hex)->exists();
        if ($duplicate) {
            return response()->json([
                'message' => "{$hex} is already in the {$target} chart. Capture again (the color usually shifts slightly) or delete the existing entry.",
            ], 422);
        }

        $snapshot = $this->saveSnapshot($validated['snapshot'] ?? null, $target);

        if ($isPh) {
            $entry = PhColorChart::create([
                'indicator' => $target,
                'ph_value'  => round((float) $validated['ph_value'], 1),
                'hex_value' => $hex,
                'source'    => 'device',
                'snapshot'  => $snapshot,
                'active'    => true,
            ]);
            $row = $this->row($entry, 'ph');
        } else {
            $category = $validated['category'];
            $entry = NpkColorChart::create([
                'nutrient'  => $target,
                'category'  => $category,
                'ppm_value' => $validated['ppm_value'] ?? NpkColorChart::DEFAULT_PPM[$target][$category],
                'hex_value' => $hex,
                'source'    => 'device',
                'snapshot'  => $snapshot,
                'active'    => true,
            ]);
            $row = $this->row($entry, 'npk');
        }

        return response()->json(['message' => "Saved {$hex} to the {$target} chart.", 'entry' => $row]);
    }

    private function row(PhColorChart|NpkColorChart $e, string $kind): array
    {
        return [
            'id'       => $e->id,
            'hex'      => $e->hex_value,
            'value'    => $kind === 'ph' ? number_format($e->ph_value, 1) : number_format($e->ppm_value, 1) . ' ppm',
            'category' => $kind === 'npk' ? $e->category : null,
            'source'   => $e->source,
            'snapshot' => $e->snapshot ? asset($e->snapshot) : null,
            'active'   => (bool) $e->active,
        ];
    }

    /** Store the capture photo under public/captures/calibration/ (no storage symlink needed). */
    private function saveSnapshot(?string $dataUrl, string $target): ?string
    {
        if (!$dataUrl || !str_starts_with($dataUrl, 'data:image/jpeg;base64,')) {
            return null;
        }
        $image = base64_decode(substr($dataUrl, strlen('data:image/jpeg;base64,')), strict: true);
        if ($image === false || strlen($image) > 2 * 1024 * 1024) {
            return null;
        }

        $dir = public_path('captures/calibration');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, recursive: true);
        }
        $file = strtolower($target) . '-' . now()->format('Ymd-His') . '-' . Str::random(6) . '.jpg';
        file_put_contents("{$dir}/{$file}", $image);

        return "captures/calibration/{$file}";
    }
}
