<?php

namespace App\Http\Middleware;

use Closure;

class TrimStrings
{

    /**
     * Fields to exclude
     */
    private $excepts = [
        'password',
        'current_password',
        'password_confirmation'
    ];
    
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
            if (!in_array($key, $this->excepts) && is_string($value)) {
                $request->query->set($key, trim($value));
            }
        }

        //Trim the strings of the POST parameters
        foreach ($request->request->all() as $key => $value) {
            if (!in_array($key, $this->excepts) && is_string($value)) {
                $request->request->set($key, trim($value));
            }
        }

        return $next($request);
    }
}
