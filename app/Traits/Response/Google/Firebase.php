<?php

namespace App\Traits\Response\Google;

trait Firebase
{
    
    /**
     * Return the error from the google API based on the status
     */
    public function sendFirebaseErrorMessage($status, $message, $code)
    {
        /**
         * We are passing the status but not using it at the moment.
         * Might use it later
         */
        if (isset($status) && isset($message)) {
            return response([
                'status' => false,
                'message' => $message
            ], $code);
        }

        return response([
            'status' => false,
            'message' => 'Something unexpected happened. Please try again'
        ], 500);
    }

}