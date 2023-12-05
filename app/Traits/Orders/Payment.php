<?php

namespace App\Traits\Orders;

use Illuminate\Support\Facades\{Http, DB};
use App\Models\Transaction;
use App\Services\Settings\Application;
use App\Jobs\SendPushNotification;

trait Payment
{
    
    /**
     * Deduct the money of the order based on the user's payment method
     */
    protected function payForOrder($order)
    {
        /**
         * Based on the payment method, we make the payment of the order
         */
        switch ($order->payment_method) {
            case 'wallet':
                $response = DB::transaction(function() use ($order) {
                    // Mark the order as en route
                    $order->markAsEnRoute();

                    // Deduct the customers money from the system
                    $order->customer->decrement('available_balance', $order->amount);

                    // Create the transaction record
                    $order->customer->transactions()->create([
                        'order_id' => $order->id,
                        'amount' => $order->amount,
                        'currency' => config('handova.currency'),
                        'type' => Transaction::DEBIT,
                        'notes' => "Secure payment of Order #{$order->id}"
                    ]);

                    return $this->buildOrderResponse(null, true);
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

                return $response;
            break;

            case 'card':
                // Set the application settings
                app()->make(Application::class)->set();

                // Load the card relationship
                $order->load(['card']);

                /**
                 * We check to see if the user might have deleted their
                 * card just as the driver is marking their order en routing
                 */
                if (is_null($order->card)) {
                    /**
                     * Send notification to the customer
                     */
                    $order->customer->notifications()->create([
                        'message' => "Payment failed for order #{$order->id}. Your card does not exist or might have been deleted"
                    ]);

                    dispatch(new SendPushNotification(
                        $order->customer,
                        "Order #{$order->id} Payment Failed",
                        "Sorry but your payment for the order with ID #{$order->id} failed because the card added to make payment is not available or has been deleted",
                        [
                            'type' => 'order',
                            'order_id' => (string) $order->id
                        ]
                    ));

                    return $this->buildOrderResponse('Payment failed. Customer card does not exist or might have been deleted', false);
                }


                // Make the charge authorization
                $response = Http::withToken(config('services.paystack.secret_key'))
                                ->post('https://api.paystack.co/transaction/charge_authorization', [
                                    'email' => $order->card->email,
                                    'amount' => $order->amount,
                                    'authorization_code' => $order->card->details['authorization_code'],
                                    'metadata' => [
                                        'type' => Transaction::TYPE_CARD_PAYMENT,
                                        'order_id' => $order->id
                                    ]
                                ]);

                $body = $response->json();

                // info($body);

                // Check if the request failed, then create a notification
                if ($response->failed()) {
                    $order->customer->notifications()->create([
                        'message' => 'Payment failed: '.$body['message']
                    ]);

                    dispatch(new SendPushNotification(
                        $order->customer,
                        "Order #{$order->id} Payment Failed",
                        "Sorry but your payment for the order with ID #{$order->id} failed. Please make payment by another payment method",
                        [
                            'type' => 'order',
                            'order_id' => (string) $order->id
                        ]
                    ));

                    return $this->buildOrderResponse('Payment failed: '.$body['message'], false);
                }

                /**
                 * Card payment is successful so mark the order as en route
                 */
                DB::transaction(function() {
                    $order->markAsEnRoute();

                    // Create a notification for the customer
                    $order->customer->notifications()->create([
                        'message' => "Your order with ID #{$order->id} is en route"
                    ]);

                    // We create a notification for the driver to know that the order is successfully in route
                    auth()->user()->notifications()->create([
                        'message' => "Job #{$order->id} has been successfully marked en route"
                    ]);
                });

                return $this->buildOrderResponse(null, true);
            break;
        }
    }

    /**
     * Handle order completion
     */
    protected function processOrderCompletion($order)
    {
        DB::transaction(function() use ($order) {
            // Mark the order status as completed
            $order->markAsCompleted();

            /**
             * If the payment method is wallet, we update the ledger balance
             */
            if ($order->payment_method === 'wallet') {
                // Debit the customer's ledger balance
                $order->customer->decrement('ledger_balance', $order->amount);
            }

            // Credit the driver's available and ledger balance
            auth()->user()->increment('available_balance', $order->amount);
            auth()->user()->increment('ledger_balance', $order->amount);

            // Create the transaction for the driver
            auth()->user()->transactions()->create([
                'amount' => $order->amount,
                'currency' => $order->currency,
                'type' => Transaction::CREDIT,
                'notes' => "Payment for successful delivery of Order #{$order->id}"
            ]);

            // Increase the count of the driver's completed orders
            auth()->user()->increment('completed_orders_count');

            // Create a notification for the customer
            $order->customer->notifications()->create([
                'message' => "Your order with ID #{$order->id} has been completed successfully"
            ]);

            // Create a credit alert notification for the driver
            auth()->user()->notifications()->createMany([
                [
                    'message' => "Order #{$order->id} delivery has been marked as completed"
                ],
                [
                    'message' => 'Your account has been credited successfully with the amount of '.$order->currency.number_format($order->amount / 100, 2)
                ]
            ]);
        });
    }

    /**
     * Handle order cancellation
     */
    protected function processOrderCancellation($order, $cancellationFee, $reason)
    {
        /**
         * Based on the payment method, we make the payment of the order
         */
        switch ($order->payment_method) {
            case 'wallet':
                /**
                 * Perform tasks that happen due to cancellation of an order
                 */
                $response = DB::transaction(function() use ($order, $cancellationFee, $reason) {
                    // Cancel the order
                    $order->markAsCanceled($reason);

                    // Deduct the customer's account for cancelling the order
                    auth()->user()->decrement('available_balance', $cancellationFee);
                    auth()->user()->decrement('ledger_balance', $cancellationFee);

                    // Finalize order cancellation
                    $this->finalizeOrderCancellation($order, auth()->user(), $cancellationFee);

                    return $this->buildOrderResponse(null, true);
                });

                return $response;
            break;

            case 'card':
                // Set the application settings
                app()->make(Application::class)->set();

                // Load the card relationship
                $order->load(['card']);

                /**
                 * We check to see if the user might have deleted their
                 * card just as the driver is marking their order en routing
                 */
                if (is_null($order->card)) {
                    /**
                     * Send notification to the customer
                     */
                    auth()->user()->notifications()->create([
                        'message' => "Cancellation failed for order #{$order->id}. Your card does not exist or might have been deleted"
                    ]);

                    dispatch(new SendPushNotification(
                        auth()->user(),
                        "Order #{$order->id} Cancellation Failed",
                        "Sorry but the cancellation of your order with ID #{$order->id} failed as your cancellation fee cannot be processed",
                        [
                            'type' => 'order',
                            'order_id' => (string) $order->id
                        ]
                    ));

                    return $this->buildOrderResponse('Cancellation failed. The card associated with this order does not exist or might have been deleted', false);
                }

                // Make the charge authorization
                $response = Http::withToken(config('services.paystack.secret_key'))
                                ->post('https://api.paystack.co/transaction/charge_authorization', [
                                    'email' => $order->card->email,
                                    'amount' => $cancellationFee,
                                    'authorization_code' => $order->card->details['authorization_code'],
                                    'metadata' => [
                                        'type' => Transaction::TYPE_ORDER_CANCELLATION,
                                        'order_id' => $order->id,
                                        'reason' => $reason
                                    ]
                                ]);

                $body = $response->json();

                // info($body);

                // Check if the request failed, then create a notification
                if ($response->failed()) {
                    auth()->user()->notifications()->create([
                        'message' => 'Cancellation failed: '.$body['message']
                    ]);

                    dispatch(new SendPushNotification(
                        auth()->user(),
                        "Order #{$order->id} Cancellation Failed",
                        "Sorry but the cancellation of your order with ID #{$order->id} failed",
                        [
                            'type' => 'order',
                            'order_id' => (string) $order->id
                        ]
                    ));

                    return $this->buildOrderResponse('Cancellation failed: '.$body['message'], false);
                }

                /**
                 * Card payment is successful so cancel the order
                 */
                $order->markAsCanceled($reason);

                return $this->buildOrderResponse(null, true);
            break;
        }
    }

    /**
     * Finalize order cancellation
     */
    protected function finalizeOrderCancellation($order, $customer, $cancellationFee)
    {
        // Create the record of the transaction
        $customer->transactions()->create([
            'order_id' => $order->id,
            'amount' => $cancellationFee,
            'currency' => $order->currency,
            'type' => Transaction::DEBIT,
            'notes' => 'Cancellation fee'
        ]);

        // Create the notifications due to order cancellation
        $customer->notifications()->createMany([
            [
                'message' => "Your order with ID #{$order->id} has been successfully canceled"
            ],
            [
                'message' => 'Due to your order cancellation of order #'.$order->id.', You have been deducted the amount of '.$order->currency.number_format($cancellationFee / 100, 2)
            ]
        ]);

        // Create the notification for the driver that the user has canceled the order
        $order->driver->notifications()->create([
            'message' => "The customer with order ID #{$order->id} just canceled the order"
        ]);
    }

    /**
     * Build the data that will be used to know if an order payment is successful or not
     */
    private function buildOrderResponse($message, $status)
    {
        return compact('status', 'message');
    }

}
