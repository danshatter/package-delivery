<?php

namespace App\Jobs;

use App\Services\Settings\Application;

class OtpRequest extends Job
{

    public $user;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Set the application settings
        app()->make(Application::class)->set();

        // Send OTP via Email
        $this->user->sendOtpMail();

        // Send OTP via SMS
        $this->user->sendOtp();
    }
}
