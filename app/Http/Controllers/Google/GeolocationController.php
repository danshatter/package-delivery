<?php

namespace App\Http\Controllers\Google;

use App\Http\Controllers\Controller;
use App\Services\Google\Geolocation;

class GeolocationController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the current coordinates
     */
    public function show()
    {
        $data = app()->make(Geolocation::class)->locate();

        // Check if there was an error
        if (isset($data['error'])) {
            return $this->sendGoogleGeolocationErrorMessage($data['error']['errors'][0]['reason']);
        }

        return $this->sendSuccess('Request successful', $this->formatGeolocationResponse($data));
    }

}