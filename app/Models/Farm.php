<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Farm extends Model
{
    protected $fillable = [
        'farmer_id', 'location_barangay_id', 'farm_name', 'farm_address', 'description',
        'polygon_coordinates', 'manual_latitude', 'manual_longitude',
        'display_latitude', 'display_longitude', 'location_source', 'location_notes',
        'area_hectares', 'farm_type', 'land_tenure', 'irrigation_type', 'slope_category',
        'elevation_meters', 'current_crops', 'established_year', 'is_active', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'polygon_coordinates' => 'array',
            'display_latitude' => 'decimal:8',
            'display_longitude' => 'decimal:8',
            'area_hectares' => 'decimal:4',
            'is_active' => 'boolean',
            'established_year' => 'integer',
        ];
    }

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(Farmer::class);
    }

    public function locationBarangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class, 'location_barangay_id');
    }

    public function soilSamples(): HasMany
    {
        return $this->hasMany(SoilSample::class);
    }

    public function getFormattedAreaAttribute(): string
    {
        return $this->area_hectares ? number_format($this->area_hectares, 4) . ' ha' : 'N/A';
    }

    public function getCenterCoordinatesAttribute(): ?array
    {
        if (!$this->display_latitude || !$this->display_longitude) {
            return null;
        }

        return [
            'lat' => (float) $this->display_latitude,
            'lng' => (float) $this->display_longitude,
        ];
    }

    public function getCurrentCropsListAttribute(): array
    {
        if (empty($this->current_crops)) {
            return [];
        }

        if (json_decode($this->current_crops)) {
            return json_decode($this->current_crops, true);
        }

        return array_filter(array_map('trim', explode(',', $this->current_crops)));
    }

    public function getFormattedCurrentCropsAttribute(): string
    {
        $crops = $this->current_crops_list;
        return empty($crops) ? 'None specified' : implode(', ', $crops);
    }

    public function getFarmAgeAttribute(): ?int
    {
        return $this->established_year ? now()->year - $this->established_year : null;
    }

    public function getIsCrossBoundaryAttribute(): bool
    {
        return $this->farmer->barangay_id !== $this->location_barangay_id;
    }

    public function getFormattedAddressAttribute(): string
    {
        return $this->farm_address ?: 'No address specified';
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByFarmer($query, $farmerId)
    {
        return $query->where('farmer_id', $farmerId);
    }

    public function scopeByLocationBarangay($query, $barangayId)
    {
        return $query->where('location_barangay_id', $barangayId);
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('farm_name', 'like', "%{$term}%")
              ->orWhere('farm_address', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%")
              ->orWhere('current_crops', 'like', "%{$term}%")
              ->orWhereHas('farmer', function ($sq) use ($term) {
                  $sq->where('first_name', 'like', "%{$term}%")
                     ->orWhere('last_name', 'like', "%{$term}%");
              });
        });
    }

    /**
     * Shoelace formula, converted from decimal degrees to hectares.
     * 1 degree ~= 111,320 meters at the equator.
     */
    public function calculateAreaFromPolygon(): ?float
    {
        if (empty($this->polygon_coordinates) || count($this->polygon_coordinates) < 4) {
            return null;
        }

        if (!$this->display_latitude) {
            return null;
        }

        $coordinates = $this->polygon_coordinates;
        $area = 0;
        $n = count($coordinates);

        for ($i = 0; $i < $n; $i++) {
            $j = ($i + 1) % $n;
            $area += $coordinates[$i]['lng'] * $coordinates[$j]['lat'];
            $area -= $coordinates[$j]['lng'] * $coordinates[$i]['lat'];
        }

        $area = abs($area) / 2.0;
        $areaSquareMeters = $area * 111320 * 111320 * cos(deg2rad($this->display_latitude));

        return round($areaSquareMeters / 10000, 4);
    }

    public function calculateCenterPoint(): ?array
    {
        if (empty($this->polygon_coordinates)) {
            return null;
        }

        $totalLat = 0;
        $totalLng = 0;
        $count = count($this->polygon_coordinates);

        foreach ($this->polygon_coordinates as $coord) {
            $totalLat += $coord['lat'];
            $totalLng += $coord['lng'];
        }

        return [
            'lat' => round($totalLat / $count, 8),
            'lng' => round($totalLng / $count, 8),
        ];
    }

    public function validateWithinMunicipalityBounds(): bool
    {
        if (!$this->display_latitude || !$this->display_longitude) {
            return false;
        }

        $north = env('FARM_NORTH_BOUND');
        $south = env('FARM_SOUTH_BOUND');
        $east = env('FARM_EAST_BOUND');
        $west = env('FARM_WEST_BOUND');

        if ($north === null || $south === null || $east === null || $west === null) {
            return true;
        }

        return $this->display_latitude >= $south && $this->display_latitude <= $north
            && $this->display_longitude >= $west && $this->display_longitude <= $east;
    }

    public function getAreaDifferencePercentageAttribute(): ?float
    {
        if (!$this->area_hectares) {
            return null;
        }

        $calculatedArea = $this->calculateAreaFromPolygon();
        if (!$calculatedArea) {
            return null;
        }

        return (abs($this->area_hectares - $calculatedArea) / $this->area_hectares) * 100;
    }

    public function getSoilSamplesCountAttribute(): int
    {
        return $this->soilSamples()->count();
    }

    public function getLatestSoilSampleAttribute(): ?SoilSample
    {
        return $this->soilSamples()->latest('sample_date')->first();
    }
}
