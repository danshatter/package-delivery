<?php

namespace App\Traits\Location;

trait CollectsCurrent
{
    
    /**
     * Update the user's current location
     */
    public function updateCurrentLocation($lat, $lng)
    {
        $this->forceFill([
            'location_latitude' => $lat,
            'location_longitude' => $lng,
            'location_updated_at' => $this->freshTimestamp()
        ])->save();
    }

}
