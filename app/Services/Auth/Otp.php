<?php

namespace App\Services\Auth;

class Otp
{

    /**
     * Generate an OTP
     */
    public function generate()
    {
        // The maximum number of digits that can be generated
        $maximum = implode('', array_fill(0, config('handova.otp_digits_number'), '9'));

        // The generated OTP 
        $otp = rand(1, (int) $maximum);

        // If the length of the OTP is less that the number of digits of the OTP, add zeros as the prefix
        if (strlen($otp) < config('handova.otp_digits_number')) {
            $length = strlen($otp);

            $otp = implode('', array_fill(0, config('handova.otp_digits_number') - $length, '0')).$otp;

            return (string) $otp;
        }

        return (string) $otp;
    }

}
