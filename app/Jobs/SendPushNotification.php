<?php

namespace App\Jobs;

use App\Services\Notifications\Personal;

class SendPushNotification extends Job
{

    public $user;
    public $title;
    public $body;
    public $notificationData;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($user, $title, $body, $notificationData = null)
    {
        $this->user = $user;
        $this->title = $title;
        $this->body = $body;
        $this->notificationData = $notificationData;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $response = app()->make(Personal::class)->send($this->user->firebase_messaging_token, $this->title, $this->body, $this->notificationData);
    }
}
