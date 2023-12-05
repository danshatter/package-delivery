<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\{User, Ride};
use App\Services\Auth\Gate;

class AcceptedDriver
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
        if (auth()->user()->driver_registration_status !== User::DRIVER_STATUS_ACCEPTED) {
            return app()->make(Gate::class)->forbidden();
        }

        // Load the ride relationship
        auth()->user()->load(['ride']);

        // If the user does not have a ride that has been approved, then they cannot proceed
        if (auth()->user()->ride?->status !== Ride::APPROVED) {
            return app()->make(Gate::class)->forbidden();
        }

        // Remove the ride relationship just in case the request does not need it
        unset(auth()->user()->ride);

        return $next($request);
    }
}
