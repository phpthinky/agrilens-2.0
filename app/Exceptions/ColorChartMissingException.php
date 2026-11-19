<?php

namespace App\Exceptions;

use Illuminate\Http\Request;
use RuntimeException;

/**
 * A color chart has no active reference colors. Color charts come only from
 * the database (Admin › Color Calibration) — there is deliberately no
 * built-in fallback, so a missing chart stops the reading instead of
 * silently matching against colors nobody calibrated.
 */
class ColorChartMissingException extends RuntimeException
{
    public function __construct(public readonly string $chart)
    {
        parent::__construct(
            "No active {$chart} reference colors. An admin must add them in Color Calibration before this test can be read."
        );
    }

    public function render(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => $this->getMessage()], 422);
        }

        return back()->with('error', $this->getMessage());
    }
}
