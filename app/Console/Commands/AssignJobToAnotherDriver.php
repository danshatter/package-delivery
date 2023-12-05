<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\{Order, User, Vehicle};
use App\Services\Geography\Location;
use App\Services\Google\Geocoding;
use App\Jobs\SendPushNotification;

class AssignJobToAnotherDriver extends Command
{

    private $maxSeconds = 20;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'job:assign';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign a job to another driver';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $startTime = time();

        start:

        // Get Orders that are due to be assigned to another driver
        $orders = Order::with(['driver.ride', 'customer'])
                    ->where('delivery_status', Order::STATUS_PENDING)
                    ->where('updated_at', '<=', Carbon::now()->subSeconds($this->maxSeconds))
                    ->get();

        $startLoopTime = time();

        foreach ($orders as $order) {
            // Get the coordinate bounds
            $coordinateData = app()->make(Location::class)->calculateCoordinateBounds($order->pickup_location_latitude, $order->pickup_location_longitude, 3);

            // Choose a nearby driver (In this case, driver is chosen at random. We could add another logic later)
            $drivers = User::validDrivers()
                        ->online()
                        ->whereRelation('ride', 'vehicle_id', $order->driver?->ride?->vehicle?->id ?? Vehicle::first()?->id)
                        // Exclude the current driver
                        ->where('id', '!=', $order->driver_id)
                        // Exclude the past drivers
                        ->whereNotIn('id', $order->past_drivers ?? [])
                        ->whereBetween('location_latitude', [data_get($coordinateData, 'latitude.min'), data_get($coordinateData, 'latitude.max')])
                        ->whereBetween('location_longitude', [data_get($coordinateData, 'longitude.min'), data_get($coordinateData, 'longitude.max')])
                        ->get();

            if (!$drivers->isEmpty()) {
                // Pick a driver at random
                $driver = $drivers->random();

                DB::transaction(function() use ($order, $driver) {
                    // Get the old driver
                    $oldDriver = $order->driver_id;

                    if (is_null($order->past_drivers)) {
                        $pastDrivers = [];
                    } else {
                        $pastDrivers = $order->past_drivers;
                    }

                    // Push the current driver the order is assigned to 
                    array_push($pastDrivers, $oldDriver);

                    // Assign the order to this driver
                    $order->forceFill([
                        'driver_id' => $driver->id,
                        'past_drivers' => $pastDrivers
                    ])->save();

                    // Create the notification for the driver
                    $driver->notifications()->create([
                        'message' => "A new job with ID #{$order->id} was assigned to you"
                    ]);
                });

                // Send a push notification to the driver on a new job
                dispatch(new SendPushNotification(
                    $driver,
                    'New Job Alert',
                    "Your services has been requested. An order with ID #{$order->id} is assigned to you",
                    [
                        'type' => 'job',
                        'job_id' => (string) $order->id,
                        'driver_id' => (string) $driver->id
                    ]
                ));
            } else {
                // Get an array representation of the past drivers
                if (is_null($order->past_drivers)) {
                    $pastDrivers = [];
                } else {
                    $pastDrivers = $order->past_drivers;
                }

                /**
                 * Check if the current driver is in the past drivers array.
                 * If they are not, we add the driver
                 */
                if (!is_null($order->driver_id) && !in_array($order->driver_id, $pastDrivers) ) {
                    array_push($pastDrivers, $order->driver_id);

                    $order->forceFill([
                        'driver_id' => null,
                        'past_drivers' => $pastDrivers
                    ])->save();
                }

                // Check if the order is already idle
                if ($order->delivery_status !== Order::STATUS_IDLE) {
                    // No drivers currently, Mark job as idle
                    $order->markAsIdle();
                }

                // Handle any additional things we want
                // Send a push notification to the driver on a new job
                dispatch(new SendPushNotification(
                    $order->customer,
                    'No Drivers Available',
                    'There is currently no drivers available at the moment. Please try again later.',
                    [
                        'type' => 'no-drivers-available',
                        'order_id' => (string) $order->id,
                    ]
                ));
            }            
        }

        $endLoopTime = time();

        // Get the time difference it took for the loop
        $loopTimeTaken = $endLoopTime - $startLoopTime;

        // Get the time the command has taken so far
        $timeTaken = $endLoopTime - $startTime;

        // The time taken for it to run
        if ($timeTaken < 60) {
            // We run the loop for every 20 seconds
            if ($loopTimeTaken < 20) {
                sleep(20 - $loopTimeTaken);
            }

            goto start;
        }
    }
}
