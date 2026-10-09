<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'username',
        'email',
        'password',
        'user_type',
        'photo_path',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'address',
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

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null;
    }

    public function getFullNameAttribute(): string
    {
        $name = implode(' ', array_filter([$this->first_name, $this->middle_name, $this->last_name]));
        if ($name === '') {
            return $this->username;
        }
        return $this->suffix ? $name . ' ' . $this->suffix : $name;
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

    public function testAssignments(): HasMany
    {
        return $this->hasMany(SoilTestAssignment::class, 'assigned_technician_id');
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
