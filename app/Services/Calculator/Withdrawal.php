<?php

namespace App\Services\Calculator;

use Exception;
use App\Services\Settings\Application;

class Withdrawal
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
     * Calculate the minimum amount a user should have for successful withdrawal
     */
    public function total()
    {
        return $this->amount + $this->fee();
    }

    /**
     * Get the fee collected by withdrawal
     */
    public function fee()
    {
        // Set the application settings
        app()->make(Application::class)->set();

        switch (config('handova.transaction_fee.type')) {
            case 'amount':
                return config('handova.transaction_fee.value');
            break;
            
            case 'percentage':
                return (
                    $this->amount * (config('handova.transaction_fee.value') / 100)
                );
            break;

            default:
                throw new Exception('Invalid withdrawal method');
            break;
        }
    }

}

