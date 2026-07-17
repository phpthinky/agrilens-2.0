<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'email',
        'password',
        'user_type',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->user_type === 'admin';
    }

    public function soilSamples(): HasMany
    {
        return $this->hasMany(SoilSample::class);
    }

    public function assignedBarangays(): BelongsToMany
    {
        return $this->belongsToMany(Barangay::class, 'technician_barangay', 'technician_id', 'barangay_id')
            ->withTimestamps();
    }

    public function getAccessibleBarangayIds(): array
    {
        return $this->isAdmin()
            ? Barangay::pluck('id')->all()
            : $this->assignedBarangays()->pluck('barangays.id')->all();
    }

    public function getAccessibleFarms(): Builder
    {
        return $this->isAdmin()
            ? Farm::query()
            : Farm::whereIn('location_barangay_id', $this->getAccessibleBarangayIds());
    }

    public function getAccessibleFarmers(): Builder
    {
        return $this->isAdmin()
            ? Farmer::query()
            : Farmer::whereIn('barangay_id', $this->getAccessibleBarangayIds());
    }
}
