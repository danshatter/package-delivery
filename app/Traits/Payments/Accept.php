<?php

namespace App\Traits\Payments;

trait Accept
{
    
    /**
     * Check if a user can pay for the order
     */
    public function canPay($amount)
    {
        return $this->available_balance >= $amount;
    }

}
