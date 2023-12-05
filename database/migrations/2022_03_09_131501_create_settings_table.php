<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('application_name')->nullable();
            $table->string('application_version')->nullable();
            $table->string('application_mail_from_email_address')->nullable();
            $table->string('application_mail_from_name')->nullable();
            $table->string('paystack_public_key')->nullable();
            $table->string('paystack_secret_key')->nullable();
            $table->string('firebase_web_api_key')->nullable();
            $table->string('firebase_project_id')->nullable();
            $table->string('google_api_key')->nullable();
            $table->string('sms_username')->nullable();
            $table->string('sms_key')->nullable();
            $table->string('android_url')->nullable();
            $table->string('apple_url')->nullable();
            $table->string('transaction_fee_type')->nullable();
            $table->string('transaction_fee_value')->nullable();
            $table->string('order_cancellation_fee_type')->nullable();
            $table->string('order_cancellation_fee_value')->nullable();
            $table->string('verify_me_public_key')->nullable();
            $table->string('verify_me_secret_key')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('settings');
    }
}
