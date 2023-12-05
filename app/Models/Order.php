<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Traits\Orders\States;

class Order extends Model
{
    use HasFactory, States;

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EN_ROUTE = 'en route';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELED = 'canceled';
    public const STATUS_IDLE = 'idle';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'driver_id',
        'card_id',
        'category',
        'pickup_location',
        'pickup_location_latitude',
        'images',
        'pickup_location_longitude',
        'total_distance_metres',
        'type',
        'sender_name',
        'sender_phone',
        'sender_address',
        'sender_email',
        'receivers',
        'delivery_note',
        'payment_method',
        'amount',
        'currency',
        'cancellation_reason'
    ];

    /**
     * The model's attributes.
     *
     * @var array
     */
    protected $attributes = [
        'delivery_status' => self::STATUS_PENDING,
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'user_id' => 'integer',
        'driver_id' => 'integer',
        'amount' => 'integer',
        'pickup_location_latitude' => 'float',
        'pickup_location_longitude' => 'float',
        'delivery_status_updated_at' => 'datetime',
        'delivery_note' => 'encrypted',
        'cancellation_reason' => 'encrypted',
        'past_drivers' => 'array',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'past_drivers'
    ];

    /**
     * Set the behaviour of the storage of insurance documents
     */
    public function setImagesAttribute($value)
    {
        $this->attributes['images'] = json_encode($value);
    }

    /**
     * Get the insurance documents in the behaviour we want
     */
    public function getImagesAttribute($value)
    {
        $images = json_decode($value, true);

        return collect($images)->map(fn($image) => Storage::url($image))->all();
    }

    /**
     * Set the behaviour of the receivers array
     */
    public function setReceiversAttribute($value)
    {
        $this->attributes['receivers'] = json_encode($value);
    }

    /**
     * Get the behaviour of the receivers array
     */
    public function getReceiversAttribute($value)
    {
        $receivers = json_decode($value, true);

        return collect($receivers)->map(fn($receiver) => [
            'name' => $receiver['name'] ?? null,
            'phone' => $receiver['phone'],
            'address' => $receiver['address'],
            'formatted_address' => $receiver['formatted_address'],
            'items' => $receiver['items'],
            'email' => $receiver['email'] ?: null,
            'quantity' => $receiver['quantity'] ?: null,
            'weight' => $receiver['weight'] ?: null,
            'delivery_note' => $receiver['delivery_note'] ?: null,
            'latitude' => $receiver['latitude'] ?? null,
            'longitude' => $receiver['longitude'] ?? null
        ])->all();
    }

    /**
     * Get pending orders
     */
    public function scopePending($query)
    {
        return $query->where('delivery_status', self::STATUS_PENDING);
    }

    /**
     * Get accepted orders
     */
    public function scopeAccepted($query)
    {
        return $query->where('delivery_status', self::STATUS_ACCEPTED);
    }

    /**
     * Get rejected orders
     */
    public function scopeRejected($query)
    {
        return $query->where('delivery_status', self::STATUS_REJECTED);
    }

    /**
     * Get en route orders
     */
    public function scopeEnRoute($query)
    {
        return $query->where('delivery_status', self::STATUS_EN_ROUTE);
    }

    /**
     * Get completed orders
     */
    public function scopeCompleted($query)
    {
        return $query->where('delivery_status', self::STATUS_COMPLETED);
    }

    /**
     * Get canceled orders
     */
    public function scopeCanceled($query)
    {
        return $query->where('delivery_status', self::STATUS_CANCELED);
    }

    /**
     * Get idle orders
     */
    public function scopeIdle($query)
    {
        return $query->where('delivery_status', self::STATUS_IDLE);
    }

    /**
     * The relationship with the User model in relation to the customer
     */
    public function customer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The relationship with the User model in relation to the driver
     */
    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    /**
     * The relationship with the Transaction model
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * The relationship with the Rating model
     */
    public function rating()
    {
        return $this->hasOne(Rating::class);
    }

    /**
     * The relationship with the Card model
     */
    public function card()
    {
        return $this->belongsTo(Card::class);
    }
    
}
