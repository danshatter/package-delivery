<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Role;
use App\Services\Auth\Gate;

class Administrator
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
        if (auth()->user()->role_id !== Role::ADMINISTRATOR) {
            return app()->make(Gate::class)->forbidden();
        }

        return $next($request);
    }
}
