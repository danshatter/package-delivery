<?php

namespace App\Traits\Driver;

use App\Models\User;

trait States
{
    
    /**
     * Mark driver registration status as pending
     */
    public function markRegistrationAsPending()
    {
        $this->forceFill([
            'driver_registration_status' => User::DRIVER_STATUS_PENDING,
            'driver_registration_status_updated_at' => $this->freshTimestamp()
        ])->save();
    }

    /**
     * Mark driver registration status as accepted
     */
    public function markRegistrationAsAccepted()
    {
        $this->forceFill([
            'driver_registration_status' => User::DRIVER_STATUS_ACCEPTED,
            'driver_registration_status_updated_at' => $this->freshTimestamp()
        ])->save();
    }

    /**
     * Mark driver registration status as rejected
     */
    public function markRegistrationAsRejected()
    {
        $this->forceFill([
            'driver_registration_status' => User::DRIVER_STATUS_REJECTED,
            'driver_registration_status_updated_at' => $this->freshTimestamp()
        ])->save();
    }


}
