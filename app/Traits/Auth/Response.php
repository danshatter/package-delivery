<?php

namespace App\Traits\Auth;

use Illuminate\Support\Facades\DB;
use App\Services\Auth\Token;

trait Response
{
    
    /**
     * The Response after a successful login
     */
    protected function user($user, $token)
    {
        return [
            'user' => $user->makeVisible([
                'email_verified_at',
                'available_balance',
                'ledger_balance',
                'profile_completed'
            ]),
            'token' => $token
        ];
    }

    /**
     * Complete the OAuth2 login process
     */
    protected function completeOauth2Login($user, $firebase_messaging_token, $device_identification)
    {
        /**
         * We could decide to verify the user automatically since the Provider is a trusted source
         */
        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        /**
         * If the user doesn't exist, The fields that we made visible do not get returned
         * We therefore, refresh the user model so the fields can show
         */
        $user->refresh();

        // Store the device ID and the firebase messaging token in the database
        $token = DB::transaction(function() use ($user, $firebase_messaging_token, $device_identification) {
            // Generate a token for the user
            $token = app()->make(Token::class)->generate($user);

            // Update the user column the firebase token and device ID
            $user->update(compact('firebase_messaging_token', 'device_identification'));

            return $token;
        });

        // Login the user
        return $this->sendSuccess('Login successful', $this->user($user, $token));
    }

}
