<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Traits\Rides\States;

class Ride extends Model
{
    use HasFactory, States;

    public const PENDING = 'pending';
    public const APPROVED = 'approved';
    public const REJECTED = 'rejected';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'vehicle_id',
        'brand',
        'model',
        'plate_number',
        'valid_insurance_documents',
        'valid_inspection_reports'
    ];

    /**
     * The model's attributes.
     *
     * @var array
     */
    protected $attributes = [
       'status' => self::PENDING
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'valid_insurance_documents',
        'valid_inspection_reports',
        'status',
        'status_updated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'user_id' => 'integer',
        'status_updated_at' => 'datetime'
    ];

    /**
     * Set the behaviour of the storage of insurance documents
     */
    public function setValidInsuranceDocumentsAttribute($value)
    {
        $this->attributes['valid_insurance_documents'] = json_encode($value);
    }

    /**
     * Get the insurance documents in the behaviour we want
     */
    public function getValidInsuranceDocumentsAttribute($value)
    {
        $documents = json_decode($value, true);

        return collect($documents)->map(fn($document) => Storage::url($document))->all();
    }

    /**
     * Set the behaviour of the storage of insurance documents
     */
    public function setValidInspectionReportsAttribute($value)
    {
        $this->attributes['valid_inspection_reports'] = json_encode($value);
    }

    /**
     * Get the insurance documents in the behaviour we want
     */
    public function getValidInspectionReportsAttribute($value)
    {
        $reports = json_decode($value, true);

        return collect($reports)->map(fn($report) => Storage::url($report))->all();
    }

    /**
     * The relationship with the User model
     */
    public function driver()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The relationship with the Vehicle model
     */
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
    
}
