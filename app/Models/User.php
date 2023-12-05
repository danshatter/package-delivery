<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Laravel\Lumen\Auth\Authorizable;
use App\Traits\Auth\{EmailVerification, UsesOtp, Defaults};
use App\Traits\Location\CollectsCurrent;
use App\Traits\Payments\Accept;
use App\Traits\Driver\States;

class User extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable, HasFactory, EmailVerification, UsesOtp, CollectsCurrent, Accept, Defaults, States;

    /**
     * The status of a user's account
     */
    public const ACTIVE = 'active';
    public const BLOCKED = 'blocked';

    /**
     * The states of driver registration
     */
    public const DRIVER_STATUS_PENDING = 'pending';
    public const DRIVER_STATUS_ACCEPTED = 'accepted';
    public const DRIVER_STATUS_REJECTED = 'rejected';

    /**
     * The states of driver online or offline
     */
    public const OFFLINE = false;
    public const ONLINE = true;

    /**
     * The states of profile completion
     */
    public const PROFILE_COMPLETE = true;
    public const PROFILE_INCOMPLETE = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'business_name',
        'email',
        'phone',
        'hear_about_us_from',
        'password',
        'used_for',
        'device_identification',
        'firebase_messaging_token',
        'next_of_kin_first_name',
        'next_of_kin_last_name',
        'next_of_kin_relationship',
        'next_of_kin_phone',
        'next_of_kin_email',
        'next_of_kin_home_address',
        'date_of_birth',
        'home_address',
        'drivers_license_number',
        'drivers_license_image',
        'drivers_license_expiration_date',
        'selfie',
        'valid_utility_bill',
        'profile_image'
    ];

    /**
     * The model's attributes.
     *
     * @var array
     */
    protected $attributes = [
        'role_id' => Role::CUSTOMER,
        'available_balance' => 0,
        'ledger_balance' => 0,
        'status' => self::ACTIVE,
        'profile_completed' => self::PROFILE_INCOMPLETE
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'role_id' => 'integer',
        'available_balance' => 'integer',
        'ledger_balance' => 'integer',
        'email_verified_at' => 'datetime',
        'otp_expires_at' => 'datetime',
        'location_updated_at' => 'datetime',
        'driver_registration_status_updated_at' => 'datetime',
        'online' => 'boolean',
        'rejected_orders_count' => 'integer',
        'completed_orders_count' => 'integer',
        'profile_completed' => 'boolean'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'role_id',
        'business_name',
        'email_verified_at',
        'date_of_birth',
        'home_address',
        'referral_link',
        'password',
        'profile_completed',
        'status',
        'otp',
        'otp_expires_at',
        'device_identification',
        'firebase_messaging_token',
        'location_latitude',
        'location_longitude',
        'location_updated_at',
        'driver_registration_status',
        'hear_about_us_from',
        'next_of_kin_first_name',
        'next_of_kin_last_name',
        'next_of_kin_relationship',
        'next_of_kin_phone',
        'next_of_kin_email',
        'next_of_kin_home_address',
        'drivers_license_number',
        'drivers_license_image',
        'drivers_license_expiration_date',
        'selfie',
        'valid_utility_bill',
        'used_for',
        'driver_registration_status',
        'driver_registration_status_updated_at',
        'online',
        'available_balance',
        'ledger_balance',
        'rejected_orders_count',
        'completed_orders_count'
    ];

    /**
     * Get the URL of the profile image of the user
     */
    public function getProfileImageAttribute($value)
    {
        /**
         * We check if the value stored in the database is a valid URL
         * This might occur if we create an account through OAuth2
         */
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }

        if (!is_null($value)) {
            return Storage::url($value);
        }

        return $value;
    }

    /**
     * Get the URL of a driver's license image
     */
    public function getDriversLicenseImageAttribute($value)
    {
        if (!is_null($value)) {
            return Storage::url($value);
        }

        return $value;
    }

    /**
     * Get the URL of the utility bill
     */
    public function getValidUtilityBillAttribute($value)
    {
        if (!is_null($value)) {
            return Storage::url($value);
        }

        return $value;
    }

    /**
     * Get the customers
     */
    public function scopeCustomers($query)
    {
        return $query->where('role_id', Role::CUSTOMER);
    }

    /**
     * Get the drivers
     */
    public function scopeDrivers($query)
    {
        return $query->where('role_id', Role::DRIVER);
    }

    /**
     * Get the valid drivers
     */
    public function scopeValidDrivers($query)
    {
        return $query->where('role_id', Role::DRIVER)
                    ->where('email_verified_at', '!=', null)
                    ->where('driver_registration_status', self::DRIVER_STATUS_ACCEPTED)
                    ->whereRelation('ride', 'status', Ride::APPROVED);
    }

    /**
     * Get the administrators
     */
    public function scopeAdmins($query)
    {
        return $query->where('role_id', Role::ADMINISTRATOR);
    }

    /**
     * Get online drivers
     */
    public function scopeOnline($query)
    {
        return $query->where('online', self::ONLINE);
    }

    /**
     * Get offline drivers
     */
    public function scopeOffline($query)
    {
        return $query->where('online', self::OFFLINE);
    }

    /**
     * Get active users
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::ACTIVE);
    }

    /**
     * Get blocked users
     */
    public function scopeBlocked($query)
    {
        return $query->where('status', self::BLOCKED);
    }

    /**
     * Relationship with the Token model
     */
    public function token()
    {
        return $this->hasOne(Token::class);
    }

    /**
     * Relationship with the Rating model in relation to the customer
     */
    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    /**
     * Relationship with the Rating model in relation to the driver
     */
    public function jobRatings()
    {
        return $this->hasMany(Rating::class, 'driver_id');
    }

    /**
     * The relationship with the Notification model
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * The relationship with the Message model in relation to the sender 
     */
    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'from');
    }

    /**
     * The relationship with the Message model in relation to the receiver
     */
    public function receivedMessages()
    {
        return $this->hasMany(Message::class, 'to');
    }

    /**
     * The relationship with the Order model
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * The relationship with the Order model in relation to the driver
     */
    public function jobs()
    {
        return $this->hasMany(Order::class, 'driver_id');
    }

    /**
     * The relationship with the Role model
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * The relationship with the Transaction model
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * The relationship with the Account model
     */
    public function accounts()
    {
        return $this->hasMany(Account::class);
    }

    /**
     * The relationship with the Card model
     */
    public function cards()
    {
        return $this->hasMany(Card::class);
    }

    /**
     * The relationship with the Ride model
     */
    public function ride()
    {
        return $this->hasOne(Ride::class);
    }

    /**
     * Convert the model instance to an array.
     *
     * @return array
     */
    public function toArray()
    {
        /**
         * Make the necessary fields visible based on the use of the application
         */
        $this->makeVisibleIf($this->used_for === 'business', ['business_name']);

        return parent::toArray();
    }

}
