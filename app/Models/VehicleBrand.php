<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleBrand extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'vehicle_id' => 'integer',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'old_brand_id',
    ];

    /**
     * The relationship with the Vehicle model
     */
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * The relationship with the VehicleModel model
     */
    public function vehicleModels()
    {
        return $this->hasMany(VehicleModel::class);
    }

    /**
     * The relationship with the Ride model
     */
    public function rides()
    {
        return $this->hasMany(Ride::class);
    }
    
}
