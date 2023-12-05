<?php

namespace App\Services\Google;

use Illuminate\Support\Facades\Http;
use App\Services\Settings\Application;

class Direction
{
    
    /**
     * Get the direction from origin to destination
     */
    public function get($origin, $destination, $waypoints = null)
    {
        // Set the application settings
        app()->make(Application::class)->set();

        $response = Http::get(config('services.google_apis.direction.endpoint'), [
            'key' => config('services.google_apis.key'),
            'origin' => $origin,
            'destination' => $destination,
            'waypoints' => is_null($waypoints) ? '' : 'optimize:true|'.implode('|', $waypoints)
        ]);

        return $response->json();
    }

}

