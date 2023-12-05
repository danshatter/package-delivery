<?php

namespace App\Traits\Payments;

use Illuminate\Support\Facades\{DB, Http};
use App\Models\{Card, User, Transaction, Order};
use App\Services\Settings\Application;
use App\Jobs\SendPushNotification;

trait Transactions
{
    
    /**
     * Case when a user credits their accounts
     */
    protected function creditCase($body)
    {
        // The user to be credited
        $user = User::find($body['data']['metadata']['user_id']);

        // Create the needed database records based on successful payment
        DB::transaction(function() use ($user, $body) {
            // The amount paid by the user
            $amount = $body['data']['amount'];

            // The currency used in the transaction
            $currency = $body['data']['currency'];

            // The payment reference
            $reference = $body['data']['reference'];

            // The card authorization details
            $authorization = $body['data']['authorization'];

            // The email to be used for card authorization
            $email = $body['data']['customer']['email'];

            // Add the amount paid to the user's balance
            $user->increment('available_balance', $amount);
            $user->increment('ledger_balance', $amount);

            // Create the record of the transaction
            $user->transactions()->create([
                'amount' => $amount,
                'currency' => $currency,
                'reference' => $reference,
                'type' => Transaction::CREDIT,
                'notes' => 'Account top up'
            ]);

            // Store the card details if it doesn't exist
            $this->storeCardIfDoesntExist($user, $email, $authorization);

            // Create a notification
            $notification = $user->notifications()->create([
                'message' => 'Your account has been credited successfully with the amount of '.$currency.number_format($amount / 100, 2)
            ]);

            dispatch(new SendPushNotification(
                $user,
                'Account Credit Successful',
                'You have successfully credited you account with the amount of '.$currency.number_format($amount / 100, 2),
                [
                    'type' => 'notification',
                    'notification_id' => (string) $notification->id
                ]
            ));
        });
    }

    /**
     * Case when a user adds their card
     */
    protected function firstChargeCase($body)
    {
        // Get the card authorization
        $authorization = $body['data']['authorization'];

        // The email used in the transaction
        $email = $body['data']['customer']['email'];

        // The payment reference
        $reference = $body['data']['reference'];

        // The user to be credited
        $user = User::find($body['data']['metadata']['user_id']);

        // Check if the card has already been added. This should always run
        $this->storeCardIfDoesntExist($user, $email, $authorization);

        // Set the application settings
        app()->make(Application::class)->set();

        // Refund the user their money for adding the card
        $response = Http::withToken(config('services.paystack.secret_key'))
                        ->post('https://api.paystack.co/refund', [
                            'transaction' => $reference
                        ]);

        // info($response->json());
    }

    /**
     * Case for order creation with card payment
     */
    protected function payForOrderWithCardCase($body)
    {
        // Get the metadata
        $metadata = $body['data']['metadata'];

        // Get the order
        $order = Order::with(['customer', 'driver'])->find($metadata['order_id']);

        // The amount paid by the user
        $amount = $body['data']['amount'];

        // The currency used in the transaction
        $currency = $body['data']['currency'];

        // The payment reference
        $reference = $body['data']['reference'];

        DB::transaction(function() use ($order, $amount, $currency, $reference) {
            /**
             * The order should be en route immediately after payment.
             * If the order is not en route for some reason we make it en route
             */
            if ($order->delivery_status !== Order::STATUS_EN_ROUTE) {
                $order->markAsEnRoute();

                // Create a notification for the customer
                $order->customer->notifications()->create([
                    'message' => "Your order with ID #{$order->id} is en route"
                ]);

                // We create a notification for the driver to know that the order is successfully in route
                $order->driver->notifications()->create([
                    'message' => "Order #{$order->id} has been successfully marked en route"
                ]);
            }

            $order->customer->notifications()->create([
                'message' => "Payment for order #{$order->id} is successful"
            ]);
    
            // Create the transaction record
            $order->customer->transactions()->create([
                'order_id' => $order->id,
                'amount' => $amount,
                'currency' => $currency,
                'type' => Transaction::DEBIT,
                'reference' => $reference,
                'notes' => "Secure payment of Order #{$order->id}"
            ]);
        });

        dispatch(new SendPushNotification(
            $order->customer,
            "Order #{$order->id} Payment Successful",
            "The payment of you order with the ID #{$order->id} was successful",
            [
                'type' => 'order',
                'order_id' => (string) $order->id
            ]
        ));
    }

    /**
     * Case for order cancellation by a customer
     */
    protected function cancelOrderCase($body)
    {
        // Get the metadata
        $metadata = $body['data']['metadata'];

        // Get the order
        $order = Order::with(['customer', 'driver'])->find($metadata['order_id']);

        // The amount paid by the user
        $amount = $body['data']['amount'];

        DB::transaction(function() use ($order, $amount) {
            // Check if the order has not been canceled
            if ($order->delivery_status !== Order::STATUS_CANCELED) {
                $order->markAsCanceled($metadata['reason']);
            }

            $this->finalizeOrderCancellation($order, $order->customer, $amount);
        });

        /**
         * Dispatch firebase Notifications
         */
    }

    /**
     * Store the card if it doesn't exist
     */
    private function storeCardIfDoesntExist($user, $email, $authorization)
    {
        // Check if the users card has already been added. If it hasn't been added, Add it
        if (!$user->cards()->whereJsonContains('details->signature', $authorization['signature'])->exists()) {
            // Add the card to the user list of cards
            $user->cards()->create([
                'email' => $email,
                'details' => $authorization
            ]);

            // Create a notification
            $user->notifications()->create([
                'message' => ucwords($authorization['brand']).' ****'.$authorization['last4'].' added successfully'
            ]);
        }
    }

}
