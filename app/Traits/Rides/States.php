<?php

namespace App\Traits\Rides;

use App\Models\Ride;

trait States
{
    
    /**
     * Mark ride as pending
     */
    public function markAsPending()
    {
        $this->forceFill([
            'status' => Ride::PENDING,
            'status_updated_at' => $this->freshTimestamp()
        ])->save();
    }

    /**
     * Mark ride as approved
     */
    public function markAsApproved()
    {
        $this->forceFill([
            'status' => Ride::APPROVED,
            'status_updated_at' => $this->freshTimestamp()
        ])->save();
    }

    /**
     * Mark ride as rejected
     */
    public function markAsRejected()
    {
        $this->forceFill([
            'status' => Ride::REJECTED,
            'status_updated_at' => $this->freshTimestamp()
        ])->save();
    }

}
