<?php

namespace App\Providers;

use Throwable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Firebase\JWT\JWT;
use App\Models\User;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot()
    {
        // Here you may define how you wish users to be authenticated for your Lumen
        // application. The callback which receives the incoming request instance
        // should return either a User instance or null. You're free to obtain
        // the User instance via an API token or any other method necessary.

        $this->app['auth']->viaRequest('api', function ($request) {
            // Get the bearer token of the request
            $bearerToken = $request->bearerToken();

            try {
                // Decode the payload of the token
                $payload = JWT::decode($bearerToken, config('services.firebase_jwt.secret'), ['HS256']);

                // Return the instance of the user to act as the authenticated user
                return User::find($payload->userId);
            } catch (Throwable $th) {
                // Something weird happened while decoding the token
                return null;
            }
        });
    }
}
