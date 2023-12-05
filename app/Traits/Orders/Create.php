<?php

namespace App\Traits\Orders;

use Illuminate\Support\Facades\DB;
use App\Services\Phone\Nigeria;
use App\Models\Transaction;

trait Create
{
    
    /**
     * Create an order
     */
    protected function storeOrder($data, $customer, $driver, $amount, $formattedPickupLocation, $formattedAddresses, $waypointsOrder, $uploadedImages, $card, $formattedLatitudes, $formattedLongitudes, $latitude, $longitude, $totalDistance)
    {
        $order = DB::transaction(function() use ($data, $customer, $driver, $amount, $formattedPickupLocation, $formattedAddresses, $waypointsOrder, $uploadedImages, $card, $formattedLatitudes, $formattedLongitudes, $latitude, $longitude, $totalDistance) {
            // Create the order
            $order = $customer->orders()->create([
                'driver_id' => $driver->id,
                'card_id' => $card?->id, // For the case of card payment
                'pickup_location' => $formattedPickupLocation,
                'pickup_location_latitude' => $latitude,
                'pickup_location_longitude' => $longitude,
                'total_distance_metres' => $totalDistance,
                'images' => $uploadedImages,
                'category' => $data['category'],
                'type' => $data['type'],
                'sender_name' => $data['sender_name'],
                'sender_phone' => app()->make(Nigeria::class)->convert($data['sender_phone']),
                'sender_address' => $data['sender_address'],
                'sender_email' => $data['sender_email'],
                'receivers' => $this->formatReceiversDetails($data['receivers'], $formattedAddresses, $waypointsOrder, $uploadedImages, $formattedLatitudes, $formattedLongitudes),
                'payment_method' => $data['payment_method'],
                'amount' => $amount,
                'currency' => config('handova.currency'),
            ]);

            // Create the notification to the customer that their order has been created but payment remains
            $customer->notifications()->create([
                'message' => "An order of ID #{$order->id} was created successfully. Please wait for driver feedback"
            ]);

            // Create the notification for the driver
            $driver->notifications()->create([
                'message' => "A new job with ID #{$order->id} was assigned to you"
            ]);

            return $order;
        });

        return $order;
    }

}
