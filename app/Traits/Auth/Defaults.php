<?php

namespace App\Traits\Auth;

use App\Models\{Role, User};

trait Defaults
{

    /**
     * Set default attributes to drivers after registration
     */
    public function setDriverDefaults()
    {
        $this->forceFill([
            // Upgrade the user to a driver
            'role_id' => Role::DRIVER,

            // Put the users registration status to pending
            'driver_registration_status' => User::DRIVER_STATUS_PENDING,

            // Set the completed orders to 0
            'completed_orders_count' => 0,

            // Set the rejected orders to 0
            'rejected_orders_count' => 0,

            // Set the online status of the driver to offline
            'online' => User::OFFLINE
        ])->save();
    }

}
