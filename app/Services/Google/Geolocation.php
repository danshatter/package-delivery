<?php

namespace App\Services\Google;

use Illuminate\Support\Facades\Http;
use App\Services\Settings\Application;

class Geolocation
{
    
    /**
     * Get the current coordinates of a user
     */
    public function locate()
    {
        // Set the application settings
        app()->make(Application::class)->set();

        $response = Http::post(config('services.google_apis.geolocation.endpoint').'?key='.config('services.google_apis.key'), [
            'considerIp' => true
        ]);

        return $response->json();
    }

}

