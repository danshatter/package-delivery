<?php

namespace App\Traits\Auth;

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use App\Services\Auth\Otp;
use App\Contracts\Sms\Dispatcher;

trait UsesOtp
{
    
    /**
     * Send the OTP to the user
     */
    public function sendOtp()
    { 
        $response = app()->make(Dispatcher::class)->send($this->phone, 'Your One-Time Password is '.$this->otp);

        // info($response);
    }

    /**
     * Generate a new OTP
     */
    public function generateOtp()
    {
        $this->forceFill([
            'otp' => app()->make(Otp::class)->generate(),
            'otp_expires_at' => Carbon::now()->addSeconds(config('handova.otp_expiration_seconds'))
        ])->save();
    }

    /**
     * Check if a user has an expired OTP
     */
    public function hasExpiredOtp()
    {
        return !is_null($this->otp_expires_at) && time() > $this->otp_expires_at->getTimestamp();
    }

}
