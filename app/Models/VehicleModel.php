<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class VehicleModel extends Model
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
        'vehicle_brand_id' => 'integer'
    ];

    /**
     * The relationship with the VehicleBrand model
     */
    public function vehicleBrand()
    {
        return $this->belongsTo(VehicleBrand::class);
    }

    /**
     * The relationship with the Ride model
     */
    public function rides()
    {
        return $this->hasMany(Ride::class);
    }
    
}
