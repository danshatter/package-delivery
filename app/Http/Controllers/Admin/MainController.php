<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use Illuminate\Support\Facades\{DB, Http, Storage};
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use App\Models\{Account, User, Role, Order, Rating, Transaction, Setting, Ride};
use App\Rules\{ValidUserAccount, ValidTransactionFeeValue, ValidCancellationFeeValue};
use App\Services\Files\Upload;
use App\Traits\Admin\Enhancements;
use App\Services\Notifications\Personal;
use App\Services\Exports\Csv;
use App\Jobs\{MailJob, SendPushNotification, SmsJob};
use App\Services\Settings\Application;
use App\Exceptions\CustomException;

class MainController extends Controller
{
    use Enhancements;

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
     * Activate a user
     */
    public function userActivate($userId)
    {
        $user = User::find($userId);

        if (is_null($user)) {
            return $this->sendErrorMessage('User not found', 404);
        }

        // Check if the user has already been activated
        if ($user->status === User::ACTIVE) {
            return $this->sendSuccess('User is already active', [
                'user_id' => $user->id
            ]);
        }

        // Activate the user
        $user->forceFill([
            'status' => User::ACTIVE
        ])->save();

        return $this->sendSuccess('User activated successfully', [
            'user_id' => $user->id
        ]);
    }

    /**
     * Block a user
     */
    public function userBlock($userId)
    {
        $user = User::find($userId);

        if (is_null($user)) {
            return $this->sendErrorMessage('User not found', 404);
        }

        // Check if the user has already been blocked
        if ($user->status === User::BLOCKED) {
            return $this->sendSuccess('User is already blocked', [
                'user_id' => $user->id
            ]);
        }

        // Activate the user
        $user->forceFill([
            'status' => User::BLOCKED
        ])->save();

        return $this->sendSuccess('User has just been blocked', [
            'user_id' => $user->id
        ]);
    }

    /**
     * Approve a driver
     */
    public function approveDriver($driverId)
    {
        $driver = User::drivers()->with(['ride'])->find($driverId);

        if (is_null($driver)) {
            return $this->sendErrorMessage('Driver not found', 404);
        }

        // Check if the driver is already approved
        if ($driver->driver_registration_status === User::DRIVER_STATUS_ACCEPTED) {
            return $this->sendSuccess('This driver has already been approved');
        }

        // Approve the driver
        $this->markDriverAsApproved($driver);

        return $this->sendSuccess('Driver approved successfully');
    }

    /**
     * Reject a driver
     */
    public function rejectDriver($driverId)
    {
        $driver = User::drivers()->with(['ride'])->find($driverId);

        if (is_null($driver)) {
            return $this->sendErrorMessage('Driver not found', 404);
        }

        // Check if the driver is already rejected
        if ($driver->driver_registration_status === User::DRIVER_STATUS_REJECTED) {
            return $this->sendSuccess('This driver has already been rejected');
        }

        // Reject the driver
        $this->markDriverAsRejected($driver);

        return $this->sendSuccess('Driver has been rejected successfully');
    }

    /**
     * Approve a ride
     */
    public function approveRide($driverId)
    {
        $driver = User::drivers()->with(['ride'])->find($driverId);

        if (is_null($driver)) {
            return $this->sendErrorMessage('Driver not found', 404);
        }

        // Check if the driver has a registered ride
        if (is_null($driver->ride)) {
            return $this->sendErrorMessage('Driver does not have a registered ride', 404);
        }

        // Check if the driver's ride has already been approved
        if ($driver->ride->status === Ride::APPROVED) {
            return $this->sendSuccess('Ride has already been approved');
        }

        $driver->ride->markAsApproved();

        return $this->sendSuccess('Ride approved successfully');
    }

    /**
     * Reject a driver's ride
     */
    public function rejectRide($driverId)
    {
        $driver = User::drivers()->with(['ride'])->find($driverId);

        if (is_null($driver)) {
            return $this->sendErrorMessage('Driver not found', 404);
        }

        // Check if the driver has a registered ride
        if (is_null($driver->ride)) {
            return $this->sendErrorMessage('Driver does not have a registered ride', 404);
        }

        // Check if the driver's ride has already been rejected
        if ($driver->ride->status === Ride::REJECTED) {
            return $this->sendSuccess('This ride has already been rejected');
        }

        $driver->ride->markAsRejected();

        return $this->sendSuccess('Ride has just been rejected');
    }

    /**
     * Track a driver
     */
    public function trackDriver($driverId)
    {
        $driver = User::drivers()->find($driverId);

        if (is_null($driver)) {
            return $this->sendErrorMessage('Driver not found', 404);
        }

        // Check if the driver has ever updated their location
        if (is_null($driver->location_latitude) || is_null($driver->location_longitude)) {
            return $this->sendSuccess('The driver has no known recent location');
        }

        // Get the last know location of the driver
        return $this->sendSuccess('Request successful', [
            'latitude' => $driver->location_latitude,
            'longitude' => $driver->location_longitude,
            'location_last_updated' => $driver->location_updated_at->diffForHumans()
        ]);
    }

    /**
     * Track an Order
     */
    public function trackOrder($orderId)
    {
        $order = Order::with(['driver'])->find($orderId);

        if (is_null($order)) {
            return $this->sendErrorMessage('Order not found', 404);
        }

        // We can only track an order if it is en route
        if ($order->delivery_status !== Order::STATUS_EN_ROUTE) {
            return $this->sendErrorMessage('Cannot track order. Not en route');
        }

        // Check if the driver has ever updated their location
        if (is_null($order->driver->location_latitude) || is_null($order->driver->location_longitude)) {
            return $this->sendSuccess('The driver has no known recent location');
        }

        // Get the last know location of the driver
        return $this->sendSuccess('Request successful', [
            'latitude' => $order->driver->location_latitude,
            'longitude' => $order->driver->location_longitude,
            'location_last_updated' => $order->driver->location_updated_at->diffForHumans()
        ]);
    }

    /**
     * Get all orders
     */
    public function orders()
    {
        $perPage = request()->query('per_page') ?? config('handova.per_page');

        $orders = Order::with(['customer', 'driver'])->latest()->paginate($perPage);

        return $this->sendSuccess('Request successful', $orders);
    }

    /**
     * Get an order
     */
    public function order($orderId)
    {
        $order = Order::with(['customer', 'driver'])->find($orderId);

        if (is_null($order)) {
            return $this->sendErrorMessage('Order not found', 404);
        }

        return $this->sendSuccess('Request successful', $order);
    }

    /**
     * Get a driver's ratings
     */
    public function driverRatings($driverId)
    {
        $perPage = request()->query('per_page') ?? config('handova.per_page');

        $driver = User::drivers()->find($driverId);

        if (is_null($driver)) {
            return $this->sendErrorMessage('Driver not found', 404);
        }

        // Get the ratings of the driver
        $ratings = Rating::with(['customer'])->where('driver_id', $driver->id)->paginate($perPage);

        return $this->sendSuccess('Request successful', $ratings);
    }

    /**
     * Get a rating on a driver
     */
    public function driverRating($driverId, $ratingId)
    {
        $driver = User::drivers()->find($driverId);

        if (is_null($driver)) {
            return $this->sendErrorMessage('Driver not found', 404);
        }
        
        $rating = $driver->jobRatings()->find($ratingId);

        if (is_null($rating)) {
            return $this->sendErrorMessage('Rating not found', 404);
        }

        return $this->sendSuccess('Request successful', $rating);
    }

    /**
     * Get a user
     */
    public function user($userId)
    {
        $user = User::find($userId);

        if (is_null($user)) {
            return $this->sendErrorMessage('User not found', 404);
        }

        return $this->sendSuccess('Request successful', $user);
    }

    /**
     * Get all customers
     */
    public function customers()
    {
        $perPage = request()->query('per_page') ?? config('handova.per_page');

        $customers = User::customers()
                        ->withCount('orders')
                        ->withSum('orders as orders_total_amount', 'amount')
                        ->paginate($perPage);

        // Make the status active for the customers
        collect($customers->items())->each(fn($customer) => $customer->makeVisible(['status']));

        return $this->sendSuccess('Request successful', $customers);
    }

    /**
     * Get a customer
     */
    public function customer($customerId)
    {
        $customer = User::customers()
                        ->withCount('orders')
                        ->withSum('orders as orders_total_amount', 'amount')
                        ->find($customerId);

        if (is_null($customer)) {
            return $this->sendErrorMessage('Customer not found', 404);
        }

        // Make the status active for the customer
        $customer->makeVisible(['status']);

        return $this->sendSuccess('Request successful', $customer);
    }

    /**
     * Get all drivers
     */
    public function drivers()
    {
        $perPage = request()->query('per_page') ?? config('handova.per_page');
        
        $drivers = User::drivers()
                        ->with(['ride'])
                        ->withCount('jobs')
                        ->withSum('jobs as jobs_total_amount', 'amount')
                        ->paginate($perPage);

        // Make the status active for the cusomers
        collect($drivers->items())->each(function($driver) {
            // Make the driver fields visible
            $driver->makeVisible([
                'status',
                'date_of_birth',
                'home_address',
                'next_of_kin_first_name',
                'next_of_kin_last_name',
                'next_of_kin_relationship',
                'next_of_kin_phone',
                'next_of_kin_email',
                'next_of_kin_home_address',
                'drivers_license_number',
                'drivers_license_image',
                'driver_registration_status',
                'driver_registration_status_updated_at'
            ]);

            // Make the status of the driver's ride visible
            $driver->ride?->makeVisible([
                'status',
                'valid_insurance_documents',
                'valid_inspection_reports'
            ]);
        });

        return $this->sendSuccess('Request successful', $drivers);
    }

    /**
     * Get a driver
     */
    public function driver($driverId)
    {
        $driver = User::drivers()
                    ->with(['ride'])
                    ->withCount('jobs')
                    ->withSum('jobs as jobs_total_amount', 'amount')
                    ->find($driverId);

        if (is_null($driver)) {
            return $this->sendErrorMessage('Driver not found', 404);
        }

        // Make the necessary driver's field visible
        $driver->makeVisible([
            'status',
            'date_of_birth',
            'home_address',
            'next_of_kin_first_name',
            'next_of_kin_last_name',
            'next_of_kin_relationship',
            'next_of_kin_phone',
            'next_of_kin_email',
            'next_of_kin_home_address',
            'drivers_license_number',
            'drivers_license_image',
            'driver_registration_status',
            'driver_registration_status_updated_at'
        ]);

        // Make the status of the driver's ride visible
        $driver->ride?->makeVisible([
            'status',
            'valid_insurance_documents',
            'valid_inspection_reports'
        ]);

        return $this->sendSuccess('Request successful', $driver);
    }

    /**
     * Get all transactions
     */
    public function transactions()
    {
        $perPage = request()->query('per_page') ?? config('handova.per_page');

        $transactions = Transaction::with(['user'])->latest()->paginate($perPage);

        return $this->sendSuccess('Request successful', $transactions);
    }

    /**
     * Get a transaction
     */
    public function transaction($transactionId)
    {
        $transaction = Transaction::with(['user'])->find($transactionId);

        if (is_null($transaction)) {
            return $this->sendErrorMessage('Transaction not found', 404);
        }

        return $this->sendSuccess('Request successful', $transaction);
    }

    /**
     * Get the orders of a customer
     */
    public function customerOrders($customerId)
    {
        $perPage = request()->query('per_page') ?? config('handova.per_page');

        $customer = User::customers()->find($customerId);

        if (is_null($customer)) {
            return $this->sendErrorMessage('Customer not found', 404);
        }

        $orders = $customer->orders()->latest()->paginate($perPage);

        return $this->sendSuccess('Request successful', $orders);
    }

    /**
     * Get an order of a customer
     */
    public function customerOrder($customerId, $orderId)
    {
        $customer = User::customers()->find($customerId);

        if (is_null($customer)) {
            return $this->sendErrorMessage('Customer not found', 404);
        }

        $order = $customer->orders()->find($orderId);

        if (is_null($order)) {
            return $this->sendErrorMessage('Order not found', 404);
        }

        return $this->sendSuccess('Request successful', $order);
    }

    /**
     * Get the transactions of a customer
     */
    public function customerTransactions($customerId)
    {
        $perPage = request()->query('per_page') ?? config('handova.per_page');

        $customer = User::customers()->find($customerId);

        if (is_null($customer)) {
            return $this->sendErrorMessage('Customer not found', 404);
        }

        $transactions = $customer->transactions()->latest()->paginate($perPage);

        return $this->sendSuccess('Request successful', $transactions);
    }

    /**
     * Get a transaction of a customer
     */
    public function customerTransaction($customerId, $transactionId)
    {
        $customer = User::customers()->find($customerId);

        if (is_null($customer)) {
            return $this->sendErrorMessage('Customer not found', 404);
        }

        $transaction = $customer->transactions()->find($transactionId);

        if (is_null($transaction)) {
            return $this->sendErrorMessage('Transaction not found', 404);
        }

        return $this->sendSuccess('Request successful', $transaction);
    }

    /**
     * Get the jobs of a driver
     */
    public function driverJobs($driverId)
    {
        $perPage = request()->query('per_page') ?? config('handova.per_page');

        $driver = User::drivers()->find($driverId);

        if (is_null($driver)) {
            return $this->sendErrorMessage('Driver not found', 404);
        }

        $jobs = $driver->jobs()->latest()->paginate($perPage);

        return $this->sendSuccess('Request successful', $jobs);
    }

    /**
     * Get a job by a driver
     */
    public function driverJob($driverId, $jobId)
    {
        $driver = User::drivers()->find($driverId);

        if (is_null($driver)) {
            return $this->sendErrorMessage('Driver not found', 404);
        }

        $job = $driver->jobs()->find($jobId);

        if (is_null($job)) {
            return $this->sendErrorMessage('Job not found', 404);
        }

        return $this->sendSuccess('Request successful', $job);
    }

    /**
     * Get the transactions of a driver
     */
    public function driverTransactions($driverId)
    {
        $perPage = request()->query('per_page') ?? config('handova.per_page');

        $driver = User::drivers()->find($driverId);

        if (is_null($driver)) {
            return $this->sendErrorMessage('Driver not found', 404);
        }

        $transactions = $driver->transactions()->latest()->paginate($perPage);

        return $this->sendSuccess('Request successful', $transactions);
    }

    /**
     * Get a transaction by a driver
     */
    public function driverTransaction($driverId, $transactionId)
    {
        $driver = User::drivers()->find($driverId);

        if (is_null($driver)) {
            return $this->sendErrorMessage('Driver not found', 404);
        }

        $transaction = $driver->transactions()->find($transactionId);

        if (is_null($transaction)) {
            return $this->sendErrorMessage('Transaction not found', 404);
        }

        return $this->sendSuccess('Request successful', $transaction);
    }

    /**
     * Get online drivers
     */
    public function onlineDrivers()
    {
        $perPage = request()->query('per_page') ?? config('handova.per_page');

        $drivers = User::validDrivers()->online()->paginate($perPage);

        return $this->sendSuccess('Request successful', $drivers);
    }

    /**
     * Get offline drivers
     */
    public function offlineDrivers()
    {
        $perPage = request()->query('per_page') ?? config('handova.per_page');

        $drivers = User::validDrivers()->offline()->paginate($perPage);

        return $this->sendSuccess('Request successful', $drivers);
    }

    /**
     * Confirm a withdrawal request
     */
    public function confirmWithdrawal($transactionId)
    {
        $transaction = Transaction::with(['user'])->withdrawal()->find($transactionId);

        if (is_null($transaction)) {
            return $this->sendErrorMessage('Transaction not found', 404);
        }

        // Check to see if the transaction has already been confirmed
        if ($transaction->meta['status'] === Transaction::WITHDRAWAL_STATUS_CONFIRMED) {
            return $this->sendSuccess('Transaction has already been confirmed');
        }

        // Check to see if the transaction has previous been rejected
        if ($transaction->meta['status'] === Transaction::WITHDRAWAL_STATUS_REJECTED) {
            return $this->sendErrorMessage('Transaction has been rejected');
        }

        /**
         * This will mean the transaction is pending so we can start processing
         */
        // Get the account
        $account = Account::find($transaction->meta['account_id']);

        // Check if the account exists or has been deleted
        if (!isset($account)) {
            throw new CustomException('Account not found or has been deleted', 400);
        }

        // Get the transfer recipient or create the transfer recipient
        $transferRecipient = $account->transferRecipient()->firstOr(function() use ($account) {
            $response = Http::withToken(config('services.paystack.secret_key'))
                            ->post('https://api.paystack.co/transferrecipient', [
                                'type' => 'nuban',
                                'name' => $account->account_name,
                                'account_number' => $account->account_number,
                                'bank_code' => $account->bank_code,
                                'currency' => config('handova.currency')
                            ]);

            // The body of the response
            $body = $response->json();

            if ($response->failed()) {
                throw new CustomException('Error creating transfer recipient: '.$body['message'], $response->status());
            }

            return $account->transferRecipient()->create([
                'code' => $body['data']['recipient_code'],
                'type' => $body['data']['type'],
                'currency' => $body['data']['currency'],
                'details' => $body['data']['details']
            ]);
        });

        // Initiate the transfer
        $response = Http::withToken(config('services.paystack.secret_key'))
                        ->post('https://api.paystack.co/transfer', [
                            'source' => 'balance',
                            'amount' => $transaction->amount,
                            'reason' => $transaction->id,
                            'recipient' => $transferRecipient->code,
                            'currency' => config('handova.currency')
                        ]);

        $body = $response->json();

        if ($response->failed()) {
            throw new CustomException('Error initiating transfer: '.$body['message'], $response->status());
        }

        return $this->sendSuccess('Withdrawal request is being confirmed');
    }

    /**
     * Reject a withdrawal request
     */
    public function rejectWithdrawal($transactionId)
    {
        $transaction = Transaction::with(['user'])->withdrawal()->find($transactionId);

        if (is_null($transaction)) {
            return $this->sendErrorMessage('Transaction not found', 404);
        }

        // Check to see if the transaction has already been rejected
        if ($transaction->meta['status'] === Transaction::WITHDRAWAL_STATUS_REJECTED) {
            return $this->sendSuccess('Transaction has already been rejected');
        }

        // Check to see if the transaction has previously been confirmed
        if ($transaction->meta['status'] === Transaction::WITHDRAWAL_STATUS_CONFIRMED) {
            return $this->sendErrorMessage('Transaction has been confirmed');
        }

        // Create the transaction
        DB::transaction(function() use ($transaction) {
            // Reverse the money back to the user's account
            $transaction->user->increment('available_balance', $transaction->meta['total']);

            // Change the status of the withdrawal
            $transaction->update([
                'meta->status' => Transaction::WITHDRAWAL_STATUS_REJECTED
            ]);

            $message = 'Sorry but your withdrawal request of '.config('handova.currency').number_format($transaction->amount / 100, 2).' was rejected';

            // Create a notification to the user that the transaction was rejected
            $transaction->user->notifications()->create([
                'message' => $message
            ]);

            dispatch(new SendPushNotification(
                $transaction->user,
                'Withdrawal Request Rejected',
                $message,
                [
                    'type' => 'withdrawal',
                    'transaction_id' => (string) $transaction->id
                ]
            ));
        });

        return $this->sendSuccess('Transaction has just been rejected');
    }

    /**
     * Get the orders for today
     */
    public function ordersToday()
    {
        $perPage = request()->query('per_page') ?? config('handova.per_page');

        $orders = Order::with(['customer', 'driver'])
                        ->whereBetween('created_at', [Carbon::now()->startOfDay(), Carbon::now()->endOfDay()])
                        ->latest()
                        ->paginate($perPage);

        return $this->sendSuccess('Request successful', $orders);
    }

    /**
     * Get all pending orders
     */
    public function ordersPending()
    {
        $perPage = request()->query('per_page') ?? config('handova.per_page');

        $orders = Order::with(['customer', 'driver'])
                        ->pending()
                        ->latest()
                        ->paginate($perPage);

        return $this->sendSuccess('Request successful', $orders);
    }

    /**
     * Get all accepted orders
     */
    public function ordersAccepted()
    {
        $perPage = request()->query('per_page') ?? config('handova.per_page');

        $orders = Order::with(['customer', 'driver'])
                        ->accepted()
                        ->latest()
                        ->paginate($perPage);

        return $this->sendSuccess('Request successful', $orders);
    }

    /**
     * Get all rejected orders
     */
    public function ordersRejected()
    {
        $perPage = request()->query('per_page') ?? config('handova.per_page');

        $orders = Order::with(['customer', 'driver'])
                        ->rejected()
                        ->latest()
                        ->paginate($perPage);

        return $this->sendSuccess('Request successful', $orders);
    }

    /**
     * Get all en route orders
     */
    public function ordersEnRoute()
    {
        $perPage = request()->query('per_page') ?? config('handova.per_page');

        $orders = Order::with(['customer', 'driver'])
                        ->enRoute()
                        ->latest()
                        ->paginate($perPage);

        return $this->sendSuccess('Request successful', $orders);
    }

    /**
     * Get all completed orders
     */
    public function ordersCompleted()
    {
        $perPage = request()->query('per_page') ?? config('handova.per_page');

        $orders = Order::with(['customer', 'driver'])
                        ->completed()
                        ->latest()
                        ->paginate($perPage);

        return $this->sendSuccess('Request successful', $orders);
    }

    /**
     * Get all canceled orders
     */
    public function ordersCanceled()
    {
        $perPage = request()->query('per_page') ?? config('handova.per_page');

        $orders = Order::with(['customer', 'driver'])
                        ->canceled()
                        ->latest()
                        ->paginate($perPage);

        return $this->sendSuccess('Request successful', $orders);
    }

    /**
     * Get all approved drivers
     */
    public function approvedDrivers()
    {
        $perPage = request()->query('per_page') ?? config('handova.per_page');

        $drivers = User::drivers()
                        ->where('driver_registration_status', User::DRIVER_STATUS_ACCEPTED)
                        ->paginate($perPage);

        return $this->sendSuccess('Request successful', $drivers);
    }

    /**
     * Get all unapproved drivers
     */
    public function unapprovedDrivers()
    {
        $perPage = request()->query('per_page') ?? config('handova.per_page');

        $drivers = User::drivers()
                        ->where('driver_registration_status', User::DRIVER_STATUS_PENDING)
                        ->paginate($perPage);

        return $this->sendSuccess('Request successful', $drivers);
    }

    /**
     * Get all rejected drivers
     */
    public function rejectedDrivers()
    {
        $perPage = request()->query('per_page') ?? config('handova.per_page');

        $drivers = User::drivers()
                        ->where('driver_registration_status', User::DRIVER_STATUS_REJECTED)
                        ->paginate($perPage);

        return $this->sendSuccess('Request successful', $drivers);
    }

    /**
     * Verify a driver's license
     */
    public function verifyDriverLicense($driverId)
    {
        $driver = User::drivers()->find($driverId);

        if (is_null($driver)) {
            return $this->sendErrorMessage('Driver not found', 404);
        }

        // Set the application settings
        app()->make(Application::class)->set();

        // Make the request to the Verify Me API to verify the driver
        $response = Http::withToken(config('services.verify_me.secret_key'))
                        ->post("https://vapi.verifyme.ng/v1/verifications/identities/drivers_license/{$driver->drivers_license_number}", [
                            'firstname' => $driver->first_name,
                            'lastname' => $driver->last_name
                        ]);

        // Decode the response from the API
        $data = $response->json();

        // Check if there was a failure in the request
        if ($response->failed()) {
            return $this->sendErrorMessage('Verification Error: '.$data['message'], $response->status());
        }

        /**
         * Looks like the driver's license actually exist
         * 
         * The photo of the user is base64 encoded. We will use Data URL to make it visible
         */
        $photo = $data['data']['photo'];

        $decodedImage = base64_decode($photo);

        // Get the mime type of the image
        $type = app()->make(Upload::class)->type($decodedImage);

        // Create the Data URL for the image 
        $data['data']['photo'] = "data:{$type};base64,{$photo}";

        return $this->sendSuccess('Request successful', $data['data']);
    }

    /**
     * Get the monthly report
     */
    public function monthlyReport()
    {
        // Set the application settings
        app()->make(Application::class)->set();

        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        $month = Carbon::now()->monthName;

        $orders = Order::with(['customer', 'driver'])
                        ->completed()
                        ->whereBetween('delivery_status_updated_at', [$startOfMonth, $endOfMonth])
                        ->get();

        $content = app()->make(Csv::class)->make([
            '#',
            'Order ID',
            'Customer Name',
            'Driver Name',
            'Amount Paid',
            'Currency',
            'Date Completed'
        ], $orders->map(fn($order, $key) => $this->orderReport($order, $key + 1))
                ->all(),
        'Monthly Report for '.config('app.name').' for the Month of '.$month.PHP_EOL);

        // Download as CSV file
        return response()->stream(function() use ($content) {
            echo $content;
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="Monthly '.config('app.name').' Report for '.$month.'.csv"'
        ]);
    }

    /**
     * Get the weekly report
     */
    public function weeklyReport()
    {
        // Set the application settings
        app()->make(Application::class)->set();

        $startOfWeek = Carbon::now()->startOfWeek(Carbon::SUNDAY);
        $endOfWeek = Carbon::now()->endOfWeek(Carbon::SATURDAY);

        $orders = Order::with(['customer', 'driver'])
                        ->completed()
                        ->whereBetween('delivery_status_updated_at', [$startOfWeek, $endOfWeek])
                        ->get();

        $content = app()->make(Csv::class)->make([
            '#',
            'Order ID',
            'Customer Name',
            'Driver Name',
            'Amount Paid',
            'Currency',
            'Date Completed'
        ], $orders->map(fn($order, $key) => $this->orderReport($order, $key + 1))
                ->all(),
        'Weekly '.config('app.name').' Report from '.$startOfWeek->toDayDateTimeString().' to '.$endOfWeek->toDayDateTimeString().PHP_EOL);

        // Download as CSV file
        return response()->stream(function() use ($content) {
            echo $content;
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="Weekly '.config('app.name').' Report.csv"'
        ]);
    }

    /**
     * Get the daily report
     */
    public function dailyReport()
    {
        // Set the application settings
        app()->make(Application::class)->set();

        $startOfDay = Carbon::now()->startOfDay();
        $endOfDay = Carbon::now()->endOfDay();
        $todayDate = Carbon::now()->format('l F jS Y');

        $orders = Order::with(['customer', 'driver'])
                        ->completed()
                        ->whereBetween('delivery_status_updated_at', [$startOfDay, $endOfDay])
                        ->get();

        $content = app()->make(Csv::class)->make([
            '#',
            'Order ID',
            'Customer Name',
            'Driver Name',
            'Amount Paid',
            'Currency',
            'Date Completed'
        ], $orders->map(fn($order, $key) => $this->orderReport($order, $key + 1))
                ->all(),
        'Daily '.config('app.name').' Report for '.$todayDate.PHP_EOL);

        // Download as CSV file
        return response()->stream(function() use ($content) {
            echo $content;
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.config('app.name').' Report for '.$todayDate.'.csv"'
        ]);
    }

    /**
     * Get the hourly report
     */
    public function hourlyReport()
    {
        // Set the application settings
        app()->make(Application::class)->set();

        $startOfHour = Carbon::now()->startOfHour();
        $endOfHour = Carbon::now()->endOfHour();
        $todayDate = Carbon::now()->format('l F jS Y');

        $orders = Order::with(['customer', 'driver'])
                ->completed()
                ->whereBetween('delivery_status_updated_at', [$startOfHour, $endOfHour])
                ->get();

        $content = app()->make(Csv::class)->make([
            '#',
            'Order ID',
            'Customer Name',
            'Driver Name',
            'Amount Paid',
            'Currency',
            'Date Completed'
        ], $orders->map(fn($order, $key) => $this->orderReport($order, $key + 1))
                ->all(),
        config('app.name').' Report of '.$todayDate.' from '.$startOfHour->format('g:i A').' to '.$endOfHour->format('g:i A').PHP_EOL);

        // Download as CSV file
        return response()->stream(function() use ($content) {
            echo $content;
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.config('app.name').' Report from '.$startOfHour->format('g:i A').' to '.$endOfHour->format('g:i A').'.csv"'
        ]);
    }

    /**
     * Get the pending payments
     */
    public function pendingPayments()
    {
        $perPage = request()->query('per_page') ?? config('handova.per_page');

        $pendingPayments = Transaction::with(['user'])
                                    ->withdrawal()
                                    ->whereJsonContains('meta->status', Transaction::WITHDRAWAL_STATUS_PROCESSING)
                                    ->latest()
                                    ->paginate($perPage);

        return $this->sendSuccess('Request successful', $pendingPayments);
    }

    /**
     * Upload the logo of the application
     */
    public function logo()
    {
        $validator = validator()->make(request()->all(), [
            'logo' => ['required', 'image', 'mimes:png', 'max:250']
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        extract($validator->validated());

        // Upload the image of the logo
        $image = $logo->storePubliclyAs('', 'logo.png');

        return $this->sendSuccess('Logo uploaded successfully', Storage::url($image));
    }

    /**
     * Send a push notification to a user
     */
    public function userNotify($userId)
    {
        $user = User::find($userId);

        if (is_null($user)) {
            return $this->sendErrorMessage('User not found', 404);
        }

        $validator = validator()->make(request()->all(), [
            'title' => ['required'],
            'message' => ['required']
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        extract($validator->validated());

        // Send a notification to the user whose firebase token has already been stored
        $response = app()->make(Personal::class)->send($user->firebase_messaging_token, $title, $message);
        
        // Get the data gotten back from the request
        $data = $response->json();

        if ($response->failed()) {
            return $this->sendFirebaseErrorMessage($data['error']['status'], $data['error']['message'], $response->status());
        }

        return $this->sendSuccess('Request successful', $data);
    }

    /**
     * Send a test notification with a token
     */
    public function sendTestNotification()
    {
        $validator = validator()->make(request()->all(), [
            'title' => ['required'],
            'message' => ['required'],
            'token' => ['required'],
            'data' => ['nullable']
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        extract($validator->validated());

        // Send a notification to the user whose firebase token has already been stored
        $response = app()->make(Personal::class)->send($token, $title, $message, $data);

        // Get the data gotten back from the request
        $data = $response->json();

        if ($response->failed()) {
            return $this->sendFirebaseErrorMessage($data['error']['status'], $data['error']['message'], $response->status());
        }

        return $this->sendSuccess('Request successful', $data);
    }

    /**
     * Search for customers by name
     */
    public function searchCustomers()
    {
        $validator = validator()->make(request()->all(), [
            'query' => ['required']
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        extract($validator->validated());

        $perPage = request()->query('per_page') ?? config('handova.per_page');

        $customers = User::customers()
                        ->where('first_name', 'LIKE', "%{$query}%")
                        ->orWhere('last_name', 'LIKE', "%{$query}%")
                        ->orderBy('first_name')
                        ->paginate($perPage);

        return $this->sendSuccess('Request successful', $customers);
    }

    /**
     * Search for drivers by name
     */
    public function searchDrivers()
    {
        $validator = validator()->make(request()->all(), [
            'query' => ['required']
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        extract($validator->validated());

        $perPage = request()->query('per_page') ?? config('handova.per_page');

        $drivers = User::drivers()
                        ->where('first_name', 'LIKE', "%{$query}%")
                        ->orWhere('last_name', 'LIKE', "%{$query}%")
                        ->orderBy('first_name')
                        ->paginate($perPage);

        return $this->sendSuccess('Request successful', $drivers);
    }

    /**
     * Search for a user by it's business name
     */
    public function searchBusiness()
    {
        $validator = validator()->make(request()->all(), [
            'query' => ['required']
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        extract($validator->validated());

        $perPage = request()->query('per_page') ?? config('handova.per_page');

        $users = User::where('business_name', 'LIKE', "%{$query}%")->paginate($perPage);

        return $this->sendSuccess('Request successful', $users);
    }

    /**
     * Get the active users
     */
    public function usersActive()
    {
        $perPage = request()->query('per_page') ?? config('handova.per_page');

        $users = User::active()->latest()->paginate($perPage);

        return $this->sendSuccess('Request successful', $users);
    }

    /**
     * Get the blocked users
     */
    public function usersBlocked()
    {
        $perPage = request()->query('per_page') ?? config('handova.per_page');

        $users = User::blocked()->latest()->paginate($perPage);

        return $this->sendSuccess('Request successful', $users);
    }

    /**
     * Get the settings of the application
     */
    public function settings()
    {
        $settings = Setting::find(1);

        if (is_null($settings)) {
            return $this->sendSuccess('There are currently no settings configured');
        }

        return $this->sendSuccess('Request successful', $settings);
    }

    /**
     * Update the settings of the application
     */
    public function settingsApplication()
    {
        $validator = validator()->make(request()->all(), [
            'name' => ['required'],
            'version' => ['required']
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        extract($validator->validated());

        // Update the settings
        Setting::updateOrCreate([
            'id' => 1
        ], [
            'application_name' => $name,
            'application_version' => $version
        ]);

        return $this->sendSuccess('Application settings updated successfully');
    }
    
    /**
     * Update the Android and Apple URL settings of the application
     */
    public function settingsUrl()
    {
        $validator = validator()->make(request()->all(), [
            'android' => ['required', 'url'],
            'apple' => ['required', 'url']
        ], [
            'url' => 'The :attribute URL must be a valid URL format'
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        extract($validator->validated());

        // Update the settings
        Setting::updateOrCreate([
            'id' => 1
        ], [
            'android_url' => $android,
            'apple_url' => $apple
        ]);

        return $this->sendSuccess('URLs updated successfully');
    }

    /**
     * Update the mail settings of the application
     */
    public function settingsMail()
    {
        $validator = validator()->make(request()->all(), [
            'from_address' => ['required', 'email'],
            'from_name' => ['required']
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        extract($validator->validated());

        // Update the settings
        Setting::updateOrCreate([
            'id' => 1
        ], [
            'application_mail_from_email_address' => $from_address,
            'application_mail_from_name' => $from_name
        ]);

        return $this->sendSuccess('Mail settings updated successfully');
    }

    /**
     * Upload the firebase service account for the application
     */
    public function settingsFirebaseServiceAccount()
    {
        $validator = validator()->make(request()->all(), [
            'service_account_file' => ['required', 'mimes:json']
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        extract($validator->validated());

        // Upload the file to the server
        $service_account_file->storeAs('', 'firebase-service-account.json', 'base');

        return $this->sendSuccess('Service account file uploaded successfully');
    }

    /**
     * Update the firebase settings of the application
     */
    public function settingsFirebaseProject()
    {
        $validator = validator()->make(request()->all(), [
            'web_api_key' => ['required'],
            'project_id' => ['required']
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        extract($validator->validated());

        // Update the settings
        Setting::updateOrCreate([
            'id' => 1
        ], [
            'firebase_web_api_key' => $web_api_key,
            'firebase_project_id' => $project_id
        ]);

        return $this->sendSuccess('Firebase settings updated successfully');
    }

    /**
     * Update the google settings of the application
     */
    public function settingsGoogle()
    {
        $validator = validator()->make(request()->all(), [
            'api_key' => ['required'],
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        extract($validator->validated());

        // Update the settings
        Setting::updateOrCreate([
            'id' => 1
        ], [
            'google_api_key' => $api_key
        ]);

        return $this->sendSuccess('Google settings updated successfully');
    }

    /**
     * Update the paystack settings of the application
     */
    public function settingsPaystack()
    {
        $validator = validator()->make(request()->all(), [
            'public_key' => ['required'],
            'secret_key' => ['required']
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        extract($validator->validated());

        // Update the settings
        Setting::updateOrCreate([
            'id' => 1
        ], [
            'paystack_public_key' => $public_key,
            'paystack_secret_key' => $secret_key
        ]);

        return $this->sendSuccess('Paystack settings updated successfully');
    }

    /**
     * Update the SMS settings of the application
     */
    public function settingsSms()
    {
        $validator = validator()->make(request()->all(), [
            'username' => ['required'],
            'auth_key' => ['required']
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        extract($validator->validated());

        // Update the settings
        Setting::updateOrCreate([
            'id' => 1
        ], [
            'sms_username' => $username,
            'sms_key' => $auth_key
        ]);

        return $this->sendSuccess('SMS settings updated successfully');
    }

    /**
     * Update the transaction fee settings of the application
     */
    public function settingsTransactionFee()
    {
        $validator = validator()->make(request()->all(), [
            'type' => ['required', Rule::in(['percentage', 'amount'])],
            'value' => ['required', 'numeric', new ValidTransactionFeeValue]
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        extract($validator->validated());

        // Update the settings
        Setting::updateOrCreate([
            'id' => 1
        ], [
            'transaction_fee_type' => $type,
            'transaction_fee_value' => $value
        ]);

        return $this->sendSuccess('Transaction fee settings updated successfully');
    }

    /**
     * Update the Cancellation fee settings of the application
     */
    public function settingsCancellationFee()
    {
        $validator = validator()->make(request()->all(), [
            'type' => ['required', Rule::in(['percentage', 'amount'])],
            'value' => ['required', 'numeric', new ValidCancellationFeeValue]
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        extract($validator->validated());

        // Update the settings
        Setting::updateOrCreate([
            'id' => 1
        ], [
            'order_cancellation_fee_type' => $type,
            'order_cancellation_fee_value' => $value
        ]);

        return $this->sendSuccess('Order cancellation fee settings updated successfully');
    }

    /**
     * Update the Verify Me API settings of the application
     */
    public function settingsVerifyMe()
    {
        $validator = validator()->make(request()->all(), [
            'public_key' => ['required'],
            'secret_key' => ['required']
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        extract($validator->validated());

        // Update the settings
        Setting::updateOrCreate([
            'id' => 1
        ], [
            'verify_me_public_key' => $public_key,
            'verify_me_secret_key' => $secret_key
        ]);

        return $this->sendSuccess('Verify Me API settings updated successfully');
    }

    /**
     * Reset the application settings
     */
    public function settingsReset()
    {
        // Reset the settings table
        Setting::truncate();

        return $this->sendSuccess('Settings reset successfully');
    }

    /**
     * Send an email to a user
     */
    public function userMail($userId)
    {
        $user = User::find($userId);

        if (is_null($user)) {
            return $this->sendErrorMessage('User not found', 404);
        }

        $validator = validator()->make(request()->all(), [
            'subject' => ['required'],
            'message' => ['required']
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        extract($validator->validated());

        // Send the mail
        dispatch(new MailJob($user, $subject, $message));

        return $this->sendSuccess('Mail is being sent');
    }

    /**
     * Send an SMS to a user
     */
    public function userSms($userId)
    {
        $user = User::find($userId);

        if (is_null($user)) {
            return $this->sendErrorMessage('User not found', 404);
        }

        $validator = validator()->make(request()->all(), [
            'message' => ['required']
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        extract($validator->validated());

        // Send the SMS to the user
        dispatch(new SmsJob($user, $message));

        return $this->sendSuccess('SMS is being sent');
    }

    /**
     * Get the total number of customers
     */
    public function customersTotal()
    {
        $customersTotal = User::customers()->count();

        return $this->sendSuccess('Request successful', $customersTotal);
    }

    /**
     * Get the total number of drivers
     */
    public function driversTotal()
    {
        $driversTotal = User::drivers()->count();

        return $this->sendSuccess('Request successful', $driversTotal);
    }

    /**
     * Get the total number of orders
     */
    public function ordersTotal()
    {
        $ordersTotal = Order::count();

        return $this->sendSuccess('Request successful', $ordersTotal);
    }

    /**
     * Get the top customers for the month
     */
    public function customerMonthLeaders()
    {
        $validator = validator()->make(request()->all(), [
            'month' => ['required', 'integer', 'min:1', 'max:12']
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        extract($validator->validated());

        // Get the top customers for the month
        $topCustomersForMonth = User::customers()
                                    ->select(['id', 'first_name', 'last_name'])
                                    ->withCount(['orders' => fn($query) => $query->whereMonth('created_at', $month)])
                                    ->withSum(['orders as total_amount' => fn($query) => $query->whereMonth('created_at', $month)], 'amount')
                                    ->orderBy('orders_count', 'desc')
                                    ->orderBy('total_amount', 'desc')
                                    ->take(10)
                                    ->get();

        return $this->sendSuccess('Request successful', $topCustomersForMonth);
    }

    /**
     * Get the top customers of all time
     */
    public function customerAllTimeLeaders()
    {
        // Get the top customers for the month
        $allTimeCustomerLeaders = User::customers()
                            ->select(['id', 'first_name', 'last_name'])
                            ->withCount('orders')
                            ->withSum('orders as total_amount', 'amount')
                            ->orderBy('orders_count', 'desc')
                            ->orderBy('total_amount', 'desc')
                            ->take(10)
                            ->get();

        return $this->sendSuccess('Request successful', $allTimeCustomerLeaders);
    }

    /**
     * Get the top drivers for the month
     */
    public function driverMonthLeaders()
    {
        $validator = validator()->make(request()->all(), [
            'month' => ['required', 'integer', 'min:1', 'max:12']
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        extract($validator->validated());

        // Get the top drivers for the month
        $topDriversForMonth = User::drivers()
                                ->select(['id', 'first_name', 'last_name'])
                                ->withCount(['jobs' => fn($query) => $query->whereMonth('created_at', $month)
                                                                        ->where('delivery_status', Order::STATUS_COMPLETED)
                                ])
                                ->withSum(['jobs as total_amount_earned' => fn($query) => $query->whereMonth('created_at', $month)
                                                                                        ->where('delivery_status', Order::STATUS_COMPLETED)
                                ], 'amount')
                                ->orderBy('jobs_count', 'desc')
                                ->orderBy('total_amount_earned', 'desc')
                                ->take(10)
                                ->get();

        return $this->sendSuccess('Request successful', $topDriversForMonth);
    }

    /**
     * Get the top drivers of all time
     */
    public function driverAllTimeLeaders()
    {
        // Get the top customers for the month
        $allTimeDriverLeaders = User::drivers()
                                    ->select(['id', 'first_name', 'last_name'])
                                    ->withCount(['jobs' => fn($query) => $query->where('delivery_status', Order::STATUS_COMPLETED)])
                                    ->withSum(['jobs as total_amount_earned' => fn($query) => $query->where('delivery_status', Order::STATUS_COMPLETED)], 'amount')
                                    ->orderBy('jobs_count', 'desc')
                                    ->orderBy('total_amount_earned', 'desc')
                                    ->take(10)
                                    ->get();

        return $this->sendSuccess('Request successful', $allTimeDriverLeaders);
    }

    /**
     * Get the number of the registered customers for the year
     */
    public function customersRegistered()
    {
        $customers = User::customers()
                        ->select(['created_at'])
                        ->whereYear('created_at', Carbon::now()->year)
                        ->get()
                        ->groupBy(fn($table) => strtolower(Carbon::parse($table->created_at)->format('F')))
                        ->map(fn($month) => $month->count());

        return $this->sendSuccess('Request successful', $customers);
    }

    /**
     * Get the number of the registered drivers for the year
     */
    public function driversRegistered()
    {
        $drivers = User::drivers()
                    ->select(['created_at'])
                    ->whereYear('created_at', Carbon::now()->year)
                    ->get()
                    ->groupBy(fn($table) => strtolower(Carbon::parse($table->created_at)->format('F')))
                    ->map(fn($month) => $month->count());

        return $this->sendSuccess('Request successful', $drivers);
    }

    /**
     * Get the pending orders for the month
     */
    public function ordersPendingMonthly()
    {
        $orders = Order::pending()
                    ->select(['created_at'])
                    ->whereYear('created_at', Carbon::now()->year)
                    ->get()
                    ->groupBy(fn($table) => strtolower(Carbon::parse($table->created_at)->format('F')))
                    ->map(fn($month) => $month->count());

        return $this->sendSuccess('Request successful', $orders);
    }

    /**
     * Get the canceled orders for the month
     */
    public function ordersCanceledMonthly()
    {
        $orders = Order::canceled()
                    ->select(['created_at'])
                    ->whereYear('created_at', Carbon::now()->year)
                    ->get()
                    ->groupBy(fn($table) => strtolower(Carbon::parse($table->created_at)->format('F')))
                    ->map(fn($month) => $month->count());

        return $this->sendSuccess('Request successful', $orders);
    }

    /**
     * Get the rejected orders for the month
     */
    public function ordersRejectedMonthly()
    {
        $orders = Order::rejected()
                    ->select(['created_at'])
                    ->whereYear('created_at', Carbon::now()->year)
                    ->get()
                    ->groupBy(fn($table) => strtolower(Carbon::parse($table->created_at)->format('F')))
                    ->map(fn($month) => $month->count());

        return $this->sendSuccess('Request successful', $orders);
    }

    /**
     * Get the completed orders for the month
     */
    public function ordersCompletedMonthly()
    {
        $orders = Order::completed()
                    ->select(['created_at'])
                    ->whereYear('created_at', Carbon::now()->year)
                    ->get()
                    ->groupBy(fn($table) => strtolower(Carbon::parse($table->created_at)->format('F')))
                    ->map(fn($month) => $month->count());

        return $this->sendSuccess('Request successful', $orders);
    }
}
