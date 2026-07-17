<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CropFertilizerSchedule extends Model
{
    protected $fillable = [
        'crop_id', 'application_order', 'application_label',
        'fertilizer_type', 'bags', 'notes',
    ];

    protected $casts = [
        'bags' => 'float',
    ];

    public function crop()
    {
        return $this->belongsTo(Crop::class);
    }

    public static function fertilizerTypes(): array
    {
        return ['14-14-14', '46-0-0', '21-0-0', '25-0-0', '16-20-0', '0-0-60'];
    }

    /**
     * Return schedule for a crop as a nested array:
     * [ application_order => [ fertilizer_type => bags, ... ], ... ]
     * Also includes application_label keyed by order.
     */
    public static function matrixForCrop(int $cropId): array
    {
        $rows = static::where('crop_id', $cropId)
            ->orderBy('application_order')
            ->orderBy('fertilizer_type')
            ->get();

        $matrix = [];
        foreach ($rows as $row) {
            $o = $row->application_order;
            if (!isset($matrix[$o])) {
                $matrix[$o] = ['label' => $row->application_label, 'fertilizers' => []];
            }
            $matrix[$o]['fertilizers'][$row->fertilizer_type] = $row->bags;
        }

        return $matrix;
    }
}
