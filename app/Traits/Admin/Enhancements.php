<?php

namespace App\Traits\Admin;

use Illuminate\Support\Facades\DB;

trait Enhancements
{
    
    /**
     * Mark the driver as approved
     */
    protected function markDriverAsApproved($driver)
    {
        DB::transaction(function() use ($driver) {
            // Approve the driver
            $driver->markRegistrationAsAccepted();

            // Check to make sure that the ride still exists
            if (!is_null($driver->ride)) {
                // Approve the ride of the driver
                $driver->ride->markAsApproved();
            }
        });
    }

    /**
     * Mark the driver as rejected
     */
    protected function markDriverAsRejected($driver)
    {
        DB::transaction(function() use ($driver) {
            // Reject the driver
            $driver->markRegistrationAsRejected();

            // Check to make sure that the ride still exists
            if (!is_null($driver->ride)) {
                // Reject the ride of the driver
                $driver->ride->markAsRejected();
            }
        });
    }

    /**
     * The contents of the CSV for orders
     */
    protected function orderReport($order, $key)
    {
        return [
            $key,
            $order->id,
            trim("{$order->customer?->first_name} {$order->customer?->last_name}"),
            trim("{$order->driver?->first_name} {$order->driver?->last_name}"),
            '"'.number_format($order->amount / 100, 2).'"',
            $order->currency,
            $order->delivery_status_updated_at,
        ];
    }

}
