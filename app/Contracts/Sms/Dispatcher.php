<?php

namespace App\Contracts\Sms;

interface Dispatcher
{
    
    /**
     * Send an SMS to a phone number
     */
    public function send($phone, $message);

}
