<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\{Bus, Mail, Http};
use Illuminate\Http\Client\Request;
use App\Models\User;
use App\Jobs\OtpRequest;
use App\Mail\Otp;

class ForgotPasswordTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Validation error occurred
     */
    public function test_validation_error_occurred_while_initiating_forgotten_password()
    {
        Bus::fake();
        $user = User::factory()->customer()->create();

        $this->json('POST', route('forgot-password'), [
            'username' => null
        ]);

        $this->response->assertInvalid(['username']);
        $this->response->assertUnprocessable();
    }

    /**
     * Forgot password was initiated successfully
     */
    public function test_forgotten_password_otp_request_was_successful()
    {
        Bus::fake();
        $user = User::factory()->customer()->create();

        $this->json('POST', route('forgot-password'), [
            'username' => $user->email
        ]);

        $this->response->assertValid();
        $this->assertResponseOk();
        $this->response->assertSeeText([
            'message' => 'OTP sent'
        ]);
        Bus::assertDispatched(OtpRequest::class);
    }

    /**
     * Job was queued successfully
     */
    public function test_forgotten_password_otp_request_job_is_queued_successfully()
    {
        Queue::fake();
        $user = User::factory()->customer()->create();

        $this->json('POST', route('forgot-password'), [
            'username' => $user->email
        ]);

        Queue::assertPushed(OtpRequest::class);
    }

    /**
     * Job that executes forgotten password ran successfully
     */
    public function test_forgotten_password_otp_request_job_ran_successfully()
    {
        Mail::fake();
        Http::fake();
        $user = User::factory()->customer()->create();

        (new OtpRequest($user))->handle();

        Mail::assertQueued(Otp::class);
        Http::assertSent(fn(Request $request) => $request->url() === config('handova.sms.url'));
    }

}
