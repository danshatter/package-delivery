<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class LoginWithGoogleTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Validation error occurred
     */
    public function test_validation_error_occurred_while_signing_in_with_google_oauth2()
    {
        Http::fake([
            'https://www.googleapis.com/*' => Http::response([
                'given_name' => 'Ben',
                'family_name' => 'Cross',
                'email' => 'bencross@gmail.com'
            ], 200, [
                'Content-Type' => 'application/json'
            ])
        ]);

        $this->json('POST', route('login.google'), [
            'access_token' => null,
            'firebase_messaging_token' => Str::random(10),
            'device_identification' => Str::random(12)
        ]);

        $this->response->assertInvalid(['access_token']);
        $this->response->assertUnprocessable();
    }

    /**
     * Invalid Google OAuth2 credentials
     */
    public function test_google_oauth2_invalid_credentials()
    {
        Http::fake([
            'https://www.googleapis.com/*' => Http::response([
                'error_description' => 'Invalid credentials',
            ], 401, [
                'Content-Type' => 'application/json'
            ])
        ]);

        $this->json('POST', route('login.google'), [
            'access_token' => Str::random(14),
            'firebase_messaging_token' => Str::random(10),
            'device_identification' => Str::random(12)
        ]);

        $this->response->assertValid();
        $this->response->assertUnauthorized();
        $this->seeJson([
            'message' => 'Invalid credentials'
        ]);
    }

    /**
     * Can login successfully
     */
    public function test_a_user_login_is_successful_with_google_oauth2()
    {
        Http::fake([
            'https://www.googleapis.com/*' => Http::response([
                'given_name' => 'Ben',
                'family_name' => 'Cross',
                'email' => 'bencross@gmail.com'
            ], 200, [
                'Content-Type' => 'application/json'
            ])
        ]);

        $this->json('POST', route('login.google'), [
            'access_token' => Str::random(14),
            'firebase_messaging_token' => Str::random(10),
            'device_identification' => Str::random(12)
        ]);

        $this->response->assertValid();
        $this->assertResponseOk();
        $this->seeJson([
            'message' => 'Login successful'
        ]);
    }
}
