<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Messaging\HandlesMessaging;

class SuperNotification extends Model
{
    use HasFactory, HandlesMessaging;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'message',
        'meta'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'read_by' => 'integer',
        'message' => 'encrypted',
        'read_at' => 'datetime',
        'delivered_at' => 'datetime',
        'meta' => 'array'
    ];

    /**
     * Mark a notification read by the current user
     */
    public function markReadBy($userId)
    {
        $this->forceFill([
            'read_by' => $userId
        ])->save();
    }
    
}
