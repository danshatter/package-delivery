<?php

namespace App\Traits\Response\Google;

trait Geocoding
{
    
    /**
     * Return the error from the google API based on the status
     */
    public function sendGoogleGeocodingErrorMessage($status)
    {
        switch ($status) {
            // When the API returns no results
            case 'ZERO_RESULTS':
                return response([
                    'status' => false,
                    'message' => 'No results found from query'
                ], 404);
            break;

            case 'OVER_QUERY_LIMIT':
                return response([
                    'status' => false,
                    'message' => 'Looks like we are over our quota'
                ], 403);
            break;

            case 'REQUEST_DENIED':
                return response([
                    'status' => false,
                    'message' => 'Request was denied by Google services'
                ], 403);
            break;

            case 'INVALID_REQUEST':
                return response([
                    'status' => false,
                    'message' => 'The request was invalid'
                ], 400);
            break;

            case 'UNKNOWN_ERROR':
                return response([
                    'status' => false,
                    'message' => 'An error occured. Please try again'
                ], 503);
            break;
            
            default:
                return response([
                    'status' => false,
                    'message' => 'Something unexpected happened. Please try again'
                ], 500);
            break;
        }
    }

    /**
     * Format the Geocoding response gotten back by Google
     */
    protected function formatGeocodingResponse($data)
    {
        return [
            'address' => data_get($data, 'results.0.formatted_address'),
            'latitude' => data_get($data, 'results.0.geometry.location.lat'),
            'longitude' => data_get($data, 'results.0.geometry.location.lng')
        ];
    }

}