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

    /** The technician's open work queue — not yet completed or cancelled. Overdue items stay in here too. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['pending', 'in_progress']);
    }

    public function scopeForTechnician(Builder $query, User $technician): Builder
    {
        return $query->where('assigned_technician_id', $technician->id);
    }

    /**
     * Calendar-date comparison (not a timestamp diff) so "today" means the
     * project's configured local date (Asia/Manila via config('app.timezone')),
     * regardless of what time of day it is.
     */
    public function isOverdue(): bool
    {
        return in_array($this->status, ['pending', 'in_progress'])
            && $this->scheduled_date->lt(now()->startOfDay());
    }

    public function canStartToday(): bool
    {
        return in_array($this->status, ['pending', 'in_progress'])
            && $this->scheduled_date->isSameDay(now());
    }

    /** pending | in_progress | completed | cancelled | overdue (overdue is derived, never stored). */
    public function displayStatus(): string
    {
        if (in_array($this->status, ['completed', 'cancelled'])) {
            return $this->status;
        }

        return $this->isOverdue() ? 'overdue' : $this->status;
    }

    /** Null when testing is currently permitted; otherwise the message to show the technician. */
    public function lockMessage(): ?string
    {
        if (in_array($this->status, ['completed', 'cancelled'])) {
            return null;
        }

        if ($this->isOverdue()) {
            return 'This scheduled soil test is overdue. Please contact the authorized administrator to reschedule it.';
        }

        if (!$this->canStartToday()) {
            return "This soil test is scheduled for {$this->scheduled_date->format('F j, Y')}. Testing will be available on the scheduled date.";
        }

        return null;
    }

    public function markInProgress(): void
    {
        if ($this->status === 'pending') {
            $this->update(['status' => 'in_progress']);
        }
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
