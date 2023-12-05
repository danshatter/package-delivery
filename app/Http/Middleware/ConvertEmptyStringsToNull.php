<?php

namespace App\Http\Middleware;

use Closure;

class ConvertEmptyStringsToNull
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
        //Trim the strings of the GET parameters
        foreach ($request->query->all() as $key => $value) {
            if ($value === "") {
                $request->query->set($key, null);
            }
        }

        //Trim the strings of the POST parameters
        foreach ($request->request->all() as $key => $value) {
            if ($value === "") {
                $request->request->set($key, null);
            }
        }

        return $next($request);
    }
}
