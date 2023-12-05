<?php

namespace App\Http\Middleware;

use Closure;

class CorsMiddleware
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
        $headers = [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE',
            'Access-Control-Allow-Headers' => 'Authorization, Content-Type'
        ];

        $response = $next($request);

        // Handle Preflight
        if ($request->isMethod('OPTIONS')) {
            $response->setContent('')
                    ->setStatusCode(204)
                    ->headers
                    ->add($headers);

            return $response;
        }
        
        // Add the necessary CORS headers
        $response->headers->add($headers);

        return $response;
    }

}
