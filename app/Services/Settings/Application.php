<?php

namespace App\Services\Settings;

use App\Models\Setting;

class Application
{
    
    /**
     * Set the settings of the application
     */
    public function set()
    {
        // Get the settings of the application
        $setting = Setting::find(1);

        // Check if there is any settings associated with the application
        if (!is_null($setting)) {
            // Check if there is an application name then set it
            if (!is_null($setting->application_name)) {
                config(['app.name' => $setting->application_name]);
            }

            // Check if there is an application version then set it
            if (!is_null($setting->application_version)) {
                config(['handova.version' => $setting->application_version]);
            }

            // Check if there is a from email address for mail then set it
            if (!is_null($setting->application_mail_from_email_address)) {
                config(['mail.from.address' => $setting->application_mail_from_email_address]);
            }

            // Check if there is a from name for mail then set it
            if (!is_null($setting->application_mail_from_name)) {
                config(['mail.from.name' => $setting->application_mail_from_name]);
            }

            // Check if there is a paystack public key then set it
            if (!is_null($setting->paystack_public_key)) {
                config(['services.paystack.public_key' => $setting->paystack_public_key]);
            }

            // Check if there is a paystack secret key then set it
            if (!is_null($setting->paystack_secret_key)) {
                config(['services.paystack.secret_key' => $setting->paystack_secret_key]);
            }

            // Check if there is a firebase web api key then set it
            if (!is_null($setting->firebase_web_api_key)) {
                config(['services.firebase_messaging.web_api_key' => $setting->firebase_web_api_key]);
            }
            
            // Check if there is a firebase project id then set it and automatically change the firebase messaging endpoint
            if (!is_null($setting->firebase_project_id)) {
                config(['services.firebase_messaging.project_id' => $setting->firebase_project_id]);

                // Change the firebase messaging endpoint
                config(['services.firebase_messaging.endpoint' => "https://fcm.googleapis.com/v1/projects/{$setting->firebase_project_id}/messages:send"]);
            }

            // Check if there is a google api key then set it
            if (!is_null($setting->google_api_key)) {
                config(['services.google_apis.key' => $setting->google_api_key]);
            }

            // Check if there is an SMS username then set it
            if (!is_null($setting->sms_username)) {
                config(['handova.sms.username' => $setting->sms_username]);
            }

            // Check if there is an SMS key then set it
            if (!is_null($setting->sms_key)) {
                config(['handova.sms.auth_key' => $setting->sms_key]);
            }

            // Check if there is a transaction fee type then set it
            if (!is_null($setting->transaction_fee_type)) {
                config(['handova.transaction_fee.type' => $setting->transaction_fee_type]);
            }

            // Check if there is a transaction fee value then set it
            if (!is_null($setting->transaction_fee_value)) {
                config(['handova.transaction_fee.value' => $setting->transaction_fee_value]);
            }

            // Check if there is an order cancellation fee type then set it
            if (!is_null($setting->order_cancellation_fee_type)) {
                config(['handova.order_cancellation_fee.type' => $setting->order_cancellation_fee_type]);
            }

            // Check if there is an order cancellation fee value then set it
            if (!is_null($setting->order_cancellation_fee_value)) {
                config(['handova.order_cancellation_fee.value' => $setting->order_cancellation_fee_value]);
            }

            // Check if there is a Verify Me API public key then set it
            if (!is_null($setting->verify_me_public_key)) {
                config(['services.verify_me.public_key' => $setting->verify_me_public_key]);
            }

            // Check if there is a Verify Me API secret key then set it
            if (!is_null($setting->verify_me_secret_key)) {
                config(['services.verify_me.secret_key' => $setting->verify_me_secret_key]);
            }
        }

        return $setting;
    }

}
