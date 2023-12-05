<?php

namespace App\Jobs;

use App\Contracts\Sms\Dispatcher;

class SmsJob extends Job
{

    public $user;
    public $messageBody;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($user, $messageBody)
    {
        $this->user = $user;
        $this->messageBody = $messageBody;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        app()->make(Dispatcher::class)->send($this->user->phone, $this->messageBody);
    }
}
