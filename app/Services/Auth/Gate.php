<?php

namespace App\Services\Auth;

class Gate
{

    /**
     * The unauthorized response
     */
    public function unauthorized()
    {
        return response([
            'status' => false,
            'message' => 'Unauthenticated.'
        ], 401);
    }

    /**
     * The forbidden response
     */
    public function forbidden()
    {
        return response([
            'status' => false,
            'message' => 'You are not authorized to perform this action'
        ], 403);
    }

    /**
     * The unverified response
     */
    public function unverified()
    {
        return response([
            'status' => false,
            'message' => 'Your account has not been verified'
        ], 403);
    }
    
    /**
     * The blocked response
     */
    public function blocked()
    {
        return response([
            'status' => false,
            'message' => 'Your account has been blocked. Contact us for more details'
        ], 403);
    }

    /**
     * The unexpected behaviour error
     */
    public function unexpected()
    {
        return response([
            'status' => false,
            'message' => 'An unexpected error occurred. Please contact us for more details'
        ], 500);
    }

}
