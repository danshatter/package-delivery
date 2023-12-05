<?php

namespace App\Services\Calculator;

use Exception;
use App\Services\Settings\Application;

class Cancellation
{

    private $amount;

    /**
     * Create an instance
     */
    public function __construct($amount)
    {
        $this->amount = $amount;
    }

    /**
     * Get the cancellation fee
     */
    public function fee()
    {
        // Set the application settings
        app()->make(Application::class)->set();

        switch (config('handova.order_cancellation_fee.type')) {
            case 'amount':
                return config('handova.order_cancellation_fee.value');
            break;
            
            case 'percentage':
                return (
                    $this->amount * (config('handova.order_cancellation_fee.value') / 100)
                );
            break;

            default:
                throw new Exception('Invalid withdrawal method');
            break;
        }
    }

}

