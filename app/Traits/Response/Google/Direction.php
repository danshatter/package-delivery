<?php

namespace App\Traits\Response\Google;

trait Direction
{
    
    /**
     * Return the error from the google API based on the status
     */
    public function sendGoogleDirectionErrorMessage($status)
    {
        switch ($status) {
            case 'NOT_FOUND':
                return response([
                    'status' => false,
                    'message' => 'At least one of the request locations was not found'
                ], 404);
            break;

            case 'ZERO_RESULTS':
                return response([
                    'status' => false,
                    'message' => 'No route could be found between origin and destination'
                ], 404);
            break;

            case 'MAX_WAYPOINTS_EXCEEDED':
                return response([
                    'status' => false,
                    'message' => 'Your request contains too many waypoints'
                ], 400);
            break;

            case 'MAX_ROUTE_LENGTH_EXCEEDED':
                return response([
                    'status' => false,
                    'message' => 'Your request route too long and unprocessed'
                ], 400);
            break;

            case 'INVALID_REQUEST':
                return response([
                    'status' => false,
                    'message' => 'Your request was invalid'
                ], 400);
            break;

            case 'OVER_DAILY_LIMIT':
                return response([
                    'status' => false,
                    'message' => 'Your usage cap has been exceeded'
                ], 400);
            break;

            case 'OVER_QUERY_LIMIT':
                return response([
                    'status' => false,
                    'message' => 'Too many requests to Google services'
                ], 429);
            break;

            case 'REQUEST_DENIED':
                return response([
                    'status' => false,
                    'message' => 'The request was denied by Google services'
                ], 403);
            break;

            case 'UNKNOWN_ERROR':
                return response([
                    'status' => false,
                    'message' => 'An error occurred. Please try again'
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
     * Format the data from the directions API
     */
    protected function formatDirectionResponse($data)
    {
        return array_map(function($location) {
            return [
                'start' => [
                    'address' => data_get($location, 'start_address'),
                    'latitude' => data_get($location, 'start_location.lat'),
                    'longitude' => data_get($location, 'start_location.lng')
                ],
                'end' => [
                    'address' => data_get($location, 'end_address'),
                    'latitude' => data_get($location, 'end_location.lat'),
                    'longitude' => data_get($location, 'end_location.lng')
                ],
                'distance' => [
                    'value' => data_get($location, 'distance.value'),
                    'unit' => 'metres'
                ],
                'duration' => [
                    'value' => data_get($location, 'duration.value'),
                    'unit' => 'seconds'
                ],
            ];
        }, $data['routes'][0]['legs']);
    }

    /**
     * Get the waypoints order of the request
     */
    protected function googleDirectionWaypointOrder($data)
    {
        return data_get($data, 'routes.0.waypoint_order');
    }

}