<?php

namespace Database\Factories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\{User, Role};

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->unique()->e164PhoneNumber(),
            'email_verified_at' => Carbon::now(),
            'password' => Hash::make('password'),
            'profile_completed' => User::PROFILE_COMPLETE,
            'status' => User::ACTIVE,
            'device_identification' => Str::random(12),
            'firebase_messaging_token' => Str::random(10),
            'available_balance' => 0,
            'ledger_balance' => 0
        ];
    }

    /**
     * The custom properties of a customer
     */
    public function customer()
    {
        return $this->state(fn($attributes) => [
            'role_id' => Role::CUSTOMER,
            'used_for' => 'personal'
        ]);
    }

    /**
     * The custom properties of a driver
     */
    public function driver()
    {
        return $this->state(fn($attributes) => [
            'role_id' => Role::DRIVER,
            'date_of_birth' => $this->faker->date(),
            'online' => User::ONLINE,
            'driver_registration_status' => User::DRIVER_STATUS_ACCEPTED,
            'driver_registration_status_updated_at' => Carbon::now(),
            'home_address' => $this->faker->address(),
        ]);
    }

    /**
     * The custom properties of an administrator
     */
    public function admin()
    {
        return $this->state(fn($attributes) => [
            'role_id' => Role::ADMINISTRATOR,
            'device_identification' => null,
            'firebase_messaging_token' => null
        ]);
    }

    /**
     * State for a business customer account
     */
    public function business()
    {
        return $this->state(fn($attributes) => [
            'business_name' => $this->faker->company,
            'used_for' => 'business'
        ]);
    }

    /**
     * State for an unverified user
     */
    public function unverified()
    {
        return $this->state(fn($attributes) => [
            'email_verified_at' => null
        ]);
    }

}
