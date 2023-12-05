<?php

namespace App\Http\Controllers\Google;

use App\Http\Controllers\Controller;
use App\Services\Google\Geocoding;

class GeocodingController extends Controller
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
     * Get formatted address and coordinates from address
     */
    public function direct()
    {
        $validator = validator()->make(request()->all(), [
            'address' => ['required']
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        extract($validator->validated());

        $data = app()->make(Geocoding::class)->direct($address);

        // Check if the response is okay from Google
        if ($data['status'] !== 'OK') {
            return $this->sendGoogleGeocodingErrorMessage($data['status']);
        }

        return $this->sendSuccess('Request successful', $this->formatGeocodingResponse($data));
    }

    /**
     * Get formatted address and coordinates from longitude and latitude
     */
    public function reverse()
    {
        $validator = validator()->make(request()->all(), [
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric']
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        extract($validator->validated());

        $data = app()->make(Geocoding::class)->reverse($latitude, $longitude);

        // Check if the response is okay from Google
        if ($data['status'] !== 'OK') {
            return $this->sendGoogleGeocodingErrorMessage($data['status']);
        }

        return $this->sendSuccess('Request successful', $this->formatGeocodingResponse($data));
    }

}