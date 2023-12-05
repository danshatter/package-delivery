<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->foreignId('card_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category')->nullable();
            $table->string('pickup_location');
            $table->string('pickup_location_latitude');
            $table->string('pickup_location_longitude');
            $table->unsignedBigInteger('total_distance_metres');
            $table->string('type');
            $table->json('images')->nullable();
            $table->string('sender_name');
            $table->string('sender_phone')->nullable();
            $table->string('sender_address');
            $table->string('sender_email')->nullable();
            $table->json('receivers');
            $table->unsignedBigInteger('amount');
            $table->string('currency');
            $table->string('payment_method');
            $table->string('delivery_status');
            $table->timestamp('delivery_status_updated_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->json('past_drivers')->nullable();
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
        Schema::dropIfExists('orders');
    }
}
