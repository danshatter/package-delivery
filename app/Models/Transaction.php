<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    /**
     * The different types of transaction
     */
    public const DEBIT = 'debit';
    public const CREDIT = 'credit';
    public const WITHDRAWAL = 'withdrawal';

    /**
     * The possible withdrawal status
     */
    public const WITHDRAWAL_STATUS_PROCESSING = 'processing withdrawal';
    public const WITHDRAWAL_STATUS_CONFIRMED = 'withdrawal confirmed successfully';
    public const WITHDRAWAL_STATUS_REJECTED = 'withdrawal request rejected';

    /**
     * The different types of charges of our application
     */
    public const TYPE_ADD_CARD = 'add_card';
    public const TYPE_CREDIT_ACCOUNT = 'credit_account';
    public const TYPE_CARD_PAYMENT = 'card_payment';
    public const TYPE_ORDER_CANCELLATION = 'order_cancellation';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'order_id',
        'amount',
        'currency',
        'reference',
        'type',
        'notes',
        'meta',
        'meta->status'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'user_id' => 'integer',
        'order_id' => 'integer',
        'amount' => 'integer',
        'notes' => 'encrypted',
        'meta' => 'array'
    ];

    /**
     * Get the debit transactions
     */
    public function scopeDebit($query)
    {
        return $query->where('type', self::DEBIT);
    }

    /**
     * Get the credit transactions
     */
    public function scopeCredit($query)
    {
        return $query->where('type', self::CREDIT);
    }

    /**
     * Get the withdrawal transactions
     */
    public function scopeWithdrawal($query)
    {
        return $query->where('type', self::WITHDRAWAL);
    }

    /**
     * The relationship with the User model
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The relationship with the Order model
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    
}
