<?php

namespace App\Services\Auth;

use Firebase\JWT\JWT;
use Carbon\Carbon;

class Token
{

    /**
     * Generate a token
     */
    public function generate($user)
    {
        // When we generate a new token, we update the details about the token in the database
        $token = JWT::encode([
            'iss' => config('app.url'),
            'iat' => time(),
            'nbf' => time(),
            // 'exp' => time() + config('services.firebase_jwt.expires_in'),
            'userId' => $user->id
        ], config('services.firebase_jwt.secret'));

        // Get the base64 signature part of the token
        $signature = explode('.', $token)[2];

        // Store a reference of the token to the database for validation
        $user->token()->updateOrCreate([], [
            'token' => hash_hmac('sha256', $signature, config('handova.auth_token_hash')),
            // 'expires_at' => Carbon::now()->addSeconds(config('services.firebase_jwt.expires_in'))
            'expires_at' => null
        ]);

        return $token;
    }

}
