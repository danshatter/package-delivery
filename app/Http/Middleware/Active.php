<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use App\Services\Auth\Gate;

class Active
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
        if (auth()->user()->status === User::BLOCKED) {
            return app()->make(Gate::class)->blocked();
        }

        /**
         * Just for us being thorough, one final check
         */
        if (!in_array(auth()->user()->status, [User::ACTIVE, User::BLOCKED])) {
            return app()->make(Gate::class)->unexpected();
        }

        return $next($request);
    }
}
