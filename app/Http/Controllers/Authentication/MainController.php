<?php

namespace App\Http\Controllers\Authentication;

use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\{Hash, DB, Http};
use App\Http\Controllers\Controller;
use App\Rules\{Phone, PhoneUnique, PhoneExists, RegisteredUser, ValidDateOfBirth, VehicleExists, ValidDriversLicenseExpirationDate, VehicleBrandExists, VehicleModelExists};
use App\Models\{User, Role, Ride, SuperNotification};
use App\Events\Registered;
use App\Services\Phone\Nigeria;
use App\Services\Auth\{Token, Gate};
use App\Jobs\{OtpRequest};
use App\Services\Settings\Application;
use App\Traits\Auth\Response;

class MainController extends Controller
{
    use Response;

    /**
     * Register a new customer
     */
    public function customerRegistration()
    {   
        $validator = validator()->make(request()->all(), [
            'first_name' => ['required', 'alpha', 'regex:/^\S*$/u'],
            'last_name' => ['required', 'alpha', 'regex:/^\S*$/u'],
            'business_name' => ['nullable', 'required_if:used_for,business', 'unique:users'],
            'email' => ['required', 'email', 'unique:users'],
            'phone' => ['required', 'regex:/^\S*$/u', new Phone, new PhoneUnique],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'password_confirmation' => ['required'],
            'used_for' => ['required', Rule::in(config('handova.uses'))],
            'hear_about_us_from' => ['nullable']
        ], [
            'regex' => ':attribute must not contain white spaces',
            'used_for.in' => ':attribute should be any of '.implode(', ', config('handova.uses'))
        ]);

        /**
         * Check if the inputted email or phone exists
         */
        if (User::where('phone', app()->make(Nigeria::class)->convert(request()->input('phone')))->exists()) {
            // User exists. Now we get the user and check if they are verified
            $user = User::firstWhere('phone', app()->make(Nigeria::class)->convert(request()->input('phone')));

            // Generate an OTP for the user
            $user->generateOtp();

            /**
             * Send the OTP to the user using SMS notification
             */
            dispatch(new OtpRequest($user));

            // Check if the user email is not verified
            if (!$user->hasVerifiedEmail()) {
                return $this->sendErrors([
                    'user_exists_and_not_verified' => [true]
                ]);
            }
        }

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        // Create the customer
        DB::transaction(function() use ($validator) {
            // Extract the need variables from the request
            extract($validator->validated());

            // Check the used for case and update the necessary columns
            if ($used_for === 'personal') {
                /**
                 * Create a user based on personal use
                 */
                $user = User::create([
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'email' => $email,
                    'phone' => app()->make(Nigeria::class)->convert($phone),
                    'password' => Hash::make($password),
                    'used_for' => $used_for,
                    'hear_about_us_from' => $hear_about_us_from ?? null
                ]);
            } elseif ($used_for === 'business') {
                /**
                 * Create a user based on business use
                 */
                $user = User::create([
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'business_name' => $business_name,
                    'email' => $email,
                    'phone' => app()->make(Nigeria::class)->convert($phone),
                    'password' => Hash::make($password),
                    'used_for' => $used_for,
                    'hear_about_us_from' => $hear_about_us_from ?? null
                ]);
            }

            // Generate an OTP for the user
            $user->generateOtp();

            // Fire the registered event to set the aftermath of registration in motion
            event(new Registered($user));
        });

        return $this->sendSuccess('User registration successful. Use the OTP sent to verify your account', null, 201);
    }

    /**
     * Register a new driver
     */
    public function driverRegistration()
    {
        $validator = validator()->make(request()->all(), [
            'first_name' => ['required', 'alpha', 'regex:/^\S*$/u'],
            'last_name' => ['required', 'alpha', 'regex:/^\S*$/u'],
            'email' => ['required', 'email', 'unique:users'],
            'phone' => ['required', 'regex:/^\S*$/u', new Phone, new PhoneUnique],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'password_confirmation' => ['required'],
            'date_of_birth' => ['required', 'date', new ValidDateOfBirth],
            'home_address' => ['required'],
            'next_of_kin_first_name' => ['required', 'alpha', 'regex:/^\S*$/u'],
            'next_of_kin_last_name' => ['required', 'alpha', 'regex:/^\S*$/u'],
            'next_of_kin_relationship' => ['required', Rule::in(config('handova.next_of_kin_relationships'))],
            'next_of_kin_phone' => ['required', 'regex:/^\S*$/u', new Phone, 'different:phone'],
            'next_of_kin_email' => ['required', 'email', 'different:email'],
            'next_of_kin_home_address' => ['required'],
        ], [
            'regex' => ':attribute must not contain white spaces',
            'next_of_kin_relationship.in' => ':attribute should be any of '.implode(', ', config('handova.next_of_kin_relationships')),
        ]);

        /**
         * Check if the inputted email or phone exists
         */
        if (User::where('phone', app()->make(Nigeria::class)->convert(request()->input('phone')))->exists()) {
            // User exists. Now we get the user and check if they are verified
            $user = User::firstWhere('phone', app()->make(Nigeria::class)->convert(request()->input('phone')));

            // Generate an OTP for the user
            $user->generateOtp();

            /**
             * Send the OTP to the user using SMS notification
             */
            dispatch(new OtpRequest($user));

            // Check if the user email is not verified
            if (!$user->hasVerifiedEmail()) {
                return $this->sendErrors([
                    'user_exists_and_not_verified' => [true]
                ]);
            }
        }

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        // Create the driver
        DB::transaction(function() use ($validator) {
            extract($validator->validated());

            $user = User::create([
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'phone' => app()->make(Nigeria::class)->convert($phone),
                'password' => Hash::make($password),
                'date_of_birth' => $date_of_birth,
                'home_address' => $home_address,
                'next_of_kin_first_name' => $next_of_kin_first_name ?? null,
                'next_of_kin_last_name' => $next_of_kin_last_name ?? null,
                'next_of_kin_relationship' => $next_of_kin_relationship ?? null,
                'next_of_kin_phone' => !is_null($next_of_kin_phone) ? app()->make(Nigeria::class)->convert($next_of_kin_phone) : null,
                'next_of_kin_email' => $next_of_kin_email ?? null,
                'next_of_kin_home_address' => $next_of_kin_home_address ?? null,
            ]);

            /**
             * Generate an OTP for the user
             */
            $user->generateOtp();

            // Set driver default attributes
            $user->setDriverDefaults();

            // Fire the registered event to set the aftermath of registration in motion
            event(new Registered($user));
        });

        return $this->sendSuccess('Driver registration successful. Use the OTP sent to verify your account', null, 201);
    }

    /**
     * Register a driver's ride
     */
    public function driverRideRegistration()
    {
        $validator = validator()->make(request()->all(), [
            'vehicle_type' => ['required', new VehicleExists],
            'brand' => ['required'],
            'model' => ['required'],
            'vehicle_plate_number' => ['required', 'unique:rides,plate_number'],
            'drivers_license_number' => ['required'],
            'drivers_license_image' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf'],
            'drivers_license_expiration_date' => ['required', 'date', new ValidDriversLicenseExpirationDate],
            'selfie' => ['required', 'image', 'mimes:jpg,jpeg,png'],
            'valid_utility_bill' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf'],
            'valid_insurance_documents' => ['required', 'array', 'max:3'],
            'valid_insurance_documents.*' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf'],
            'valid_inspection_reports' => ['required', 'array', 'max:3'],
            'valid_inspection_reports.*' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf'],
        ], [
            'regex' => ':attribute must not contain white spaces',
            'next_of_kin_relationship.in' => ':attribute should be any of '.implode(', ', config('handova.next_of_kin_relationships')),
        ], [
            'valid_insurance_documents.*' => 'valid insurance documents',
            'valid_inspection_reports.*' => 'valid inspection reports'
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        // The driver's license
        $driversLicense = request()->file('drivers_license_image');

        // Store the driver's license
        $driversLicensePath = $driversLicense->storePublicly('licenses');

        // The utility bill
        $utilityBill = request()->file('valid_utility_bill');

        // Store the utility bill
        $utilityBillPath = $utilityBill->storePublicly('utility-bills');

        // The driver's selfie
        $selfie = request()->file('selfie');

        // Store the selfie
        $selfiePath = $selfie->storePublicly('selfies');

        /**
         * The valid insurance documents
         */
        $validInsuranceDocuments = [];

        foreach (request()->file('valid_insurance_documents') as $insuranceDocument) {
            $validInsuranceDocuments[] = $insuranceDocument->storePublicly('insurance-documents');
        }

        /**
         * The inspection reports
         */
        $validInspectionReports = [];

        foreach (request()->file('valid_inspection_reports') as $inspectionReport) {
            $validInspectionReports[] = $inspectionReport->storePublicly('inspection-reports');
        }

        // Store the necessary documents in the User model and in the Ride model
        DB::transaction(function() use ($validator, $driversLicensePath, $utilityBillPath, $validInsuranceDocuments, $validInspectionReports, $selfiePath) {
            // Extract the need variables from the request
            extract($validator->validated());

            auth()->user()->update([
                'drivers_license_number' => $drivers_license_number,
                'drivers_license_image' => $driversLicensePath,
                'drivers_license_expiration_date' => $drivers_license_expiration_date,
                'valid_utility_bill' => $utilityBillPath,
                'selfie' => $selfiePath
            ]);

            // Mark the driver's profile as completed
            auth()->user()->forceFill([
                'profile_completed' => User::PROFILE_COMPLETE
            ])->save();

            /**
             * Create the record of the ride
             */
            auth()->user()->ride()->create([
                'vehicle_id' => $vehicle_type, 
                'brand' => $brand,
                'model' => $model,
                'plate_number' => $vehicle_plate_number,
                'valid_insurance_documents' => $validInsuranceDocuments,
                'valid_inspection_reports' => $validInspectionReports
            ]);

            /**
             * Create a notification to the admin that a driver uploaded necessary documents
             * and requires verification
             */
            SuperNotification::create([
                'message' => 'A new driver with user ID #'.auth()->id().' has uploaded their document and requires verification'
            ]);
        });

        return $this->sendSuccess('Car details uploaded created successfully. We will get back to you after document verification', null, 201);
    }

    /**
     * Admin Registration
     */
    public function adminRegistration()
    {
        $validator = validator()->make(request()->all(), [
            'first_name' => ['required', 'alpha', 'regex:/^\S*$/u'],
            'last_name' => ['required', 'alpha', 'regex:/^\S*$/u'],
            'email' => ['required', 'email', 'unique:users'],
            'phone' => ['required', 'regex:/^\S*$/u', new Phone, new PhoneUnique],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'password_confirmation' => ['required'],
        ], [
            'regex' => ':attribute must not contain white spaces'
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        // Create the Administrator
        DB::transaction(function() use ($validator) {
            // Extract the need variables from the request
            extract($validator->validated());

            $user = User::create([
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'phone' => app()->make(Nigeria::class)->convert($phone),
                'password' => Hash::make($password),
            ]);

            // Upgrade the user to Administrator and verify the administrator
            $user->forceFill([
                'role_id' => Role::ADMINISTRATOR,
                'email_verified_at' => $user->freshTimestamp()
            ])->save();
        });

        return $this->sendSuccess('Admin registration successful. You can now login in', null, 201);
    }

    /**
     * Login a user
     */
    public function login()
    {
        $validator = validator()->make(request()->all(), [
            'username' => ['required', new RegisteredUser],
            'password' => ['required'],
            'firebase_messaging_token' => ['required'],
            'device_identification' => ['required']
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        extract($validator->validated());

        // Get the user in question
        $user = User::where('email', $username)
                    ->orWhere('phone', app()->make(Nigeria::class)->convert($username))
                    ->first();

        // Check if the password inputted is a correct password
        if (!Hash::check($password, $user->password)) {
            return $this->sendErrorMessage('Invalid username and password combination');
        }

        // Store the device ID and the firebase messaging token in the database
        $token = DB::transaction(function() use ($user, $firebase_messaging_token, $device_identification) {
            // Generate a token for the user
            $token = app()->make(Token::class)->generate($user);

            // Update the user column the firebase token and device ID
            $user->update(compact('firebase_messaging_token', 'device_identification'));

            return $token;
        });

        // Login the user
        return $this->sendSuccess('Login successful', $this->user($user, $token));
    }

    /**
     * Logout a user
     */
    public function logout()
    {
        // If the user is a driver, we make them offline then log them out
        if (auth()->user()->role_id === Role::DRIVER) {
            DB::transaction(function() {
                // Go offline
                auth()->user()->forceFill([
                    'online' => User::OFFLINE,
                ])->save();
    
                // Invalidate the token by deleting the reference from the database
                auth()->user()->token()->delete();
            });
        } else {
            // Invalidate the token by deleting the reference from the database
            auth()->user()->token()->delete();
        }

        return $this->sendSuccess('User logged out successful');
    }

    /**
     * Verify a user's account using OTP
     */
    public function userVerify()
    {
        $validator = validator()->make(request()->all(), [
            'otp' => ['required', 'numeric', 'digits:'.config('handova.otp_digits_number')],
            'phone' => ['required', new Phone, new PhoneExists]
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        extract($validator->validated());

        // Get the user
        $user = User::firstWhere('phone', app()->make(Nigeria::class)->convert($phone));

        // Check if the user has already been verified
        if ($user->hasVerifiedEmail()) {
            return $this->sendSuccess('You have already been verified');
        }

        // Check if the OTP is correct
        if ((string) $otp !== (string) $user->otp) {
            return $this->sendErrorMessage('Invalid OTP');
        }

        // Set the application settings
        app()->make(Application::class)->set();

        DB::transaction(function() use ($user) {
            // Verify the user's email
            $user->markEmailAsVerified();

            // Create a welcome notification for the user
            $user->notifications()->create([
                'message' => 'Welcome to '.config('app.name').', '.ucfirst($user->first_name).'. We are really glad to have you'
            ]);
        });

        return $this->sendSuccess('User verification successful');
    }

    /**
     * Send an OTP code for forgotten password
     */
    public function forgotPassword()
    {
        $validator = validator()->make(request()->all(), [
            'username' => ['required', new RegisteredUser],
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        extract($validator->validated());

        // Get the user in question
        $user = User::where('email', $username)
                    ->orWhere('phone', app()->make(Nigeria::class)->convert($username))
                    ->first();

        // Generate an OTP for the user
        $user->generateOtp();

        /**
         * Send the OTP to the user using SMS notification
         */
        dispatch(new OtpRequest($user));
        
        return $this->sendSuccess('OTP sent to "'.$user->phone.'" and "'.$user->email.'". Use it to reset your password');
    }

    /**
     * Reset a user's password
     */
    public function resetPassword()
    {
        $validator = validator()->make(request()->all(), [
            'phone' => ['required', new Phone, new PhoneExists],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'password_confirmation' => ['required'],
            'otp' => ['required', 'numeric', 'digits:'.config('handova.otp_digits_number')]
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        extract($validator->validated());

        $user = User::where('phone', app()->make(Nigeria::class)->convert($phone))->first();

        /**
         * Check if the OTP has expired
         */
        if ($user->hasExpiredOtp()) {
            return $this->sendErrorMessage('Your OTP has expired. Please resend OTP to generate a new one', 403);
        }

        // Check if the OTP is a correct OTP
        if ((string) $otp !== (string) $user->otp) {
            return $this->sendErrorMessage('Invalid OTP');
        }
        
        // Reset the password of the user and invalidate the OTP
        $user->forceFill([
            'password' => Hash::make($password),
            'otp' => null,
            'otp_expires_at' => null
        ])->save();

        return $this->sendSuccess('Password reset successfully');
    }

    /**
     * Change the password of the authenticated user
     */
    public function changePassword()
    {
        $validator = validator()->make(request()->all(), [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:6', 'different:current_password', 'confirmed'],
            'password_confirmation' => ['required'],
        ], [
            'current_password' => 'The password is incorrect'
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        extract($validator->validated());

        // Change the authenticated user's password
        auth()->user()->update([
            'password' => Hash::make($password)
        ]);

        return $this->sendSuccess('Password changed successfully');
    }

    /**
     * Resend the OTP
     */
    public function resendOtp()
    {
        $validator = validator()->make(request()->all(), [
            'phone' => ['required', new Phone, new PhoneExists],
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        extract($validator->validated());

        $user = User::where('phone', app()->make(Nigeria::class)->convert($phone))->first();

        // Generate an OTP for the user
        $user->generateOtp();

        /**
         * Send the OTP to the user using SMS notification
         */
        dispatch(new OtpRequest($user));
        
        return $this->sendSuccess('OTP request sent successfully');
    }

    /**
     * Login for administrators
     */
    public function adminLogin()
    {
        $validator = validator()->make(request()->all(), [
            'username' => ['required', new RegisteredUser],
            'password' => ['required']
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        extract($validator->validated());

        // Get the user in question
        $user = User::where('email', $username)
                    ->orWhere('phone', app()->make(Nigeria::class)->convert($username))
                    ->first();

        // Check to see if the user is an admin
        if ($user->role_id !== Role::ADMINISTRATOR) {
            return app()->make(Gate::class)->forbidden();
        }

        // Check if the password inputted is a correct password
        if (!Hash::check($password, $user->password)) {
            return $this->sendErrorMessage('Invalid username and password combination');
        }

        // Check if there is any device identification or firebase messaging token linked
        if (!is_null($user->device_identification) || !is_null($user->firebase_messaging_token)) {
            // If it exists, remove it and return the token
            $token = DB::transaction(function() use ($user) {
                $token = app()->make(Token::class)->generate($user);

                $user->update([
                    'device_identification' => null,
                    'firebase_messaging_token' => null
                ]);

                return $token;
            });
        } else {
            // Just return the token
            $token = app()->make(Token::class)->generate($user);
        }

        // Login the user
        return $this->sendSuccess('Login successful', compact('user', 'token'));
    }

    /**
     * Login a user using Google OAuth2
     */
    public function loginWithGoogle()
    {
        // Get the access token from the request
        $validator = validator()->make(request()->all(), [
            'access_token' => ['required'],
            'firebase_messaging_token' => ['required'],
            'device_identification' => ['required']
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        extract($validator->validated());

        // Get the access token and make the request
        $response = Http::withToken($access_token)
                        ->get('https://www.googleapis.com/oauth2/v3/userinfo');

        // Parse JSON response
        $body = $response->json();

        // Check if the token returns a valid response
        if ($response->failed()) {
            return $this->sendErrorMessage($body['error_description'], $response->status());
        }

        /**
         * Valid user. Now we check if the user already exists. If they exist, we return the user.
         * If they don't we create the user and return it
         */
        $user = User::firstOrCreate([
            'email' => $body['email']
        ], [
            'first_name' => $body['given_name'],
            'last_name' => $body['family_name'],
            'profile_image' => $body['picture'] ?? null
        ]);

        // Complete the OAuth2 login process
        return $this->completeOauth2Login($user, $firebase_messaging_token, $device_identification);
    }

    /**
     * Login a user using Facebook OAuth2
     */
    public function loginWithFacebook()
    {
        // Get the access token from the request
        $validator = validator()->make(request()->all(), [
            'access_token' => ['required'],
            'firebase_messaging_token' => ['required'],
            'device_identification' => ['required']
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        extract($validator->validated());

        // Get the access token and make the request
        $response = Http::get("https://graph.facebook.com/v13.0/me", [
            'fields' => 'first_name,last_name,email,picture',
            'access_token' => $access_token
        ]);

        // Parse JSON response
        $body = $response->json();

        // Check if the token returns a valid response
        if ($response->failed()) {
            return $this->sendErrorMessage($body['error']['message'], 401);
        }

        /**
         * Valid user. Now we check if the user already exists. If they exist, we return the user.
         * If they don't we create the user and return it
         */
        $user = User::firstOrCreate([
            'email' => $body['email']
        ], [
            'first_name' => $body['first_name'],
            'last_name' => $body['last_name'],
            'profile_image' => $body['picture']['data']['url'] ?? null
        ]);

        // Complete the OAuth2 login process
        return $this->completeOauth2Login($user, $firebase_messaging_token, $device_identification);
    }

}
