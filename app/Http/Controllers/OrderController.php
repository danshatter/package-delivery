<?php

namespace App\Http\Controllers;

use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\{DB, Storage};
use App\Models\{User, Vehicle, Order};
use App\Rules\{ValidDriver, Phone, DifferentPhone, VehicleExists, ValidUserCard};
use App\Services\Google\{Direction, Geocoding};
use App\Services\Geography\Location;
use App\Services\Phone\Nigeria;
use App\Services\Calculator\{Payment, Speed};
use App\Services\Files\Upload;
use App\Traits\Orders\{Process, Create};

class OrderController extends Controller
{
    use Process, Create;

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
     * Create an order
     */
    public function store()
    {
        $validator = validator()->make(request()->all(), [
            'pickup_location' => ['required'],
            'vehicle_id' => ['required', new VehicleExists],
            'type' => ['required', Rule::in(config('handova.services'))],
            'category' => ['required'],
            'sender_name' => ['required'],
            'sender_phone' => ['required', new Phone],
            'sender_address' => ['required'],
            'sender_email' => ['required', 'email'],
            'images' => ['required', 'array'],
            'images.*' => ['required', 'image', 'mimes:jpg,jpeg,png'],
            'receivers' => ['required', 'array', 'max:3'],
            'receivers.*.name' => ['required'],
            'receivers.*.phone' => ['required', new Phone, new DifferentPhone],
            'receivers.*.address' => ['required', 'different:sender_address'],
            'receivers.*.items' => ['required', 'array'],
            'receivers.*.items.*' => ['required', 'distinct'],
            'receivers.*.email' => ['nullable', 'email', 'different:sender_email'],
            'receivers.*.quantity' => ['nullable', 'integer'],
            'receivers.*.weight' => ['nullable', 'numeric'],
            'receivers.*.delivery_note' => ['nullable'],
            'payment_method' => ['required', Rule::in(config('handova.payment_methods'))],
            'card' => ['nullable', 'required_if:payment_method,card', new ValidUserCard(auth()->user())]
        ], [
            'type.in' => 'The :attribute must be any of '.implode(', ', config('handova.services')),
            'payment_method.in' => 'The :attribute must be any of '.implode(', ', config('handova.payment_methods')),
        ], [
            'receivers.*.name' => 'receiver name',
            'receivers.*.phone' => 'receiver phone number',
            'receivers.*.address' => 'receiver address',
            'receivers.*.items' => 'receiver items',
            'receivers.*.items.*' => 'receiver items',
            'receivers.*.email' => 'receiver email',
            'receivers.*.image' => 'image',
            'receivers.*.quantity' => 'quantity',
            'receivers.*.weight' => 'weight',
            'receivers.*.delivery_note' => 'delivery note',
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        // Extract some data to be used for other requests
        $pickup_location = request()->input('pickup_location');
        $receivers = request()->input('receivers');
        $vehicle_id = request()->input('vehicle_id');
        $payment_method = request()->input('payment_method');
        $card_id = request()->input('card');

        // Get the coordinates of the pickup address
        $geocodingData = app()->make(Geocoding::class)->direct($pickup_location);

        // Check if the response is okay from Google
        if ($geocodingData['status'] !== 'OK') {
            return $this->sendGoogleGeocodingErrorMessage($geocodingData['status']);
        }

        $formattedGeocodingData = $this->formatGeocodingResponse($geocodingData);

        // Get the coordinates from the geocoding data
        $latitude = $formattedGeocodingData['latitude'];
        $longitude = $formattedGeocodingData['longitude'];
        $formattedPickupLocation = $formattedGeocodingData['address'];

        // Get the destination we will use for the request. We will use the last one in the array
        $destination = end($receivers)['address'];

        // Redeclare receivers array to be used by reference once
        $receiversArray = $receivers;

        // Remove the last one which is the destination
        array_pop($receiversArray);

        // Get any waypoints deliveries that could occur
        $waypoints = $receiversArray;

        /**
         * Calculate the directions data using the first receiver address as the destination and
         * any other addresses as the waypoints
         */
        $directionData = app()->make(Direction::class)->get(
            $pickup_location,
            $destination,
            !empty($waypoints) ? array_map(fn($waypoint) => $waypoint['address'], $waypoints) : null
        );

        // Check if the response is okay from Google
        if ($directionData['status'] !== 'OK') {
            return $this->sendGoogleDirectionErrorMessage($directionData['status']);
        }

        // Format the data gotten from the Google Directions API
        $formattedData = $this->formatDirectionResponse($directionData);

        // Get the waypoint order just in case of multiple delivery addresses
        $waypointsOrder = $this->googleDirectionWaypointOrder($directionData);

        // Get the formatted addresses
        $formattedAddresses = array_map(fn($location) => $location['end']['address'], $formattedData);

        // Get the formatted latitudes
        $formattedLatitudes = array_map(fn($location) => $location['end']['latitude'], $formattedData);

        // Get the formatted longitudes
        $formattedLongitudes = array_map(fn($location) => $location['end']['longitude'], $formattedData);

        // Calculate the total distance in metres
        $totalDistance = collect($formattedData)->sum('distance.value');

        // Get the coordinate bounds
        $coordinateData = app()->make(Location::class)->calculateCoordinateBounds($latitude, $longitude, 3);

        // Choose a nearby driver (In this case, driver is chosen at random. We could add another logic later)
        $drivers = User::withAvg('jobRatings as average_rating', 'stars')
                        ->with(['ride.vehicle'])
                        ->whereRelation('ride', 'vehicle_id', $vehicle_id)
                        ->validDrivers()
                        ->online()
                        ->whereBetween('location_latitude', [data_get($coordinateData, 'latitude.min'), data_get($coordinateData, 'latitude.max')])
                        ->whereBetween('location_longitude', [data_get($coordinateData, 'longitude.min'), data_get($coordinateData, 'longitude.max')])
                        ->get();

        // Check if a driver is available
        if ($drivers->isEmpty()) {
            return $this->sendErrorMessage('No drivers found at the moment. Please try again later');
        }

        // Select a driver at random
        $driver = $drivers->random();

        // Check if the user has added a card to the system
        if (!auth()->user()->cards()->exists()) {
            return $this->sendErrorMessage('Please add a card to complete order placement');
        }

        // Get the possible arrival time of the driver
        $possibleArrivalTime = app()->make(Speed::class)->possibleArrivalTime($latitude, $longitude, $driver->location_latitude, $driver->location_longitude, $driver->ride->vehicle);

        // Calculate the exact amount again so the user doesn't enter their custom amount
        $amount = app()->make(Payment::class)->calculateMainAmount($totalDistance, $driver->ride->vehicle);

        // Check what type of payment method was selected
        switch ($payment_method) {
            // Everything looks good, Now place the order
            case 'wallet':
                // Check if the user has enough money in their account
                if (!auth()->user()->canPay($amount)) {
                    return $this->sendErrorMessage('Insufficient balance');
                }
                
                // Place the order using wallet
                return $this->placeOrder($validator->validated(), $driver, $amount, $formattedPickupLocation, $formattedAddresses, $waypointsOrder, $possibleArrivalTime, null, $formattedLatitudes, $formattedLongitudes, $latitude, $longitude, $totalDistance);
            break;

            case 'card':
                // Get the card in question
                $card = auth()->user()->cards()->find($card_id);

                /**
                 * Perhaps, check if the card has sufficient balance to perform the transaction
                 */

                // Place the order using card
                return $this->placeOrder($validator->validated(), $driver, $amount, $formattedPickupLocation, $formattedAddresses, $waypointsOrder, $possibleArrivalTime, $card, $formattedLatitudes, $formattedLatitudes, $latitude, $longitude, $totalDistance);
            break;
            
            // This should never run
            default:
                return $this->sendErrorMessage('Invalid Payment method selected', 500);
            break;
        }
    }

    /**
     * Update an order
     */
    public function update($orderId)
    {
        $order = auth()->user()->orders()->find($orderId);

        if (is_null($order)) {
            return $this->sendErrorMessage('Order not found', 404);
        }

        // Check if the order has already been completed
        if ($order->delivery_status === Order::STATUS_COMPLETED) {
            return $this->sendSuccess('This order has already been delivered');
        }

        // Check if the order is en route
        if ($order->delivery_status === Order::STATUS_EN_ROUTE) {
            return $this->sendSuccess('This order is en route');
        }

        // Check if the order has been accepted
        if ($order->delivery_status === Order::STATUS_ACCEPTED) {
            return $this->sendSuccess('This order has been accepted. Delivery process ongoing');
        }

        // Check if the order has been accepted
        if ($order->delivery_status === Order::STATUS_PENDING) {
            return $this->sendSuccess('Awaiting reply from driver');
        }

        // Check for any other state apart from Rejection
        if ($order->delivery_status !== Order::STATUS_REJECTED) {
            return $this->sendErrorMessage('An unknown error occurred. Please contact us for more details', 500);
        }

        // Order can only be updated if the order has been rejected by a previous driver
        if (!auth()->user()->canPay($order->amount)) {
            return $this->sendErrorMessage('Insufficient balance');
        }

        $validator = validator()->make(request()->all(), [
            'driver_id' => ['required', 'exists:users,id', new ValidDriver]
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        extract($validator->validated());

        // Get the driver
        $driver = User::with(['ride'])->validDrivers()->where('id', $driver_id)->first();

        // Check if the driver exists
        if (is_null($driver)) {
            return $this->sendErrorMessage('Driver does not exist or is not a valid driver', 404);
        }

        // This shouldn't run normally but just in case
        if (is_null($driver->ride)) {
            return $this->sendErrorMessage('This driver does not have a registered ride at the moment');
        }

        // Check to see if the driver is offline
        if ($driver->online === User::OFFLINE) {
            return $this->sendErrorMessage('This driver is currently offline. Please select another driver');
        }

        DB::transaction(function() use ($order, $driver) {
            // Assign a new driver
            $order->forceFill([
                'driver_id' => $driver->id
            ])->save();

            // Mark order as pending
            $order->markAsPending();

            // Create the notification to the user that their order has been created but payment remains
            auth()->user()->notifications()->create([
                'message' => "Order #{$order->id} was successfully assigned to another driver"
            ]);

            // Create the notification for the driver
            $driver->notifications()->create([
                'message' => "A new order with ID #{$order->id} was added for you"
            ]);

            /**
             * Possible firebase notification for the driver
             */
        });

        return $this->sendSuccess('Order updated with new driver', $order);
    }

}
