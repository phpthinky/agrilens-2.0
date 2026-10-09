<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class SoilTestAssignment extends Model
{
    protected $fillable = [
        'farm_id', 'assigned_technician_id', 'scheduled_date', 'status', 'assigned_by',
        'completed_by', 'soil_sample_id', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
        ];
    }

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_technician_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function soilSample(): BelongsTo
    {
        return $this->belongsTo(SoilSample::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeForTechnician(Builder $query, User $technician): Builder
    {
        return $query->where('assigned_technician_id', $technician->id);
    }

    public function markCompleted(User $technician, SoilSample $sample): void
    {
        $this->update([
            'status' => 'completed',
            'completed_by' => $technician->id,
            'soil_sample_id' => $sample->id,
        ]);
    }
}
