<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'plate',
        'make',
        'model',
        'daily_rate',
        'available'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'daily_rate' => 'float',
        'available' => 'boolean',
    ];

    /**
     * Get the rentals for the vehicle.
     */
    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }
}
