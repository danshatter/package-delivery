<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'order_id',
        'driver_id',
        'stars',
        'comment'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'user_id' => 'integer',
        'driver_id' => 'integer',
        'stars' => 'integer',
        'comment' => 'encrypted'
    ];

    /**
     * Relationship with the User model in relation to the customer
     */
    public function customer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relationship with the User model in relation to the driver
     */
    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    /**
     * Relationship with the User model in relation to the driver
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    
}
