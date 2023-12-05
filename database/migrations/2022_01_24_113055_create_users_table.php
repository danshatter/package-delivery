<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->nullable()->constrained()->nullOnDelete();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('business_name')->nullable()->unique();
            $table->string('email')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('phone')->nullable()->unique();
            $table->string('hear_about_us_from')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('home_address')->nullable();
            $table->string('profile_image')->nullable();
            $table->string('referral_link')->nullable();
            $table->string('next_of_kin_first_name')->nullable();
            $table->string('next_of_kin_last_name')->nullable();
            $table->string('next_of_kin_relationship')->nullable();
            $table->string('next_of_kin_phone')->nullable();
            $table->string('next_of_kin_email')->nullable();
            $table->string('next_of_kin_home_address')->nullable();
            $table->string('drivers_license_number')->nullable();
            $table->string('drivers_license_image')->nullable();
            $table->string('drivers_license_expiration_date')->nullable();
            $table->string('selfie')->nullable();
            $table->string('valid_utility_bill')->nullable();
            $table->string('password')->nullable();
            $table->boolean('profile_completed');
            $table->string('status');
            $table->string('used_for')->nullable();
            $table->string('otp')->nullable();
            $table->timestamp('otp_expires_at')->nullable();
            $table->string('device_identification')->nullable();
            $table->string('firebase_messaging_token')->nullable();
            $table->unsignedBigInteger('available_balance');
            $table->unsignedBigInteger('ledger_balance');
            $table->string('driver_registration_status')->nullable();
            $table->timestamp('driver_registration_status_updated_at')->nullable();
            $table->string('location_latitude')->nullable();
            $table->string('location_longitude')->nullable();
            $table->timestamp('location_updated_at')->nullable();
            $table->boolean('online')->nullable();
            $table->unsignedBigInteger('rejected_orders_count')->nullable();
            $table->unsignedBigInteger('completed_orders_count')->nullable();
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
        Schema::dropIfExists('users');
    }
}
