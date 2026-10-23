<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FertilizerScheduleStage extends Model
{
    protected $fillable = [
        'crop_id', 'stage', 'label', 'n_fraction', 'p_fraction', 'k_fraction',
    ];

    protected function casts(): array
    {
        return [
            'n_fraction' => 'float',
            'p_fraction' => 'float',
            'k_fraction' => 'float',
        ];
    }

    public function crop(): BelongsTo
    {
        return $this->belongsTo(Crop::class);
    }
}
