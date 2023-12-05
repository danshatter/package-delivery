<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromoCode extends Model
{
    use HasFactory;

    public const PUBLIC_CODE = 'public';
    public const PRIVATE_CODE = 'private';
    public const RESTRICTED_CODE =  'restricted';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'code',
        'type',
        'value',
        'value_type',
        'expires_at',
        'is_used'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_used' => 'boolean',
        'expires_in' => 'datetime'
    ];
    
}
