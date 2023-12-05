<?php

namespace App\Traits\Orders;

use App\Models\Order;

trait States
{
    
    /**
     * Mark an order as pending
     */
    public function markAsPending()
    {
        $this->forceFill([
            'delivery_status' => Order::STATUS_PENDING,
            'delivery_status_updated_at' => $this->freshTimestamp()
        ])->save();
    }

    /**
     * Mark an order as accepted
     */
    public function markAsAccepted()
    {
        $this->forceFill([
            'delivery_status' => Order::STATUS_ACCEPTED,
            'delivery_status_updated_at' => $this->freshTimestamp()
        ])->save();
    }

    /**
     * Mark an order as rejected
     */
    public function markAsRejected()
    {
        $this->forceFill([
            'delivery_status' => Order::STATUS_REJECTED,
            'delivery_status_updated_at' => $this->freshTimestamp()
        ])->save();
    }

    /**
     * Mark an order as en route
     */
    public function markAsEnRoute()
    {
        $this->forceFill([
            'delivery_status' => Order::STATUS_EN_ROUTE,
            'delivery_status_updated_at' => $this->freshTimestamp()
        ])->save();
    }

    /**
     * Mark an order as completed
     */
    public function markAsCompleted()
    {
        $this->forceFill([
            'delivery_status' => Order::STATUS_COMPLETED,
            'delivery_status_updated_at' => $this->freshTimestamp()
        ])->save();
    }

    /**
     * Mark an order as canceled
     */
    public function markAsCanceled($reason)
    {
        $this->forceFill([
            'delivery_status' => Order::STATUS_CANCELED,
            'delivery_status_updated_at' => $this->freshTimestamp(),
            'cancellation_reason' => $reason
        ])->save();
    }

    /**
     * Mark an order as idle
     */
    public function markAsIdle()
    {
        $this->forceFill([
            'driver_id' => null,
            'delivery_status' => Order::STATUS_IDLE,
            'delivery_status_updated_at' => $this->freshTimestamp()
        ])->save();
    }

}
