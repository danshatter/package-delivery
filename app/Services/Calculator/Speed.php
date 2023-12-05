<?php

namespace App\Services\Calculator;

use App\Services\Geography\Distance;

class Speed
{
    
    /**
     * Calculate the total time a vehicle type will reach destination
     */
    public function calculateTotalTime($distance, $vehicle, $unit = 'm')
    {
        // Check if the unit put in the function is in kilometres, convert it to metres
        if ($unit === 'km') {
            $distance *= 1000;
        }

        // Convert the average distance of the vehicle from kilometres to metres
        $vehicleDistanceMetres = $vehicle->average_speed_km_per_hour * 1000;

        // Calculate the time in seconds it will take to reach destination
        return ($distance / $vehicleDistanceMetres) * 3600;
    }

    /**
     * Convert a time to the nearest minute
     */
    public function convertToNearestMinuteInSeconds($seconds)
    {
        return (intdiv($seconds, 60) * 60);
    }

    /**
     * Calculate the minimum and maximum possible delivery time
     */
    public function calculateMinMaxDeliveryTime($distance, $vehicle)
    {
        $minDeliveryTime = $this->calculateTotalTime($distance, $vehicle) * ((100 - config('handova.delivery_time_bound')) / 100);
        $maxDeliveryTime = $this->calculateTotalTime($distance, $vehicle) * ((100 + config('handova.delivery_time_bound')) / 100);

        return [
            'min' => $this->convertToNearestSecondThroughMinute($minDeliveryTime, 'min'),
            'max' => $this->convertToNearestSecondThroughMinute($maxDeliveryTime, 'max'),
        ];
    }

    /**
     * Calculate the possible arrival time for a vehicle at a particular distance
     */
    public function possibleArrivalTime($latitude1, $longitude1, $latitude2, $longitude2, $vehicle)
    {
        // Calculate the distance in kilometers between the points
        $distance = app()->make(Distance::class)->get($latitude1, $longitude1, $latitude2, $longitude2);

        // Caluclate the minimum arrival
        $minArrivalTime = $this->calculateTotalTime($distance, $vehicle, 'km') * ((100 - config('handova.arrival_time_bound')) / 100);
        $maxArrivalTime = $this->calculateTotalTime($distance, $vehicle, 'km') * ((100 + config('handova.arrival_time_bound')) / 100);

        $min = $this->convertToNearestSecondThroughMinute($minArrivalTime, 'min');
        $max = $this->convertToNearestSecondThroughMinute($maxArrivalTime, 'max');

        /**
         * Check if the minimum delivery time in seconds is equal to the maximum delivery time,
         * then add one minute to the maximum time
         */
        if ($min === $max) {
            $max++;
        }

        return compact('min', 'max');
    }

    /**
     * Convert the time to seconds through the nearest minute
     */
    private function convertToNearestSecondThroughMinute($seconds, $type)
    {
        $minute = intdiv($seconds, 60);

        switch ($type) {
            case 'min':
                // If the place is nearby and time is less than one minute, make it one minute
                if ((int) $minute === 0) {
                    $minute = 1;
                }
            break;
            
            case 'max':
                // If the place is nearby and time is less than or equal to one minute, make it two minutes
                if ((int) $minute === 0 || (int) $minute === 1) {
                    $minute = 2;
                }
            break;

            default:
                
            break;
        }

        return $minute * 60;
    }

}

