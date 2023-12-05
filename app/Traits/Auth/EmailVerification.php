<?php

namespace App\Traits\Auth;

use Illuminate\Support\Facades\Mail;
use App\Mail\{Verification, Otp};
use App\Services\Settings\Application;

trait EmailVerification
{
    
    /**
     * Send verification email to the user
     */
    public function sendVerificationMail()
    {
        // Set the application settings
        app()->make(Application::class)->set();

        Mail::to($this->email)->send((new Verification($this))
                             ->subject('Welcome to '.config('app.name')));
    }

    /**
     * Send OTP request email
     */
    public function sendOtpMail()
    {
        // Set the application settings
        app()->make(Application::class)->set();

        Mail::to($this->email)->send((new Otp($this))
                             ->subject('OTP Request'));
    }

    /**
     * Determine if the user has verified their email address.
     *
     * @return bool
     */
    public function hasVerifiedEmail()
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * Mark the given user's email as verified.
     *
     * @return bool
     */
    public function markEmailAsVerified()
    {
        return $this->forceFill([
            'email_verified_at' => $this->freshTimestamp(),
            'otp' => null,
            'otp_expires_at' => null
        ])->save();
    }

}
