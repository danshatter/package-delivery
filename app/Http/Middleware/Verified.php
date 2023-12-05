<?php

namespace App\Http\Middleware;

use Closure;
use App\Services\Auth\Gate;

class Verified
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
        if (!auth()->user()->hasVerifiedEmail()) {
            return app()->make(Gate::class)->unverified();
        }

        return $next($request);
    }
}
