<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;
use Carbon\Carbon;
use App\Services\Auth\Otp;
use App\Models\User;

class ResetPasswordTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Validation error occurred
     */
    public function test_validation_error_occurred_while_resetting_password()
    {
        $user = User::factory()->customer()->create();

        $this->json('PUT', route('reset-password'), [
            'phone' => null
        ]);

        $this->response->assertInvalid(['phone']);
        $this->response->assertUnprocessable();
    }

    /**
     * OTP expired
     */
    public function test_otp_has_expired()
    {
        $user = User::factory()->customer()->create([
            'otp' => app()->make(Otp::class)->generate(),
            'otp_expires_at' => Carbon::now()->subMinutes(2)
        ]);

        $this->json('PUT', route('reset-password'), [
            'phone' => $user->phone,
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
            'otp' => $user->otp
        ]);

        $this->response->assertForbidden();
        $this->response->assertSeeText('Your OTP has expired');
    }

    /**
     * Invalid OTP
     */
    public function test_invalid_otp()
    {
        $user = User::factory()->customer()->create([
            'otp' => app()->make(Otp::class)->generate(),
            'otp_expires_at' => Carbon::now()->addMinutes(20)
        ]);

        $this->json('PUT', route('reset-password'), [
            'phone' => $user->phone,
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
            'otp' => implode('', array_fill(0, config('handova.otp_digits_number'), '0'))
        ]);

        $this->assertResponseStatus(400);
        $this->seeJson([
            'message' => 'Invalid OTP'
        ]);
    }

    /**
     * Password reset successful
     */
    public function test_password_reset_successful()
    {
        $user = User::factory()->customer()->create([
            'otp' => app()->make(Otp::class)->generate(),
            'otp_expires_at' => Carbon::now()->addMinutes(20)
        ]);
        $oldPassword = $user->password;

        $this->json('PUT', route('reset-password'), [
            'phone' => $user->phone,
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
            'otp' => $user->otp
        ]);
        $user->refresh();

        $this->response->assertValid();
        $this->assertResponseOk();
        $this->assertNotEquals($oldPassword, $user->password);
        $this->seeJson([
            'message' => 'Password reset successfully'
        ]);
    }

}
