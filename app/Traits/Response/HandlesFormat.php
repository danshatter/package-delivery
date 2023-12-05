<?php

namespace App\Traits\Response;

use App\Services\Phone\Nigeria;

trait HandlesFormat
{
    
    /**
     * Format the errors to be used by the backend
     */
    public function sendErrors($errors)
    {
        // Convert the errors to array
        if (!is_array($errors)) {
            $errorsArray = $errors->toArray();
        } else {
            $errorsArray = $errors;
        }

        // Get the first element of the array and use it as the message of the response
        $message = reset($errorsArray);

        return response([
            'status' => false,
            'message' => $message[0],
            'errors' => $errors
        ], 422);
    }

    /**
     * Format the errors to be used by the backend
     */
    public function sendErrorMessage($message, $status = 400)
    {
        return response([
            'status' => false,
            'message' => $message
        ], $status);
    }

    /**
     * The format for success responses
     */
    public function sendSuccess($message, $data = null, $status = 200)
    {
        if (func_num_args() === 1 || (func_num_args() === 3 && is_null($data))) {
            return response([
                'status' => true,
                'message' => $message
            ], $status);
        }

        return response([
            'status' => true,
            'message' => $message,
            'data' => $data
        ], $status);
    }

    /**
     * Formatting the receivers data for creating an order
     */
    protected function formatReceiversDetails($receivers, $formattedAddresses, $waypointsOrder, $uploadedImages, $formattedLatitudes, $formattedLongitudes)
    {
        return collect($receivers)->map(function($receiver, $key) use ($formattedAddresses, $waypointsOrder, $uploadedImages, $formattedLatitudes, $formattedLongitudes) {
            $body = [
                'name' => $receiver['name'],
                'phone' => app()->make(Nigeria::class)->convert($receiver['phone']),
                'address' => $receiver['address'],
                'items' => $receiver['items'] ?? null,
                'email' => $receiver['email'] ?? null,
                'quantity' => $receiver['quantity'] ?? null,
                'weight' => $receiver['weight'] ?? null,
                'delivery_note' => $receiver['delivery_note'] ?? null
            ];

            // Check if there are no waypoints
            if (empty($waypointsOrder)) {
                $body['formatted_address'] = $formattedAddresses[0];
                $body['latitude'] = $formattedLatitudes[0];
                $body['longitude'] = $formattedLongitudes[0];
            } else {
                /**
                 * Waypoints exists. Do logic here
                 */
                // Case for the last address
                if ($key === count($waypointsOrder)) {
                    $body['formatted_address'] = end($formattedAddresses);
                    $body['latitude'] = end($formattedLatitudes);
                    $body['longitude'] = end($formattedLongitudes);
                } else {
                    // Get the key or position of the current iteration in the waypoints array
                    $position = array_search($key, $waypointsOrder);

                    $body['formatted_address'] = $formattedAddresses[$position];
                    $body['latitude'] = $formattedLatitudes[$position];
                    $body['longitude'] = $formattedLongitudes[$position];
                }
            }

            return $body;
        })->all();
    }

}
