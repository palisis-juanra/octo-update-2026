<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->uuid()->primary();
            $table->timestamps();
            $table->string('booking_id');
            $table->string('account_id');
            $table->string('channel_id');
            $table->string('status');
            $table->string('availability_id');
            $table->string('product_id');
            $table->string('option_id');
            $table->json('unit_items');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
