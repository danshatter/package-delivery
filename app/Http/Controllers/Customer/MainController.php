<?php

namespace App\Http\Controllers\Customer;

use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\{DB, Storage};
use App\Http\Controllers\Controller;
use App\Models\{Rating, User, Role, Vehicle, Order, Ride, Transaction};
use App\Services\Geography\Location;
use App\Services\Google\{Direction, Geocoding};
use App\Services\Calculator\{Speed, Payment, Cancellation};
use App\Services\Files\Upload;
use App\Rules\{ValidDriver, VehicleExists, JsonMax};
use App\Services\Settings\Application;
use App\Traits\Orders\Payment as OrderPayment;
use App\Jobs\SendPushNotification;

class MainController extends Controller
{
    use OrderPayment;

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
     * Get ratings of logged in user
     */
    public function ratings()
    {
        $perPage = request()->query('per_page') ?? config('handova.per_page');

        $ratings = auth()->user()->ratings()->with(['driver'])->latest()->paginate($perPage);

        return $this->sendSuccess('Request successful', $ratings->items());
    }

    /**
     * Get a rating
     */
    public function showRatings($ratingId)
    {
        $rating = auth()->user()->ratings()->with(['driver'])->find($ratingId);

        if (is_null($rating)) {
            return $this->sendErrorMessage('Rating not found', 404);
        }

        return $this->sendSuccess('Request successful', $rating);
    }

    /**
     * Rate a driver
     */
    public function storeRating()
    {
        $validator = validator()->make(request()->all(), [
            'driver_id' => ['required', 'exists:users,id', new ValidDriver],
            'stars' => ['required', 'integer', 'in:1,2,3,4,5'],
            'comment' => ['nullable']
        ], [
            'stars.in' => 'The :attribute must have a value between 1 to 5',
            'driver_id.exists' => 'This driver does not exist'
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        $driver = User::validDrivers()->find(request()->input('driver_id'));

        $rating = DB::transaction(function() use ($validator, $driver) {
            extract($validator->validated());

            // Create the rating
            $rating = auth()->user()->ratings()->create([
                'driver_id' => $driver->id,
                'stars' => $stars,
                'comment' => $comment ?? null
            ]);

            // Notify the driver that a rating has just been added
            $driver->notifications()->create([
                'message' => 'You have just been rated by a customer'
            ]);

            dispatch(new SendPushNotification(
                $driver,
                'New Rating',
                'A customer '.auth()->user()->first_name.' '.auth()->user()->last_name.' just rated you',
                [
                    'type' => 'rating',
                    'rating_id' => (string) $rating->id
                ]
            ));

            dispatch(new SendPushNotification(
                auth()->user(),
                'Rating Added Successfully',
                "You have successfully rated the driver {$driver->first_name} {$driver->last_name}",
                [
                    'type' => 'rating',
                    'rating_id' => (string) $rating->id
                ]
            ));

            return $rating;
        });

        return $this->sendSuccess('Driver rated successfully', $rating, 201);
    }

    /**
     * Update a rating
     */
    public function updateRating($ratingId)
    {
        $rating = auth()->user()->ratings()->with(['driver'])->find($ratingId);

        if (is_null($rating)) {
            return $this->sendErrorMessage('Rating not found', 404);
        }

        // Check to make sure that the driver still exists
        if (is_null($rating->driver)) {
            return $this->sendErrorMessage('Driver does not exist or has been deleted', 404);
        }

        $validator = validator()->make(request()->all(), [
            'stars' => ['required', 'integer', 'in:1,2,3,4,5'],
            'comment' => ['nullable']
        ], [
            'stars.in' => 'The :attribute must have a value between 1 to 5',
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        $rating = DB::transaction(function() use ($validator, $rating) {
            extract($validator->validated());

            $rating->update([
                'stars' => $stars,
                'comment' => $comment ?? null
            ]);

            $rating->driver->notifications()->create([
                'message' => 'A rating on you has just been updated'
            ]);

            dispatch(new SendPushNotification(
                $rating->driver,
                'Rating Updated',
                'A customer '.auth()->user()->first_name.' '.auth()->user()->last_name.' just updated their rating on you',
                [
                    'type' => 'rating',
                    'rating_id' => (string) $rating->id
                ]
            ));

            dispatch(new SendPushNotification(
                auth()->user(),
                'Rating Updated Successfully',
                "Your rating on the driver {$rating->driver->first_name} {$rating->driver->last_name} was successfully updated",
                [
                    'type' => 'rating',
                    'rating_id' => (string) $rating->id
                ]
            ));

            return $rating;
        });

        return $this->sendSuccess('Rating updated successfully', $rating);
    }

    /**
     * Delete a rating
     */
    public function destroyRating($ratingId)
    {
        $rating = auth()->user()->ratings()->find($ratingId);

        if (is_null($rating)) {
            return $this->sendSuccess('Rating not found');
        }

        $rating->delete();

        return $this->sendSuccess('Rating deleted successfully');
    }

    /**
     * Search for nearby drivers and return all necessary data needed related to the search query
     */
    public function searchDrivers()
    {
        $validator = validator()->make(request()->all(), [
            'origin' => ['required'],
            'destination' => ['required'],
            'vehicle_id' => ['required', new VehicleExists],
            'search_radius' => ['nullable', 'numeric']
        ], [
            'vehicle_id.exists' => 'The :attribute does not exist'
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        extract($validator->validated());

        // Get the coordinates of the user based on the given address
        $geocodingData = app()->make(Geocoding::class)->direct($origin);

        // Check if the response is okay from Google
        if ($geocodingData['status'] !== 'OK') {
            return $this->sendGoogleGeocodingErrorMessage($geocodingData['status']);
        }

        $formattedGeocodingData = $this->formatGeocodingResponse($geocodingData);

        // Get the coordinates from the geocoding data
        $latitude = $formattedGeocodingData['latitude'];
        $longitude = $formattedGeocodingData['longitude'];

        // Get the coordinate bounds
        $data = app()->make(Location::class)->calculateCoordinateBounds($latitude, $longitude, $search_radius ?? config('handova.search_radius'));

        // Get the driver's that are in the location
        $drivers = User::withAvg('jobRatings as average_rating', 'stars')
                        ->with(['ride'])
                        ->whereRelation('ride', 'vehicle_id', $vehicle_id)
                        ->validDrivers()
                        ->online()
                        ->whereBetween('location_latitude', [data_get($data, 'latitude.min'), data_get($data, 'latitude.max')])
                        ->whereBetween('location_longitude', [data_get($data, 'longitude.min'), data_get($data, 'longitude.max')])
                        ->get();

        // Check if the drivers is empty
        if ($drivers->isEmpty()) {
            return $this->sendSuccess('No drivers found at the moment. Please try again later');
        }

        // Get the directions data
        $directionData = app()->make(Direction::class)->get($origin, $destination);

        // Check if the response is okay from Google
        if ($directionData['status'] !== 'OK') {
            return $this->sendGoogleDirectionErrorMessage($directionData['status']);
        }

        // Format the data gotten from the Google Directions API
        $formattedData = $this->formatDirectionResponse($directionData);

        // Calculate the total distance in metres
        $totalDistance = collect($formattedData)->sum('distance.value');

        // This is the total duration according to Google. We could need this later but not now
        // $totalDuration = collect($formattedData)->sum('duration.value');

        // Get the vehicle
        $vehicle = Vehicle::find($vehicle_id);

        // Calculate the time in seconds it will take a particular type of vehicle to reach the destination
        $possibleDeliveryTime = app()->make(Speed::class)->calculateMinMaxDeliveryTime($totalDistance, $vehicle);

        // Get the exact time it will take for delivery to take place
        $exactTime = app()->make(Speed::class)->calculateTotalTime($totalDistance, $vehicle);

        // Calculate the main amount payable by a user
        $exactAmount = app()->make(Payment::class)->calculateMainAmount($totalDistance, $vehicle);

        // Calculate the minimum and maximum amount to be paid
        $possibleAmount = app()->make(Payment::class)->calculateMinMaxAmount($totalDistance, $vehicle);

        // Append the possible arrival time for each driver and format the rating to the nearest 1 decimal place
        $drivers->each(function($driver) use ($latitude, $longitude, $vehicle) {
            $driver->setAttribute('possible_arrival_time', [
                'unit' => 'seconds',
                'ranges' => app()->make(Speed::class)->possibleArrivalTime($latitude, $longitude, $driver->location_latitude, $driver->location_longitude, $vehicle)
            ]);

            $driver->setAttribute('average_rating', round($driver->average_rating, 1));
        });

        // TODO - Amount payable range
        return $this->sendSuccess('Request successful', [
            'drivers' => $drivers,
            'possible_delivery_time' => [
                'unit' => 'seconds',
                'exact_value' => app()->make(Speed::class)->convertToNearestMinuteInSeconds($exactTime),
                'ranges' => [
                    'min' => data_get($possibleDeliveryTime, 'min'),
                    'max' => data_get($possibleDeliveryTime, 'max'),
                ]
            ],
            'price' => [
                'currency' => config('handova.currency'),
                'exact_value' => $exactAmount,
                'ranges' => [
                    'min' => data_get($possibleAmount, 'min'),
                    'max' => data_get($possibleAmount, 'max'),
                ]
            ]
        ]);
    }

    /**
     * Search for the different vehicles and their prices ranges for order completion
     */
    public function searchVehicles()
    {
        $vehicles = Vehicle::all();

        // This shouldn't run as we should have vehicles added by the Admin
        if ($vehicles->isEmpty()) {
            return $this->sendErrorMessage('We currently have no vehicles. Please check back later', 404);
        }

        $validator = validator()->make(request()->all(), [
            'origin' => ['required'],
            'destination' => ['required']
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        extract($validator->validated());

        // Get the directions data
        $directionData = app()->make(Direction::class)->get($origin, $destination);

        // Check if the response is okay from Google
        if ($directionData['status'] !== 'OK') {
            return $this->sendGoogleDirectionErrorMessage($directionData['status']);
        }

        // Format the data gotten from the Google Directions API
        $formattedData = $this->formatDirectionResponse($directionData);

        // Calculate the total distance in metres
        $totalDistance = collect($formattedData)->sum('distance.value');

        // This is the total duration according to Google. We could need this later but not now
        // $totalDuration = collect($formattedData)->sum('duration.value');

        // Return the different possible prices and arrival times for each vehicle
        $vehicles->each(function($vehicle) use ($totalDistance) {
            // Calculate the time in seconds it will take a particular type of vehicle to reach the destination
            $possibleDeliveryTime = app()->make(Speed::class)->calculateMinMaxDeliveryTime($totalDistance, $vehicle);

            // Calculate the minimum and maximum amount payable by a user
            $possiblePrice = app()->make(Payment::class)->calculateMinMaxAmount($totalDistance, $vehicle);
     
            // Get the exact time it will take for delivery to take place
            $exactTime = app()->make(Speed::class)->calculateTotalTime($totalDistance, $vehicle);

            // Calculate the main amount payable by a user
            $exactAmount = app()->make(Payment::class)->calculateMainAmount($totalDistance, $vehicle);

            $vehicle->setAttribute('possible_delivery_time', [
                'unit' => 'seconds',
                'exact_value' => app()->make(Speed::class)->convertToNearestMinuteInSeconds($exactTime),
                'ranges' => [
                    'min' => data_get($possibleDeliveryTime, 'min'),
                    'max' => data_get($possibleDeliveryTime, 'max')
                ]
            ]);

            $vehicle->setAttribute('possible_price', [
                'currency' => config('handova.currency'),
                'exact_value' => $exactAmount,
                'ranges' => [
                    'min' => data_get($possiblePrice, 'min'),
                    'max' => data_get($possiblePrice, 'max'),
                ]
            ]);
        });

        return $this->sendSuccess('Request successful', $vehicles);
    }

    /**
     * Get the orders of the authenticated user
     */
    public function orders()
    {
        $perPage = request()->query('per_page') ?? config('handova.per_page');

        $orders = auth()->user()->orders()->with(['driver.ride'])->latest()->paginate($perPage);

        return $this->sendSuccess('Request successful', $orders->items());
    }

    /**
     * Track an order
     */
    public function trackOrder($orderId)
    {
        // Set the application settings
        app()->make(Application::class)->set();

        $order = auth()->user()->orders()->with(['driver'])->find($orderId);

        if (is_null($order)) {
            return $this->sendErrorMessage('Order not found', 404);
        }

        switch ($order->delivery_status) {
            case Order::STATUS_PENDING:
                return $this->sendErrorMessage('Awaiting driver reply on order');
            break;

            case Order::STATUS_COMPLETED:
            case Order::STATUS_REJECTED:
                return $this->sendErrorMessage('You cannot track this order as it is '.$order->delivery_status);
            break;

            case Order::STATUS_ACCEPTED:
            case Order::STATUS_EN_ROUTE:
                // Just as a fail safe, we will check if the driver exists
                if (is_null($order->driver)) {
                    return $this->sendErrorMessage('Driver does not exist or is not at '.config('app.name').' anymore');
                }

                // Check if the driver is offline
                if ($order->driver->online === User::OFFLINE) {
                    return $this->sendErrorMessage('Unable to track driver, currently offline');
                }

                // If the driver does not have an updated coordinates
                if (!isset($order->driver->location_latitude) || !isset($order->driver->location_longitude)) {
                    return $this->sendErrorMessage('Cannot track order at the moment. Please try again later');
                }

                /**
                 * If the order status is accepted, origin is the current location and destination is the
                 * pickup location while when it en route, the origin is pickup location and the destination is
                 * the delivery location
                 */
                if ($order->delivery_status === Order::STATUS_ACCEPTED) {
                    $destination = $order->pickup_location;
                } else {
                    // Get the receivers
                    $receivers = $order->receivers;

                    // Get the last item of the receivers
                    $destination = array_pop($receivers);

                    $destination = $destination['formatted_address'];
                }

                $directionData = app()->make(Direction::class)->get("{$order->driver->location_latitude},{$order->driver->location_longitude}", $destination);

                // Check if the response is okay from Google
                if ($directionData['status'] !== 'OK') {
                    return $this->sendGoogleDirectionErrorMessage($directionData['status']);
                }
        
                // Format the data gotten from the Google Directions API
                $formattedDirectionData = $this->formatDirectionResponse($directionData);

                // Calculate the time in seconds
                $totalTime = collect($formattedDirectionData)->sum('duration.value');
                // If order status is accepted, origin is the current location and destination is the 

                // Return the longitude and latitude of the driver
                return $this->sendSuccess('Request successful', [
                    'latitude' => (float) $order->driver->location_latitude,
                    'longitude' => (float) $order->driver->location_longitude,
                    'time' => $totalTime
                ]);
            break;

            default:
                return $this->sendErrorMessage('Invalid delivery status');
            break;
        }
    }

    /**
     * Get an order
     */
    public function showOrder($orderId)
    {
        $order = auth()->user()->orders()->with(['driver.ride'])->find($orderId);

        if (is_null($order)) {
            return $this->sendErrorMessage('Order not found', 404);
        }

        return $this->sendSuccess('Request successful', $order);
    }

    /**
     * Rate an order
     */
    public function rateOrder($orderId)
    {
        $order = auth()->user()->orders()->with(['rating', 'driver'])->find($orderId);

        if (is_null($order)) {
            return $this->sendErrorMessage('Order not found', 404);
        }

        // Check if there is already a rating for this order
        if (!is_null($order->rating)) {
            return $this->sendSuccess('Order has already been rated. You could try updating the rating');
        }

        $validator = validator()->make(request()->all(), [
            'stars' => ['required', 'integer', 'in:1,2,3,4,5'],
            'comment' => ['nullable']
        ], [
            'stars.in' => 'The :attribute must have a value between 1 to 5'
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        $rating = DB::transaction(function() use ($validator, $order) {
            extract($validator->validated());

            // Create the rating
            $rating = auth()->user()->ratings()->create([
                'order_id' => $order->id,   
                'driver_id' => $order->driver?->id,
                'stars' => $stars,
                'comment' => $comment ?? null
            ]);

            // Notify the driver that a rating has just been added
            $order->driver?->notifications()?->create([
                'message' => 'You have just been rated by a customer'
            ]);

            dispatch(new SendPushNotification(
                $rating->driver,
                'New Order Rating',
                'A customer '.auth()->user()->first_name.' '.auth()->user()->last_name.' just rated an order which you dispatched',
                [
                    'type' => 'rating',
                    'rating_id' => (string) $rating->id
                ]
            ));

            dispatch(new SendPushNotification(
                auth()->user(),
                'Order Rated Successfully',
                "You have successfully rated your order with ID #{$order->id}",
                [
                    'type' => 'rating',
                    'rating_id' => (string) $rating->id
                ]
            ));

            return $rating->setAttribute('order', $order);
        });

        return $this->sendSuccess('Order rated successfully', $rating, 201);
    }

    /**
     * Get the estimation of possible price and possible delivery time
     */
    public function orderEstimation()
    {
        $validator = validator()->make(request()->all(), [
            'origin' => ['required'],
            'destinations' => ['required', new JsonMax(3)],
            'vehicle_id' => ['required', new VehicleExists]
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        extract($validator->validated());

        // JSON decode the destinations
        $destinations = json_decode($destinations, true);

        $directionData = app()->make(Direction::class)->get($origin, $destinations[0], array_slice($destinations, 1) ?: null);

        // Check if the response is okay from Google
        if ($directionData['status'] !== 'OK') {
            return $this->sendGoogleDirectionErrorMessage($directionData['status']);
        }

        // Format the data gotten from the Google Directions API
        $formattedDirectionData = $this->formatDirectionResponse($directionData);

        // Calculate the total distance in metres
        $totalDistance = collect($formattedDirectionData)->sum('distance.value');

        // Get the vehicle
        $vehicle = Vehicle::find($vehicle_id);

        // Calculate the time in seconds it will take a particular type of vehicle to reach the destination
        $possibleDeliveryTime = app()->make(Speed::class)->calculateMinMaxDeliveryTime($totalDistance, $vehicle);

        // Get the exact time it will take for delivery to take place
        $exactTime = app()->make(Speed::class)->calculateTotalTime($totalDistance, $vehicle);

        // Calculate the main amount payable by a user
        $exactAmount = app()->make(Payment::class)->calculateMainAmount($totalDistance, $vehicle);

        // Calculate the minimum and maximum amount to be paid
        $possibleAmount = app()->make(Payment::class)->calculateMinMaxAmount($totalDistance, $vehicle);

        return $this->sendSuccess('Request successful', [
            'delivery_time' => [
                'unit' => 'seconds',
                'exact_value' => app()->make(Speed::class)->convertToNearestMinuteInSeconds($exactTime),
                'ranges' => [
                    'min' => data_get($possibleDeliveryTime, 'min'),
                    'max' => data_get($possibleDeliveryTime, 'max')
                ]
            ],
            'price' => [
                'currency' => config('handova.currency'),
                'exact_value' => $exactAmount,
                'ranges' => [
                    'min' => data_get($possibleAmount, 'min'),
                    'max' => data_get($possibleAmount, 'max'),
                ]
            ]
        ]);
    }

    /**
     * Cancel an order
     */
    public function cancelOrder($orderId)
    {
        $order = auth()->user()->orders()->with(['driver'])->find($orderId);

        if (is_null($order)) {
            return $this->sendErrorMessage('Order not found', 404);
        }

        // The cases where we cannot cancel an order
        switch ($order->delivery_status) {
            case Order::STATUS_CANCELED:
                return $this->sendSuccess('Order has already been canceled');
            break;

            case Order::STATUS_COMPLETED:
                return $this->sendSuccess('This order has been completed');
            break;

            case Order::STATUS_EN_ROUTE:
            case Order::STATUS_REJECTED:
            case Order::STATUS_ACCEPTED:
                return $this->sendErrorMessage("You cannot cancel the order as it is {$order->delivery_status}", 403);
            break;
        }

        $validator = validator()->make(request()->all(), [
            'reason' => ['nullable']
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        extract($validator->validated());

        // Get the cancellation fee
        $cancellationFee = app()->make(Cancellation::class, [
            'amount' => $order->amount
        ])->fee();

        /**
         * Cancel the order
         */
        $response = $this->processOrderCancellation($order, $cancellationFee, $reason ?? null);

        // Check if there was an error while processing the order payment
        if ($response['status'] === false) {
            return $this->sendErrorMessage($response['message'], 403);
        }

        /**
         * Send firebase notification to the driver
         */
        dispatch(new SendPushNotification(
            $order->driver,
            "Job #{$order->id} Cancelled By Customer",
            "Sorry but the Job with ID #{$order->id} has been canceled by the customer",
            [
                'type' => 'job_cancellation',
                'job_id' => (string) $order->id,
                'delivery_status' => $order->delivery_status
            ]
        ));

        /**
         * Send firebase notification to the customer
         */
        dispatch(new SendPushNotification(
            auth()->user(),
            "Order #{$order->id} Cancelled Successfully",
            "You have successfully cancelled your order with ID #{$order->id}",
            [
                'type' => 'order_cancellation',
                'order_id' => (string) $order->id,
                'delivery_status' => $order->delivery_status
            ]
        ));

        return $this->sendSuccess('You have successfully canceled your order');
    }

    /**
     * The arrival time of a driver
     */
    public function driverArrivalTime($orderId)
    {
        $order = auth()->user()->orders()->with(['driver.ride.vehicle'])->find($orderId);

        if (is_null($order)) {
            return $this->sendErrorMessage('Order not found', 404);
        }

        // We can only get the arrival time of the order if the order has been accepted by a driver
        if ($order->delivery_status !== Order::STATUS_ACCEPTED) {
            return $this->sendErrorMessage('You cannot get the arrival time of the driver as the order is '.$order->delivery_status);
        }

        // Order accepted, so we calculate the arrival time of the driver
        $possibleArrivalTime = app()->make(Speed::class)->possibleArrivalTime($order->pickup_location_latitude, $order->pickup_location_longitude, $order->driver->location_latitude, $order->driver->location_longitude, $order->driver?->ride?->vehicle);

        return $this->sendSuccess('Request successful', [
            'unit' => 'seconds',
            'possible_arrival_time' => $possibleArrivalTime
        ]);
    }

    /**
     * Get active order
     */
    public function active()
    {
        $activeOrder = auth()->user()->orders()
                                    ->with(['driver.ride.vehicle'])
                                    ->where('delivery_status', Order::STATUS_ACCEPTED)
                                    ->orWhere('delivery_status', Order::STATUS_EN_ROUTE)
                                    ->latest()
                                    ->first();
        
        // If there is an active order
        if (!is_null($activeOrder)) {
            // Make the driver coordinates visible
            $activeOrder->driver->makeVisible([
                'location_latitude',
                'location_longitude',
                'location_updated_at'
            ]);

            /**
             * If the order is accepted, the destination is the pickup location while if it is en route,
             * the destination is the receivers address
             */
            if ($activeOrder->delivery_status === Order::STATUS_ACCEPTED) {
                $latitude = $activeOrder->pickup_location_latitude;
                $longitude = $activeOrder->pickup_location_longitude;
            } elseif ($activeOrder->delivery_status === Order::STATUS_EN_ROUTE) {
                // Get the receivers
                $receivers = $activeOrder->receivers;

                // Get the last item of the receivers
                $destination = array_pop($receivers);

                $latitude = $destination['latitude'];
                $longitude = $destination['longitude'];
            }

            // Get the possible arrival time of the driver
            $possibleArrivalTime = app()->make(Speed::class)->possibleArrivalTime($latitude, $longitude, $activeOrder->driver->location_latitude, $activeOrder->driver->location_longitude, $activeOrder->driver->ride->vehicle);

            // Get the average rating of the driver through the driver model
            $driver = User::withAvg('jobRatings as average_rating', 'stars')->find(auth()->id());

            $activeOrder->driver->setAttribute('arrival_time', [
                'unit' => 'seconds',
                'value' => $possibleArrivalTime['max']
            ]);

            $activeOrder->driver->setAttribute('average_rating', $driver?->average_rating);
        }

        return $this->sendSuccess('Request successful', $activeOrder);
    }

}
