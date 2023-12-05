<?php

namespace App\Http\Controllers\Driver;

use Carbon\Carbon;
use Illuminate\Support\Facades\{DB, Storage};
use App\Http\Controllers\Controller;
use App\Models\{User, Order, Transaction, Ride, SuperNotification};
use App\Rules\{ValidUserAccount, VehicleBrandExists, VehicleExists, VehicleModelExists};
use App\Services\Calculator\Withdrawal;
use App\Services\Files\Upload;
use App\Services\Settings\Application;
use App\Traits\Orders\Payment;
use App\Jobs\SendPushNotification;

class MainController extends Controller
{
    use Payment;

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
     * Check the registration status
     */
    public function status()
    {
        // Set the application settings
        app()->make(Application::class)->set();

        switch (auth()->user()->driver_registration_status) {
            // Registration still in a pending stage
            case User::DRIVER_STATUS_PENDING:
                return $this->sendSuccess('Your registration is still being processed');
            break;

            // Registration rejected
            case User::DRIVER_STATUS_REJECTED:
                return $this->sendSuccess('Sorry but your registration to be a driver at '.config('app.name').' has been rejected');
            break;

            // Registration accepted
            case User::DRIVER_STATUS_ACCEPTED:
                return $this->sendSuccess('Your registration has been accepted. You are now a qualified driver at '.config('app.name'));
            break;
            
            default:
                return $this->sendErrorMessage('Invalid status. Please contact us for more details', 500);
            break;
        }
    }

    /**
     * Check the status of the driver's ride
     */
    public function rideStatus()
    {
        // Set the application settings
        app()->make(Application::class)->set();

        auth()->user()->load(['ride']);

        // Check if the user has a ride
        if (is_null(auth()->user()->ride)) {
            return $this->sendErrorMessage('You do not have a registered ride at '.config('app.name'), 404);
        }

        switch (auth()->user()->ride->status) {
            // Ride registration is still pending
            case Ride::PENDING:
                return $this->sendSuccess('Your ride registration is still being processed');
            break;
            
            // Ride registration accepted
            case Ride::APPROVED:
                return $this->sendSuccess('Your ride is now an approved ride at '.config('app.name'));
            break;

            // Ride registration rejected
            case Ride::REJECTED:
                return $this->sendSuccess('Sorry but your ride registration at '.config('app.name').' has been rejected');
            break;
            
            default:
                return $this->sendErrorMessage('Invalid registration status. Please contact us for more details', 500);
            break;
        }
    }

    /**
     * Accept a job
     */
    public function acceptJob($jobId)
    {
        $job = auth()->user()->jobs()->with(['customer'])->find($jobId);

        if (is_null($job)) {
            return $this->sendErrorMessage('Job not found', 404);
        }

        // The cases where we cannot accept an job
        switch ($job->delivery_status) {
            case Order::STATUS_ACCEPTED:
                return $this->sendSuccess('You have already accepted this job');
            break;

            case Order::STATUS_COMPLETED:
                return $this->sendSuccess('This job has been completed');
            break;

            case Order::STATUS_EN_ROUTE:
            case Order::STATUS_REJECTED:
            case Order::STATUS_CANCELED:
            case Order::STATUS_IDLE:
                return $this->sendErrorMessage("You cannot accept this order as it is {$job->delivery_status}", 403);
            break;
        }

        DB::transaction(function() use ($job) {
            // Mark the delivery as accepted
            $job->markAsAccepted();

            // Create a notification for the customer
            $job->customer->notifications()->create([
                'message' => "Your order with ID #{$job->id} has just been accepted by the driver"
            ]);

            dispatch(new SendPushNotification(
                $job->customer,
                'Order Accepted',
                "Your order with ID #{$job->id} was just accepted by a driver. The driver will be at the pickup location shortly",
                [
                    'type' => 'order',
                    'order_id' => (string) $job->id,
                    'delivery_status' => $job->delivery_status
                ]
            ));

            dispatch(new SendPushNotification(
                auth()->user(),
                'Job Accepted Successfully',
                "You successfully accepted the job with ID #{$job->id}",
                [
                    'type' => 'job',
                    'job_id' => (string) $job->id,
                    'delivery_status' => $job->delivery_status
                ]
            ));
        });

        return $this->sendSuccess('Job accepted successfully');
    }

    /**
     * Reject a job
     */
    public function rejectJob($jobId)
    {
        $job = auth()->user()->jobs()->with(['customer'])->find($jobId);

        if (is_null($job)) {
            return $this->sendErrorMessage('Job not found', 404);
        }

        // The cases where we cannot reject an job
        switch ($job->delivery_status) {
            case Order::STATUS_REJECTED:
                return $this->sendSuccess('You have already rejected this job');
            break;

            case Order::STATUS_COMPLETED:
                return $this->sendSuccess('This job has been completed');
            break;

            case Order::STATUS_EN_ROUTE:
            case Order::STATUS_ACCEPTED:
            case Order::STATUS_CANCELED:
            case Order::STATUS_IDLE:
                return $this->sendErrorMessage("You cannot reject this job as it is {$job->delivery_status}", 403);
            break;
        }

        DB::transaction(function() use ($job) {
            $job->markAsRejected();

            // Increase the count of the rejected orders by the driver
            auth()->user()->increment('rejected_orders_count');

            $job->customer->notifications()->create([
                'message' => "Your order with ID #{$job->id} was just rejected by the driver. Please assign delivery to another driver"
            ]);

            dispatch(new SendPushNotification(
                $job->customer,
                'Order Rejected',
                "Sorry but order with ID #{$job->id} was rejected. Please assign the order to another driver",
                [
                    'type' => 'order',
                    'order_id' => (string) $job->id,
                    'delivery_status' => $job->delivery_status
                ]
            ));

            dispatch(new SendPushNotification(
                auth()->user(),
                'Job Rejected',
                "You just rejected the job with ID #{$job->id}",
                [
                    'type' => 'job',
                    'job_id' => (string) $job->id,
                    'delivery_status' => $job->delivery_status
                ]
            ));
        });

        return $this->sendSuccess('Job has just been rejected');
    }

    /**
     * Mark a job as en route
     */
    public function jobEnRoute($jobId)
    {
        $job = auth()->user()->jobs()->with(['customer'])->find($jobId);

        if (is_null($job)) {
            return $this->sendErrorMessage('Job not found', 404);
        }

        // The cases where we cannot mark a job en route
        switch ($job->delivery_status) {
            case Order::STATUS_EN_ROUTE:
                return $this->sendSuccess('You have already marked this job en route');
            break;

            case Order::STATUS_COMPLETED:
                return $this->sendSuccess('This job has been completed');
            break;

            case Order::STATUS_PENDING:
            case Order::STATUS_REJECTED:
            case Order::STATUS_CANCELED:
            case Order::STATUS_IDLE:
                return $this->sendErrorMessage("You cannot mark this job en route as it is {$job->delivery_status}", 403);
            break;
        }

        /**
         * Make the payment of the order
         */
        $response = $this->payForOrder($job);

        // Check if there was an error while processing the order payment
        if ($response['status'] === false) {
            return $this->sendErrorMessage($response['message'], 403);
        }

        // Create a notification for the customer
        $job->customer->notifications()->create([
            'message' => "Your order with ID #{$job->id} is en route"
        ]);

        dispatch(new SendPushNotification(
            $job->customer,
            'Order En Route',
            "Your order with ID #{$job->id} is now en route",
            [
                'type' => 'order',
                'order_id' => (string) $job->id,
                'delivery_status' => $job->delivery_status
            ]
        ));

        dispatch(new SendPushNotification(
            auth()->user(),
            "Job #{$job->id} En Route",
            "You just marked the job #{$job->id} en route",
            [
                'type' => 'job',
                'job_id' => (string) $job->id,
                'delivery_status' => $job->delivery_status
            ]
        ));

        return $this->sendSuccess('Job is now en route');
    }

    /**
     * Mark a job as completed
     */
    public function jobCompleted($jobId)
    {
        $job = auth()->user()->jobs()->with(['customer'])->find($jobId);

        if (is_null($job)) {
            return $this->sendErrorMessage('Job not found', 404);
        }

        // The cases where we cannot complete an job
        switch ($job->delivery_status) {
            case Order::STATUS_COMPLETED:
                return $this->sendSuccess('This job is already completed');
            break;

            case Order::STATUS_PENDING:
            case Order::STATUS_REJECTED:
            case Order::STATUS_ACCEPTED:
            case Order::STATUS_CANCELED:
            case Order::STATUS_IDLE:
                return $this->sendErrorMessage("You cannot mark this job as completed as it is {$job->delivery_status}", 403);
            break;
        }

        // Process the order completion process
        $this->processOrderCompletion($job);

        dispatch(new SendPushNotification(
            $job->customer,
            "Order #{$job->id} Completed",
            "Your order with ID #{$job->id} is officially completed. Thank you for patronizing us",
            [
                'type' => 'order',
                'order_id' => (string) $job->id,
                'delivery_status' => $job->delivery_status
            ]
        ));

        dispatch(new SendPushNotification(
            auth()->user(),
            "Job #{$job->id} Completed",
            "The job with ID #{$job->id} is successfully marked as completed.",
            [
                'type' => 'job',
                'job_id' => (string) $job->id,
                'delivery_status' => $job->delivery_status
            ]
        ));

        return $this->sendSuccess('Job is officially completed');
    }

    /**
     * Get the jobs of the driver
     */
    public function jobs()
    {
        $perPage = request()->query('per_page') ?? config('handova.per_page');

        $jobs = auth()->user()->jobs()->latest()->paginate($perPage);

        return $this->sendSuccess('Request successful', $jobs->items());
    }

    /**
     * Update the driver's current location
     */
    public function updateCurrentLocation()
    {
        $validator = validator()->make(request()->all(), [
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric']
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        extract($validator->validated());

        auth()->user()->updateCurrentLocation($latitude, $longitude);

        return $this->sendSuccess('Location updated successfully', compact('latitude', 'longitude'));
    }

    /**
     * Go online
     */
    public function goOnline()
    {
        if (auth()->user()->online === User::ONLINE) {
            return $this->sendSuccess('You are already online');
        }

        // Set the user status to online
        auth()->user()->forceFill([
            'online' => User::ONLINE
        ])->save();

        return $this->sendSuccess('You are now online');
    }

    /**
     * Go offline
     */
    public function goOffline()
    {
        if (auth()->user()->online === User::OFFLINE) {
            return $this->sendSuccess('You are already offline');
        }

        // Set the user status to offline
        auth()->user()->forceFill([
            'online' => User::OFFLINE
        ])->save();

        return $this->sendSuccess('You are now offline');
    }

    /**
     * Get all driver ratings
     */
    public function ratings()
    {
        $perPage = request()->query('per_page') ?? config('handova.per_page');

        $ratings = auth()->user()->jobRatings()->with(['customer'])->latest()->paginate($perPage);

        return $this->sendSuccess('Request successful', $ratings->items());
    }

    /**
     * Get a rating on the driver
     */
    public function showRating($ratingId)
    {
        $rating = auth()->user()->jobRatings()->with(['customer'])->find($ratingId);

        if (is_null($rating)) {
            return $this->sendErrorMessage('Rating not found', 404);
        }

        return $this->sendSuccess('Request successful', $rating);
    }

    /**
     * Get a job by a driver
     */
    public function showJob($jobId)
    {
        $job = auth()->user()->jobs()->with(['customer'])->find($jobId);

        if (is_null($job)) {
            return $this->sendErrorMessage('Job not found');
        }

        return $this->sendSuccess('Request successful', $job);
    }

    /**
     * Update a driver's ride
     */
    public function rides()
    {
        auth()->user()->load(['ride']);

        $validator = validator()->make(request()->all(), [
            'vehicle_type' => ['required', new VehicleExists],
            'brand' => ['required'],
            'model' => ['required'],
            'plate_number' => ['required', 'unique:rides'.(is_null(auth()->user()->ride) ? '' : ',plate_number,'.auth()->user()->ride->id)],
            'valid_insurance_documents' => ['required', 'array', 'max:3'],
            'valid_insurance_documents.*' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf'],
            'valid_inspection_reports' => ['required', 'array', 'max:3'],
            'valid_inspection_reports.*' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf'],
        ], [], [
            'valid_insurance_documents.*' => 'valid insurance documents'
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        extract($validator->validated());

        // Upload the insurance documents
        $validInsuranceDocuments = [];

        // The storage of valid insurance documents
        foreach (request()->file('valid_insurance_documents') as $insuranceDocument) {
            $validInsuranceDocuments[] = $insuranceDocument->storePublicly('insurance-documents');
        }

        // Upload the inspection reports
        $validInspectionReports = [];

        foreach (request()->file('valid_inspection_reports') as $inspectionReport) {
            $validInspectionReports[] = $inspectionReport->storePublicly('inspection-reports');
        }

        // Delete the old insurance documents files if they exist
        Storage::delete(
            collect(auth()->user()->ride->valid_insurance_documents)
                ->map(fn($document) => app()->make(Upload::class)->pathFromUrl($document))
                ->all()
        );

        DB::transaction(function() use ($vehicle_type, $brand, $model, $plate_number, $validInsuranceDocuments, $validInspectionReports) {
            // Update the ride details of the driver
            auth()->user()->ride->forceFill([
                'brand' => $brand,
                'model' => $model,
                'plate_number' => $plate_number,
                'valid_insurance_documents' => $validInsuranceDocuments,
                'valid_inspection_reports' => $validInspectionReports
            ])->save();

            // Change the user ride status to pending
            auth()->user()->ride->markAsPending();

            /**
             * Send notification to Admin that a new ride needs approval
             */
            /**
             * Create a notification to the admin that a driver uploaded necessary documents
             * and requires verification
             */
            SuperNotification::create([
                'message' => 'A new driver with user ID #'.auth()->id().' has uploaded their document and requires verification'
            ]);
        });

        return $this->sendSuccess('Ride details updated successfully. You will be unavailable until your details has been approved');
    }

    /**
     * Check if the authenticated user is online
     */
    public function online()
    {
        return $this->sendSuccess('Request successful', [
            'online' => auth()->user()->online
        ]);
    }

    /**
     * Get the user's earnings for the week
     */
    public function weekEarnings()
    {
        // Get the start of the week
        $startOfWeek = Carbon::now()->startOfWeek(Carbon::SUNDAY);

        // Get the end of the week
        $endOfWeek = Carbon::now()->endOfWeek(Carbon::SATURDAY);

        // Get the jobs of the week
        $query = auth()->user()->jobs()
                            ->where('delivery_status', Order::STATUS_COMPLETED)
                            ->whereBetween('delivery_status_updated_at', [$startOfWeek, $endOfWeek]);

        return $this->sendSuccess('Request successful', [
            'start_date' => $startOfWeek,
            'end_date' => $endOfWeek,
            'total_amount_earned' => $query->sum('amount'),
            'total_jobs' => $query->count(),
            'currency' => config('handova.currency')
        ]);
    }

    /**
     * Get the withdrawal history of the user
     */
    public function withdrawals()
    {
        $perPage = request()->query('per_page') ?? config('handova.per_page');

        $transactions = auth()->user()->transactions()->withdrawal()->latest()->paginate($perPage);

        return $this->sendSuccess('Request successful', $transactions->items());
    }

    /**
     * Perform a withdrawal request
     */
    public function withdraw()
    {        
        $validator = validator()->make(request()->all(), [
            'amount' => ['required', 'integer'],
            'account_id' => ['required', 'integer', new ValidUserAccount(auth()->user())]
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        extract($validator->validated());

        // Get the maximum amount the user should have in their wallet
        $withdrawal = app()->make(Withdrawal::class, compact('amount'));

        // Check if the amount is withdrawable
        if (auth()->user()->available_balance < $withdrawal->total()) {
            return $this->sendErrorMessage('Insufficient balance');
        }

        DB::transaction(function() use ($amount, $account_id, $withdrawal) {
            // Deduct the amount from the user account
            auth()->user()->decrement('available_balance', $withdrawal->total());

            // Create the transaction
            $transaction = auth()->user()->transactions()->create([
                'amount' => $amount,
                'currency' => config('handova.currency'),
                'type' => Transaction::WITHDRAWAL,
                'notes' => 'Withdrawal request',
                'meta' => [
                    'status' => Transaction::WITHDRAWAL_STATUS_PROCESSING,
                    'fee' => $withdrawal->fee(),
                    'total' => $withdrawal->total(),
                    'account_id' => $account_id
                ]
            ]);

            // Alert the admin on the notification
            SuperNotification::create([
                'message' => 'A new driver just made a withdrawal request',
                'meta' => [
                    'account_id' => $account_id,
                    'transaction_id' => $transaction->id,
                ]
            ]);

            dispatch(new SendPushNotification(
                auth()->user(),
                'Withdrawal Request Initiated',
                'Your withdrawal request was initiated successfully. We are currently processing it',
                [
                    'type' => 'withdrawal',
                    'transaction_id' => (string) $transaction->id
                ]
            ));
        });

        return $this->sendSuccess('Withdrawal request successful. It is being processed');
    }

}
