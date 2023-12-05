<?php

namespace App\Traits\Response\Google;

trait Geolocation
{
    
    /**
     * Return the error from the google API based on the status
     */
    public function sendGoogleGeolocationErrorMessage($status)
    {
        switch ($status) {
            // When the API returns no results
            case 'parseError':
                return response([
                    'status' => false,
                    'message' => 'Invalid request content'
                ], 400);
            break;

            case 'dailyLimitExceeded':
                return response([
                    'status' => false,
                    'message' => 'Looks like we have exceeded our daily limit'
                ], 403);
            break;

            case 'keyInvalid':
                return response([
                    'status' => false,
                    'message' => 'The API key is invalid'
                ], 403);
            break;

            case 'userRateLimitExceeded':
                return response([
                    'status' => false,
                    'message' => 'Your rate limit has been exceeded. Please try again in a few minutes'
                ], 403);
            break;

            case 'notFound':
                return response([
                    'status' => false,
                    'message' => 'No results found'
                ], 404);
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
     * Format the Geolocation response
     */
    protected function formatGeolocationResponse($data)
    {
        return [
            'latitude' => data_get($data, 'location.lat'),
            'longitude' => data_get($data, 'location.lng'),
            'accuracy' => data_get($data, 'accuracy')
        ];
    }

}