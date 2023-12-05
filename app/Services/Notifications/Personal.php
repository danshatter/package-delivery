<?php

namespace App\Services\Notifications;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Http;
use App\Services\Settings\Application;

class Personal
{
    
    private $authToken;
    private $user;

    /**
     * Create a new instance
     *
     * @return void
     */
    public function __construct()
    {
        $this->authToken = (new ServiceAccountCredentials(config('services.firebase_messaging.auth_scope'), config('services.firebase_messaging.credentials')))->fetchAuthToken();
    }

    /**
     * Send a push notification to a user
     */
    public function send($token, $title, $body, $data = null)
    {
        // Set the application settings
        app()->make(Application::class)->set();

        // Initialize payload
        $payload = [];

        // Add the data to the notification
        if (!is_null($data)) {
            $payload = compact('data');
        }

        $response = Http::withToken($this->authToken['access_token'])->post(config('services.firebase_messaging.endpoint'), [
            'message' => array_merge([
                'notification' => [
                    'title' => $title,
                    'body' => $body
                ],
                'token' => $token
                // 'android' => [
                //     'notification' => [
                //         'icon' => config('handova.logo'),
                //         'click_action' => null
                //     ] 
                // ],
                // 'apns' => [
                //     'click_action' => null
                // ]
            ], $payload)
        ]);

        return $response;
    }

}

