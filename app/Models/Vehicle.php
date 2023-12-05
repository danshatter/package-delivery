<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\{Model, SoftDeletes};
use Illuminate\Support\Facades\Storage;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    public const BIKES = 1;
    public const CARS = 2;
    public const TRUCKS = 3;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'amount_per_km',
        'average_speed_km_per_hour',
        'currency',
        'image'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'average_speed_km_per_hour' => 'integer',
        'amount_per_km' => 'integer'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'average_speed_km_per_hour',
        'amount_per_km',
        'currency',
    ];

    /**
     * Get the URL image of the vehicle
     */
    public function getImageAttribute($value)
    {
        if (!is_null($value)) {
            return Storage::url($value);
        }

        return $value;
    }

    /**
     * The relationship with the VehicleBrand model
     */
    public function vehicleBrands()
    {
        return $this->hasMany(VehicleBrand::class);
    }

    /**
     * The relationship with the Ride model
     */
    public function rides()
    {
        return $this->hasMany(Ride::class);
    }
    
}
