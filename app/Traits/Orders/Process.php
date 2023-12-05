<?php

namespace App\Traits\Orders;

use Illuminate\Support\Facades\DB;
use App\Services\Files\Upload;
use App\Services\Phone\Nigeria;
use App\Models\Transaction;
use App\Jobs\SendPushNotification;

trait Process
{

    /**
     * Place an order
     */
    protected function placeOrder($data, $driver, $amount, $formattedPickupLocation, $formattedAddresses, $waypointsOrder, $possibleArrivalTime, $card, $formattedLatitudes, $formattedLongitudes, $latitude, $longitude, $totalDistance)
    {
        // Handle images upload
        $uploadedImages = $this->uploadImages($data['images']);

        // Set the arrival time of the driver
        $driver->setAttribute('arrival_time', [
            'unit' => 'seconds',
            'value' => $possibleArrivalTime['max']
        ]);

        // Make the driver's logitude and latitude visible
        $driver->makeVisible(['location_latitude', 'location_longitude', 'location_updated_at']);

        // Create the order
        $order = $this->storeOrder($data, auth()->user(), $driver, $amount, $formattedPickupLocation, $formattedAddresses, $waypointsOrder, $uploadedImages, $card, $formattedLatitudes, $formattedLongitudes, $latitude, $longitude, $totalDistance);

        // Set the driver as an attribute to the order
        $order->setAttribute('driver', $driver);

        // Send a push notification to the customer on successful order creation
        dispatch(new SendPushNotification(
            auth()->user(),
            'Order Created',
            "You successfully created an order. Your order ID is #{$order->id}",
            [
                'type' => 'order',
                'order_id' => (string) $order->id,
                'delivery_status' => $order->delivery_status
            ]
        ));

        // Send a push notification to the driver on a new job
        dispatch(new SendPushNotification(
            $driver,
            'New Job Alert',
            "Your services has been requested. An order with ID #{$order->id} is assigned to you",
            [
                'type' => 'job',
                'job_id' => (string) $order->id,
                'driver_id' => (string) $driver->id,
                'delivery_status' => $order->delivery_status
            ]
        ));

        return $this->sendSuccess('Order created successfully', $order, 201);
    }
    
    /**
     * Handle images upload
     */
    private function uploadImages($images)
    {
        // Upload the images
        $uploadedImages = [];

        if (!empty($images)) {
            foreach ($images as $key => $image) {
                // An image can be empty or nullable so we check it just in case
                if (!empty($image)) {
                    // Upload the image
                    $uploadedImages[$key] = $image->storePublicly('orders/images');

                    // Decode the image base64 string
                    // $content = base64_decode($image);
        
                    // $uploadedImages[$key] = app()->make(Upload::class)->create('orders/images', $content);
                }
            }
        }

        return $uploadedImages;
    }

}
