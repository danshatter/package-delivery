<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;
use App\Models\User;

class LoginTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Validation error occurred
     */
    public function test_validation_error_occurred_while_signing_in()
    {
        $user = User::factory()->customer()->create();

        $this->json('POST', route('login'), [
            'username' => $user->email,
            'password' => null,
            'firebase_messaging_token' => $user->firebase_messaging_token,
            'device_identification' => $user->device_identification
        ]);

        $this->response->assertInvalid(['password']);
        $this->response->assertUnprocessable();
    }

    /**
     * Invalid username and password
     */
    public function test_invalid_username_and_password_combination()
    {
        $user = User::factory()->customer()->create();

        $this->json('POST', route('login'), [
            'username' => $user->email,
            'password' => 'wrong-password',
            'firebase_messaging_token' => $user->firebase_messaging_token,
            'device_identification' => $user->device_identification
        ]);

        $this->response->assertValid();
        $this->seeStatusCode(400);
        $this->seeJson([
            'message' => 'Invalid username and password combination'
        ]);
    }

    /**
     * Can login successfully
     */
    public function test_a_user_login_is_successful()
    {
        $user = User::factory()->customer()->create();

        $this->json('POST', route('login'), [
            'username' => $user->email,
            'password' => 'password',
            'firebase_messaging_token' => $user->firebase_messaging_token,
            'device_identification' => $user->device_identification
        ]);

        $this->response->assertValid();
        $this->assertResponseOk();
        $this->seeJson([
            'message' => 'Login successful'
        ]);
    }
}
