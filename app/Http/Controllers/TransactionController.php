<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\{Http, DB};
use App\Models\{User, Transaction, Notification, Card, SuperNotification};
use App\Traits\Payments\Transactions;
use App\Traits\Orders\{Create, Payment};
use App\Services\Settings\Application;
use App\Jobs\SendPushNotification;

class TransactionController extends Controller
{
    use Transactions, Create, Payment;

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
     * Credit a users account
     */
    public function credit()
    {
        $validator = validator()->make(request()->all(), [
            'amount' => ['required', 'integer']
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        extract($validator->validated());

        // Credit the user's account
        DB::transaction(function() use ($amount) {
            auth()->user()->increment('available_balance', $amount);
            auth()->user()->increment('ledger_balance', $amount);

            // Create the notification for account credited
            auth()->user()->notifications()->create([
                'message' => 'Account credited with the amount of '.config('handova.currency').number_format($amount / 100, 2)
            ]);

            // Create the record of the transaction
            auth()->user()->transactions()->create([
                'amount' => $amount,
                'currency' => config('handova.currency'),
                'reference' => Str::random(40),
                'type' => Transaction::CREDIT,
                'notes' => 'Account top up'
            ]);
        });

        return $this->sendSuccess('Account credited successfully');
    }

    /**
     * Get the transactions of the authenticated user
     */
    public function index()
    {
        $perPage = request()->query('per_page') ?? config('handova.per_page');

        $transactions = auth()->user()->transactions()->latest()->paginate($perPage);

        return $this->sendSuccess('Request successful', $transactions->items());
    }

    /**
     * Get a transaction by a the authenticated user
     */
    public function show($transactionId)
    {
        $transaction = auth()->user()->transactions()->find($transactionId);

        if (is_null($transaction)) {
            return $this->sendErrorMessage('Transaction not found', 404);
        }

        return $this->sendSuccess('Request successful', $transaction);
    }

    /**
     * Credit a user's account
     */
    public function store()
    {
        // Set the application settings
        app()->make(Application::class)->set();

        if (request()->header('X-Paystack-Signature') === hash_hmac('sha512', request()->getContent(), config('services.paystack.secret_key'))) {
            http_response_code(200);

            // Decode the JSON data
			$body = request()->json()->all();

			// Check the event just in case we want to add more events
			switch ($body['event']) {
				case 'charge.success':
                    // The type of transaction it is
                    $type = $body['data']['metadata']['type'];

                    // Check to see what type of transaction it is
                    if ($type === Transaction::TYPE_CREDIT_ACCOUNT) {
                        // Credit the user account
                        $this->creditCase($body);
                    } elseif ($type === Transaction::TYPE_ADD_CARD) {
                        // Add the user's card an reverse the transaction
                        $this->firstChargeCase($body);
                    } elseif ($type === Transaction::TYPE_CARD_PAYMENT) {
                        // Payment for order with card
                        $this->payForOrderWithCardCase($body);
                    } elseif ($type === Transaction::TYPE_ORDER_CANCELLATION) {
                        // Cancel the order of a user
                        $this->cancelOrderCase($body);
                    }
				break;

                case 'transfer.success':
                    $transaction = Transaction::with(['user'])
                                            ->find($body['data']['reason']);
                    
                    // Update the transaction status
                    $message = DB::transaction(function() use ($transaction, $body) {
                        // Deduct the total amount from the ledger balance
                        $transaction->user->decrement('ledger_balance', $transaction->meta['total']);

                        // Change the status of the withdrawal
                        $transaction->update([
                            'reference' => $body['data']['reference'],
                            'meta->status' => Transaction::WITHDRAWAL_STATUS_CONFIRMED
                        ]);

                        $message = 'Your withdrawal request for the amount of '.config('handova.currency').number_format($transaction->amount / 100, 2).' was successful. Your bank account was successfully credited';

                        // Create a notification to the user that the transaction was completed
                        $transaction->user->notifications()->create([
                            'message' => $message
                        ]);

                        /**
                         * Create a notification for successful transfer
                         */
                        SuperNotification::create([
                            'message' => 'The withdrawal request with transaction ID #'.$transaction->id.' for the amount of '.config('handova.currency').number_format($transaction->amount / 100, 2).' was successful'
                        ]);

                        return $message;
                    });

                    dispatch(new SendPushNotification(
                        $transaction->user,
                        'Payment Successful',
                        $message,
                        [
                            'type' => 'withdrawal',
                            'transaction_id' => (string) $transaction->id
                        ]
                    ));
                break;

                case 'transfer.failed':
                    $transaction = Transaction::with(['user'])
                                            ->find($body['data']['reason']);

                    /**
                     * Create a notification for failed transfer
                     */
                    SuperNotification::create([
                        'message' => 'Sorry but the transfer of funds for the withdrawal request with transaction ID #'.$transaction->id.' for the amount of '.config('handova.currency').number_format($transaction->amount / 100, 2).' failed.'
                    ]);
                break;

                case 'transfer.reversed':
                    $transaction = Transaction::with(['user'])
                                            ->find($body['data']['reason']);

                    /**
                     * Create a notification for failed transfer
                     */
                    SuperNotification::create([
                        'message' => 'The amount of '.config('handova.currency').number_format($transaction->amount / 100, 2).' that was deducted for the transfer with transaction ID #'.$transaction->id.' has been reversed.'
                    ]);
                break;

				default:
					return response(null, 500);
				break;
			}

            exit(0);
        }

        return response(null, 500);
    }

}
