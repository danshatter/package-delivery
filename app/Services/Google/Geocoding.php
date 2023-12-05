<?php

namespace App\Services\Google;

use Illuminate\Support\Facades\Http;
use App\Services\Settings\Application;

class Geocoding
{
    
    /**
     * Get the geocoding results from an address
     */
    public function direct($address)
    {
        // Set the application settings
        app()->make(Application::class)->set();

        $response = Http::get(config('services.google_apis.geocoding.endpoint'), [
            'address' => $address,
            'key' => config('services.google_apis.key')
        ]);

        return $response->json();
    }

    /**
     * Get the geocoding results from an latitude and longitude
     */
    public function reverse($lat, $lng)
    {
        // Set the application settings
        app()->make(Application::class)->set();

        $response = Http::get(config('services.google_apis.geocoding.endpoint'), [
            'latlng' => "{$lat},{$lng}",
            'key' => config('services.google_apis.key')
        ]);

        return $response->json();
    }

}

