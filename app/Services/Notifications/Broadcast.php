<?php

namespace App\Services\Notifications;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Pool;
use App\Services\Settings\Application;

class Broadcast
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
    public function send($tokens, $title, $body)
    {
        // Set the application settings
        app()->make(Application::class)->set();

        $response = Http::pool(
            fn(Pool $pool) => collect($tokens)->map(
                fn($token) => $pool->withToken($this->authToken['access_token'])
                                ->post(config('services.firebase_messaging.endpoint'), [
                                    'message' => [
                                        'notification' => [
                                            'title' => $title,
                                            'body' => $body,
                                            'image' => config('handova.logo')
                                        ],
                                        'token' => $token,
                                        // 'android' => [
                                        //     'notification' => [
                                        //         'icon' => config('handova.logo'),
                                        //         // 'click_action' => null
                                        //     ] 
                                        // ],
                                        // 'apns' => [
                                            // 'click_action' => null
                                        // ]
                                    ]
                                ])
            )->all()
        );

        return $response;
    }

}

