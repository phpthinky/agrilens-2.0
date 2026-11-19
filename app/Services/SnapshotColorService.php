<?php

namespace App\Services;

use RuntimeException;

/**
 * Server-side twin of the browser colorimeter's color pick
 * (public/js/colorimeter-sample.js — keep the two in step).
 *
 * The web pages draw the webcam frame onto a 320×240 canvas and pick the
 * color there. The field app only uploads the raw photo from the colorimeter
 * box's USB webcam, so the same pick is reproduced here — the server stays
 * the single place where a photo becomes a color, and from there a pH / ppm
 * value.
 *
 * The box holds the tube upright at its centre, close to the camera, so the
 * tube fills about a quarter of the frame and the LEDs reflect off the glass
 * near its edges. The pick therefore finds the tube by color saturation,
 * averages only the middle 40 % of its width, and drops glints and specks.
 * A colorless liquid falls back to a narrow centre strip.
 */
class SnapshotColorService
{
    public const CANVAS_WIDTH  = 320;
    public const CANVAS_HEIGHT = 240;

    private const MARGIN           = 0.10;
    private const BAND             = 4;
    private const SMOOTH           = 2;
    private const SAT_MIN          = 40;
    private const SAT_ABOVE_MEDIAN = 30;
    private const MIN_TUBE         = 0.05;
    private const MAX_OFFSET       = 0.25;
    private const MIN_BANDS        = 0.25;
    private const CORE             = 0.20;
    private const STRIP            = 30 / 320;
    private const DROP_DARK        = 0.03;
    private const DROP_BRIGHT      = 0.95;
    private const GLARE            = 250;
    private const MIN_PIXELS       = 50;
    private const DARK             = 40;
    private const BAND_DIM         = 0.6;
    private const BAND_PALE        = 0.6;

    /**
     * @return array{hex: string, r: int, g: int, b: int, method: string, pixels: int, bands: int}
     */
    public function sampleTube(string $imageBytes): array
    {
        $source = @imagecreatefromstring($imageBytes);
        if ($source === false) {
            throw new RuntimeException('The photo could not be decoded.');
        }

        $w = self::CANVAS_WIDTH;
        $h = self::CANVAS_HEIGHT;
        $canvas = imagecreatetruecolor($w, $h);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $w, $h, imagesx($source), imagesy($source));
        imagedestroy($source);

        // Read the canvas once into flat channel arrays.
        $R = $G = $B = [];
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $rgb = imagecolorat($canvas, $x, $y);
                $R[] = ($rgb >> 16) & 0xFF;
                $G[] = ($rgb >> 8) & 0xFF;
                $B[] = $rgb & 0xFF;
            }
        }
        imagedestroy($canvas);

        $top    = (int) round($h * self::MARGIN);
        $bottom = $h - $top;

        $bands = [];
        $total = 0;
        for ($y = $top; $y + self::BAND <= $bottom; $y += self::BAND) {
            $total++;
            $run = $this->bandRun($R, $G, $B, $w, $y, $y + self::BAND);
            if ($run !== null) {
                $bands[] = [$y, $run[0], $run[1]];
            }
        }

        $boxes = [];
        foreach ($bands as [$y, $x1, $x2]) {
            $c    = intdiv($x1 + $x2, 2);
            $half = max(2, (int) floor(($x2 - $x1 + 1) * self::CORE));
            $boxes[] = [$c - $half, $c + $half, $y, $y + self::BAND];
        }
        // Drop bands that don't look like the rest of the liquid: far darker
        // (cap or label edge, shadow at the surface) or far paler (glass rim,
        // the holder below the liquid, air above it).
        if ($boxes) {
            $stats = array_map(fn ($b) => $this->boxStats($R, $G, $B, $w, $b), $boxes);
            $mv    = $this->median(array_column($stats, 0));
            $ms    = $this->median(array_column($stats, 1));
            $boxes = array_values(array_filter(
                $boxes,
                fn ($b, $i) => $stats[$i][0] >= $mv * self::BAND_DIM && $stats[$i][1] >= $ms * self::BAND_PALE,
                ARRAY_FILTER_USE_BOTH
            ));
        }

        $tube = count($boxes) >= (int) ceil($total * self::MIN_BANDS);
        if (!$tube) {
            $boxes = [];
            $sw      = max(4, (int) round($w * self::STRIP));
            $x1      = intdiv($w - $sw, 2);
            $boxes[] = [$x1, $x1 + $sw, $top, $bottom];
        }

        $px = [];
        foreach ($boxes as [$x1, $x2, $y1, $y2]) {
            for ($y = $y1; $y < $y2; $y++) {
                for ($x = $x1; $x < $x2; $x++) {
                    $i    = $y * $w + $x;
                    $px[] = [$R[$i], $G[$i], $B[$i], max($R[$i], $G[$i], $B[$i])];
                }
            }
        }

        $vs = array_column($px, 3);
        sort($vs);
        $n  = count($vs);
        $lo = $vs[(int) floor($n * self::DROP_DARK)];
        $hi = $vs[(int) floor($n * self::DROP_BRIGHT)];

        $keep = array_filter($px, fn ($p) => $p[3] >= $lo && $p[3] <= $hi && $p[3] < self::GLARE);
        if (count($keep) < self::MIN_PIXELS) {
            $keep = array_filter($px, fn ($p) => $p[3] < self::GLARE);
        }
        if (count($keep) < self::MIN_PIXELS) {
            $keep = $px;
        }

        $k = count($keep);
        $r = (int) round(array_sum(array_column($keep, 0)) / $k);
        $g = (int) round(array_sum(array_column($keep, 1)) / $k);
        $b = (int) round(array_sum(array_column($keep, 2)) / $k);

        return [
            'hex'    => sprintf('#%02X%02X%02X', $r, $g, $b),
            'r'      => $r,
            'g'      => $g,
            'b'      => $b,
            'method' => $tube ? 'tube' : 'center',
            'pixels' => $k,
            'bands'  => $tube ? count($boxes) : 0,
        ];
    }

    /** @return array{0: float, 1: float} [mean V, saturation of the mean color] of a box [x1, x2) × [y1, y2). */
    private function boxStats(array $R, array $G, array $B, int $w, array $box): array
    {
        [$x1, $x2, $y1, $y2] = $box;
        $v = $r = $g = $b = $n = 0;
        for ($y = $y1; $y < $y2; $y++) {
            for ($x = $x1; $x < $x2; $x++) {
                $i  = $y * $w + $x;
                $v += max($R[$i], $G[$i], $B[$i]);
                $r += $R[$i];
                $g += $G[$i];
                $b += $B[$i];
                $n++;
            }
        }
        $max = max($r, $g, $b);

        return [$v / $n, $max === 0 ? 0.0 : ($max - min($r, $g, $b)) / $max];
    }

    private function median(array $list): float
    {
        sort($list);

        return $list[intdiv(count($list), 2)];
    }

    /** Tube run [x1, x2] in rows [y0, y1), or null. */
    private function bandRun(array $R, array $G, array $B, int $w, int $y0, int $y1): ?array
    {
        $col = array_fill(0, $w, 0);
        for ($y = $y0; $y < $y1; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $i   = $y * $w + $x;
                $max = max($R[$i], $G[$i], $B[$i]);
                $min = min($R[$i], $G[$i], $B[$i]);
                $col[$x] += $max < self::DARK ? 0 : intdiv(($max - $min) * 255, $max);
            }
        }

        $rows = $y1 - $y0;
        $s    = [];
        for ($x = 0; $x < $w; $x++) {
            $sum = 0;
            $cnt = 0;
            for ($k = max(0, $x - self::SMOOTH); $k <= min($w - 1, $x + self::SMOOTH); $k++) {
                $sum += $col[$k] / $rows;
                $cnt++;
            }
            $s[$x] = $sum / $cnt;
        }

        $sorted = $s;
        sort($sorted);
        $thr = max(self::SAT_MIN, $sorted[intdiv($w, 2)] + self::SAT_ABOVE_MEDIAN);

        $mid      = intdiv($w, 2);
        $best     = null;
        $bestDist = PHP_INT_MAX;
        for ($x = 0; $x < $w; $x++) {
            if ($s[$x] <= $thr) {
                continue;
            }
            $x2 = $x;
            while ($x2 + 1 < $w && $s[$x2 + 1] > $thr) {
                $x2++;
            }
            $dist = $mid < $x ? $x - $mid : ($mid > $x2 ? $mid - $x2 : 0);
            if ($x2 - $x + 1 >= (int) round($w * self::MIN_TUBE) && $dist < $bestDist) {
                $best     = [$x, $x2];
                $bestDist = $dist;
            }
            $x = $x2;
        }

        if ($best === null || $bestDist > (int) round($w * self::MAX_OFFSET)) {
            return null;
        }

        return $best;
    }
}
