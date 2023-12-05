<?php

namespace App\Services\Geography;

class Distance
{

    /**
     * Calculate the distance between two points
     */
    public function get($latitude1, $longitude1, $latitude2, $longitude2, $unit = 'km', $decimals = 9) {
        // Calculate the distance in degrees
        $degrees = rad2deg(acos((sin(deg2rad($latitude1)) * sin(deg2rad($latitude2))) + (cos(deg2rad($latitude1)) * cos(deg2rad($latitude2)) * cos(deg2rad($longitude1 - $longitude2)))));
    
        // Convert the distance in degrees to the chosen unit (kilometres, miles or nautical miles)
        switch($unit) {
            case 'km':
                $distance = $degrees * 111.13384; // 1 degree = 111.13384 km, based on the average diameter of the Earth (12,735 km)
                break;
            case 'mi':
                $distance = $degrees * 69.05482; // 1 degree = 69.05482 miles, based on the average diameter of the Earth (7,913.1 miles)
                break;
            case 'nmi':
                $distance =  $degrees * 59.97662; // 1 degree = 59.97662 nautic miles, based on the average diameter of the Earth (6,876.3 nautical miles)
        }

        return round($distance, $decimals);
    }

}

