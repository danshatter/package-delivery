<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Rules\{Phone, PhoneUnique, ValidDateOfBirth};
use App\Services\Phone\Nigeria;
use App\Models\{User, Role};
use App\Services\Files\Upload;
use App\Services\Settings\Application;

class UserController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Display the contents of a user's profile
     */
    public function show()
    {
        // Check if the user is a customer
        if (auth()->user()->role_id === Role::CUSTOMER) {
            $data = auth()->user()->makeVisible([
                'email_verified_at',
                'hear_about_us_from',
                'referral_link',
                'used_for',
                'available_balance',
                'profile_completed',
                'ledger_balance',
            ]);

            return $this->sendSuccess('Request successful', $data);
        }

        // Check if the user is a driver
        if (auth()->user()->role_id === Role::DRIVER) {
            // Fetch the ride relationship
            auth()->user()->load(['ride']);

            $data = auth()->user()->makeVisible([
                'email_verified_at',
                'date_of_birth',
                'home_address',
                'referral_link',
                'driver_registration_status',
                'next_of_kin_first_name',
                'next_of_kin_last_name',
                'next_of_kin_relationship',
                'next_of_kin_phone',
                'profile_completed',
                'next_of_kin_email',
                'next_of_kin_home_address',
                'drivers_license_number',
                'drivers_license_image',
                'drivers_license_expiration_date',
                'driver_registration_status',
                'available_balance',
                'ledger_balance',
                'rejected_orders_count',
                'completed_orders_count'
            ]);

            return $this->sendSuccess('Request successful', $data);
        }

        return $this->sendSuccess('Request successful', auth()->user());
    }

    /**
     * Update the profile of a customer
     */
    public function customerUpdate()
    {
        // Rules initialization
        $rules = [];

        // Check if the application is used for business
        if (auth()->user()->used_for === 'business') {
            $rules['business_name'] = ['required', 'unique:users,business_name,'.auth()->id()];
        }

        // Validate the user data
        $validator = validator()->make(request()->all(), array_merge([
            'first_name' => ['required', 'alpha', 'regex:/^\S*$/u'],
            'last_name' => ['required', 'alpha', 'regex:/^\S*$/u'],
            'email' => ['required', 'email', 'unique:users,email,'.auth()->id()],
            'phone' => ['required', 'regex:/^\S*$/u', new Phone, new PhoneUnique(auth()->user())]
        ], $rules));

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        // Update the customer details with necessary data and change the phone number to the international format
        auth()->user()->update(array_merge($validator->validated(), [
            'phone' => app()->make(Nigeria::class)->convert(request()->input('phone'))
        ]));

        return $this->sendSuccess('Profile updated successfully');
    }

    /**
     * Update the profile of a driver
     */
    public function driverUpdate()
    {
        $validator = validator()->make(request()->all(), [
            'first_name' => ['required', 'alpha', 'regex:/^\S*$/u'],
            'last_name' => ['required', 'alpha', 'regex:/^\S*$/u'],
            'email' => ['required', 'email', 'unique:users,email,'.auth()->id()],
            'phone' => ['required', 'regex:/^\S*$/u', new Phone, new PhoneUnique(auth()->user())],
            'date_of_birth' => ['required', 'date', new ValidDateOfBirth],
            'home_address' => ['required'],
            'next_of_kin_first_name' => ['required', 'alpha', 'regex:/^\S*$/u'],
            'next_of_kin_last_name' => ['required', 'alpha', 'regex:/^\S*$/u'],
            'next_of_kin_relationship' => ['required', Rule::in(config('handova.next_of_kin_relationships'))],
            'next_of_kin_phone' => ['required', 'regex:/^\S*$/u', new Phone, 'different:phone'],
            'next_of_kin_email' => ['required', 'email', 'different:email'],
            'next_of_kin_home_address' => ['required'],
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        extract($validator->validated());

        auth()->user()->update([
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'phone' => app()->make(Nigeria::class)->convert($phone),
            'date_of_birth' => $date_of_birth,
            'home_address' => $home_address,
            'next_of_kin_first_name' => $next_of_kin_first_name ?? null,
            'next_of_kin_last_name' => $next_of_kin_last_name ?? null,
            'next_of_kin_relationship' => $next_of_kin_relationship ?? null,
            'next_of_kin_phone' => !is_null($next_of_kin_phone) ? app()->make(Nigeria::class)->convert($next_of_kin_phone) : null,
            'next_of_kin_email' => $next_of_kin_email ?? null,
            'next_of_kin_home_address' => $next_of_kin_home_address ?? null,
        ]);

        return $this->sendSuccess('Profile updated successfully');
    }

    /**
     * Update the profile of an admin
     */
    public function adminUpdate()
    {
        $validator = validator()->make(request()->all(), [
            'first_name' => ['required', 'alpha', 'regex:/^\S*$/u'],
            'last_name' => ['required', 'alpha', 'regex:/^\S*$/u'],
            'email' => ['required', 'email', 'unique:users,email,'.auth()->id()],
            'phone' => ['required', 'regex:/^\S*$/u', new Phone, new PhoneUnique(auth()->user())],
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        extract($validator->validated());

        auth()->user()->update([
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'phone' => app()->make(Nigeria::class)->convert($phone),
        ]);

        return $this->sendSuccess('Profile updated successfully');
    }

    /**
     * Upload a profile image
     */
    public function profileImage()
    {
        $validator = validator()->make(request()->all(), [
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png']
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        // Get the path to the old profile image
        $oldImagePath = app()->make(Upload::class)->pathFromUrl(auth()->user()->profile_image);

        extract($validator->validated());

        // Upload the image
        $newImage = $image->storePublicly('photos/profile');

        // Update the user's profile picture
        auth()->user()->update([
            'profile_image' => $newImage
        ]);

        // Delete the old profile image if it exists
        Storage::delete($oldImagePath);

        return $this->sendSuccess('Profile picture uploaded successfully', auth()->user()->profile_image);
    }

    /**
     * Webhook for the Verify Me API
     */
    public function verifyMeWebhook()
    {
        // Set the application settings
        app()->make(Application::class)->set();

        if (request()->header('X-Verifyme-Signature') === hash_hmac('sha512', request()->getContent(), config('services.verify_me.secret_key'))) {
            /**
             * Add logic here
             */

        }

        return response(null, 500);
    }

}
