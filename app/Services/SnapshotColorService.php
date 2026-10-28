<?php

namespace App\Services;

use RuntimeException;

/**
 * Server-side twin of the browser colorimeter's color pick.
 *
 * The web pages draw the webcam frame onto a 320×240 canvas and average the
 * 70×70 px square in its centre (the on-screen crosshair). The field app only
 * uploads the raw photo from the colorimeter box's USB webcam, so the same
 * pick is reproduced here — the server stays the single place where a photo
 * becomes a color, and from there a pH / ppm value.
 */
class SnapshotColorService
{
    public const CANVAS_WIDTH  = 320;
    public const CANVAS_HEIGHT = 240;
    public const ZONE_SIZE     = 70;

    /** @return array{hex: string, r: int, g: int, b: int} */
    public function sampleCenter(string $imageBytes): array
    {
        $source = @imagecreatefromstring($imageBytes);
        if ($source === false) {
            throw new RuntimeException('The photo could not be decoded.');
        }

        $canvas = imagecreatetruecolor(self::CANVAS_WIDTH, self::CANVAS_HEIGHT);
        imagecopyresampled(
            $canvas, $source,
            0, 0, 0, 0,
            self::CANVAS_WIDTH, self::CANVAS_HEIGHT,
            imagesx($source), imagesy($source)
        );
        imagedestroy($source);

        $x0 = intdiv(self::CANVAS_WIDTH, 2) - intdiv(self::ZONE_SIZE, 2);
        $y0 = intdiv(self::CANVAS_HEIGHT, 2) - intdiv(self::ZONE_SIZE, 2);
        $r = $g = $b = 0;

        for ($y = $y0; $y < $y0 + self::ZONE_SIZE; $y++) {
            for ($x = $x0; $x < $x0 + self::ZONE_SIZE; $x++) {
                $rgb = imagecolorat($canvas, $x, $y);
                $r += ($rgb >> 16) & 0xFF;
                $g += ($rgb >> 8) & 0xFF;
                $b += $rgb & 0xFF;
            }
        }
        imagedestroy($canvas);

        $n = self::ZONE_SIZE * self::ZONE_SIZE;
        $r = (int) round($r / $n);
        $g = (int) round($g / $n);
        $b = (int) round($b / $n);

        return [
            'hex' => sprintf('#%02X%02X%02X', $r, $g, $b),
            'r'   => $r,
            'g'   => $g,
            'b'   => $b,
        ];
    }
}
