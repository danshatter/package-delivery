<?php

namespace App\Services\Geography;

class Location
{

    /**
     * Calculate the minimum and maximum coordinates from a location at a particular radius
     */
    public function calculateCoordinateBounds($latitude, $longitude, $radius)
    {
        $latitudeMin = $latitude - ($radius / 69);
        $latitudeMax = $latitude + ($radius / 69);
        $longitudeMin = $longitude - $radius / abs(cos(deg2rad($latitude)) * 69);
        $longitudeMax = $longitude + $radius / abs(cos(deg2rad($latitude)) * 69);

        return [
            'latitude' => [
                'min' => $latitudeMin,
                'max' => $latitudeMax
            ],
            'longitude' => [
                'min' => $longitudeMin,
                'max' => $longitudeMax
            ]
        ];
    }

}

