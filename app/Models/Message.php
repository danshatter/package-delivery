<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Messaging\HandlesMessaging;

class Message extends Model
{
    use HasFactory, HandlesMessaging;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'to',
        'message'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'from' => 'integer',
        'to' => 'integer',
        'message' => 'encrypted',
        'read_at' => 'datetime',
        'delivered_at' => 'datetime'
    ];

    /**
     * The relationship with the User model in relation the the sender
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'from');
    }

    /**
     * The relationship with the User model in relation to the receiver
     */
    public function receiver()
    {
        return $this->belongsTo(User::class, 'to');
    }
    
}
