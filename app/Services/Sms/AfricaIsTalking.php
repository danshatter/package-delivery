<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use App\Contracts\Sms\Dispatcher;
use App\Services\Settings\Application;

class AfricaIsTalking implements Dispatcher
{
    
    /**
     * Send an SMS to a phone number
     */
    public function send($phone, $message)
    {
        // Set the application settings
        app()->make(Application::class)->set();

        $response = Http::asForm()->acceptJson()->withHeaders([
            'apiKey' => config('handova.sms.auth_key')
        ])
        ->post(config('handova.sms.url'), [
            'username' => config('handova.sms.username'),
            'to' => $phone,
            'message' => $message
        ]);

        return $response;
    }

}
