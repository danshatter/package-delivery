<?php

namespace App\Http\Middleware;

use Closure;
use App\Services\Auth\Gate;

class ValidToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Check if the record of the token still exist
        if (!auth()->user()->token()->exists()) {
            return app()->make(Gate::class)->unauthorized();
        }

        // Get the token reference from the database
        $token = auth()->user()->token()->first();

        // Get the bearer token to check the hash
        $bearerToken = $request->bearerToken();

        // Get the base64 signature part of the token
        $signature = explode('.', $bearerToken)[2];

        // Check if the token hash matches
        if ($token->token !== hash_hmac('sha256', $signature, config('handova.auth_token_hash'))) {
            return app()->make(Gate::class)->unauthorized();
        }

        // Check if the token has expired according to our database
        // if (time() > $token->expires_at->getTimestamp()) {
        //     return app()->make(Gate::class)->unauthorized();
        // }

        return $next($request);
    }
    
}
