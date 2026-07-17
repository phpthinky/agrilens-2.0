<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Barangay extends Model
{
    protected $fillable = [
        'name', 'code', 'description', 'area_hectares', 'population', 'boundaries', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'boundaries' => 'array',
            'area_hectares' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function farmers(): HasMany
    {
        return $this->hasMany(Farmer::class);
    }

    public function farms(): HasMany
    {
        return $this->hasMany(Farm::class, 'location_barangay_id');
    }

    public function residentOwnedFarms(): HasManyThrough
    {
        return $this->hasManyThrough(Farm::class, Farmer::class);
    }

    public function assignedTechnicians(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'technician_barangay', 'barangay_id', 'technician_id')
            ->withTimestamps();
    }

    public function soilSamples(): HasManyThrough
    {
        return $this->hasManyThrough(
            SoilSample::class,
            Farm::class,
            'location_barangay_id',
            'farm_id',
            'id',
            'id'
        );
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrderByName($query)
    {
        return $query->orderBy('name', 'asc');
    }

    public function getFormattedAreaAttribute(): string
    {
        return $this->area_hectares ? number_format($this->area_hectares, 2) . ' ha' : 'N/A';
    }

    public function getFormattedPopulationAttribute(): string
    {
        return $this->population ? number_format($this->population) : 'N/A';
    }

    public function getFarmersCountAttribute(): int
    {
        return $this->farmers()->count();
    }

    public function getFarmsCountAttribute(): int
    {
        return $this->farms()->count();
    }

    public function getTechniciansCountAttribute(): int
    {
        return $this->assignedTechnicians()->count();
    }

    public function getSoilSamplesCountAttribute(): int
    {
        return $this->soilSamples()->count();
    }

    public function getAverageFertilityAttribute(): float
    {
        $farms = $this->farms()->active()->with('soilSamples')->get();

        $total = 0;
        $count = 0;
        foreach ($farms as $farm) {
            $latest = $farm->soilSamples->sortByDesc('sample_date')->first();
            if ($latest && $latest->fertility_score !== null) {
                $total += $latest->fertility_score;
                $count++;
            }
        }

        return $count > 0 ? round($total / $count, 2) : 0;
    }

    public function hasTechnician(User $technician): bool
    {
        return $this->assignedTechnicians()->where('users.id', $technician->id)->exists();
    }

    public function assignTechnician(User $technician): void
    {
        if (!$this->hasTechnician($technician)) {
            $this->assignedTechnicians()->attach($technician->id);
        }
    }

    public function removeTechnician(User $technician): void
    {
        $this->assignedTechnicians()->detach($technician->id);
    }

    public function canBeDeleted(): bool
    {
        return $this->farmers_count === 0 && $this->farms_count === 0;
    }

    public function getDeletionBlockers(): array
    {
        $blockers = [];

        if ($this->farmers_count > 0) {
            $blockers[] = "{$this->farmers_count} farmer(s) assigned";
        }

        if ($this->farms_count > 0) {
            $blockers[] = "{$this->farms_count} farm(s) located here";
        }

        return $blockers;
    }
}
