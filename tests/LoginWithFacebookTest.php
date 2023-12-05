<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class LoginWithFacebookTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Validation error occurred
     */
    public function test_validation_error_occurred_while_signing_in_with_facebook_oauth2()
    {
        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'first_name' => 'Ben',
                'last_name' => 'Cross',
                'email' => 'bencross@yahoo.com'
            ], 200, [
                'Content-Type' => 'application/json'
            ])
        ]);

        $this->json('POST', route('login.facebook'), [
            'access_token' => null,
            'firebase_messaging_token' => Str::random(10),
            'device_identification' => Str::random(12)
        ]);

        $this->response->assertInvalid(['access_token']);
        $this->response->assertUnprocessable();
    }

    /**
     * Invalid Facebook OAuth2 credentials
     */
    public function test_facebook_oauth2_invalid_credentials()
    {
        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'error' => [
                    'message' => 'Invalid access token'
                ]
            ], 401, [
                'Content-Type' => 'application/json'
            ])
        ]);

        $this->json('POST', route('login.facebook'), [
            'access_token' => Str::random(14),
            'firebase_messaging_token' => Str::random(10),
            'device_identification' => Str::random(12)
        ]);

        $this->response->assertValid();
        $this->response->assertUnauthorized();
        $this->seeJson([
            'message' => 'Invalid access token'
        ]);
    }

    /**
     * Can login successfully
     */
    public function test_a_user_login_is_successful_with_facebook_oauth2()
    {
        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'first_name' => 'Ben',
                'last_name' => 'Cross',
                'email' => 'bencross@yahoo.com'
            ], 200, [
                'Content-Type' => 'application/json'
            ])
        ]);

        $this->json('POST', route('login.facebook'), [
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
