<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'application_name',
        'application_version',
        'application_mail_from_email_address',
        'application_mail_from_name',
        'paystack_public_key',
        'paystack_secret_key',
        'firebase_web_api_key',
        'firebase_project_id',
        'google_api_key',
        'sms_username',
        'sms_key',
        'android_url',
        'apple_url',
        'transaction_fee_type',
        'transaction_fee_value',
        'order_cancellation_fee_type',
        'order_cancellation_fee_value',
        'verify_me_public_key',
        'verify_me_secret_key'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [

    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [

    ];

}
