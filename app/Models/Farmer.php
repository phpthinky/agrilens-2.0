<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Farmer extends Model
{
    protected $fillable = [
        'barangay_id', 'first_name', 'middle_name', 'last_name', 'suffix', 'gender',
        'birth_date', 'contact_number', 'email', 'address', 'id_type', 'id_number',
        'farmer_type', 'years_farming', 'crops_grown', 'total_farm_area',
        'education_level', 'is_active', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'total_farm_area' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class);
    }

    public function farms(): HasMany
    {
        return $this->hasMany(Farm::class);
    }

    public function soilSamples(): HasManyThrough
    {
        return $this->hasManyThrough(SoilSample::class, Farm::class);
    }

    public function getFullNameAttribute(): string
    {
        $name = implode(' ', array_filter([$this->first_name, $this->middle_name, $this->last_name]));
        return $this->suffix ? $name . ' ' . $this->suffix : $name;
    }

    public function getAgeAttribute(): ?int
    {
        return $this->birth_date ? $this->birth_date->age : null;
    }

    public function getCropsListAttribute(): array
    {
        if (empty($this->crops_grown)) {
            return [];
        }

        if (json_decode($this->crops_grown)) {
            return json_decode($this->crops_grown, true);
        }

        return array_filter(array_map('trim', explode(',', $this->crops_grown)));
    }

    public function getFormattedCropsAttribute(): string
    {
        $crops = $this->crops_list;
        return empty($crops) ? 'None specified' : implode(', ', $crops);
    }

    public function getFarmsCountAttribute(): int
    {
        return $this->farms()->count();
    }

    public function getActiveFarmsCountAttribute(): int
    {
        return $this->farms()->active()->count();
    }

    public function getTotalFarmAreaAttribute(): float
    {
        return $this->farms()->active()->sum('area_hectares') ?? 0;
    }

    public function getFormattedTotalFarmAreaAttribute(): string
    {
        $area = $this->total_farm_area;
        return $area > 0 ? number_format($area, 4) . ' ha' : 'N/A';
    }

    public function getSoilSamplesCountAttribute(): int
    {
        return $this->soilSamples()->count();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByBarangay($query, $barangayId)
    {
        return $query->where('barangay_id', $barangayId);
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('first_name', 'like', "%{$term}%")
              ->orWhere('middle_name', 'like', "%{$term}%")
              ->orWhere('last_name', 'like', "%{$term}%")
              ->orWhere('contact_number', 'like', "%{$term}%")
              ->orWhere('email', 'like', "%{$term}%");
        });
    }
}
