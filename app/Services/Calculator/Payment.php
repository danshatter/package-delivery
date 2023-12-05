<?php

namespace App\Services\Calculator;

class Payment
{

    /**
     * Calculate the minimum and maximum payable amount by a user
     */
    public function calculateMinMaxAmount($distance, $vehicle)
    {
        $minAmount = $this->calculateExactAmount($distance, $vehicle) * ((100 - config('handova.delivery_price_percentage_bound')) / 100);
        $maxAmount = $this->calculateExactAmount($distance, $vehicle) * ((100 + config('handova.delivery_price_percentage_bound')) / 100);

        return [
            'min' => $this->convertToNearestTenNaira($minAmount),
            'max' => $this->convertToNearestTenNaira($maxAmount),
        ];
    }

    /**
     * Get the main amount the user will pay
     */
    public function calculateMainAmount($distance, $vehicle) {
        $amount = $this->calculateExactAmount($distance, $vehicle);

        return $this->convertToNearestTenNaira($amount);
    }

    /**
     * Calculate the exact amount payable by a user
     */
    private function calculateExactAmount($distance, $vehicle)
    {
        // Convert the distance from metres to kilometres
        $distanceKm = $distance / 1000;

        return $vehicle->amount_per_km * $distanceKm;
    }

    /**
     * Convert the amount to the nearest hundred of the highest denominational value
     */
    private function convertToNearestTenNaira($amount)
    {
        $hundreds = intdiv($amount, 1000);

        return $hundreds * 1000;
    }

}

